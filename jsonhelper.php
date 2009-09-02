<?php
function prepareJsonStringForDecoding($jsonString) {
	$jsonString = preg_replace('/^"|"$/',"",stripslashes($jsonString));
	return (get_magic_quotes_gpc() ? stripslashes($jsonString) : $jsonString);
}
?>