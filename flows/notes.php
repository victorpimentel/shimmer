<?php
if (!defined('Shimmer')) header('Location:/');
if (isset($_GET['loudandclear'])) {
	echo 'LOUDANDCLEAR';
	exit();
}

// Check if a cache exists first, to save a few DB calls
include_once('cachemanager.php');
if (CacheManager::printCacheForCurrentHash()) {
	echo "<!-- pulled from cache -->";
	exit();
}

// If we get to here, there is no cache
$appName    = $_GET['appName'];
$appVariant = $_GET['appVariant'];
if (!isset($appVariant)) $appVariant = "";
$targetVersion = $_GET['appVersion'];
$minVersion    = $_GET['minVersion'];
if (!isset($templateName)) $templateName = "default";

if ( isset($appName) ) {
	$app = $Shimmer->apps->appFromNameAndVariant($appName, $appVariant);
	if ($app) {
		$whereConditions = array("onlyLive"=>true);
		$versionsRestricted = false;
		if ( isset($targetVersion) ) {
			$targetVersionInfo = $Shimmer->versions->version($app,array('increment'=>$targetVersion));
			if ($targetVersionInfo) {
				$targetTimestamp = $targetVersionInfo['published'];
				if ( isset($minVersion) ) {
					$minVersionInfo = $Shimmer->versions->version($app,array('increment'=>$minVersion));
					if ($minVersionInfo) {
						$minTimestamp = $minVersionInfo['published'];
						if ( isset($targetTimestamp) && isset($minTimestamp) ) {
							if ($minTimestamp < $targetTimestamp) {
								$versionsRestricted = true;
								$whereConditions['minTimestamp'] = $minTimestamp;
								$whereConditions['maxTimestamp'] = $targetTimestamp;
							}
						}
					}
				}
				
				if (!$versionsRestricted && isset($targetTimestamp)) $whereConditions['timestamp'] = $targetTimestamp;
			}
		}
		
		$themeXML = @file_get_contents($Shimmer->base . '/templates/' . $app['notesTheme']);
		if ($themeXML===false) $themeXML = @file_get_contents($Shimmer->base . '/templates/default.shimmer.xml');

		if ( $themeXML !== false ) {
			$themeTree = @simplexml_load_string($themeXML);
			if ($themeTree && isTheme($themeTree) ) {
				
				$versions = $Shimmer->versions->versions($app, $whereConditions);
				
				if ($versions && count($versions)>0) {

					header('Content-type: text/html');
					
					// Start output buffering, so we can cache the response we are about to create
					ob_start();

					$headerTemplate = $themeTree->layout->header;
					$headerTemplate = str_replace("<<APP_NAME>>",$appName,$headerTemplate);
					echo trim($headerTemplate);
					
					foreach ($versions as $version) {
						$versionTemplate = $themeTree->layout->release;
						$versionTemplate = str_replace(	"<<APP_NAME>>",			$appName,								$versionTemplate);
						$versionTemplate = str_replace(	"<<VERSION_NUMBER>>",	$version['version'],					$versionTemplate);
						$versionTemplate = str_replace(	"<<DOWNLOAD_SIZE>>",	$version['bytes'],						$versionTemplate);
						$versionTemplate = str_replace(	"<<NOTES>>",			$version['notes'],						$versionTemplate);
						$versionTemplate = str_replace(	"<<RELEASE_DATE>>",		date("F j, Y",$version['published']),	$versionTemplate);
						$versionTemplate = str_replace(	"<<DOWNLOAD_URL>>",		strval($version['download']),			$versionTemplate);
						if (isset($minVersion)) $versionTemplate = str_replace("<<CURRENT_USER_VERSION>>", $minVersion, $versionTemplate);
						
						if (isset($version['build']) && strlen($version['build'])>0) {
							$versionTemplate = str_replace("<<BUILD_NUMBER>>", $version['build'], $versionTemplate);
						} else {
							$versionTemplate = str_replace("<<BUILD_NUMBER>>", '1', $versionTemplate);
						}

						echo trim($versionTemplate);
					}

					$footerTemplate = $themeTree->layout->footer;
					$footerTemplate = str_replace("<<APP_NAME>>",$appName,$footerTemplate);
					$footerTemplate = str_replace('\t',"\t",$footerTemplate);
					echo "\n" . trim($footerTemplate);
					
					// Print, disable and cache the buffer
					$buffer = ob_get_contents();
					ob_end_clean();
					echo $buffer;
					CacheManager::storeCacheForCurrentHash($buffer);
					

				} else if ( $versions && sizeof($versions)==0 ) {
					$nodataTemplate = $themeTree->layout->nodata;
					echo $nodataTemplate;
				}
			} else echo "The supplied template file is not valid";

		} else echo "The supplied template could not be found";

	}// else echo 'The app name you have supplied does not exist';
}// else echo 'Please supply an app name using the \'app\' parameter';

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

?>