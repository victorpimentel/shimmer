<?php
	if (!defined('Shimmer')) header('Location:/');
	if (!$Shimmer->auth->authenticated) {
		$email = $_POST['email'];
		if ( isset($email) && $Shimmer->auth->sendReset($email) ) $sentOK = true;
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
				if ($sentOK) {
					echo '<div class="message ok">A reset link has been sent to your email.</div>';
				} else if (isset($email) && !$sendOK) {
					echo '<div class="message error">Could not sent reset email. Did you enter the correct email?</div>';
				}
			?>
			<form action="?resend" method="post" name="resend">
				<fieldset>
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<th>Email</th>
							<td><input type="text" id="email" name="email" value="" /></td>
						</tr>
					</table>
					<div style="text-align:center" id="button_container">
						<input type="submit" value="Log In" id="resend-button" />
					</div>
				</fieldset>
			</form>
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