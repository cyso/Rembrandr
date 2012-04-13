<?php

require_once(dirname(__FILE__) . "/../config/database.config.php");
require_once(dirname(__FILE__) . "/../config/rijksmuseum.config.php");
require_once("HttpRequest.php");

function Zextract($bullshit, $filter = "") {
	$out = array();
	foreach ($bullshit as $shit) {
		$shit = trim($shit);
		if (empty($shit)) {
			continue;
		}
		if ($filter && strpos($shit, $filter) !== false) {
			$out[] = (string) $shit;
		} else if (!$filter) {
			$out[] = (string) $shit;
		}
	}
	return $out;
}

function process_record($record, $database) {
	$shorthand = $record->metadata->children("http://www.openarchives.org/OAI/2.0/oai_dc/")->dc->children("http://purl.org/dc/elements/1.1/");
	$id = (string) trim($record->header->identifier[0]);
	$image = (string) trim($shorthand->format[0]);
	$language = (string) trim($shorthand->language[0]);
	$license = (string) trim($shorthand->rights[0]);
	$title = (string) trim($shorthand->title[0]);
	$description = (string) $shorthand->description[0];
	$formats = Zextract($shorthand->format, ": ");
	$coverage = implode(", ", Zextract($shorthand->coverage));
	$type = implode(", ", Zextract($shorthand->type));

	return compact("id", "image", "language", "license", "title", "description", "formats", "coverage", "type");
}

// Connect to the database!
try {
	$database = new PDO(DatabaseConfig::DSN, DatabaseConfig::USERNAME, DatabaseConfig::PASSWORD);
} catch (PDOException $e) {
	printf("Failed to connect to database:\n");
	printf("%s\n", $e);
	die(1);
}

// List ALL the objects!

$round = 1;
$resume = null;
while (true) {
	printf("Round %d; Fetching from: %s\n", $round, RijksmuseumConfig::getListUrl($resume));
	$request = new HttpRequest("get", RijksmuseumConfig::getListUrl($resume));

	if ($request->hasError()) {
		printf("An error occured: %s\nLast resume id was: %s\n\n", $request->getError(), $resume);
		die(2);
	}

	$raw = $request->getResponse();
	$xml = new SimpleXMLElement($raw);
	$xml->registerXPathNamespace("o", "http://www.openarchives.org/OAI/2.0/");

	$records = $xml->xpath("/o:OAI-PMH/o:ListRecords/o:record");

	if (count($records) == 0) {
		printf("Response contained no content, we're done");
		die(0);
	}

	foreach ($records as $record) {
		var_dump(process_record($record, $database));
	}

	$token = $xml->xpath("/o:OAI-PMH/o:ListRecords/o:resumptionToken");
	if (count($token) == 0) {
		break;
	}
	$resume = (string)$token[0];
	$round += 1;
}

// Done!

?>
