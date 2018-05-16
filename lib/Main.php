<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 16.05.2018
 * Time: 15:06
 */

namespace test;


class Main {
	public function readUserInput(){
		echo "Enter command (1 to install, 2 to load, 0 to abort): ";
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);

		return trim($line);
	}
}