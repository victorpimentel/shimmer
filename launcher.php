<?php
slog("\n------------------------ Launching Shimmer at " . date(DATE_RSS) . ' for ' . $_SERVER['REQUEST_URI']);
if (!defined('Shimmer')) header('Location:/');
slog('passed initial flow test');

include('safesql.php');
include('shimmer.php');
@include("db_details.php");

// Work out if the DB is working, and if the user exists
slog('initialising setup status booleans');
$dbWorked = false;
$userSet = false;
$dbConfigSet = isset($shimmer_host) && isset($shimmer_user) && isset($shimmer_pass) && isset($shimmer_db);

slog('assigning setup status booleans');
if ($dbConfigSet) {
	slog('dbConfigSet is true, so attempting to connect');
	if (mysql_connect($shimmer_host,$shimmer_user,$shimmer_pass) && mysql_select_db($shimmer_db)) {
		slog('connected successfully, dbWorked = true', 1);
		$dbWorked = true;
		slog('checking if email and password are set');
		$emailResult	= mysql_query("SELECT * FROM `settings` WHERE `id`='email'");
		$passResult		= mysql_query("SELECT * FROM `settings` WHERE `id`='pass'");
		if ($emailResult && $passResult) {
			slog('email and pass are set. userSet = true', 1);
			$emailRow	= mysql_fetch_array($emailResult);
			$passRow	= mysql_fetch_array($passResult);
			if ($emailRow && $passRow) $userSet = true;
		} else slog('email and pass are not set. userSet = false', 1);
	} else slog('connect failed. dbWorked = false', 1);
}

slog('MySQL error check #1 (if any): ' . mysql_error());

slog('initialising setup redirect booleans');
// Do we need to Install? If DB is not working, or no user exists, then redirect appropriately
$isHello   = isset($_GET['hello']);
$isAccount = isset($_GET['account']);

slog('assigning setup status booleans');
if (!$isHello && !$dbConfigSet && !$dbWorked) {
	slog('redirecting to ./?hello', 1);
	header('Location: ./?hello');
	exit();
} else if (!$isAccount && $dbWorked && !$userSet) {
	slog('redirecting to ./?account', 1);
	header('Location: ./?account');
	exit();
} else if ( $dbConfigSet && $dbWorked && $userSet && ($isHello||$isAccount) ) {
	slog('everything is set. redirecting to dashboard', 1);
	header('Location: ./');
	exit();
} else slog('no redirect needed. continuing execution', 1);

// If we get to this point, then the DB is working and the user exists (or the tests have been skipped)

if (!$dbConfigSet && !$dbWorked && $isHello) {
	slog('displaying DB setup page');
	include('flows/setup_db.php');
} else {
	slog('attempting to connect to database...');
	$shimmerParamArray = array('server'=>$shimmer_host, 'username'=>$shimmer_user, 'password'=>$shimmer_pass, 'database'=>$shimmer_db);
	$Shimmer = new Shimmer($shimmerParamArray);
	$Shimmer->setup();
	slog('MySQL error check #2 (if any): ' . mysql_error());
	if ($Shimmer->database['connected']) {
		slog('connected to db', 1);
		slog('checking if account is already set up');
		if (!$userSet && isset($_GET['account'])) {
			slog('need to setup account. redirecting.', 1);
			include('flows/setup_account.php');
			exit();
		} else slog('account is set. continuing execution', 1);
		
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
		slog('MySQL error check #3 (if any): ' . mysql_error());
		slog('could not connect to database. redirecting to DB error page.', 1);
		include('flows/dberror.php');
	}	
}

function slog($message, $nesting=0) {
	if (defined('shimmer_log') && shimmer_log==true) {
		$out = '';
		for ($i=0; $i < $nesting; $i++) $out .= '  > ';
		$out .= $message;
		file_put_contents('shimmer_log.php', $out . "\n", FILE_APPEND);
	}
}

?>