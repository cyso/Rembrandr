<?php

require_once(dirname(__FILE__) . "/../config/database.config.php");
require_once(dirname(__FILE__) . "/../config/rijksmuseum.config.php");
require_once("HttpRequest.php");

try {
	$database = new PDO(DatabaseConfig::DSN, DatabaseConfig::USERNAME, DatabaseConfig::PASSWORD);
} catch (PDOException $e) {
	printf("Failed to connect to database:\n");
	printf("%s\n", $e);
	die(1);
}

// List ALL the objects!

$resume = null;
while (true) {
	$request = new HttpRequest("get", RijksmuseumConfig::getListUrl($resume));

	if ($request->hasError()) {
		printf("An error occured: %s\nLast resume id was: %s\n\n", $request->getError(), $resume);
		die(2);
	}

	echo $request->getResponse();
	break;
}

// Done!


?>
