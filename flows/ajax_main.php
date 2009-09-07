<?php
if (!defined('Shimmer')) header('Location:/');
include_once('parseappcast.php');

$returnArray = array('wasOK'=>false);

$action = $_REQUEST['action'];

if ($action=="get.data.many") {
	// Todo reference base for proper include location
	include_once('workers/worker_data.php');
	$dataWorker = new DataWorker();
	
	if (isset($_GET['applist'])) $returnArray['allApps'] = $dataWorker->getAppList();
	
	if (isset($_GET['versions']) || isset($_GET['graphs']) || isset($_GET['graphdata']) || isset($_GET['masks']) || isset($_GET['notestheme']) || isset($_GET['usessparkle']) || isset($_GET['identifier']) || isset($_GET['keyStatus'])) {
		$chosenApps = array();
		if (isset($_GET['appID'])) {
			$requestedApp = $Shimmer->apps->appFromID($_GET['appID']);
			if ($requestedApp) array_push($chosenApps, $requestedApp);
		} else if (isset($_GET['appIDs'])) {
			$requestedApps = explode(',', $_GET['appIDs']);
			if (sizeof($requestedApps)>0) {
				foreach ($requestedApps as $requestedAppName) {
					$requestedApp = $Shimmer->apps->appFromID($requestedAppName);
					if ($requestedApp) array_push($chosenApps, $requestedApp);
				}
			}
		}
		
		if (sizeof($chosenApps)<=0) {
			$defaultApp = $Shimmer->apps->defaultApp();
			if ($defaultApp) array_push($chosenApps, $defaultApp);
		}
	
		if (sizeof($chosenApps)>0) {
			$finalApps = array();
			foreach ($chosenApps as $chosenApp) {
				$currentAppSet = array(
					'id'      => $chosenApp['id'],
					'name'    => $chosenApp['name'],
					'variant' => $chosenApp['variant']
				);
				
				if (isset($_GET['versions'])) {

					$versions = $Shimmer->versions->versions($chosenApp, array('includeUserCount'=>true));
					if ($versions!==false) {

						$allVersions = array();
						foreach ($versions as $version) {
							$versionInfo = array (
								'version'  => $version['version'],
								'build'    => $version['build'],
								'date'     => $version['published'],
								'modified' => $version['modified'],
								'live'     => $version['live'],
							);

							$versionInfo['users']		=	intval($version['user_count']);
							$versionInfo['downloads']	=	intval($version['download_count']);

							$versionInfo['downloadRateNumbers']	= $Shimmer->rates->rateNumbers($version['download_rate']);
							$versionInfo['userRateNumbers']		= $Shimmer->rates->rateNumbers($version['user_rate']);

							array_push($allVersions, $versionInfo );
						}
						$currentAppSet['allVersions'] = $allVersions;
					} else $returnArray['reason'] = "Could not access versions for supplied application";
				}

				if (isset($_GET['graphs'])) {
					// List of Parameters & Graphs, including Sparkle values
					$params = $Shimmer->stats->parametersForApp($chosenApp, true);
					$graphs = $Shimmer->stats->graphsForApp($chosenApp, true);
					if (isset($graphs) && isset($params)) {
						$currentAppSet['params'] = $params;
						$currentAppSet['graphs'] = $graphs;
					}
				}
				
				if (isset($_GET['masks'])) {
					$currentAppSet['masks'] = array(
						'download' => $chosenApp['downloadMask'],
						'notes'    => $chosenApp['notesMask']
					);
				}

				if (isset($_GET['graphdata'])) {
					// Graph Data for all 4 boxes
					include_once($Shimmer->base . "/workers/worker_stat.php");
					if (!isset($statWorker)) $statWorker = new StatWorker();
					$locations = array('r1b1', 'r2b1', 'r2b2', 'r2b3');
					$currentAppSet['stats'] = $statWorker->performStatLookup($chosenApp['id'], $locations);
				}
				
				if (isset($_GET['keyStatus'])) {
					$keys = $Shimmer->apps->dsaKeysForApp($chosenApp);
					$keysStatus = array(
						'public'  => ($keys['public'] ? true : false),
						'private' => ($keys['private'] ? true : false)
					);
					$currentAppSet['keystatus'] = $keysStatus;
				}
				
				if (isset($_GET['notestheme']))  { $currentAppSet['notesTheme']  = $chosenApp['notesTheme'];   }
				if (isset($_GET['usessparkle'])) { $currentAppSet['usesSparkle'] = $chosenApp['usesSparkle']; }
				if (isset($_GET['identifier']))  { $currentAppSet['identifier']  = $chosenApp['identifier'];   }
				
				array_push($finalApps, $currentAppSet);
			}
			$returnArray['apps'] = $finalApps;

		} else $returnArray['reason'] = 'Could not find the requested app';
		
	}
	
	if (isset($_GET['noteslist'])) {
		$allNotesTemplates = array();
		$allNotesFiles = glob($Shimmer->base . '/templates/*.xml');
		if ($allNotesFiles) {
			foreach ($allNotesFiles as $currentFile) {
				$tree = @simplexml_load_file($currentFile);
				if ($tree && $tree->details && $tree->layout) {
					array_push($allNotesTemplates, array(
						'file'	=> basename($currentFile),
						'name'	=> strval($tree->details->name)
					));
				}
			}
		}
		$returnArray['noteslist'] = $allNotesTemplates;
	}
	
	$returnArray['wasOK'] = true;
	
} else if ( $action == "app.add.version" ) {
	$appID = $_POST['appID'];
	$app = $Shimmer->apps->appFromID($appID);
	if ($app) {
		$newVersion		=	$_POST['version_number'];
		$newBuild		=	$_POST['build_number'];
		$downloadURL	=	$_POST['download_url'];
		$bytes			=	$_POST['bytes'];
		$signature		=	$_POST['signature'];
		$notes			=	$_POST['release_notes'];
		
		if (isset($newVersion) && isset($newBuild)) {
			$searchArray = array('version'=>$newVersion);
			if (strlen($newBuild)>0) $searchArray['build'] = $newBuild;
			if (!$Shimmer->versions->exists($app,$searchArray)) {
				$versionAdded = $Shimmer->versions->add($app, array(
					'version'   => $newVersion,
					'build'     => $newBuild,
					'download'  => $downloadURL,
					'size'      => $bytes,
					'signature' => $signature,
					'notes'     => $notes,
					'DID_CHECK_EXISTS' => true
				));
				
				if ($versionAdded) {
					$returnArray['wasOK'] = true;
				} else $returnArray['reason'] = "Failed to add version";
			} else $returnArray['reason'] = "Version already exists";
		} else $returnArray['reason'] = "Please supply version and build parameters";
	} else $returnArray['reason'] = "Supplied app does not exist";
} else if ( $action == "app.update.version" ) {
	$appID = $_POST['appID'];
	$app = $Shimmer->apps->appFromID($appID);
	if ($app) {
		$ref_timestamp = $_POST['reference_timestamp'];
		$new_timestamp = $_POST['new_timestamp'];
		
		$updateValues = array(
			'version'	=>	'',
			'build'		=>	'',
			'download'	=>	'',
			'bytes'		=>	0,
			'signature'	=>	'',
			'notes'		=>	'',
			'published'	=>	$new_timestamp,
			'REFERENCE_TIMESTAMP' => $ref_timestamp,
			'DID_CHECK_EXISTS' => true
		);

		if (isset($_POST['version_number']))	$updateValues['version']	= $_POST['version_number'];
		if (isset($_POST['build_number']))	$updateValues['build']		= $_POST['build_number'];
		if (isset($_POST['download_url']))	$updateValues['download']	= $_POST['download_url'];
		if (isset($_POST['bytes']))			$updateValues['bytes']		= $_POST['bytes'];
		if (isset($_POST['signature']))		$updateValues['signature']	= $_POST['signature'];
		if (isset($_POST['release_notes']))	$updateValues['notes']		= $_POST['release_notes'];
		
		if ($Shimmer->versions->exists($app, array('timestamp'=>$ref_timestamp) )) {
			$versionUpdated = $Shimmer->versions->update($app, $updateValues);
			
			if ($versionUpdated) {
				$returnArray['wasOK'] = true;
			} else $returnArray['reason'] = "Could not update version";
		} else $returnArray['reason'] = "Reference version does not exist";
	} else $returnArray['reason'] = "Supplied app does not exist";
} else if ( $action == "app.delete.version" ) {
	$appID = $_GET['appID'];
	$app = $Shimmer->apps->appFromID($appID);
	if ($app) {
		$deleteTimestamp = $_GET['delete_timestamp'];
		$deleteVersion   = $_GET['delete_version'];
		if ( $Shimmer->versions->delete($app,$deleteTimestamp,true) ) {
			$returnArray['wasOK'] = true;
			$returnArray['deletedTimestamp'] = $deleteTimestamp;
		} else $returnArray['reason'] = "Version delete failed";
	} else $returnArray['reason'] = "Supplied app name does not exist";
} else if ( $action == "app.delete" ) {
	$appID = $_GET['appID'];
	$existingApp = $Shimmer->apps->appFromID($appID);
	if ($existingApp) {
		if ( $Shimmer->apps->delete($existingApp) ) {
			$returnArray['wasOK'] = true;
		} else $returnArray['reason'] = "Delete app failed";
	} else $returnArray['reason'] = "App does not exist";
} else if ( $action == "app.save" ) {
	$appID          = $_POST['appID'];
	$newName		= $_POST['new_name'];
	$variant		= $_POST['variant'];
	$paramJson		= $_POST['parameters'];
	$graphJson		= $_POST['graphs'];
	$notesTheme		= $_POST['notes'];
	$notesMask		= $_POST['notesMask'];
	$downloadMask	= $_POST['downloadMask'];
	$usesSparkle	= (isset($_POST['usesSparkle']) && $_POST['usesSparkle']=='1' ? true : false);
	$identifier		= (isset($_POST['identifier']) ? $_POST['identifier'] : 'ip');
	$publicKeySess  = $_POST['publicKey'];
	$privateKeySess = $_POST['privateKey'];
	
	include_once('jsonhelper.php');
	$params = json_decode(prepareJsonStringForDecoding($paramJson), true);
	$graphs = json_decode(prepareJsonStringForDecoding($graphJson), true);

	if (isset($newName) && isset($params) && isset($graphs)) {
		
		$appReference = false;
		
		// UPDATING AN APP
		if (isset($appID)) {
			$existingApp = $Shimmer->apps->appFromNameAndVariant($newName, $variant);
			if ($existingApp==false || $existingApp['id']==$appID) {
				// App name and Variant are ok, so rename be referencing appID
				$appReference = $Shimmer->apps->renameAppWithID($appID, $newName, $variant);

				if (!$appReference) $returnArray['reason'] = 'Could not rename the app';
			} else {
				$returnArray['nameused'] = true;
				$returnArray['reason'] = "That app name is already in use";
			}
			
		// NEW APP
		} else {
			//check for name/variant collisions
			$existingApp = $Shimmer->apps->appFromNameAndVariant($newName, $variant);
			if ($existingApp==false) {
				$appReference = $Shimmer->apps->createNew($newName, $variant, $usesSparkle);
			} else {
				$returnArray['nameused'] = true;
				$returnArray['reason'] = "That app name is already in use";
			}
		}
		
		if ($appReference) {
			// The app was saved or renamed. Anything else is a bonus.
			$returnArray['wasOK'] = true;
			
			$paramsValid = $Shimmer->stats->paramsAreValid($params);
			$graphsValid = $Shimmer->stats->graphsAreValid($graphs);
		
			if ($paramsValid && $graphsValid) {
				// Add Parameters and Graphs
				if ($Shimmer->stats->setParametersAndGraphsForApp($appReference, $params, $graphs)) {
					// Update the 'Uses Sparkle' flag
					$Shimmer->apps->setAppUsesSparkle($appReference, $usesSparkle);
					$Shimmer->apps->setAppIdentifier($appReference, $identifier);
					
					$Shimmer->apps->setNotesThemeForApp($appReference, $notesTheme);
					$Shimmer->apps->setMasksForApp($appReference, array(
						'downloadMask' => $downloadMask,
						'notesMask'    => $notesMask
					));
					
					// Try to read the uploaded DSA keys from the tmp/ folder
					$Shimmer->apps->loadSignatureKeysForApp($appReference, array(
						'public'  => (strlen($publicKeySess)  >0 ? $publicKeySess  : false),
						'private' => (strlen($privateKeySess) >0 ? $privateKeySess : false),
					));
					$returnArray['wasOK'] = true;
					
					// Todo reference base for proper include location
					include_once('workers/worker_data.php');
					$dataWorker = new DataWorker();
					$refreshedAppList = $dataWorker->getAppList();
					$returnArray['allApps'] = $refreshedAppList;

					$returnArray['createdID']      = $appReference['id'];
					$returnArray['createdAppName'] = $appReference['name'];
					
					// Try to import versions for appcast. Still return OK even if it doesn't work.
					$appcast		= $_POST['appcastURL'];
					$versionsString	= $_POST['importVersions'];
					if (isset($appcast) && isset($versionsString) && $Shimmer->isURL($appcast)) {
						$chosenVersions = json_decode(prepareJsonStringForDecoding($versionsString), false);
						if ($chosenVersions && sizeof($chosenVersions)>0) {
							$theXml = $Shimmer->readURL($appcast);
							if ($theXml) {
								$existingVersions = $Shimmer->versions->flatVersions($appReference);
								if ($existingVersions==false) $existingVersions = array();
								$versions = parse_appcast($theXml);
								$usedTimestamps = array();
								$worked = true;
								$importCount = 0;
								foreach( $versions as $version => $details ) {
									if ( in_array($version,$chosenVersions) && !in_array($version, $existingVersions) ) {
										$versionNumber	= $version;
										$buildNumber	= $details['build'];
										$downloadURL	= $details['url'];
										if (strlen($details['notes'])>0) {
											$notes = $details['notes'];
										} else if (strlen($details['noteslink'])>0) {
											$notes = $Shimmer->readURL($details['noteslink']);
										} else {
											$notes = "";
										}
										$signature	= $details['signature'];
										$size		= $details['size'];
										
										// Work out the timestamp for the version. This gets a bit tricky
										// if all appcast dates are the same, or don't exist at all.
										$date = $details['date'];
										if ($date) {
											while (in_array($date,$usedTimestamps)) $date = intval($date)-10;												
										} else {
											$usedCount = sizeof($usedTimestamps);
											if ($usedCount>0) {
												$mostAncientDate = $usedTimestamps[$usedCount-1];
											} else {
												$mostAncientDate = time();
											}
											$date = intval($mostAncientDate)-10;
										}
										array_push($usedTimestamps,$date);

										$addWorked = $Shimmer->versions->add($appReference, array(
											'version'			=>	strval($versionNumber),
											'build'				=>	strval($buildNumber),
											'download'			=>	strval($downloadURL),
											'size'				=>	strval($size),
											'signature'			=>	strval($signature),
											'notes'				=>	preg_replace("/.*<body[^>]*>|<\/body>.*/si", "", strval($notes)),
											'published'			=>	strval($date),
											'modified'			=>	strval($date),
										));
										if ( !$addWorked ) {
											$worked = false;
										} else {
											$importCount++;
										}
									}
								}
								$returnArray['importCount']	= $importCount;
							}
						}
					}
					
				} else {
					$returnArray['reason'] = "Could not create Parameters and Graphs";
					$returnArray['error']  = mysql_error();
					$returnArray['params']  = $params;
				}
			} else {
				$returnArray['reason'] = 'Some of the supplied values were not valid';
				$returnArray['paramsValid'] = $paramsValid;
				$returnArray['graphsValid'] = $graphsValid;
			}	

		}		
	}	
} else if ($action=="appcast.getversions") {
	$appcastURL = $_POST['url'];
	if (strlen($appcastURL)) {
		$theXml = $Shimmer->readURL($appcastURL);
		if ($theXml) {
			$returnArray['wasOK'] = true;
			$allVersions = array();
			$versions = parse_appcast($theXml);
			foreach( $versions as $version => $details ) array_push($allVersions,$version);
			$returnArray['versions'] = $allVersions;
		} else $returnArray['reason'] = 'XML could not be loaded';
	} else $returnArray['reason'] = 'Supplied appcast URL was not valid';
} else if ( $action == "apps.reorder" ) {
	$sortDataString = $_POST['sortdata'];
	parse_str($sortDataString);
	for ($i = 0; $i < count($applist); $i++) {
		$currentID = $applist[$i];
		$currentAppOrder = $i+1;

		$updateSql = "UPDATE `applications` SET `app_order`='" . sql_safe($currentAppOrder) . "' WHERE `id`='" . sql_safe($currentID) . "'";
		if ( $Shimmer->query($updateSql) ) {
			$returnArray['wasOK'] = true;
			
			$Shimmer->apps->loadAppsList();
			$allApps = array();
			foreach ($Shimmer->apps->list as $app) {
				array_push($allApps, array(
					'name'	=>	$app['name'],
					'id'	=>	$app['id'],
					'users'	=>	$Shimmer->stats->appUsers($app)
				));
			}
			$returnArray['allApps'] = $allApps;
		} else {
			$returnArray['reason'] = "Reorder apps didn't work";
		}
	}
} else if ( $action == "version.live.set" ) {
	$appID = $_POST['appID'];
	$timestamp = $_POST['ref_timestamp'];
	$isLive = $_POST['is_live'];
	if ( isset($appID) && isset($timestamp) && isset($isLive) ) {
		$app = $Shimmer->apps->appFromID($appID);
		if ($app) {
			$liveInt = $isLive=="1" ? 1 : 0;
			$sql = "UPDATE `" . sql_safe($app['versions_table']) . "` SET `live`=$liveInt WHERE `published`='" . sql_safe($timestamp) . "'";
			if ( $Shimmer->query($sql) ) {
				$returnArray['wasOK'] = true;	
			} else $returnArray['reason'] = "Could not switch live flag";
		} else $returnArray['reason'] = "App does not exist";
	}
} else if ( $action == "app.version.get.values" ) {
	$appID = $_GET['appID'];
	$timestamp = $_GET['timestamp'];
	
	if ( strlen($appID)>0 && strlen($timestamp)>0 ) {
		$app = $Shimmer->apps->appFromID($appID);
		if ($app) {
			$versions = $Shimmer->versions->versions($app,array(
				'timestamp'			=>	$timestamp,
				'onlyLive'			=>	false,
				'flipDirection'		=>	true,
				'limit'				=>	1
			));
			if ($versions && count($versions)>0) {
				$version = $versions[0];
				$returnArray["wasOK"] = true;
				$returnArray["versionInfo"] = 	array(
												'version'	=>	$version['version'],
												'build'		=>	$version['build'],
												'download'	=>	$version['download'],
												'bytes'		=>	$version['size'],
												'signature'	=>	$version['signature'],
												'date'		=>	$version['published'],
												'live'		=>	$version['live'],
												'notes'		=>	$version['notes']
												);
			} else $returnArray["reason"] = "Version does not exist";
		} else $returnArray["reason"] = "App does not exist";
	} else $returnArray["reason"] = "Not all required parameters set";
} else {
	$returnArray["reason"] = "Unknown Action";
}

echo json_encode($returnArray);

?>