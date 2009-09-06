<?php
if (!defined('Shimmer')) header('Location:/');
$returnArray = array('wasOK'=>false);

$action = $_REQUEST['action'];

if ($action=="process.go") {
	$appID       = $_GET['appID'];
	$source      = $_GET['source'];
	$destination = $Shimmer->base . '/tmp/autoproc' . time() . '.tmp';

	// Fetch the DSA Keys
	$app = $Shimmer->apps->appFromID($appID);
	if ($app) {
		$keys = $Shimmer->apps->dsaKeysForApp($app);
		// Check that keys exist, download file
		if ($keys && $keys['private'] && copy($source, $destination)) {
			$filesize = filesize($destination);
			// Save the Private DSA Key to a temp file, the generate the signature
			$privateKeyPath = $Shimmer->writeToTempFile('private' . time() . '.pem', $keys['private']);
			$signature = shell_exec('openssl dgst -sha1 -binary < "' . $destination . '" | openssl dgst -dss1 -sign "' . $privateKeyPath . '" | openssl enc -base64');
			
			$Shimmer->stats->incrementTotalDsaCount();
		
			$returnArray['signature'] = $signature;
			$returnArray['filesize']  = $filesize;
			$returnArray['wasOK']     = true;
			
		} else $returnArray['reason'] = 'Private key not set';
	}
} else $returnArray['reason'] = 'Unknown action';

echo json_encode($returnArray);

?>