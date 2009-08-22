<?php
if (!defined('Shimmer')) header('Location:/');
$saveError = 'The login details could not be saved';
$attemptedSave	= false;
if (isset($_POST['email']) && isset($_POST['pass'])) {
	$attemptedSave = true;
	$email	= $_POST['email'];
	$pass	= $_POST['pass'];
	
	if (strlen($email)>0 && strlen($pass)>0) {
		if ( $Shimmer->auth->setLogin($email, $pass) ) {
			header('Location: ./#Welcome');
			exit();
		}
	} else $saveError = 'Please enter an email and password';
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

<body onload="document.account.email.focus();">
	<div id="wrapper">
		<div id="title">
			<div id="box_corner_top_left" class="box_corner_top"></div>
			<div id="box_corner_top_right" class="box_corner_top"></div>
			<h3 id="header">Welcome to Shimmer</h3>
		</div>
		<div id="content">
			<?php
				if ($attemptedSave) {
					echo '<div class="message error">' . $saveError . '</div>';
				} else if (isset($_GET['dbWorked'])) {
					echo '<div class="message ok">Database details saved successfully</div>';
				}
			?>
			<div class="message">You can now create your Shimmer account</div>
			<form action="?account" method="post" name="account">
				<fieldset>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<th>Email</th>
							<td><input type="text" id="email" name="email" value="<?php echo $_POST['email'] ?>" /></td>
						</tr>
						<tr>
							<th>Password</th>
							<td><input type="password" name="pass" value="<?php echo $_POST['pass'] ?>" /></td>
						</tr>
					</table>
					<div style="text-align:center" id="button_container">
						<input type="submit" value="Log In" id="create-account-button" />
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