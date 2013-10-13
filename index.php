<?php 

require("./common.php"); 

	$bootstrap = null;

	try {
		$dbh = new PDO('mysql:host=localhost;dbname=brainiac', BRAINIAC_LOGIN, BRAINIAC_PASSW);

		if (! init_db($dbh)) {
			header($_SERVER["SERVER_PROTOCOL"]." 500 Interval error");
			die(print_r($dbh->errorInfo(), true));
		}

		$bootstrap = readAll($dbh);
		$dbh = null;

	} catch (PDOException $e) {
		header($_SERVER["SERVER_PROTOCOL"]." 500 Failed to connect to database");
		die($e->getMessage());
	}
?>
<!DOCTYPE HTML PUBLIC>
<html>
<head>
	<title>My Little Brain</title>
	<script src="js/vendor/zepto.min.js"></script>
	<script src="js/vendor/underscore-min.js"></script>
	<script src="js/vendor/backbone-min.js"></script>
	<script src="js/brainiac.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
	<script type="text/template" id="task-template">
		<header>
			<div id="task-title"><%= title %></div>
		</header>
		<div id="task-body"><%= text %></div>
		<footer>
			<div id="task-keywords"><%= keywords %></div>
			<div id="task-actions">
				<ul>
					<li><a href="#task/<%= id %>/edit">edit</a></li>
					<li><a href="#task/<%= id %>/delete">remove</a></li>
				</ul>
			</div>
			<div id="task-list">
				<a href="#list">Tasks list</a>
			</div>
		</footer>
	</script>
	<script type="text/template" id="task-edit-template">
		<form id="task-edit">
			<input type="text" class="title"/><br>
			<textarea class="text"/><br>
			<input type="text" class="keywords"/><br>
			<div>
				<input style="float: left;" type="button" class="abort" value="Abort"/>
				<input style="float: left;" type="button" class="save" value="Save"/>
			</div>
		</form>
	</script>
	<script type="text/template" id="task-list-template">
		<ul class="task-list">
			<% _.each(tasks, function (task) { %>
				<li class="task-item"><a href="#task/<%= task.id %>"><%= task.title %></a></li>
			<%}); %>
				<li class="task-new"><a href="#create">Add a new task ...</a></li>
		</ul>
	</script>
	<div id='app-container'></div>
</body>
	<script type="text/javascript">
		(function () {
			brainiac.collection.reset(<?php echo $bootstrap; ?>);
			brainiac.router.start();
		})();
	</script>
</html>