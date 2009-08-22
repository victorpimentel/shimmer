<?php
function prepareJsonStringForDecoding($jsonString) {
	$jsonString = (get_magic_quotes_gpc() ? stripslashes($jsonString) : $jsonString);
	$jsonString = preg_replace('/^"|"$/',"",$jsonString);
	return (get_magic_quotes_gpc() ? stripslashes($jsonString) : $jsonString);
}
?>