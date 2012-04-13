CREATE TABLE objects (
	id INTEGER PRIMARY KEY,
	identifier TEXT UNIQUE,
	image TEXT,
	language TEXT,
	license TEXT,
	title TEXT,
	description TEXT,
	coverage TEXT,
	type TEXT
);
CREATE TABLE people (
	id INTEGER PRIMARY KEY,
	name TEXT UNIQUE
);
CREATE TABLE subjects (
	object_id INTEGER,
	people_id INTEGER,
	PRIMARY KEY(object_id, people_id),
	FOREIGN KEY(object_id) REFERENCES objects(id),
	FOREIGN KEY(people_id) REFERENCES people(id)
);
CREATE TABLE authors (
	object_id INTEGER,
	people_id INTEGER,
	type TEXT,
	PRIMARY KEY(object_id, people_id, type),
	FOREIGN KEY(object_id) REFERENCES objects(id),
	FOREIGN KEY(people_id) REFERENCES people(id)
);

PRAGMA foreign_keys = ON;
