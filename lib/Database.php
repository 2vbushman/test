<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 16.05.2018
 * Time: 14:58
 */

namespace test;

class Database {
	static $_instance;
	private $db;

	private function __construct() {
		$this->db = new \PDO("mysql:host=" . Config::HOST, Config::LOGIN, Config::PASS);
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}

	private function __clone() {
	}

	public static function getInstance() {
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	public function prepare() {
		try {
			$this->createDatabase();
			$this->createTable();
			$this->createProcedure();
			echo "Database prepared successfully" . PHP_EOL;
		} catch (\PDOException $exc) {
			echo "Mysql error " . $exc->getMessage();
		}
	}

	private function createDatabase() {
		$this->db->exec("CREATE DATABASE IF NOT EXISTS " . Config::DB_NAME);
		$this->db->exec("USE " . Config::DB_NAME);
	}

	private function createTable() {
		$createTable = "CREATE TABLE IF NOT EXISTS `user` ("
			. "`github_id` int(11) UNSIGNED NOT NULL, "
			. "`github_login` varchar(255) NOT NULL, "
			. "PRIMARY KEY (github_id) "
			. ") ENGINE=InnoDB;";
		$this->db->exec($createTable);
	}

	private function createProcedure() {
		$createProcedure = "DROP PROCEDURE IF EXISTS `insert_user`";
		$this->db->exec($createProcedure);

		$createProcedure =
			"CREATE DEFINER=`" . Config::LOGIN . "`@`" . Config::HOST . "` PROCEDURE `"
			. Config::PROCEDURE . "`(IN `id` int(11), IN `login` VARCHAR(255)) "
			. "BEGIN "
			. "DECLARE CONTINUE HANDLER FOR 1062 "
			. "BEGIN "
			. "UPDATE `user` set `github_login` = `login` WHERE `github_id` = `id`; "
			. "END; "
			. "INSERT INTO user VALUES(`id`, `login`); "
			. "END; ";
		$this->db->exec($createProcedure);
	}

	public function loadData() {
		$curl = \curl_init();
		$user_agent = "User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64; rv:15.0) Gecko/20100101 Firefox/15.0.1 YB/6.9.1";
		$options = [
			CURLOPT_URL => "https://api.github.com/users",
			CURLOPT_USERAGENT => $user_agent,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_ENCODING => 'gzip, deflate',
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_VERBOSE => false];

		curl_setopt_array($curl, $options);
		$content = curl_exec($curl);

		if (curl_errno($curl)) {
			echo 'Curl error: ' . curl_error($curl);
			exit;
		}
		curl_close($curl);
		//echo $content;
		$this->insertToDatabase(json_decode($content, true));
	}

	private function insertToDatabase($result) {
		$limit = sizeof($result);
		if ($limit == 0) {
			echo "Empty users list" . PHP_EOL;
			exit;
		}

		try {
			$this->db->beginTransaction();
			for ($i = 0; $i < $limit; $i++) {
				//echo $result[$i]["id"] . " " . $result[$i]["login"] . PHP_EOL;
				$this->insertUser((int)$result[$i]["id"], $result[$i]["login"]);
			}
			$this->db->commit();
			echo "Users loaded successfully" . PHP_EOL;
		} catch (\PDOException $exc) {
			if($exc->getCode() == 42000){
				echo "Database not initialized. Restart app with key 1";
			} else {
				echo "Mysql error " . $exc->getMessage();
			}
			$this->db->rollBack();
		} finally {
			$this->db = null;
		}
	}

	private function insertUser($id, $login) {
		$this->db->exec("USE " . Config::DB_NAME);
		$stmt = $this->db->prepare("CALL `" . Config::PROCEDURE . "`(?, ?)");
		$stmt->bindValue(1, (int)$id, \PDO::PARAM_INT);
		$stmt->bindValue(2, $login, \PDO::PARAM_STR);
		$stmt->execute();
	}
}