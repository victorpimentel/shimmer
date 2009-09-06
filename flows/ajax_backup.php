<?php
if (!defined('Shimmer')) header('Location:/');

$action = $_REQUEST['action'];
$sessionKey = $_REQUEST['backup_session'];
$returnArray = array('wasOK'=>false);

if ( $action == "backup.upload") {
	deleteOldBackupFiles();
	if ( $_FILES['backup_import_file'] ) {
		if ($_FILES['backup_import_file']['error']>0) {
			echo "---ERROR";
			$failureReason = "";
			switch ($_FILES['backup_import_file']['error']) {
				case 1:case 2:
					$failureReason = "The file was too large";
					break;
				case 3:
					$failureReason = "The file did not upload properly";
					break;
				case 4:
					$failureReason = "No file was uploaded";
					break;
				case 6:case 7:
					$failureReason = "The file could not be saved";
					break;
				default:
					$failureReason = "An unknown error occured";
					break;
			}
			$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'failed'=>true, 'reason'=>$failureReason),true);
		} else {
			if ( preg_match('/^[0-9]+$/',$sessionKey) && $Shimmer->tempFolder() ) {
				$uploaddir = $Shimmer->tempFolder;
				$uploadFile = $uploaddir . md5(time() . "_" . basename($_FILES['backup_import_file']['name'])) . ".back";
				if (move_uploaded_file($_FILES['backup_import_file']['tmp_name'], $uploadFile)) {
					//process uploaded file
					$backup = @simplexml_load_file($uploadFile);
					$shouldDeleteFile = true;
					if ($backup) {
						if ($backup->app) {
							$returnArray['wasOK'] = true;
							$allApps = array();
							foreach ($backup->app as $currentApp) {
								array_push($allApps,array(
									'id'      => strval($currentApp['id']),
									'name'    => strval($currentApp['name']),
									'variant' => strval($currentApp['variant']),
									'valid'   => ($Shimmer->apps->appFromID($currentApp['id']) ? false : true)
								));
							}
							if (count($allApps)>0) {
								$shouldDeleteFile = false;
								$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'file'=>$uploadFile, 'apps'=>$allApps),true);
							} else {
								$returnArray['reason'] = "Not valid (no apps found in backup)";
								$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'failed'=>true, 'reason'=>'The backup file contain no apps.'),true);
							}
						} else {
							$returnArray['reason'] = "Not valid (no apps found in backup)";
							$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'failed'=>true, 'reason'=>'The backup file contained no apps'),true);
						}
					} else {
						$returnArray['reason'] = "Not valid";
						$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'failed'=>true, 'reason'=>'The file was not valid'),true);
					}
					if ($shouldDeleteFile) unlink($uploadFile);
				} else {
					$returnArray['reason'] = 'Could not move temporary file';
					$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'failed'=>true, 'reason'=>'Could not save file. Please check permissions on /tmp'),true);
				}
			} else {
				$returnArray['reason'] = 'Could not move temporary file';
				$Shimmer->pref->save('BackupProcessing',array('session'=>$sessionKey, 'failed'=>true, 'reason'=>'Could not save file. Please check permissions on /tmp'),true);
			}
		}
	}
} else if ( $action == "backup.upload.check" ) {
	if ( preg_match('/^[0-9]+$/',$sessionKey) ) {
		$checkDictionary = $Shimmer->pref->read('BackupProcessing',true);
		if ($checkDictionary) {
			$lastSession = $checkDictionary['session'];
			$returnArray['wasOK'] = true;
			$wasUpdated = ($lastSession == $sessionKey);
			$returnArray['updated'] = $wasUpdated;
			if ($wasUpdated) {
				if ($checkDictionary['apps']) {
					$returnArray['apps'] = $checkDictionary['apps'];
				} else if ($checkDictionary['failed']) {
					$returnArray['failed'] = true;
					$returnArray['reason'] = $checkDictionary['reason'];
				}
			}
		} else {
			$returnArray['reason'] = "Could not fetch backup values";
		}
	}
} else {
	$returnArray['reason'] = "Unknown action";
}

echo json_encode($returnArray);

function deleteOldBackupFiles() {
	global $Shimmer;
	if ( $Shimmer->tempFolder() ) {
		$ageLimit = 0*60;
		$currentTime = time();
		foreach (glob($Shimmer->tempFolder . '*.back') as $currentFile) {
			if (($currentTime - filectime($currentFile)) > $ageLimit) unlink($currentFile);
		}
	}
}

?>