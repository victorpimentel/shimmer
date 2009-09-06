<?php
if (!defined('Shimmer')) header('Location:/');
if (isset($_GET['loudandclear'])) {
	echo 'LOUDANDCLEAR';
	exit();
}
$appName	= $_GET['appName'];
$appVariant = $_GET['appVariant'];
if (!isset($appVariant)) $appVariant = "";
$version	= $_GET['version'];
$build		= $_GET['build'];

$paramCount = 1;
if ( isset($version) && isset($appName) ) {
	$paramCount += 2;
	$app = $Shimmer->apps->appFromNameAndVariant($appName, $appVariant);
	if ($app) {
		$versionSearch = array('onlyLive'=>true, 'version'=>$version);
		if (isset($build) && strlen($build)>0) {
			$versionSearch['build'] = $build;
			$paramCount++;
		}
		$theVersion = $Shimmer->versions->version($app, $versionSearch);
		if ($theVersion) {
			$downloadURL = $theVersion['download'];
			// If there are extra parameters supplied, append them to the download URL
			if (sizeof($_GET)-$paramCount>0) {
				$extraParameters = array();
				foreach($_GET as $variable => $value) {
					if ($variable!='download' && $variable!='app' && $variable!='version' && $variable!='build') {
						array_push($extraParameters, "$variable=$value");
					}
				}
				$downloadURL .= preg_match('/\?(&?[^=]+=[^=]+)*$/', $downloadURL) ? '&' : '?';
				$downloadURL .= implode('&',$extraParameters);
			}
			header('Location: ' . $downloadURL);
			$newCount = intval($theVersion['download_count']) + 1;
			$updateSql = "UPDATE `" . sql_safe($app['versions_table']) . "` SET `download_count`=" . sql_safe($newCount) . " WHERE `version`='" . sql_safe($version) . "'";
			if (isset($build) && strlen($build)>0) $updateSql .= " AND `build` = '" . $build . "'";
			$Shimmer->query($updateSql);
		} else echo 'Download link could not be found, because the version does not exist.';
		$Shimmer->rates->processVersionRates($app['id']);
	} else echo 'Download link could not be found, because the application table does not exist.<br>Please try again later.';
}

?>