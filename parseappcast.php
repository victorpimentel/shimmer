<?php
if (!defined('Shimmer')) header('Location:/');

function parse_appcast($appcastXml) {
	$sparkleURI = "http://www.andymatuschak.org/xml-namespaces/sparkle";
	$xmlobj = @simplexml_load_string($appcastXml);
	if ($xmlobj) {
		$items = $xmlobj-> channel-> item;
		$versions = array();
		foreach( $items as $item ){
			$thisVersion = array();

			$item_sparkle      = $item-> children($sparkleURI);

			$releasenotes_link = $item_sparkle -> releaseNotesLink;
			$minSysVersion     = $item_sparkle -> minimumSystemVersion;
			$releasenotes      = $item         -> description;
			$pubDate           = $item         -> pubDate;

			$enclosure         = $item->enclosure;
			$enclosure_sparkle = $enclosure-> attributes($sparkleURI);
			$buildNumber       = '';

				$version     = $enclosure_sparkle['shortVersionString'];
				if ($version) {
					$buildNumber = $enclosure_sparkle['version'];
				} else {
					$version = $enclosure_sparkle['version'];
				}

				$signature   = strval($enclosure_sparkle['dsaSignature']);
				$filesize    = $enclosure['length'];
				$filekind    = $enclosure['type'];
				$downloadurl = strval($enclosure['url']);

			$thisVersion['url']       = $downloadurl;
			$thisVersion['signature'] = $signature;
			$thisVersion['size']      = $filesize;
			$thisVersion['kind']      = $filekind;
			$thisVersion['date']      = strtotime($pubDate);
			$thisVersion['minos']     = $minSysVersion;
			$thisVersion['notes']     = $releasenotes;
			$thisVersion['noteslink'] = $releasenotes_link;
			$thisVersion['build']     = $buildNumber;

			$versions[''.$version] = $thisVersion;

		}
		return $versions;
	}
	return array();
}

?>