<?php
if (!defined('Shimmer')) header('Location:/');
$type = $_GET['type'];
if ($type=="pref") {
	include('flows/ajax_pref.php');
} else if ($type=="backup") {
	include('flows/ajax_backup.php');
} else if ($type=="autoprocess") {
	include('flows/ajax_autoprocess.php');
} 	else if ($type=="dsa") {
		include('flows/ajax_dsa.php');
} else {
	include('flows/ajax_main.php');
}
?>