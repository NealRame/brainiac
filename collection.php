<?php

require("./common.php");

function handle_Documents($parts) {

	try {
		$dbh = new PDO('mysql:host=localhost;dbname=brainiac', BRAINIAC_LOGIN, BRAINIAC_PASSW);

		if (! init_db($dbh)) {
			header($_SERVER["SERVER_PROTOCOL"]." 500 Interval error");
			die(print_r($dbh->errorInfo(), true));
		}

		$method = $_SERVER['REQUEST_METHOD'];

		if (isset($parts[0])) {
			$id = intval($parts[0]);
			switch ($method) {
				case 'GET':
					$response = read($dbh, $id);
					break;

				case 'PUT':
					$response = update($dbh, $id);
					break;

				case 'DELETE':
					$response = destroy($dbh, $id);
					break;

				default:
					header($_SERVER["SERVER_PROTOCOL"]." 500 Unsupported method");
					die("<h1>Unsupported method</h1>");
			}
		} else {
			switch ($method) {
				case 'GET':
					$response = readAll($dbh);
					break;
				
				case 'POST':
					$response = create($dbh);
					break;

				default:
					header($_SERVER["SERVER_PROTOCOL"]." 500 Unsupported method");
					die("<h1>Unsupported method</h1>");
			}
		}

		if ($response) {
			echo $response;
		}

	} catch (PDOException $e) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Failed to connect to database");
		die($e->getMessage());
	} 

	$dbh = null;
}

if (! isset($_SERVER['PATH_INFO'])) {
	header($_SERVER["SERVER_PROTOCOL"]." 500 Collection required");
	die("<h1>500 Collection required</h1>");
}

$uri = trim($_SERVER['PATH_INFO'], '/');
$parts = explode('/', $uri);

switch ($parts[0]) {
	case 'Tasks':
		handle_Documents(array_slice($parts, 1));
		break;

	default:
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not found");
		die("<h1>404 Not Found</h1>");
		break;
}






