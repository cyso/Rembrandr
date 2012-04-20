<?php

class Rijksmuseum {
	private $database = null;
	private $collection = null;

	public function __construct() {
		$m = new Mongo();
		$this->database = $m->selectDB("rembrandt");
		$this->collection = $this->database->selectCollection("objects");
	}

	public function get($clause) {
		return $this->find($clause, 1);
	}

	public function find($clause, $count = -1) {
		if ($count === 1) {
			return $this->collection->findOne($clause);
		} else {
			return $this->collection->find($clause);
		}
	}

	public function all($field) {
		$a = $this->database->command(array("distinct" => "objects", "key" => $field));
		return array_unique($a['values']);
	}

	public function findBy($field, $values) {
		$regex = new MongoRegex("/" . implode("|", $values) . "/i");

		return $this->find(array($field => $regex));
	}

	public function __call($name, $arguments) {
		if (strpos($name, "findBy") === 0 && count($arguments) == 1) {
			$name = strtolower(str_replace("findBy", "", $name));
			return $this->findBy($name, $arguments[0]);
		} else if (strpos($name, "all") === 0 && count($arguments) == 0) {
			$name = strtolower(str_replace("all", "", $name));
			return $this->all($name);
		} else if (strpos($name, "getBy") === 0 && count($arguments) == 1) {
			$name = strtolower(str_replace("getBy", "", $name));
			return $this->get(array($name => $arguments[0]));
		} else {
			trigger_error("Call to non-existant function", E_USER_ERROR);
		}
	}
}

?>
