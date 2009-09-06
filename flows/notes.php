<?php
if (!defined('Shimmer')) header('Location:/');
if (isset($_GET['loudandclear'])) {
	echo 'LOUDANDCLEAR';
	exit();
}
$appName		= $_GET['appName'];
$appVariant = $_GET['appVariant'];
if (!isset($appVariant)) $appVariant = "";
$versionLimit	= $_GET['limit'];
$targetVersion	= $_GET['version'];
$targetBuild	= $_GET['build'];
$minVersion		= $_GET['appVersion'];
if (!isset($templateName)) $templateName = "default";

if ( isset($appName) ) {
	$app = $Shimmer->apps->appFromNameAndVariant($appName, $appVariant);
	if ($app) {
		$whereConditions = array("onlyLive"=>true);
		$versionsRestricted = false;

		if ( isset($targetVersion) ) {
			$targetParameters = array( 'version' => $targetVersion );
			if (isset($targetBuild)) $targetParameters['build'] = $targetBuild;
			
			$targetVersionInfo = $Shimmer->versions->version($app,$targetParameters);
			if ($targetVersionInfo) {
				$targetTimestamp = $targetVersionInfo['published'];
				if ( isset($minVersion) ) {
					$minVersionInfo = $Shimmer->versions->version($app,array('version'=>$minVersion));
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