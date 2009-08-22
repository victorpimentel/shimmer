<?php
if (!defined('Shimmer')) header('Location:/');
$returnArray = array('wasOK'=>false);

$action = $_REQUEST['action'];

if ($action=="process.go") {
	$sessionKey  = $_REQUEST['session'];
	$appName     = $_GET['app'];
	$source      = $_GET['source'];
	$destination = $Shimmer->base . '/tmp/autoproc' . time() . '.tmp';

	// Fetch the DSA Keys
	$app  = $Shimmer->apps->app($appName);
	$keys = $Shimmer->apps->dsaKeysForApp($app);

	// Check that keys exist, download file
	if ($keys && $keys['private'] && copy($source, $destination)) {
		$filesize = filesize($destination);

		// Save the Private DSA Key to a temp file, the generate the signature
		$privateKeyPath = $Shimmer->writeToTempFile('private' . time() . '.pem', $keys['private']);
		$signature = shell_exec('openssl dgst -sha1 -binary < "' . $destination . '" | openssl dgst -dss1 -sign "' . $privateKeyPath . '" | openssl enc -base64');
		
		// Save the results, so the UI's poller can get the values
			// $Shimmer->pref->save('FileProcessing',array(
			// 			'session'  => $sessionKey,
			// 			'sig'      => $signature,
			// 			'size'     => $filesize,
			// 			'launched' =>true
			// 		), true);
			
		$Shimmer->stats->incrementTotalDsaCount();
		
		$returnArray['signature'] = $signature;
		$returnArray['filesize']  = $filesize;
		$returnArray['wasOK']     = true;
			
	}
} else $returnArray['reason'] = 'Unknown action';

echo json_encode($returnArray);

?>