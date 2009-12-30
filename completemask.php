<?php
function completeMask($mask, $app, $version, $escape=true) {
	$link = str_replace('_APP_',     $app['name'],                                                               $mask);
	$link = str_replace('_VARIANT_', $app['variant'],                                                            $link);
	$link = str_replace('_RELEASE_', ($app['incrementType']=='build' ? $version['build'] : $version['version']), $link);
	if ($escape) $link = htmlentities($link);
	$link = str_replace(' ', '%20',	$link);
	return $link;
	// htmlspecialchars
}
?>