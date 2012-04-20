<?php

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
	return array_unique($out);
}

function Zfilter($bullshit, $split = ":", $filter = "/(.*)/") {
	$shit = explode($split, $bullshit);
	$out = array();
	foreach ($shit as $s) {
		if (preg_match($filter, $s, $matches)) {
			$out[] = trim($matches[1]);
		} else {
			$out[] = trim($s);
		}
	}
	//printf("%s -> %s\n", $bullshit, implode(",", $out));
	return $out;
}

function process_record($record, $database) {
	$shorthand = $record->metadata->children("http://www.openarchives.org/OAI/2.0/oai_dc/")->dc->children("http://purl.org/dc/elements/1.1/");
	$id = (string) trim($record->header->identifier[0]);
	$_id = $id;
	$image = (string) trim($shorthand->format[0]);
	$language = (string) trim($shorthand->language[0]);
	$license = Zfilter($shorthand->rights[0], "se:");
	$license = trim($license[1]);
	$title = (string) trim($shorthand->title[0]);
	$description = (string) $shorthand->description[0];
	$date = Zfilter($shorthand->date[0], "- ", "/([-\d]+)/");
	if (count($date) == 1 && empty($date[0])) {
		$date = null;
	}
	$formats = Zextract($shorthand->format, ": ");
	$coverage = Zextract($shorthand->coverage);
	$type = Zextract($shorthand->type);

	return compact("_id", "id", "image", "language", "license", "title", "description", "date", "formats", "coverage", "type");
}

// Connect to the database!
try {
	$mongo = new Mongo();
	$database = $mongo->selectDB("rembrandt");
	$objects = $database->selectCollection("objects");
} catch (MongoConnnectionException $e) {
	printf("Failed to connect to database:\n");
	printf("%s\n", $e);
	die(1);
} catch (InvalidArgumentException $iae) {
	printf("Invalid database name:\n");
	printf("%s\n", $iae);
	die(1);
}

// List ALL the objects!

$round = 1;
$resume = null;
$inserted = array();
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
		break;
	}

	foreach ($records as $record) {
		$record = process_record($record, $database);
		try {
			printf("Saving %s - %s (%s)\n", $record['id'], $record['title'], implode(",", empty($record['date'])?array():$record['date']));
			$objects->save($record);
			$inserted[] = $record['id'];
		} catch (MongoException $mo) {
			printf("Failed to insert object: %s", $mo);
			die(3);
		}
	}

	$token = $xml->xpath("/o:OAI-PMH/o:ListRecords/o:resumptionToken");
	if (count($token) == 0) {
		printf("Response contained no token, we're done\n");
		break;
	}
	$resume = (string)$token[0];
	$round += 1;
}

$inserted = array_unique($inserted);
sort($inserted);
$present = $database->command(array("distinct" => "objects", "key" => "_id"));
sort($present['values']);

$deleted = array_diff($inserted, $present['values']);

printf("Found %d entries which have been deleted from the API\n", count($deleted));
try {
	$objects->remove(array("_id" => array('$in' => $deleted)));
} catch (MongoException $mo) {
	printf("Failed to delete objects: %s\n", $mo);
	die(4);
}
printf("Deleted!\n");

// Done!

?>
