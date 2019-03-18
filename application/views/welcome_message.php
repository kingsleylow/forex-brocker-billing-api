<?php
/**
 * @var	$http_host string
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Welcome to api.privatefx.com</title>
</head>
<body>

<div id="container">
	<h1>Welcome to <?php echo $http_host; ?>!</h1>

	<div id="body">
		<p>The page you are looking at is being generated dynamically by <?php echo $http_host; ?>.</p>
	</div>

	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>

</body>
</html>