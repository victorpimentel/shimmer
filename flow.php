<?php
if (!defined('Shimmer')) header('Location:/');
if (isset($_GET['combine'])) {
	include('flows/combine.php');
} else if (isset($_GET['logout'])) {
	include('flows/logout.php');
} else if (isset($_GET['ajax'])) {
	include('flows/ajax.php');
} else if (isset($_GET['generate'])) {
	include('flows/generate.php');
} else if (isset($_GET['export'])) {
	include('flows/export.php');
} else if (isset($_GET['download'])) {
	include('flows/download.php');
} else if (isset($_GET['api'])) {
	include('flows/api.php');
} else if (isset($_GET['appcast'])) {
	include('flows/appcast.php');
} else if (isset($_GET['notes'])) {
	include('flows/notes.php');
} else if (isset($_GET['devtest'])) {
	include('flows/dev_test.php');
} else if (isset($_GET['resend'])) {
	include('flows/resend.php');
} else if (isset($_GET['reset'])) {
	include('flows/reset.php');
} else if (isset($_GET['info'])) {
	include('flows/info.php');
} else if (isset($_GET['uninstall'])) {
	include('flows/uninstall.php');
} else {
	include('flows/homepage.php');
}
?>