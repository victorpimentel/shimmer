<?php
if (!defined('Shimmer')) header('Location:/');
$exportFormat = $_GET['format'];
$wantDownload = isset( $_GET['download'] );

if ( $exportFormat=="xml" ) {
	
	$exp = simplexml_load_string("<shimmer></shimmer>");
	
	// Add attributes to the main <shimmer> element
	$exp->addAttribute("version", $Shimmer->version);
	$exp->addAttribute("generated", time());
	
	$apps = $Shimmer->apps->list;
	if ($apps) {
		foreach ($apps as $app) {
			$expApp = $exp->addChild("app");
			
			// Add attributes to the current <app> element
			$expApp->addAttribute("name",           strval($app['name']));
			$expApp->addAttribute("variant",        strval($app['variant']));
			$expApp->addAttribute("id",             strval($app['id']));
			$expApp->addAttribute("usesSparkle",    (strval($app['usesSparkle']))!="1" ? "0" : "1");
			$expApp->addAttribute("identifier",     strval($app['identifier']));
			$expApp->addAttribute("incrementType",  strtolower($app['incrementType']));
			
			// Add custom Parameter definitions to the current <app> element
			$expAppParams = $expApp->addChild("parameters");
			$params = $Shimmer->stats->parametersForApp($app);
			$node = dom_import_simplexml($expAppParams);
			$no = $node->ownerDocument;
			$node->appendChild($no->createCDATASection(serialize($params)));
			
			// Add custom Graph definitions to the current <app> element
			$expAppGraphs = $expApp->addChild("graphs");
			$graphs = $Shimmer->stats->graphsForApp($app);
			$node = dom_import_simplexml($expAppGraphs);
			$no = $node->ownerDocument;
			$node->appendChild($no->createCDATASection(serialize($graphs)));

			// Add versions to the current <app> element
			$expAppVersions = $expApp->addChild("versions");
			
			$versions = $Shimmer->versions->versions($app);
			if ($versions) {
				foreach ($versions as $version) {
					$ver = $expAppVersions->addChild("version");
					
					$node = dom_import_simplexml($ver);
					$no = $node->ownerDocument;
					$node->appendChild($no->createCDATASection(mysql_real_escape_string(strval($version['notes']))));
					
					$publishDate = (int)$version['published'];
					$modifyDate = (int)$version['modified'];
					if ($modifyDate<$publishDate) $modifyDate=$publishDate;
					
					$ver->addAttribute("number",strval($version['version']));
					$ver->addAttribute("build",strval($version['build']));
					$ver->addAttribute("published",$publishDate);
					$ver->addAttribute("modified",$modifyDate);
					$ver->addAttribute("live",(int)$version['live']);
					$ver->addAttribute("url",strval($version['download']));
					$ver->addAttribute("size",(int)$version['size']);
					$ver->addAttribute("signature",strval($version['signature']));
					$ver->addAttribute("downloads",(int)$version['download_count']);
					
					// Add the download and user rates as version attributes
					for ($rateType=0; $rateType < 2; $rateType++) { 
						$rateSql = ($rateType==0 ? 'download_rate' : 'user_rate');
						$rateAtt = ($rateType==0 ? 'downloadRates' : 'userRates');
						$originalData = $version[$rateSql];
						if ($originalData) {
							$rates   = unserialize($originalData);
							$rateString = "";
							foreach ($rates as $theIndex => $ratePair) {
								$rateString .= $ratePair['date'] = $ratePair['count'];
								if ($theIndex<count($rates)-1) $rateString .= ',';
							}
							$ver->addAttribute($rateAtt,$rateString);
						}
					}
				}
			}
			
			// Statistics
			$expAppStats = $expApp->addChild("statistics");
			$statFields = array();
			$listColsSql = "SHOW COLUMNS FROM `" . sql_safe($app['users_table']) . "`";
			$listColsResult = $Shimmer->query($listColsSql);
			if ($listColsResult) {
				while ($row = mysql_fetch_array($listColsResult)) {
					$fieldName = $row['Field'];
					if (strlen($fieldName)>0) array_push($statFields,$fieldName);
				}
			}

			if ( count($statFields)>0 ) {
				$Shimmer->stats->deleteOldUsers();
				$statSql = "SELECT * FROM `" . sql_safe($app['users_table']) . "`";
				$statRows = $Shimmer->query($statSql);
				if ($statRows) {
					while ($statRow = mysql_fetch_array($statRows)) {
						$stat = $expAppStats->addChild("stat");
						foreach ($statFields as $key => $statField) $stat->addAttribute(strtolower($statField),strval($statRow[$statField]));
					}
				}
			}
		}
	}
	
	if ($wantDownload && $Shimmer->tempFolder() ) {
		$exportFilename = time() . ".xml";
		$exportPath = $Shimmer->tempFolder . $exportFilename;
		$exp->asXML($exportPath);
		if ( file_exists($exportPath) ) {
			header("Content-Type: application/shimmer-download");
			header("Content-Disposition: attachment; filename=ShimmerExport." . date('Ymd') . ".xml" );
			header("Content-Length: " . filesize($exportPath));
			ob_clean();
			flush();
			readfile($exportPath);
			unlink($exportPath);
			exit;
		}
	} else echo $exp->asXML();
} else { // default is json,
	echo "Please supply a format parameter, such as &quot;format=xml&quot;.";
}

?>