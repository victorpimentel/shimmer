<html>
<head>
	<title>Shimmer: Graph Info</title>
	<style>
		h3 {
			margin:0 0 0.2em 0;
			padding:0;
			color:#0000CC;
			clear:both;
		}
		
		h5 {
			margin:0 0 0.2em 0;
			padding:0;
			color:#CC0000;
		}
		
		p {
			margin:0 0 1em 0;
			padding:0;
		}
		
		table, table th, table td {
			border:1px solid #777;
		}
		
		table {
			margin-bottom:1em;
		}
		
		table.float {
			float:left;
			margin-right:1em;
		}
		
		table th, table td {
			padding:3px 5px;
		}
		
		table th {
			background:#DDD;
		}
		
		table tr:hover {
			background:#FFFF99;
		}
		
		table td:first-child {
			background:#EEE;
		}
		
		img.sparkle-icon {
			padding-left:10px;
		}
	</style>
</head>
<body>
<?php
if (!defined('Shimmer')) header('Location:/');
// This file is used to show settings info to help fix bugs

$statKeys = $Shimmer->pref->read("parameters",true); // Params
$statFields = $Shimmer->pref->read("graphs",true); // Graphs
$boxPairs = $Shimmer->pref->read("boxes",true); // Boxes
?>
<p>This page can make solving bugs much easier. You can also see if your appcast is being accessed properly, by looking at the &apos;Most Recent Activity&apos; column below.</p>

<h3>Your Apps</h3>
<table cellpadding=0 cellspacing=0>
	<tr>
		<th>App Name</th>
		<th>Most Recent Activity</th>
		<th>Oldest User</th>
		<th>Newest User</th>
		<th>Average time since activity</th>
	</tr>
<?php
$now = date('Y-m-d', time());
foreach ($Shimmer->apps->list as $app) {
	echo "<tr><td>" . $app['name'] . ($app['usesSparkle'] ? '<img src="img/sparkle.png" class="sparkle-icon" title="App uses Sparkle" />' : '') .  "</td>";
	$lastSeenResult = $Shimmer->query("SELECT TO_DAYS('$now')-TO_DAYS(MAX(last_seen)) as 'difference', AVG(TO_DAYS('$now')-TO_DAYS(last_seen)) as 'avg', TO_DAYS('$now')-TO_DAYS(MAX(first_seen)) as 'newest', TO_DAYS('$now')-TO_DAYS(MIN(first_seen)) as 'oldest' FROM `" . sql_safe($app['users_table']) . "` ORDER BY 'difference' ASC");
	if ($lastSeenResult) {
		$row = mysql_fetch_array($lastSeenResult);
		echo "<td>";
		if (strlen($row['difference'])>0) {
			if ($row['difference']>"0") {
				echo $row['difference'] . " days ago";
			} else {
				echo "Today";
			}
		} else echo "Never";
		echo "</td>";
		
		echo "<td>";
		echo $row['oldest'] . " days";
		echo "</td>";
		
		echo "<td>";
		if (strlen($row['newest'])>0) {
			if ($row['newest']>"0") {
				echo $row['newest'] . " days ago";
			} else {
				echo "Today";
			}
		} else echo "Never";
		echo "</td>";
		
		echo "<td>";
		if (strlen($row['avg'])>0) {
			echo $row['avg'] . " days";
		} else echo "Never";
		echo "</td></tr>";
	}
}
?>
</table>

<h3>Box Selections</h3>
<?php
foreach ($Shimmer->apps->list as $app) {
	echo '<table cellpadding=0 cellspacing=0 class="float"><tr><th colspan="2">' . $app['name'] . ($app['usesSparkle'] ? '<img src="img/sparkle.png" class="sparkle-icon" title="App uses Sparkle" />' : '') . '</th></tr>';
	$boxes = $Shimmer->apps->boxesForApp($app);
	foreach ($boxes as $location => $graphID) {
		if (!$graphID) $graphID = "Not Set";
		echo "<tr><td>$location</td><td>$graphID</td></tr>";
	}
	echo "</table>";
}
?>

<h3>Custom Graphs</h3>
<?php
foreach ($Shimmer->apps->list as $app) {
	echo '<table cellpadding=0 cellspacing=0 class="float"><tr><th colspan="2">' . $app['name'] . ($app['usesSparkle'] ? '<img src="img/sparkle.png" class="sparkle-icon" title="Includes default Sparkle Graphs" />' : '') . '</th></tr>';
	$graphs = $Shimmer->stats->graphsForApp($app, false);
	if (sizeof($graphs)>0) {
		foreach ($graphs as $i => $graph) {
			echo "<tr><td>" . $graph['name'] . "</td><td>" . $graph['id'] . "</td></tr>";
		}
	} else {
		echo "<tr><td>No</td><td>Graphs</td></tr>";
	}
	echo "</table>";
}
?>

</body>
</html>