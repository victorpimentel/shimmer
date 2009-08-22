<?php
if (!defined('Shimmer')) header('Location:/');
$didDelete = false;
if (isset($_POST['go'])) {
	$didDelete = $Shimmer->table->dropAllTables();
	$fh = @fopen('db_details.php', 'w');
	if ($fh) {
		fwrite($fh, "");
		fclose($fh);
		return true;
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<title>Shimmer: Uninstall</title>
	<link rel="shortcut icon" type="image/ico" href="img/favicon.ico" />
	<link href="css/minipage.css" type="text/css" rel="stylesheet">
</head>

<body>
	<div id="wrapper">
		<div id="title">
			<div id="box_corner_top_left" class="box_corner_top"></div>
			<div id="box_corner_top_right" class="box_corner_top"></div>
			<h3 id="header">Uninstall Shimmer</h3>
		</div>
		<div id="content">
			<?php
				if ($didDelete) {
					echo '<div class="message ok">The Shimmer database has been deleted.<br />You can now remove Shimmer from your web server.</div>';
				} else {
					echo '<div class="message error"><p>Uninstalling Shimmer is permanent and will erase all information.</p><p>Are you sure you want to continue?</p></div>';
				}
			?>
			<form action="?uninstall" method="post">
				<fieldset>
					<input type="hidden" name="go" value="imsorrybabyididntmeanit" /></td>
					<div style="text-align:center" id="button_container">
						<input type="submit" value="Log In" id="login-button" />
					</div>
				</fieldset>
			</form>
		</div>
		<div class="box_footer">
			<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
			<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
		</div>
		<div id="bottom-links">
			<a href="./" id="forgot-link">I can&apos;t do it. Take me back!</a><a href="http://shimmerapp.com"><img src="img/releasesbyshimmer.gif" id="releases-by-shimmer" alt="Releases by Shimmer" title="Releases by Shimmer" align="center" /></a>
		</div>
	</div>
</body>
</html>