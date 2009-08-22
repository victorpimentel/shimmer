<?php
if (!defined('Shimmer')) header('Location:/');
include($Shimmer->base . "/workers/worker_stat.php");
$statWorker = new StatWorker();

$returnArray = array('wasOK'=>false);

$action = $_REQUEST['action'];

if ( $action == "stats.id" ) {
	$appName = $_GET['app_name'];
	$location = $_GET['location'];
	$returnArray['wasOK'] = true;
	$returnArray['stats'] = $statWorker->performStatLookup($appName, array($location));
} else if ( $action == "stats.id.many" ) {
	$appName = $_GET['app_name'];
	$locations = explode(';',$_GET['locations']);
	$returnArray['wasOK'] = true;
	$returnArray['stats'] = $statWorker->performStatLookup($appName, $locations);
} else $returnArray['reason'] = 'Unknown action';

echo json_encode($returnArray);

?>