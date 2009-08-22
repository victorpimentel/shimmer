<?php
if (!defined('Shimmer')) header('Location:/');

////////  DEV TEST ////////////////////////////////////////////////////////////
///////  This file is used to run custom commands during development       ////
///////  and testing. Any code should be cleared or commented after use.   ////
///////////////////////////////////////////////////////////////////////////////

// $result = $Shimmer->query("SELECT COUNT(*) AS 'user_count' FROM `users_scrobblepod` WHERE `last_seen`>='2009-07-10'");
// $row = mysql_fetch_array($result);
// echo $row['user_count'];

// $dsaParams = shell_exec('openssl dsaparam 2048 < /dev/urandom');
// writeToTempFile('dsaparam.pem', $dsaParams);
// 
// // Generate Private key
// $privateKey = shell_exec('openssl gendsa tmp/dsaparam.pem');
// writeToTempFile('dsa_priv.pem', $privateKey);
// 
// // Generate Public Key
// $publicKey = shell_exec('openssl dsa -in tmp/dsa_priv.pem -pubout');
// 
// echo "Public Key<br /><pre>$publicKey</pre>";
// echo "<br />Private Key<br /><pre>$privateKey</pre>";
// 

?>