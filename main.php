<?php
namespace test;

error_reporting(E_ALL);
ini_set("display_errors", "1");

require_once "lib/Config.php";
require_once "lib/Database.php";
require_once "lib/Main.php";

if (!extension_loaded("pdo_mysql")) {
	die("extension load error");
}

try {
	$main = new Main();
	$line = $main->readUserInput();
	$db = Database::getInstance();

	if(trim($line) == "0"){
		echo "aborting!";
		exit;
	} else if(trim($line) == "1"){
		//подготовка бд
		$db->prepare();
	}else if(trim($line) == "2"){
		//загрузка в бд
		$db->loadData();
	} else {
		echo "Unknown command!";
		exit;
	}

}  catch (\Exception $exc) {
	echo "Error " . $exc->getMessage();
}
