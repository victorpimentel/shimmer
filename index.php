<?php
define('Shimmer',true);
define('shimmer_log',false && !isset($_GET['ajax']) && !isset($_GET['combine']));
// error_reporting(E_ERROR||E_PARSE);
error_reporting(0);
include('launcher.php');
?>