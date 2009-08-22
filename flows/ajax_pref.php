<?php
if (!defined('Shimmer')) header('Location:/');
$returnArray = array('wasOK'=>false);

$action = $_REQUEST['action'];

if ($action == "preferences.save") {
	$email	= $_POST['email'];
	$pass	= $_POST['password'];
	if (isset($email) && isset($pass)) {
		$Shimmer->auth->setLogin($email, $pass);
	}
	$base = $_POST['baseURL'];
	if (isset($base)) $Shimmer->pref->save('baseURL', $base);
	
	// Import some backup data if requested
	$backupSession		= $_POST['backupSession'];
	$backupChoicesData	= $_POST['backupChoices'];
	if (isset($backupSession) && strlen($backupSession)>0 && isset($backupChoicesData)) {
		include_once('jsonhelper.php');
		include($Shimmer->base . "/workers/worker_backup.php");
		$selectedAppNames = json_decode(prepareJsonStringForDecoding($backupChoicesData), false);
		$backupWorker = new BackupWorker();
		$backupWorker->import($backupSession, $selectedAppNames);
	}
	$returnArray['wasOK'] = true;
} else if ($action=="preferences.get.values") {
	// Email
	$email		= $Shimmer->pref->read('email');
	$returnArray['email'] = $email;
	
	// Password
	$pass		= $Shimmer->pref->read('pass');
	$returnArray['pass'] = $pass;
	
	// Base URL
	$baseURL	= $Shimmer->baseURL;
	$returnArray['baseURL'] = $baseURL;
	
	$returnArray['wasOK'] = true;

} else if ( $action == "prefs.box.update" ) {
	$appName	= $_POST['app'];
	if (isset($appName)) {
		$app = $Shimmer->apps->app($appName);
		if ($app) {
			$box		= $_POST['box'];
			$graphId	= $_POST['id'];
			if (strlen($box)>0 && strlen($graphId)>0) {
				$Shimmer->stats->setBoxTargetForApp($app, $box, $graphId);
				$returnArray['wasOK'] = true;
				if (isset($appName)) {
					include($Shimmer->base . "/workers/worker_stat.php");
					$statWorker = new StatWorker();
					$locations = array($box);
					$returnArray['stats'] = $statWorker->performStatLookup($app['name'], $locations);
				}
			} else $returnArray['reason'] = "One of the supplied values is zero-length.";
		} else $returnArray['reason'] = "App does not exist.";
	}
} else if ($action=="prefs.masks.test") {
	include_once('completemask.php');
	$downloadMask	= $_GET['downloadMask'];
	$notesMask		= $_GET['notesMask'];

	$returnArray['downloadWorking']	= false;
	$returnArray['notesWorking']	= false;
	
	$app		= array('name'=>'Test');
	$version	= array('version'=>'1.0', 'build'=>'1');
	
	$downloadURL	= completeMask($downloadMask,	$app, $version);
	$downloadURL	.= preg_match('/\?(&?[^=]+=[^=]+)*$/', $downloadURL) ? '&' : '?';
	$downloadURL	.= "loudandclear";
	
	$notesURL		= completeMask($notesMask,		$app, $version);
	$notesURL		.= preg_match('/\?(&?[^=]+=[^=]+)*$/', $notesURL) ? '&' : '?';
	$notesURL		.= "loudandclear";

	if ($Shimmer->readURL($downloadURL,	true)	=="LOUDANDCLEAR") $returnArray['downloadWorking']	= true;
	if ($Shimmer->readURL($notesURL,	true)	=="LOUDANDCLEAR") $returnArray['notesWorking']		= true;
	$returnArray['wasOK'] = true;
} else {
	$returnArray['reason'] = "Unknown action";
}

echo json_encode($returnArray);

?>