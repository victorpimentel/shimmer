<?php
if (!defined('Shimmer')) header('Location:/');

class StatManager {
	var $Shimmer;
	var $graphs;
	// var $boxes;
	var $defaultGraphChoices = array();
	var $paramCache = array();
	var $graphCache = array();
	
	function StatManager() {
		global $Shimmer;
		$this->Shimmer =& $Shimmer;
		
		$graphsValue = $this->Shimmer->pref->read("graphs",true);
		$this->graphs = $graphsValue ? $graphsValue : array();
	}
	
	// Used to modify the stored Parameters and Graphs for the supplied app.
	// Adds, renames, and removes when necessary.
	function setParametersAndGraphsForApp($app, $parameterSets, $graphSets) {
		$this->setParametersForApp($app, $parameterSets);
		$this->setGraphsForApp($app, $graphSets);
		return true;
	}
	
	//////////////////////////////////////////////////////////////////////////////////
	// SPARKLE-FRIENDLY PARAMETER FUNCTIONS                                         //
	// Only custom Parameters are stored, Sparkle is added to list when retrieving. //
	//////////////////////////////////////////////////////////////////////////////////
	
	function parametersForApp($app, $includeSparkle=false) {
		$appID = $app['id'];
		if (!isset($this->paramCache[$appID])) {
			$didRetrieve = false;
			$sql = "SELECT `params` FROM `applications` WHERE `id`='" . sql_safe($appID) . "' LIMIT 1";
			if ($row = $this->Shimmer->querySelect($sql, true)) {
				$paramString = $row['params'];
				$unserializedList = unserialize($paramString);
				if ($unserializedList!=false) {
					$didRetrieve = true;
					$this->paramCache[$appID] = $unserializedList;
				}
			}
			if (!$didRetrieve) $this->paramCache[$appID] = array();
		}
		$returnArray = $this->paramCache[$appID];
		if ($includeSparkle && $app['usesSparkle']) {
			$sparkleParams = $this->sparkleParameterDefinitions();
			foreach ($sparkleParams as $sparkleParam) $returnArray[] = $sparkleParam;
		}
		return $returnArray;
	}
	
	function setParametersForApp($app, $parameterSets) {
		// Get existing Parameters, excluding default Sparkle Parameters
		$existingParameters = $this->parametersForApp($app);

		// A list of Parameter names which users are not allowed to enter. Reserved + Sparkle.
		$reservedParameters = array('ip', 'last_version', 'last_build', 'first_version', 'first_build', 'last_seen', 'first_seen');
		foreach ($this->sparkleParameterDefinitions() as $sparkleParamName) $reservedParameters[] = $sparkleParamName;
		
		$newParams			= array(); // flat list of Param names
		$renamedParams		= array(); // oldName, name
		$deletedParams		= array(); // flat list of Param names
		$unalteredParams	= array(); // flat list of Param names

		foreach ($parameterSets as $key => $parameterSet) {
			// If the new name isn't already a Parameter
			if (!in_array($parameterSet['name'], $existingParameters) && !in_array($parameterSet['name'], $reservedParameters)) {
				$addedRenamePair = false;
				// If an old Parameter name was supplied (ie. we want to rename)
				if (isset($parameterSet['oldName'])) {
					// If the old Parameter name actually exists
					if (in_array($parameterSet['oldName'], $existingParameters)) {
						$addedRenamePair = true;
						array_push($renamedParams, array(
							'name'		=> $parameterSet['name'],
							'oldName'	=> $parameterSet['oldName']
						));
					}
				}
			
				// If we didn't want to rename the Parameter, then add it
				if (!$addedRenamePair) {
					if (!in_array($parameterSet['name'], $existingParameters)) {
						array_push($newParams, $parameterSet['name']);
					}
				}
			} else {
				array_push($unalteredParams, $parameterSet['name']);
			}
		}
		
		// If any of the existing Params have not been altered, then delete them
		$oldParamsToBeRenamed = array();
		foreach ($renamedParams as $key => $param) {
			array_push($oldParamsToBeRenamed, $param['oldName']);
		}		
		
		foreach ($existingParameters as $i => $param) {
			if (!in_array($param,$oldParamsToBeRenamed) && !in_array($param,$newParams) && !in_array($param,$unalteredParams)) {
				array_push($deletedParams, $param);
			}
		}
		
		// Add any new Parameters. Not the same as renamed Parameters.
		$this->addParametersForApp($app, $newParams);
		
		// Rename any edited Parameters
		$this->renameParametersForApp($app, $renamedParams);
		
		// Delete any removed Parameters
		$this->deleteParametersForApp($app, $deletedParams);
	}
	
	// Adds a set of Parameters for a specific app.
	// Updates 'params' value in 'applications' table.
	// Creates multiple columns in the 'users_*' table.
	function addParametersForApp($app, $parameterSets) {
		if (sizeof($parameterSets)==0) return true;
		if ($app) {
			// Get existing Parameters, excluding default Sparkle Parameters
			$existingParameters = $this->parametersForApp($app);
			foreach ($parameterSets as $i => $paramSet) {
				if (in_array($paramSet,$existingParameters)) {
					unset($parameterSets[$i]);
				} else {
					array_push($existingParameters, $paramSet);
				}
			}
			
			// Because we unset a value in $parameterSets, we should re-index it to ensure it is a 'flat' array
			$parameterSets = array_values($parameterSets);
			
			if ( $this->saveParametersArrayForApp($app, $existingParameters) ) {
				// Create the new columns in the 'users_*' table
				$sql = "ALTER TABLE `" . sql_safe($app['users_table']) . "`";
				foreach ($parameterSets as $i => $paramSet) {
					$sql .= " ADD COLUMN `" . sql_safe($paramSet) . "` VARCHAR(128) NOT NULL";
					if ($i+1<sizeof($parameterSets)) $sql .= ",";
				}
				$result = $this->Shimmer->query($sql);
				if ($result) return true;
			}
		}
		return false;
	}
	
	function renameParametersForApp($app, $parameterSets) {
		if (sizeof($parameterSets)==0) return true;
		if ($app) {
			// Get existing Parameters, excluding default Sparkle Parameters
			$existingParameters = $this->parametersForApp($app);
			foreach ($parameterSets as $i => $paramSet) {
				foreach ($existingParameters as $e => $existingParameter) {
					if ($existingParameter==$paramSet['oldName']) {
						$existingParameters[$e] = $paramSet['name'];
					}
				}
			}
			
			if ( $this->saveParametersArrayForApp($app, $existingParameters) ) {
				// Create the new columns in the 'users_*' table
				$sql = "ALTER TABLE `" . sql_safe($app['users_table']) . "`";
				foreach ($parameterSets as $i => $paramSet) {
					$sql .= " CHANGE `" . sql_safe($paramSet['oldName']) . "` `" . sql_safe($paramSet['name']) . "` VARCHAR(128) NOT NULL";
					if ($i+1<sizeof($parameterSets)) $sql .= ",";
				}
				$result = $this->Shimmer->query($sql);
				if ($result) return true;
			}
		}
		return false;
	}
	
	function deleteParametersForApp($app, $parameterSets, $dropPhysicalColumns=true) {
		if (sizeof($parameterSets)==0) return true;
		if ($app) {
			// Get existing Parameters, excluding default Sparkle Parameters
			$existingParameters = $this->parametersForApp($app);
			foreach ($parameterSets as $i => $param) {
				foreach ($existingParameters as $e => $existingParameter) {
					if ($existingParameter==$param) {
						unset($existingParameters[$e]);
						break;
					}
				}
			}
			
			// Re-index the Param list
			$existingParameters = array_values($existingParameters);
						
			if ( $this->saveParametersArrayForApp($app, $existingParameters) ) {
				if ($dropPhysicalColumns) {
					$sql = "ALTER TABLE `" . sql_safe($app['users_table']) . "`";
					foreach ($parameterSets as $i => $param) {
						$sql .= " DROP COLUMN `" . sql_safe($param) . "`";
						if ($i+1<sizeof($parameterSets)) $sql .= ",";
					}
					$this->Shimmer->query($sql);
					return true;
				} else return true;
			}
		}
		return false;
	}
	
	function parameterExists($app,$key) {
		$testColSql = "SHOW COLUMNS FROM `" . sql_safe($app['users_table']) . "` WHERE `field` = '" . sql_safe($key) . "'";
		$result = $this->Shimmer->query($testColSql);
		if ($result) {
		 	if (mysql_num_rows($result)>0) return true;
		}
		return false;
	}
	
	// Check is the supplied Parameter list is valid
	function paramsAreValid($params) {
		$paramsValid = true;
		foreach ($params as $param) {
			// CHECK FOR INVALID CHARACTERS HERE!!! NO SPACES IN PARAMS!
			if (!isset($param)) {
				$paramsValid = false;
				break;
			}
		}
		return $paramsValid;
	}
	
	// Takes an array, and stores it in the 'applications' table
	function saveParametersArrayForApp($app, $parameterArray) {
		$updateValue = str_replace("'","\'",serialize($parameterArray));
		$sql = "UPDATE `applications` SET `params`='" . sql_safe($updateValue) . "' WHERE `id`='" . sql_safe($app['id']) . "'";
		$worked = $this->Shimmer->query($sql);
		if ($worked) {
			$this->paramCache[$app['id']] = $parameterArray;
			return true;
		}
		return false;
	}
	
	///////////////////////////////////////////////////////////////////////////////
	// SPARKLE-FRIENDLY GRAPH FUNCTIONS                                          //
	// Only custom Graphs are stored, Sparkle is added to list when retrieving.  //
	///////////////////////////////////////////////////////////////////////////////

	function graphsForApp($app, $includeSparkle=false) {
		$appID = $app['id'];
		if (!isset($this->graphCache[$appName])) {
			$didRetrieve = false;
			$sql = "SELECT `graphs` FROM `applications` WHERE `id`='" . sql_safe($appID) . "' LIMIT 1";
			$result = $this->Shimmer->query($sql);
			if ($result) {
				$row = mysql_fetch_array($result);
				$graphString = $row['graphs'];
				$unserializedList = unserialize($graphString);
				if ($unserializedList!=false) {
					$didRetrieve = true;
					$this->graphCache[$appName] = $unserializedList;
				}
			}
			if (!$didRetrieve) $this->graphCache[$appName] = array();
		}
		$returnArray = $this->graphCache[$appName];
		if ($includeSparkle && $app['usesSparkle']) {
			$sparkleGraphs = $this->sparkleGraphsDefinitions();
			foreach ($sparkleGraphs as $sparkleGraph) $returnArray[] = $sparkleGraph;
		}
		return $returnArray;
	}
	
	function setGraphsForApp($app, $graphSets) {
		// Get existing Graphs, excluding default Sparkle Graphs
		$existingGraphs = $this->graphsForApp($app);
		
		// We keep a list of the Graphs we add or update, so that afterwards we
		// can delete any that aren't used any more
		$usedGraphIDs	= array();
		
		// Process each supplied Graph (ie. Add or Update)
		foreach ($graphSets as $key => $graph) {
			if (!isset($graph['id'])) $graph['id'] = md5(serialize($graph).time());	
			
			// Work out if the supplied Graph already exists
			$matchedGraphIndex = -1;
			foreach ($existingGraphs as $key => $existingGraph) {
				if ($existingGraph['id']==$graph['id']) {
					$matchedGraphIndex = $key;
					break;
				}
			}
			
			// Either update or insert the Graph
			if ($matchedGraphIndex >= 0) {
				// If we find a match, update the existing graph to reflect the new Graph definition
				$existingGraphs[$matchedGraphIndex] = $graph;
			} else {
				// If we didn't update an existing Graph, then we add it instead
				array_push($existingGraphs, $graph);
			}
			
			array_push($usedGraphIDs, $graph['id']);
		}
		
		// So we've added and updated all the Graphs. Now we just get rid of any old Graphs
		// which we didn't touch, using the $existingGraphs and $usedGraphIDs lists.
		foreach ($existingGraphs as $i => $graph) {
			if (!in_array($graph['id'], $usedGraphIDs)) {
				unset($existingGraphs[$i]);
			}
		}
		
		// Re-index because we might have unset some values
		$existingGraphs = array_values($existingGraphs);
		
		if ( $this->saveGraphsArrayForApp($app, $existingGraphs) ) return true;
	}
	
	// Does a Graph with the supplied ID exist for the supplied app?
	function graphExistsForApp($app, $graphID) {
		if ($graphID=="users" || $graphID=="downloads") return true;
		$existingGraphs = $this->graphsForApp($app, true);
		if ($existingGraphs) {
			foreach ($existingGraphs as $existingGraph) {
				if ($existingGraph['id']==$graphID) return true;
			}
		}
		return false;
	}
	
	// Validates the supplied Graph array
	function graphsAreValid($graphs) {
		$graphsValid = true;
		foreach ($graphs as $graph) {
			if (!isset($graph['name']) || !isset($graph['key']) || !isset($graph['type']) || !isset($graph['values'])) {
				$graphsValid = false;
				break;
			}
			if (sizeof($graph['values'])>0) {
				if ($graph['type']=="1") {
					foreach ($graph['values'] as $grouping) {
						if (!isset($grouping['label']) || !isset($grouping['match'])) {
							$graphsValid = false;
							break 2;
						}
					}
				} else if ($graph['type']=="2") {
					foreach ($graph['values'] as $grouping) {
						if (!isset($grouping['conditions']) || !isset($grouping['modifications']) || !isset($grouping['round']) || !isset($grouping['unit'])) {
							$graphsValid = false;
							break 2;
						}
					}
				}
			}
		}
		return $graphsValid;
	}
	
	// Takes an array, and stores it in the 'applications' table in serialized form
	function saveGraphsArrayForApp($app, $graphArray) {
		$updateValue = str_replace("'","\'",serialize($graphArray));
		$sql = "UPDATE `applications` SET `graphs`='" . sql_safe($updateValue) . "' WHERE `id`='" . sql_safe($app['id']) . "'";
		$worked = $this->Shimmer->query($sql);
		if ($worked) {
			$this->graphCache[$app['id']] = $graphArray;
			return true;
		}
		return false;
	}
	
	///////////////////////////////////
	// OTHER STATS					//
	// General usage stat functions //
	///////////////////////////////////
	
	function appUsers($app) {
		if ($app) {
			$cutoff = date('Y-m-d', time()-(86400*30));
			$userSql = "SELECT COUNT(*) AS 'user_count' FROM `" . sql_safe($app['users_table']) . "` WHERE `last_seen`>='" . sql_safe($cutoff) . "'";
			$countResult = $this->Shimmer->query($userSql);
			if ($countResult) {
				$row = mysql_fetch_array($countResult);
				return intval($row['user_count']);
			}
		}
		return 0;
	}
	
	function allAppUsers() {
		$total = 0;
		foreach ($this->Shimmer->apps->list as $app) {
			$total += $this->appUsers($app);
		}
		return $total;
	}
	
	// How many DSA signatures has Shimmer calculated?
	function totalDsaCount() {
		return intval($this->Shimmer->pref->read('dsa_gen_count'));
	}
	
	// Update the stored number of DSA signature calculated by Shimmer
	function incrementTotalDsaCount($by=1) {
		$this->Shimmer->pref->save('dsa_gen_count', $this->totalDsaCount()+1);
	}
	
	// On average, how long is it since a user has been seen?
	function averageTimeSinceUserSeenForApp($app) {
		$now = date('Y-m-d', time());
		$sql = "SELECT AVG(TO_DAYS('" . $now . "')-TO_DAYS(last_seen)) as 'avg' FROM `" . sql_safe($app['users_table']). "`";
		$result = mysql_query($sql);
		if ($result) {
			$row = mysql_fetch_array($result);
			if ($row) return intval($row['avg']);
		}
		return 0;
	}
	
	// For all apps, what is the average time since a user has been seen
	function averageTimeSinceUserSeenForAllApps() {
		$tally = 0;
		foreach ($this->Shimmer->apps->list as $app) {
			$tally += $this->averageTimeSinceUserSeenForApp($app);
		}
		return $tally;
	}
	
	function setBoxTargetForApp($app, $location, $graphId) {
		if ($this->graphExistsForApp($app, $graphId)) {
			$boxes = $this->Shimmer->apps->boxesForApp($app);
			if ($boxes) {
				foreach ($boxes as $loc => &$field) {
					if ($loc == $location) $field = $graphId;
				}
				$boxData = str_replace("'","\'",serialize($boxes));
				
				$sql = "UPDATE `applications` SET `boxes`='" . sql_safe($boxData) . "' WHERE `id`='" . sql_safe($app['id']) . "'";
				$result = $this->Shimmer->query($sql);
				if ($result) return true;
			}
		}
		return false;
	}
			
	function userExists($app,$identifierValue) {
		$userSql = "SELECT COUNT(*) AS 'user_count' FROM `" . sql_safe($app['users_table']) . "` WHERE `" . $app['identifier'] . "`='" . sql_safe($identifierValue) . "'";
		$countResult = $this->Shimmer->query($userSql);
		if ($countResult) {
			$row = mysql_fetch_array($countResult);
			if (intval($row['user_count'])>0) return true;
		}
		return false;
	}
	
	// NEEDS TO BE FIXED fixme needsfix
	function resetToDefaultBoxAssignments() {
		$boxPrefs = array	(
								array('box'=>'r1b1', 'id'=>$Shimmer->stats->defaultGraphChoices['r1b1'] ),
								array('box'=>'r2b1', 'id'=>$Shimmer->stats->defaultGraphChoices['r2b1'] ),
								array('box'=>'r2b2', 'id'=>$Shimmer->stats->defaultGraphChoices['r2b2'] ),
								array('box'=>'r2b3', 'id'=>$Shimmer->stats->defaultGraphChoices['r2b3'] )
							);
		$this->Shimmer->pref->save("boxes",$boxPrefs,true);
	}
	
	// Delete any user entries which have not been updated in the last 60 days
	function deleteOldUsers() {
		foreach ($this->Shimmer->apps->list as $app) {
			$sql = "DELETE FROM `" . sql_safe($app['users_table']) . "` WHERE `last_seen` < TIMESTAMPADD(DAY,-60,NOW())";
			$this->Shimmer->query($sql);
		}
	}
	
	////////////////////////////////////////////
	// SPARKLE STAT DEFINITIONS				  //
	// Define the default Sparkle Graphs and  //
	// Parameters, so we can append them to   //
	// other lists when needed.				  //
	////////////////////////////////////////////
	
	function sparkleParameterDefinitions() {
		return array( "lang", "osVersion", "cputype", "cpu64bit", "cpusubtype", "model", "ncpu", "ramMB", "cpuFreqMHz");
	}
	
	function sparkleGraphsDefinitions() {
		$sparkleGraphs = array	(
			// Graph Type: 1=text, 2=number //

			// LANGUAGE //
			// Sourced from http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
			array	(
				"id"		=> "sparkle-language",
				"type"		=> 1,
				"name"		=> "Language",
				"key"		=> "lang",
				"stock"		=> true,
				"values"	=> array(
					array("label"=>"Afrikaans",		"searches"=>array("^af(-.*)?$")),
					array("label"=>"Albanian",		"searches"=>array("^sq(-.*)?$")),
					array("label"=>"Arabic",		"searches"=>array("^ar(-.*)?$")),
					array("label"=>"Armenian",		"searches"=>array("^hy(-.*)?$")),
					array("label"=>"Azerbaijani",	"searches"=>array("^az(-.*)?$")),
					array("label"=>"Belarusian",	"searches"=>array("^be(-.*)?$")),
					array("label"=>"Bengali",		"searches"=>array("^bn(-.*)?$")),
					array("label"=>"Bosnian",		"searches"=>array("^bs(-.*)?$")),
					array("label"=>"Bulgarian",		"searches"=>array("^bg(-.*)?$")),
					array("label"=>"Burmese",		"searches"=>array("^my(-.*)?$")),
					array("label"=>"Catalan",		"searches"=>array("^ca(-.*)?$")),
					array("label"=>"Chamorro",		"searches"=>array("^ch(-.*)?$")),
					array("label"=>"Chinese",		"searches"=>array("^zh(-.*)?$", "^chin")),
					array("label"=>"Cornish",		"searches"=>array("^kw(-.*)?$")),
					array("label"=>"Corsican",		"searches"=>array("^co(-.*)?$")),
					array("label"=>"Croatian",		"searches"=>array("^hr(-.*)?$")),
					array("label"=>"Czech",			"searches"=>array("^cs(-.*)?$")),
					array("label"=>"Danish",		"searches"=>array("^da(-.*)?$")),
					array("label"=>"Dutch",			"searches"=>array("^nl(-.*)?$")),
					array("label"=>"English",		"searches"=>array("^en(g|-)?") ),
					array("label"=>"Estonian",		"searches"=>array("^et(-.*)?$")),
					array("label"=>"Fijian",		"searches"=>array("^fj(-.*)?$")),
					array("label"=>"Finnish",		"searches"=>array("^fi(-.*)?$")),
					array("label"=>"French",		"searches"=>array("^fr(-.*)?$", "^fren") ),
					array("label"=>"Galician",		"searches"=>array("^gl(-.*)?$")),
					array("label"=>"Ganda",			"searches"=>array("^lg(-.*)?$")),
					array("label"=>"Georgian",		"searches"=>array("^ka(-.*)?$")),
					array("label"=>"German",		"searches"=>array("^de(-.*)?$", "^germ") ),
					array("label"=>"Greek",			"searches"=>array("^el(-.*)?$", "^gree") ),
					array("label"=>"Haitian",		"searches"=>array("^ht(-.*)?$")),
					array("label"=>"Hebrew",		"searches"=>array("^he(-.*)?$")),
					array("label"=>"Hindi",			"searches"=>array("^hi(-.*)?$")),
					array("label"=>"Hungarian",		"searches"=>array("^hu(-.*)?$")),
					array("label"=>"Icelandic",		"searches"=>array("^is(-.*)?$")),
					array("label"=>"Indonesian",	"searches"=>array("^id(-.*)?$")),
					array("label"=>"Irish",			"searches"=>array("^ga(-.*)?$")),
					array("label"=>"Italian",		"searches"=>array("^it(-.*)?$", "^ital")),
					array("label"=>"Japanese",		"searches"=>array("^ja(-.*)?$", "^japa")),
					array("label"=>"Javanese",		"searches"=>array("^jv(-.*)?$")),
					array("label"=>"Kazakh",		"searches"=>array("^kk(-.*)?$")),
					array("label"=>"Kongo",			"searches"=>array("^kg(-.*)?$")),
					array("label"=>"Korean",		"searches"=>array("^ko(-.*)?$")),
					array("label"=>"Lao",			"searches"=>array("^lo(-.*)?$")),
					array("label"=>"Latin",			"searches"=>array("^la(-.*)?$")),
					array("label"=>"Latvian",		"searches"=>array("^lv(-.*)?$")),
					array("label"=>"Lithuanian",	"searches"=>array("^lt(-.*)?$")),
					array("label"=>"Luxembourgish",	"searches"=>array("^lb(-.*)?$")),
					array("label"=>"Macedonian",	"searches"=>array("^mk(-.*)?$")),
					array("label"=>"Malay",			"searches"=>array("^ms(-.*)?$")),
					array("label"=>"Maltese",		"searches"=>array("^mt(-.*)?$")),
					array("label"=>"Norwegian",		"searches"=>array("^(no|nb|nn)(-.*)?$")),
					array("label"=>"Persian",		"searches"=>array("^fa(-.*)?$")),
					array("label"=>"Polish",		"searches"=>array("^pl(-.*)?$")),
					array("label"=>"Portuguese",	"searches"=>array("^pt(-.*)?$")),
					array("label"=>"Romanian",		"searches"=>array("^ro(-.*)?$")),
					array("label"=>"Russian",		"searches"=>array("^ru(-.*)?$")),
					array("label"=>"Serbian",		"searches"=>array("^sr(-.*)?$")),
					array("label"=>"Serbo-Croatian","searches"=>array("^sh(-.*)?$")),
					array("label"=>"Slovak",		"searches"=>array("^sk(-.*)?$")),
					array("label"=>"Slovenian",		"searches"=>array("^sl(-.*)?$")),
					array("label"=>"Spanish",		"searches"=>array("^es(-.*)?$")),
					array("label"=>"Swedish",		"searches"=>array("^sv(-.*)?$", "^swe")),
					array("label"=>"Tahitian",		"searches"=>array("^ty(-.*)?$")),
					array("label"=>"Turkish",		"searches"=>array("^tr(-.*)?$")),
					array("label"=>"Ukrainian",		"searches"=>array("^uk(-.*)?$")),
					array("label"=>"Vietnamese",	"searches"=>array("^vi(-.*)?$")),
					array("label"=>"Welsh",			"searches"=>array("^cy(-.*)?$")),
				)
			),

			// MAJOR OS VERSION //	
			array(
				"id"		=> "sparkle-major-os",
				"type"		=> 1,
				"name"   => "Major OS Version",
				"key"    => "osVersion",
				"stock"  => true,
				"values" => array(
					array("label"=>"Snow Leopard", "searches"=>array("10.6") ),
					array("label"=>"Leopard", "searches"=>array("10.5") ),
					array("label"=>"Tiger", "searches"=>array("10.4") ),
				)
			),

			// FULL OS VERSION //	
			array(
				"id"		=> "sparkle-os",
				"type"		=> 1,
				"name"		=> "OS Version",
				"key"		=> "osVersion",
				"stock"  => true,
				"values" => array()
			),

			// RAM //
			array(
				"id"		=> "sparkle-ram",
				"type"		=> 2,
				"name"		=> "Computer RAM",
				"key"		=> "ramMB",
				"stock"		=> true,
				"values"	=> array(
					array(
						"searches"		=> array("^[0-9]+$"),
						"conditions"	=> array( array("operator"=>'greater', "value"=>999) ),
						"modifications"	=> array( array("operator"=>'divide', "value"=>1000) ),
						"round"			=> 0,
						"unit"			=> "GB"
					),
					array(
						"searches"		=> array("^[0-9]+$"),
						"conditions"	=> array( array("operator"<'greater', "value"=>999) ),
						"modifications"	=> array(),
						"round"			=> 0,
						"unit"			=> "MB"
					)	
				)
			),

			// CPU COUNT //
			array(
				"id"		=> "sparkle-cpu-count",
				"type"		=> 1,
				"name"		=> "CPU Count",
				"key"		=> "ncpu",
				"stock"		=> true,
				"values"	=> array()
			),

			// CPU TYPE //
			array(
				"id"		=> "sparkle-cpu-type",
				"type"		=> 1,
				"name"		=> "CPU Type",
				"key"		=> "cputype",
				"stock"		=> true,
				"values"	=> array(
					array("label"=>"PowerPC",	"searches"=>array("^18$") ),
					array("label"=>"Intel",		"searches"=>array("^7$") )
				)
			),

			// CPU FREQUENCY //
			array(
				"id"		=> "sparkle-cpu-frequency",
				"type"		=> 2,
				"name"		=> "CPU Frequency",
				"key"		=> "cpuFreqMHz",
				"stock"		=> true,
				"values"	=> array(
					array(
						"searches"		=> array("^[0-9]+$"),
						"conditions"	=> array( array("operator"=>'less_or_equal', "value"=>999) ),
						"modifications"	=> array(),
						"round"			=> 0,
						"unit"			=> "MHz"
					),
					array(
						"searches"		=> array("^[0-9]+$"),
						"conditions"	=> array( array("operator"=>'greater',	"value"=>999) ),
						"modifications"	=> array( array("operator"=>'divide',	"value"=>1000) ),
						"round"			=> 1,
						"unit"			=> "GHz"
					)
				)
			),

			// COMPUTER Model //
			array(
				"id"		=> "sparkle-computer-model",
				"type"		=> 1,
				"name"   => "Computer Model",
				"key"    => "model",
				"stock"  => true,
				"values" => array	(
					array("label"=>"MacBookAir",	"searches"=>array("MacBookAir") ),
					array("label"=>"MacBookPro",	"searches"=>array("MacBookPro") ),
					array("label"=>"MacBook",		"searches"=>array("MacBook[0-9]+") ),
					array("label"=>"Mac Pro",		"searches"=>array("MacPro") ),
					array("label"=>"iMac",			"searches"=>array("iMac") ),
					array("label"=>"PowerBook",		"searches"=>array("PowerBook") ),
					array("label"=>"Mac Mini",		"searches"=>array("Macmini") ),
					array("label"=>"Power Mac",		"searches"=>array("PowerMac") ),
				)
			)
	);

		return $sparkleGraphs;
	}
	
}


?>