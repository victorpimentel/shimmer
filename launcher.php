<?php
if (!defined('Shimmer')) header('Location:/');

include('safesql.php');
include('shimmer.php');
@include("db_details.php");

// Work out if the DB is working, and if the user exists

$dbWorked = false;
$userSet = false;
$dbConfigSet = isset($shimmer_host) && isset($shimmer_user) && isset($shimmer_pass) && isset($shimmer_db);

if ($dbConfigSet) {
	if (mysql_connect($shimmer_host,$shimmer_user,$shimmer_pass) && mysql_select_db($shimmer_db)) {
		$dbWorked = true;
		$emailResult	= mysql_query("SELECT * FROM `settings` WHERE `id`='email'");
		$passResult		= mysql_query("SELECT * FROM `settings` WHERE `id`='pass'");
		if ($emailResult && $passResult) {
			$emailRow	= mysql_fetch_array($emailResult);
			$passRow	= mysql_fetch_array($passResult);
			if ($emailRow && $passRow) $userSet = true;
		}
	}
}

// Do we need to Install? If DB is not working, or no user exists, then redirect appropriately
$isHello   = isset($_GET['hello']);
$isAccount = isset($_GET['account']);

if (!$isHello && !$dbConfigSet && !$dbWorked) {
	header('Location: ./?hello');
	exit();
} else if (!$isAccount && $dbWorked && !$userSet) {
	header('Location: ./?account');
	exit();
} else if ( $dbConfigSet && $dbWorked && $userSet && ($isHello||$isAccount) ) {
	header('Location: ./');
	exit();
}

// If we get to this point, then the DB is working and the user exists (or the tests have been skipped)

if (!$dbConfigSet && !$dbWorked && $isHello) {
	include('flows/setup_db.php');
} else {
	$shimmerParamArray = array('server'=>$shimmer_host, 'username'=>$shimmer_user, 'password'=>$shimmer_pass, 'database'=>$shimmer_db);
	$Shimmer = new Shimmer($shimmerParamArray);
	$Shimmer->setup();
	if ($Shimmer->database['connected']) {
		if (!$userSet && isset($_GET['account'])) {
			include('flows/setup_account.php');
			exit();
		}
		$publicMethods = array('appcast', 'notes', 'generate', 'api', 'export', 'download', 'autoprocess', 'devtest', 'resend', 'reset');
		$publicMethodUsed = false;
		foreach ($publicMethods as $method) {
			if (isset($_GET[$method])) {
				$publicMethodUsed = true;
				break;
			}
		}
		
		if (isset($_GET['debug']) && $Shimmer->auth->authenticated) {
			$starttime = explode(' ', microtime());  
			$starttime =  $starttime[1] + $starttime[0];
		}
		
		if ($Shimmer->auth->authenticated && (isset($_GET['login']) || isset($_GET['resend']))) {
			header('Location: ./');
			return;
		}
		
		if ($Shimmer->auth->authenticated || $publicMethodUsed) {
			include('flow.php');
		} else if (isset($_GET['ajax'])){
			// If the user supplies an incorrect auth cookie, add a lockout entry
			$Shimmer->auth->addAttemptIfCookieSupplied();
			if ($Shimmer->auth->loginAttemptsExceeded()) {
				echo json_encode( array('wasOK'=>false, 'reason'=>'Too many unauthorized attempts. Try again in 5 minutes.') );
			} else {
				echo json_encode( array('wasOK'=>false, 'reason'=>'Not authorized') );
			}
		} else {
			include('flows/login.php');
		}
		
		if (isset($_GET['debug']) && $Shimmer->auth->authenticated) {
			$mtime = explode(' ', microtime());  
			$totaltime = $mtime[0] +  $mtime[1] - $starttime;  
			printf('<div style="font-size:12px;padding-top:1em;">loaded in %.3f seconds</div>',  $totaltime);
		}
		
		$Shimmer->dumpQueries();

		// Optimize tables once a day
		$currentTime = time();
		$lastOptimized = $Shimmer->pref->read('lastOptimized');
		if ($lastOptimized==false) $lastOptimized = $currentTime - 86400;
		if ($currentTime - $lastOptimized >= 86400) {
			$Shimmer->table->optimizeTables();
			$Shimmer->pref->save('lastOptimized', $currentTime);
		}
	} else if (isset($_GET['ajax'])) {
		echo json_encode( array('wasOK'=>false, 'reason'=>'Could not connect to database') );
	} else {
		include('flows/dberror.php');
	}	
}
?>