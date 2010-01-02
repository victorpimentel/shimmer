<?php
if (!defined('Shimmer')) header('Location:/');

$appName    = $_GET['appName'];
$appVariant = $_GET['appVariant'];
if (!isset($appVariant)) $appVariant = "";

if ( isset($appName) ) {
	$app = $Shimmer->apps->appFromNameAndVariant($appName, $appVariant);
	if ($app) {
		// Check if a cache exists first, to save a few DB calls
		include_once('cachemanager.php');
		if (!CacheManager::printCacheForCurrentHash('application/rss+xml')) {
			$versionLimit = $_GET['limit'];
			$minVersion   = $_GET['appVersion'];
		
			$whereConditions = array("onlyLive"=>true);
			if ( !isset($_GET['all']) ) $whereConditions['limit'] = 1;
			$versions = $Shimmer->versions->versions($app, $whereConditions);
			if ($versions && count($versions)>0) {
				$notesMask    = $app['notesMask'];
				$downloadMask = $app['downloadMask'];
				if ($notesMask && $downloadMask) {
					header('Content-type: application/rss+xml');
					
					// Start output buffering, so we can cache the response we are about to create
					ob_start();
					
					echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\" xmlns:sparkle=\"http://www.andymatuschak.org/xml-namespaces/sparkle\">\n\t<channel>\n\t\t<title>$appName Updates</title>\n\t\t<description>Updates for " . $appName . "</description>\n\t\t<generator>Shimmer</generator>";

					foreach ($versions as $version) {
						$sparkleVersionCode = '';
						$build = $version['build'];
						$versionNumber = $version['version'];
						if (isset($build) && strlen($build)>0) {
							$sparkleVersionCode = 'sparkle:version="' . $build . '" sparkle:shortVersionString="' . $versionNumber . '"';
						} else {
							$sparkleVersionCode = 'sparkle:version="' . $versionNumber . '"';
						}
						include_once('completemask.php');
						$notesLink    = completeMask($notesMask,    $app, $version, false);
						$downloadLink = completeMask($downloadMask, $app, $version, true);
				
						// Tack the user's own version on the release notes URL, so that they can see notes for multiple versions.
						if (isset($minVersion)) $notesLink = $Shimmer->appendParameterToURL($notesLink, 'minVersion=' . $minVersion, true);

						echo "\n\n\t\t<item>\n\t\t\t<title>$appName " . $version['version'] . "</title>\n\t\t\t<pubDate>" . date(DATE_RSS,$version['published']) . "</pubDate>\n\t\t\t<sparkle:releaseNotesLink><![CDATA[" . $notesLink . "]]></sparkle:releaseNotesLink>\n\t\t\t<enclosure\n\t\t\t\tsparkle:dsaSignature=\"" . $version['signature'] . "\"\n\t\t\t\t" . $sparkleVersionCode . "\n\t\t\t\turl=\"" . $downloadLink . "\"\n\t\t\t\tlength=\"" . $version['size'] . "\"\n\t\t\t\ttype=\"application/octet-stream\" />\n\t\t</item>";
					}
					echo "\n\n\t</channel>\n</rss>";
					
					// Print, disable and cache the buffer
					$buffer = ob_get_contents();
					ob_end_clean();
					echo $buffer;
					CacheManager::storeCacheForCurrentHash($buffer);
					
				} else echo "Please configure your custom Download and Notes masks in Shimmer";
			}
		} else { // we used the cache, so print out a bit of whitespace for debugging
			echo "\n\n    \n\n";
		}

		// If the app uses Sparkle, make sure the expected User-Agent is supplied. Helps to prevent fake stats.
		$userAgentPassed = true;
		if ($app['usesSparkle']) {
			$userAgentPassed = preg_match('/^.*\sSparkle\//', $_SERVER["HTTP_USER_AGENT"]);
		}
		
		if ($userAgentPassed) {
			$usersTable = $app['users_table'];
			if ($usersTable) {
				$columnRestrictions = array();
				$listColsSql = "SHOW COLUMNS FROM `" . sql_safe($usersTable) . "`";
				$listColsResult = $Shimmer->query($listColsSql);
				if ($listColsResult) {
					while ($row = mysql_fetch_array($listColsResult)) {
						$fieldName = $row['Field'];
						if (strlen($fieldName)>0) array_push($columnRestrictions,strtolower($fieldName));
					}
				}
				$sparkleParams = sparkleParameters($app, $columnRestrictions);
				if ($sparkleParams) refreshUser($app,$sparkleParams,$columnRestrictions);
			}
		}
		
		$Shimmer->rates->processVersionRates($app['id']);
		
	} else echo 'The app name you have supplied does not exist';
} else echo 'Please supply an app name using the \'appName\' (and optional `appVariant`) parameters';

function refreshUser($app,$sparkleParams,$columnRestrictions) {
	global $Shimmer;
	
	// Prevent spoofing of app version
	$existingIncrement = $Shimmer->versions->versionAndBuildForIncrement($app, $sparkleParams['appversion']);
	if ($existingIncrement) {
		$theIP = $Shimmer->requestIP();
		
		// Find the unique Identifier for the request
		$appIdentifier = $app['identifier'];
		$identifierValue = false;
		if ($appIdentifier=='ip') {
			$identifierValue = $theIP;
		} else {
			$identifierParamValue = $sparkleParams[strtolower($appIdentifier)];
			if ($identifierParamValue && strlen($identifierParamValue)>0) $identifierValue = $identifierParamValue;
		}
		
		if ($identifierValue) {
			// Work out if the user has been seen before, using the app's Identifier
			$userAlreadyExists = ($identifierValue ? $Shimmer->stats->userExists($app,$identifierValue) : false);

			$newValues = array( array('name'=>'ip', 'value'=>$theIP) );
	
			// All $paramName values are guaranteed to be lowercase
			foreach ($sparkleParams as $paramName => $paramValue) {
				if ( strlen($paramValue)>0 ) {
					// Do extra processing for version numbers.
					if ( $paramName == "appversion" ) {
						if ($existingIncrement['version']) array_push( $newValues, array('name'=>'last_version', 'value'=>$existingIncrement['version']) );
						if ($existingIncrement['build'])   array_push( $newValues, array('name'=>'last_build',   'value'=>$existingIncrement['build']) );
						if (!$userAlreadyExists) {
							if ($existingIncrement['version']) array_push( $newValues, array('name'=>'first_version', 'value'=>$existingIncrement['version']) );
							if ($existingIncrement['build'])   array_push( $newValues, array('name'=>'first_build',   'value'=>$existingIncrement['build']) );
						}
					} else if ( in_array($paramName, $columnRestrictions) ){
						array_push( $newValues, array('name'=>$paramName, 'value'=>$paramValue) );
					}
				}
			}

			$currentDateSql = date('Y-m-d');
			array_push( $newValues, array('name'=>"last_seen", 'value'=>$currentDateSql) );
			if (!$userAlreadyExists) array_push( $newValues, array('name'=>"first_seen", 'value'=>$currentDateSql) );
		
			$usersTable = $app['users_table'];

			if ( $userAlreadyExists ) {
				$updateSql = "UPDATE `" . sql_safe($usersTable) . "` SET ";
				foreach ($newValues as $i => $newPair) {
					$updateSql .= "`" . sql_safe($newPair['name']) . "`='" . sql_safe($newPair['value']) . "'";
					if ($i+1<count($newValues)) $updateSql .= ", ";
				}
				$updateSql .= " WHERE `" . $appIdentifier . "`='" . sql_safe($identifierValue)  . "'";
				$worked = $Shimmer->query($updateSql);
			} else {
				$insertSql = "INSERT INTO `" . sql_safe($usersTable) . "` (";
				foreach ($newValues as $i => $newPair) {
					$insertSql .= "`" . sql_safe($newPair['name']) . "`";
					if ($i+1<count($newValues)) $insertSql .= ", ";
				}
				$insertSql .= ") VALUES (";
				foreach ($newValues as $i => $newPair) {
					$insertSql .= "'" . sql_safe($newPair['value']) . "'";
					if ($i+1<count($newValues)) $insertSql .= ", ";
				}
				$insertSql .= ")";
				$worked = $Shimmer->query($insertSql);
			}
		}
	}
}

function isTheme($xmlobj) {
	if (! $xmlobj->details) return false;
	if (! $xmlobj->layout) return false;

	if (! $xmlobj->details->name) return false;
	if (! $xmlobj->details->author) return false;
	if (! $xmlobj->details->author['email']) return false;
	if (! $xmlobj->details->version) return false;
	if (! $xmlobj->details->protected) return false;

	if (! $xmlobj->layout->header) return false;
	if (! $xmlobj->layout->release) return false;
	if (! $xmlobj->layout->footer) return false;

	return true;
}

function sparkleParameters($app, $columnRestrictions) {
	//	$columnRestrictions is assumed to be an array, with each item representing a valid users table column name
	$returnSparkleParameters = array();
	$allMandatoryParamsPresent = true;
	
	$getPairs = array();
	foreach ($_GET as $key => $getValue) if ($key!="appcast") $getPairs[strtolower($key)] = $getValue;

	// Only add an entry if certain Parameters are present. This includes a non-IP Identifier Parameter.
	$mandatoryParameters = array("appversion");
	if ($app['identifier']!="ip") array_push($mandatoryParameters, strtolower($app['identifier']));
	
	foreach ($mandatoryParameters as $mandatoryParamName) {
		$lowercaseParamName = strtolower($mandatoryParamName);
		$theMandatoryParam = $getPairs[$lowercaseParamName];
		if ( strlen(trim($theMandatoryParam))==0 ) {
			$allMandatoryParamsPresent = false;
			break;
		} else {
			$returnSparkleParameters[$lowercaseParamName] = $theMandatoryParam;
		}
	}

	if ( $allMandatoryParamsPresent ) {
		foreach ($columnRestrictions as $validColumn) {
			$lowercaseColumnName = strtolower($validColumn);
			$paramValue = $getPairs[$lowercaseColumnName];
			if ( strlen($paramValue)>0 ) $returnSparkleParameters[$lowercaseColumnName] = $paramValue;
		}
		return $returnSparkleParameters;
	}
	return false;
}

?>