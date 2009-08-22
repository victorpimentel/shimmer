<?php
if (!defined('Shimmer')) header('Location:/');
function sql_safe($value,$allow_wildcards = true) {
	// Taken from the PHP site and modified for wildcards.
	$return_value = $value;

	// Reverse magic_quotes_gpc/magic_quotes_sybase effects on those vars if ON.
	if (get_magic_quotes_gpc()) {
		if (ini_get('magic_quotes_sybase')) {
			$return_value = str_replace("''", "'", $return_value);      
		} else {
			$return_value = stripslashes($return_value);
		}
	}
  
	//Escape wildcards for SQL injection protection on LIKE, GRANT, and REVOKE commands.
	if (!$allow_wildcards) {
		$return_value = str_replace('%','\%',$return_value);
		$return_value = str_replace('_','\_',$return_value);
	}
  
	return mysql_real_escape_string($return_value);
}
?>