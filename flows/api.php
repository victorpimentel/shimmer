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

$appName    = $_GET['appName'];
$appVariant = $_GET['appVariant'];
if (!isset($appVariant)) $appVariant = "";

$method = strtolower($_GET['method']);
if ($method == "version.info") {
	if ($app = $Shimmer->apps->appFromNameAndVariant($appName, $appVariant)) {
		// CHOOSE WHICH FIELDS TO SELECT
		$chosenColumns = array();
		$allowedFilters = array("version","build","size","signature","notes","users","downloads");
		$filterPairs = array(
							array("downloads","download_count"),
							array("size",     "bytes"),
							array("users",    "user_count")
						);
		$filterString = $_GET['fields'];
		if ( isset($filterString) && strlen($filterString)>0 ) {
			$userFilters = explode(',',strtolower($filterString));
			if ( count($userFilters)>0 ) {
				if (!in_array("version",$userFilters)) array_push($userFilters,"version");
				foreach ($userFilters as $key => $userFilter) {
					if (strlen($userFilter)>0 && in_array($userFilter,$allowedFilters) && $userFilter != "users") {
						array_push( $chosenColumns, convertFieldName($filterPairs,strval($userFilter),1) );
					}
				}
			}
		}
	
		// Search Setup (only return live versions, etc)
		$searchConditions = array( 'fields'=>$chosenColumns, 'onlyLive'=>true );
		if ($userFilters && in_array("users",$userFilters)) $searchConditions['includeUserCount'] = true;
	
		// Version Search
		$versionCriteria = $_GET['appVersion'];
		if (!isset($versionCriteria)) $versionCriteria = "all";
		if ($versionCriteria == "latest") {
			$searchConditions['limit'] = 1;
		} else if ($versionCriteria == "oldest") {
			$searchConditions['flipDirection'] = true;
			$searchConditions['limit'] = 1;
		} else if ($versionCriteria != "all") {
			$searchConditions['increment'] = $versionCriteria;
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
	} else $resp['reason'] = "App does not exist";
} else if ($method == "application.list") {
	$returnedApplications = array();
	foreach ($Shimmer->apps->list as $app) {
		$currentApplication = array(
			'name'        => $app['name'],
			'variant'     => $app['variant'],
			'displayName' => $app['name'] . ($app['variant'] ? ': ' . $app['variant'] : ''),
			'id'          => $app['id']
		);
		array_push($returnedApplications, $currentApplication);
	}
	$resp['applications'] = $returnedApplications;
	$resp['ok'] = true;
} else $resp['reason'] = "No method supplied";

if ($resp['ok']===false && !$resp['reason']) $resp['reason'] = "Unknown error";

////////////////////////

$callback = $_GET['callback'];
if ($callback) {
	header('Content-Type: application/javascript');
	echo $callback . "(" . json_encode($resp) . ");";
} else if (isset($_GET['xml'])) {
	header('Content-Type: text/xml');
	$exp = simplexml_load_string("<shimmer></shimmer>");
	$exp->addAttribute("version", $Shimmer->version);
	$exp->addAttribute("generated", time());
	$exp->addAttribute("ok", $resp['ok']?1:0);
	
	if ($method == "version.info") {
		$versionsNode = $exp->addChild("versions");
		foreach ($resp['versions'] as $version) {
			$versionNode = $versionsNode->addChild("version");
			if (isset($version['version']))   $versionNode->addAttribute("version",   $version['version']);
			if (isset($version['build']))     $versionNode->addAttribute("build",     $version['build']);
			if (isset($version['size']))      $versionNode->addAttribute("size",      $version['size']);
			if (isset($version['downloads'])) $versionNode->addAttribute("downloads", $version['downloads']);
			if (isset($version['signature'])) $versionNode->addAttribute("signature", $version['signature']);
			
			// Insert the release notes as CDATA if present
			if (isset($version['notes'])) {
				$expNotes = $versionNode->addChild("notes");
				$node     = dom_import_simplexml($expNotes);
				$no       = $node->ownerDocument;
				$node->appendChild($no->createCDATASection($version['notes']));
			}
		}
	} else if ($method == "application.list") {
		$appsNode = $exp->addChild("apps");
		foreach ($resp['applications'] as $app) {
			$appNode = $appsNode->addChild("app");
			$appNode->addAttribute("name",        $app['name']);
			$appNode->addAttribute("variant",     $app['variant']);
			$appNode->addAttribute("displayName", $app['displayName']);
			$appNode->addAttribute("id",          $app['id']);
		}
	}
	
	echo $exp->asXML();
} else {
	header('Content-Type: application/json');
	echo json_encode($resp);
}

?>