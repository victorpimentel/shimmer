<?php
if (!defined('Shimmer')) header('Location:/');
if (isset($_GET['loudandclear'])) {
	echo 'LOUDANDCLEAR';
	exit();
}
$appName	= $_GET['appName'];
$appVariant = $_GET['appVariant'];
if (!isset($appVariant)) $appVariant = "";
$version	= $_GET['appVersion'];

$paramCount = 1;
if ( isset($version) && isset($appName) ) {
	$paramCount += 2;
	$app = $Shimmer->apps->appFromNameAndVariant($appName, $appVariant);
	if ($app) {
		$versionSearch = array('onlyLive'=>true, 'increment'=>$version);
		$theVersion = $Shimmer->versions->version($app, $versionSearch);
		if ($theVersion) {
			$downloadURL = $theVersion['download'];
			// If there are extra parameters supplied, append them to the download URL
			if (sizeof($_GET)-$paramCount>0) {
				$extraParameters = array();
				foreach($_GET as $variable => $value) {
					if ($variable!='download' && $variable!='appName' && $variable!='appVariant' && $variable!='appVersion') {
						array_push($extraParameters, $variable . '=' .  ($value ? $value : ''));
					}
				}
				$downloadURL = $Shimmer->appendParameterToURL($downloadURL, implode('&',$extraParameters));
			}
			header('Location: ' . $downloadURL);
			$newCount = intval($theVersion['download_count']) + 1;
			$updateSql = "UPDATE `" . sql_safe($app['versions_table']) . "` SET `download_count`=" . sql_safe($newCount) . " WHERE `" . $app['incrementType'] . "`='" . sql_safe($version) . "' LIMIT 1";
			$Shimmer->query($updateSql);
		} else echo 'Download link could not be found, because the version does not exist.';
		$Shimmer->rates->processVersionRates($app['id']);
	} else echo 'Download link could not be found, because the application table does not exist.<br>Please try again later.';
}

?>