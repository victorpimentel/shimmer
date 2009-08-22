<?php
if (!defined('Shimmer')) header('Location:/');
if ($_SERVER['HTTP_HOST'] != "localhost") $domain = str_replace("www.",".",$_SERVER['HTTP_HOST']);	
setcookie("shimmer_session","", time()-60, "/", $domain);
header('Location: ./');
?>