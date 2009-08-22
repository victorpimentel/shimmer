<?php
if (!defined('Shimmer')) header('Location:/');
$resp = array('ok'=>false);

// 1 is user>sql, 2 is sql>user
function convertFieldName($table,$field,$type=1) {
	$sourceCol = ($type==1 ? 0 : 1);
	$destCol   = ($type==1 ? 1 : 0);
	foreach ($table as $row) {
		if ($row[$sourceCol]==$field) return $row[$destCol];
	}
	return $field;
}

$appName = $_GET['app'];
if ( isset($appName) ) {
	$app = $Shimmer->apps->app($appName);
	if ($app) {
		$versionsTable = $app['versions_table'];
		if ($versionsTable) {
			$method = strtolower($_GET['method']);
			if ($method == "version.info") {
			
				// CHOOSE WHICH FIELDS TO SELECT
				$chosenColumns = array();
				$allowedFilters = array("version","build","size","signature","notes","users","downloads");
				$filterPairs = array(
									array("downloads","download_count"),
									array("size","bytes"),
									array("users","user_count")
								);
				$filterString = $_GET['fields'];
				if ( isset($filterString) && strlen($filterString)>0 ) {
					$userFilters = explode(',',strtolower($filterString));
					if ( count($userFilters)>0 ) {
						if (!in_array("version",$userFilters)) array_push($userFilters,"version");
						foreach ($userFilters as $key => $userFilter) {
							if (strlen($userFilter)>0 && in_array($userFilter,$allowedFilters) && $userFilter != "users") {
								array_push(
									$chosenColumns,
									convertFieldName($filterPairs,strval($userFilter),1)
								);
							}
						}
					}
				}
				
				// Search Setup (only return live versions, etc)
				$searchConditions = array( 'fields'=>$chosenColumns, 'onlyLive'=>true );
				if ($userFilters && in_array("users",$userFilters)) $searchConditions['includeUserCount'] = true;
				
				// Version Search
				$versionCriteria = $_GET['version'];
				if (!isset($versionCriteria)) $versionCriteria = "all";
				if ($versionCriteria == "latest") {
					$searchConditions['limit'] = 1;
				} else if ($versionCriteria == "oldest") {
					$searchConditions['flipDirection'] = true;
					$searchConditions['limit'] = 1;
				} else if ($versionCriteria !== "all") {
					$searchConditions['version'] = $versionCriteria;
				}
				
				// Build Search
				$buildCriteria = $_GET['build'];
				if (isset($buildCriteria)) {
					$searchConditions['build'] = $buildCriteria;
				}
				
				$searchedVersions = $Shimmer->versions->versions($app, $searchConditions);
				if ($searchedVersions!==false) {
					$resp['ok'] = true;
					$returnVersions = array();
					foreach ($searchedVersions as $version) {
						$currentVersion = array();
						foreach ($version as $currentColumn => $currentValue) {
							if (isset($currentValue)) {
								$userColumnName = convertFieldName($filterPairs,strval($currentColumn),2);
								if (in_array($userColumnName,$allowedFilters) && $currentColumn!="version") $currentVersion[$userColumnName] = $currentValue;
							}
						}
						$rowVersion = strval($version['version']);
						$currentVersion['version'] = $rowVersion;
						array_push($returnVersions,$currentVersion);
					}
					$resp['versions'] = $returnVersions;
				}
			} else $resp['reason'] = "No method supplied";
		} else $resp['reason'] = "Could not open table";
	} else $resp['reason'] = "App does not exist";
} else $resp['reason'] = "No app supplied";

if ($resp['ok']===false && !$resp['reason']) $resp['reason'] = "Unknown error";

////////////////////////

$callback = $_GET['callback'];
if ($callback) {
	header('Content-Type: application/javascript');
	echo $callback . "(" . json_encode($resp) . ");";
} else {
	header('Content-Type: application/json');
	echo json_encode($resp);
}

?>