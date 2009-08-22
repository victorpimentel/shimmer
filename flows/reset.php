<?php
	if (!defined('Shimmer')) header('Location:/');
	if (!$Shimmer->auth->authenticated) {
		$message = "<div class=\"message error\">Please click on the link provided in the reset email</div>";
		$code = $_GET['code'];
		if ( isset($code) ) {
			if ($Shimmer->auth->useResetCode($code)) {
				$message = "<div class=\"message ok\">You are now logged in to Shimmer.<br>Please change your password.</div><a href=\"./\">Go to Shimmer</a>";
			} else {
				$message = "<div class=\"message error\">The reset code provided is not valid</div>";
			}
		}
	} else header('Location: ./');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<title>Shimmer: Resend Password</title>
	<link rel="shortcut icon" type="image/ico" href="img/favicon.ico" />
	<link href="css/minipage.css" type="text/css" rel="stylesheet">
</head>

<body onload="document.resend.email.focus();">
	<div id="wrapper">
		<div id="title">
			<div id="box_corner_top_left" class="box_corner_top"></div>
			<div id="box_corner_top_right" class="box_corner_top"></div>
			<h3 id="header">Shimmer</h3>
		</div>
		<div id="content">
			<?php
				if ($message) echo $message;
			?>
		</div>
		<div class="box_footer">
			<div id="box_corner_bottom_left" class="box_corner_bottom"></div>
			<div id="box_corner_bottom_right" class="box_corner_bottom"></div>
		</div>
		<div id="bottom-links">
			<a href="./" id="forgot-link">Remembered your password?</a><a href="http://shimmerapp.com"><img src="img/releasesbyshimmer.gif" id="releases-by-shimmer" alt="Releases by Shimmer" title="Releases by Shimmer" align="center" /></a>
		</div>
	</div>
</body>
</html>