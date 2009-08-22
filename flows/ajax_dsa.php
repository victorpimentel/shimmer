<?php
if (!defined('Shimmer')) header('Location:/');

$action = $_REQUEST['action'];
$returnArray = array('wasOK'=>false);

$publicMode  = 1;
$privateMode = 2;
$bothMode    = 3;

$publicAccessKey  = 'public-key-processing';
$privateAccessKey = 'private-key-processing';

if ( $action == "upload.public" || $action == "upload.private" ) {
	include($Shimmer->base . "/workers/worker_dsa.php");
	$worker = new SignatureWorker();
	
	$mode      = ($action=="upload.public" ? $publicMode        : $privateMode);
	$accessKey = ($action=="upload.public" ? $publicAccessKey   : $privateAccessKey);
	$input     = ($action=="upload.public" ? 'public-key-input' : 'private-key-input');
	
	$returnArray['wasOK'] = $worker->handleUpload($mode, $accessKey, $input);
} else if ( $action == "upload.check" ) {
	$sessionKey = $_REQUEST['session'];
	if ( preg_match('/^[0-9]+$/',$sessionKey) ) {
		$type       = $_GET['key_type'];
		$checkKey   = ($type=='public' ? $publicAccessKey : $privateAccessKey);
		$checkDictionary = $Shimmer->pref->read($checkKey,true);
		if ($checkDictionary) {
			$lastSession = $checkDictionary['session'];
			$returnArray['wasOK'] = true;
			$wasUpdated = ($lastSession == $sessionKey);
			$returnArray['updated'] = $wasUpdated;
			$returnArray['type']    = $type;
			$returnArray['session'] = $sessionKey;
			if ($wasUpdated) {
				if (isset($checkDictionary['key_ok'])) {
					$returnArray['key_ok'] = $checkDictionary['key_ok'];
				} else if ($checkDictionary['failed']) {
					$returnArray['failed'] = true;
					$returnArray['reason'] = $checkDictionary['reason'];
				}
			}
		} else {
			$returnArray['reason'] = "Could not fetch DSA processing status";
		}
	}
}

echo json_encode($returnArray);
?>