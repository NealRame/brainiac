<?php

define('BRAINIAC_LOGIN', 'brainiac_api');
define('BRAINIAC_PASSW', 'AhSdPt7wqT7srms2');

// define('BRAINIAC_LOGIN', 'pouet');
// define('BRAINIAC_PASSW', 'JdUChZsAJM3DFPAx');

/**
 * Crée la table 'documents' si elle n'existe pas déjà
 */
function init_db ($dbh) {
	$status = 
		$dbh->exec(
			"CREATE TABLE IF NOT EXISTS Tasks"
				. "("
					. "id INT UNSIGNED NOT NULL AUTO_INCREMENT,"
					. "title VARCHAR(255) NOT NULL,"
					. "text TEXT,"
					. "keywords VARCHAR(255),"
					. "CONSTRAINT PRIMARY KEY (id)"
				. ")"
		);
	return ! ($status === false);
}

/**
 * Handle create request
 * @param $dbh - PDO object
 */
function create ($dbh) {
	$values = json_decode(file_get_contents('php://input'), true);

	if ($values == null) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request is not valid");
		return false;
	}

	if (! isset($values['title']) || $values['title'] == '') {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Task title is required");
		return false;
	}

	if (! isset($values['text'])) {
		$values['text'] = '';
	}

	if (! isset($values['keywords'])) {
		$values['keywords'] = '';
	}

	$query = $dbh->prepare("INSERT INTO Tasks (title,text,keywords) VALUES (:title,:text,:keywords)");

	if (! $query->execute([':title' => $values['title'], ':text' => $values['text'], ':keywords' => $values['keywords']])) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request has failed");
		return false;
	}

	$query = $dbh->prepare("SELECT LAST_INSERT_ID()");
	$query->execute(); // TODO test return value !

	$id = $query->fetchColumn();

	return json_encode([ 'id' => $id]);
}

/**
 * Read a record given its ID in the Documents table
 * @return a json string representing the result false in case of error.
 * @param $dbh PDO object
 * @param $id the ID of the requested document
 */
function read ($dbh, $id) {
	$query = $dbh->prepare("SELECT * FROM Tasks WHERE id=:id");
	if (! $query->execute([':id' => $id])) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request has failed");
		return false;
	}
	$res = $query->fetch(PDO::FETCH_ASSOC);
	if (! $res) {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Item not found");
		return false;
	}
	return json_encode($res);
}

/**
 * Readl all records in the Documents table
 * @return a json string representing the result false in case of error.
 * @param $dbh PDO object
 */
function readAll ($dbh) {
	$query = $dbh->prepare("SELECT * FROM Tasks");
	if (! $query->execute()) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request has failed");
		return false;
	}
	return json_encode($query->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Handle update request
 * @param $dbh PDO object
 * @param $id the ID of the document to be updated
 */
function update ($dbh, $id) {
	$values = json_decode(file_get_contents('php://input'), true);

	if ($values == null) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request is not valid");
		return ;
	}

	$update_arg = [];
	$update_set = [];

	foreach ($values as $attr => $value) {
		switch ($attr) {
			case 'id':
				break;
			case 'title':
			case 'text':
			case 'keywords':
				$update_arg[':'.$attr] = $value;
				$update_set[] = $attr . '=:' . $attr;
				break;
			default:
				header($_SERVER["SERVER_PROTOCOL"]." 500 Unexpected attribute: " . $attr . "=" . $value);
				return false;
		}
	}

	$req = "UPDATE Tasks SET " . implode(',', $update_set) . " WHERE id=$id";
	$query = $dbh->prepare($req);

	if (! $query->execute($update_arg)) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request has failed");
		return false;
	}
	
	return json_encode($query->rowCount() > 0 ? $values : []);
}

/**
 * Handle destroy request
 * @param $dbh PDO object
 * @param $id the ID of the document to be destroyed
 */
function destroy ($dbh, $id) {
	$query = $dbh->prepare("DELETE FROM Tasks WHERE id=$id");
	if (! $query->execute()) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Request has failed");
		return false;
	}
}
