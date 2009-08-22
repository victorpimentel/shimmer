<?php
if (!defined('Shimmer')) header('Location:/');
class BackupWorker {
	var $Shimmer;
	
	function BackupWorker() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
	}
	
	function import($session, $apps) {
		if ($apps && count($apps)>0) {
			$checkDictionary = $this->Shimmer->pref->read('BackupProcessing',true);
			if ($checkDictionary && $checkDictionary['session']==$session) {
				$uploadFile = $checkDictionary['file'];
				if ($uploadFile) {
					if ($backup = @simplexml_load_file($uploadFile)) {
						if ($backup->app && $backup->app->statistics) {
							$allApps = $backup->app;
							foreach ($allApps as $currentApp) $this->tryToImportBackup($currentApp,$apps);
							return true;
						}
					}
					unlink($uploadFile);
				}
			}
		}
		return false;
	}
	
	// Delete any backup files uploaded more than 10 minutes ago
	function deleteOldBackupFiles() {
		if ( $this->Shimmer->tempFolder() ) {
			$ageLimit = 10*60; // 10 minutes
			$currentTime = time();
			foreach (glob($this->Shimmer->tempFolder . '*.back') as $currentFile) {
				if (($currentTime - filectime($currentFile)) > $ageLimit) unlink($currentFile);
			}
		}
	}

	function tryToImportBackup($currentApp,$selectedAppNames) {
		$appName = $currentApp['name'];
		if ($appName && in_array($appName,$selectedAppNames)) {
			$lowercaseAppName = $this->Shimmer->apps->convertTableName($appName);
			$currentVersionsTable = "versions_" . $lowercaseAppName;
			$currentUsersTable = "users_" . $lowercaseAppName;

			if ( !$this->Shimmer->apps->app($appName) ) {
				$usesSparkle = ($currentApp['usesSparkle']!="1" ? false : true);
				$newApp = $this->Shimmer->apps->create($appName, $usesSparkle);
				if ($newApp) {
					$paramData = strval($currentApp->parameters);
					if ($paramData) $params = unserialize($paramData);
					if (!$params) $params = array();
					$formattedParams = array();
					foreach ($params as $param) array_push($formattedParams, array('name'=>$param));

					$graphData = strval($currentApp->graphs);
					if ($graphData) $graphs = unserialize($graphData);
					if (!$graphs) $graphs = array();
					
					$this->Shimmer->stats->setParametersAndGraphsForApp($newApp, $formattedParams, $graphs);
					
					$appIdentifier = strval($currentApp['identifier']);
					$this->Shimmer->apps->setAppIdentifier($newApp, $appIdentifier);
					
					$allVersions = $currentApp->versions->version;
					foreach ($allVersions as $currentVersion) {
						$this->Shimmer->versions->add($newApp, array(
							'version'			=>	strval($currentVersion['number']),
							'build'				=>	strval($currentVersion['build']),
							'download'			=>	strval($currentVersion['url']),
							'size'				=>	strval($currentVersion['size']),
							'signature'			=>	strval($currentVersion['signature']),
							'notes'				=>	str_replace('\n', "\n", strval($currentVersion)),
							'published'			=>	strval($currentVersion['published']),
							'modified'			=>	strval($currentVersion['modified']),
							'download_count'	=>	strval($currentVersion['downloads']),
							'live'				=>	intval($currentVersion['live']),
							'download_rate'		=>	strval($currentVersion['downloadRates']),
							'user_rate'			=>	strval($currentVersion['userRates'])
						));
					}

					$allStats = $currentApp->statistics->stat;

					$listColsSql = "SHOW COLUMNS FROM `" . sql_safe($newApp['users_table']) . "`";
					$listColsResult = $this->Shimmer->query($listColsSql);
					$allColumns = array();
					if ($listColsResult) {
						while ($row = mysql_fetch_array($listColsResult)) {
							$fieldName = $row['Field'];
							if (strlen($fieldName)>0) array_push($allColumns,strtolower($fieldName));
						}
					}

					if ( count($allColumns)>0 ) {
						foreach ($allStats as $currentStat) {
							// Collect all applicable column values, for later insertion
							$insertNames  = array();
							$insertValues = array();
							foreach ($allColumns as $currentColumn) {
								if (strlen($currentStat[$currentColumn])>0 && $columnValue = $currentStat[$currentColumn]) {
									array_push($insertNames,	sql_safe($currentColumn));
									array_push($insertValues,	sql_safe($columnValue));
								}
							}
							
							if (count($insertNames)>0 && in_array($appIdentifier, $insertNames)) {
								// Insert collected values into users table
								$insertSql = "INSERT INTO `" . sql_safe($newApp['users_table']) . "`";
								$insertSql .= " (`" . implode("`, `",$insertNames) . "`)";
								$insertSql .= " VALUES ('" . implode("', '",$insertValues) . "')";
								$this->Shimmer->query($insertSql);
							}
						}
					}
				}
			}
		}
	}

}
?>