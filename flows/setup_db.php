<?php
if (!defined('Shimmer')) {
	header('Location:/');
	exit();
}
$attemptedSave	= false;
if (isset($_POST['host']) && isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['db'])) {
	$attemptedSave = true;
	$host = $_POST['host'];
	$user = $_POST['user'];
	$pass = $_POST['pass'];
	$db   = $_POST['db'];
	
	$Shimmer = new Shimmer( array('server'=>$host, 'username'=>$user, 'password'=>$pass, 'database'=>$db) );
	if ($Shimmer->database['connected']) {
		$Shimmer->setup();
		if ($Shimmer->table->testTables()) {
			if ( save_db_details($host,$user,$pass,$db) ) {
				if ($Shimmer->table->createCoreTables()) {
					$Shimmer->tempFolder();
					$Shimmer->pref->save('lastVersion', '0');
					header('Location: ./?account&dbWorked');
					exit();
				}
			}
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<title>Shimmer: Install</title>
	<link rel="shortcut icon" type="image/ico" href="img/favicon.ico" />
	<link href="css/minipage.css" type="text/css" rel="stylesheet">
</head>

<body onload="document.install.host.focus();">
	<div id="wrapper">
		<div id="title">
			<div id="box_corner_top_left" class="box_corner_top"></div>
			<div id="box_corner_top_right" class="box_corner_top"></div>
			<h3 id="header">Welcome to Shimmer</h3>
		</div>
		<div id="content">
			<?php
				if ($attemptedSave) {
					echo '<div class="message error">The database details were incorrect.</div>';
				} else {
					echo '<div class="message">To get started, enter your database details.</div>';
				}
			?>
			<form action="?hello" method="post" name="install">
				<fieldset>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<th>Host</th>
							<td><input type="text" id="host" name="host" value="<?php echo $_POST['host'] ?>" /></td>
						</tr>
						<tr>
							<th>User</th>
							<td><input type="text" name="user" value="<?php echo $_POST['user'] ?>" /></td>
						</tr>
						<tr>
							<th>Password</th>
							<td><input type="password" name="pass" value="<?php echo $_POST['pass'] ?>" /></td>
						</tr>
						<tr>
							<th>Database</th>
							<td><input type="text" name="db" value="<?php echo $_POST['db'] ?>" /></td>
						</tr>
					</table>
					<div style="text-align:center" id="button_container">
						<input type="submit" value="Log In" id="setup-db-button" />
					</div>
				</fieldset>
			</form>
		</div>
		<div class="box_footer">
			<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
			<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
		</div>
		<div id="bottom-links">
			<a href="http://shimmerapp.com"><img src="img/releasesbyshimmer.gif" id="releases-by-shimmer" alt="Releases by Shimmer" title="Releases by Shimmer" align="center" /></a>
		</div>
	</div>
</body>
</html>
<?php
function save_db_details($host,$user,$pass,$db) {
	$phpString = "<?php";
	$phpString .= "\nif (!defined('Shimmer')) header('Location:/');";
	$phpString .= "\n$" . "shimmer_host='"	. $host	. "';";
	$phpString .= "\n$" . "shimmer_user='"	. $user	. "';";
	$phpString .= "\n$" . "shimmer_pass='"	. $pass	. "';";
	$phpString .= "\n$" . "shimmer_db='"	. $db	. "';";
	$phpString .= "\n?>";
	
	$fh = @fopen('db_details.php', 'w');
	if ($fh) {
		fwrite($fh, $phpString);
		fclose($fh);
		return true;
	}
	return false;
}
?>