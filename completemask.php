<?php
function completeMask($mask, $app, $version) {
	$link = str_replace('_APP_',	$app['name'],			$mask);
	$link = str_replace('_VER_',	$version['version'],	$link);
	$link = str_replace('_BUILD_',	$version['build'],		$link);
	$link = htmlentities($link);
	$link = str_replace(' ', '%20',	$link);
	return $link;
	// htmlspecialchars
}
?>