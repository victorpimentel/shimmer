// APPS: Used to control/modify apps

apps = {
	
	// Helpful variables
	'hasReloadedAppList': false,
	
	// Stores the name and index of the currently displayed app
	'currentApp': "",
	'currentAppIndex': 0,
	
	knownApps: new Array(),
	
	knownAppNames: function() {
		var names = [];
		for (var i=0; i < apps.knownApps.length; i++) {
			var name = apps.knownApps[i].name;
			if (name && name.length>0) names.push(name);
		};
		return names;
	},
	
	// Return the number of apps
	'knownAppsCount': function() {
		return this.knownApps.length;
	},
	
	'appAtIndex': function(appIndex) {
		if (this.knownAppsCount()>0 && this.knownAppsCount()>=appIndex) return this.knownApps[appIndex].name;
		return "";
	},
	
	'indexOfApp': function(appName) {
		for (var i=0; i < this.knownApps.length; i++) {
			if (this.knownApps[i].name==appName) return i;
		};
		return -1;
	},
	
	'addKnownApp': function(appName) {
		if (this.indexOfApp(appName)<0) this.knownApps.push({name:appName});
	},
	
	'clearKnownApps': function() {
		this.knownApps.length = 0;
	},
	
	// Refreshes graphs every 5 minutes
	'refreshVersionsTimer': false,
	
	// Reloads the app list, which then triggers a graph refresh
	reloadAppList: function() {
		clearTimeout(this.refreshVersionsTimer);
		new Ajax.Request('?ajax', {
			parameters: {
				action:		'get.data.many',
				app:		apps.currentApp,
				applist:	true,
				graphs:		true
			},
			method:'get',
			onSuccess: function(transport) {
				var response = transport.responseText;
				var theResponse = JSON.parse(response);
				if (theResponse.apps && theResponse.apps.length>0) {
					// Update the cached Stat definition list
					var theApp = theResponse.apps[0];
					apps.updateAppCacheWithStatDefinitions(apps.currentAppIndex, theApp.params, theApp.graphs);

					apps.processReceivedAppList(theResponse.allApps);
				}
				boxes.redrawAllGraphChoosersForCurrentApp();
			}
		});
	},
	
	reloadCurrentAppVersionsAndGraphs: function() {
		apps.switchApp(apps.currentApp, true);
	},
	
	// Generates new app lists from updated app list data
	processReceivedAppList: function(foundApps, reloadVersionsToo) {
		if (typeof reloadVersionsToo == "undefined") reloadVersionsToo=true;
		appsUI.regenerateAppList(foundApps, reloadVersionsToo);
		this.hasReloadedAppList = true;
	},
	
	// Restarts the reload timer. Defaults to 5 minutes.
	'refreshReloadTimer': function(delay) {
		if (typeof delay == "undefined") delay=5*60;
		clearTimeout(this.refreshVersionsTimer);
		this.refreshVersionsTimer = setTimeout("versions.reloadVersionsForApp(apps.currentApp)",delay*1000);
	},
	
	reloadStatDefinitionsForCurrentApp: function() {
		if (this.currentApp.length>0) {
			this.reloadStatDefinitionsForApp(this.currentApp);
		}
	},
	
	switchToAppAtIndex: function(theIndex, reloadVersionsToo) {
		if (typeof reloadVersionsToo == "undefined") reloadVersionsToo = true;
		if (versions.isReloadingVersions == false) {
			if (this.knownAppsCount()>0) {
				if (theIndex < 0) theIndex = 0;
				var indexChanged = this.currentAppIndex != theIndex;
				this.currentAppIndex = theIndex;
				var appName = this.appAtIndex(theIndex);
				this.switchApp(appName, reloadVersionsToo);
			}
		}
	},
	
	switchApp: function(appName, reloadVersionsToo) {
		if (versions.isReloadingVersions == false) {
			if (typeof reloadVersionsToo == "undefined") reloadVersionsToo = true;
			var didUpdateTitle = false;
			var indexOfAppName = apps.indexOfApp(appName);
			if (appName.length>0 && indexOfAppName>-1) {
				apps.currentAppIndex = indexOfAppName;
				if (appName != apps.currentApp) {
					apps.currentApp = appName;
					$('current_app_title').innerHTML = appName + ' <small>' + appsUI.storedUserCountForAppName(appName) + ' users</small>';
				}
				didUpdateTitle = true;
				Shimmer.state.setPageTitle(appName);

				if (reloadVersionsToo) {
					var initialParameters = {action:'get.data.many', versions:true, graphs:true, graphdata:true};
					var currentHash = Shimmer.state.getAppNameFromURL();
					if (currentHash && currentHash.length>0) initialParameters.app = currentHash;

					new Ajax.Request('?ajax', {
						method:'get',
						parameters: initialParameters,
						onSuccess: function(transport) {
							var response = transport.responseText;
							var theResponse = JSON.parse(response);
							if (theResponse.wasOK) {
								if (theResponse.apps && theResponse.apps.length>0) {
									chosenApp = theResponse.apps[0];
									var chosenAppName = chosenApp.name;
									var appIndex = apps.indexOfApp(chosenAppName);

									// Cache and Draw the Graph List for the chosen App
									apps.updateAppCacheWithStatDefinitions( appIndex, chosenApp.params, chosenApp.graphs );
									boxes.redrawAllGraphChoosersForCurrentApp();

									// Reload the Versions table for the chosen App
									versions.reloadVersionsForApp_handleResponse(chosenApp.allVersions);

									// Draw the 4 Graphs for the chosen App
									processGraphData(chosenApp.stats);
									
									versionsUI.table.scroll.constrainer.reapplyConstraint();
									versionsUI.table.scroll.slider.setTop(0);
								}
							}
						}
					});
				}
			} else if (apps.knownAppsCount()>0) {
				didUpdateTitle = true;
				var theApp = apps.appAtIndex(apps.currentAppIndex);
				this.switchApp( apps.appAtIndex(apps.currentAppIndex) );
			}
			if (!didUpdateTitle && apps.knownAppsCount() == 0) {
				apps.currentApp = "";
				$('current_app_title').innerHTML = 'No Apps';
				Shimmer.showWelcomeArea();
			}
		}
	},
	
	reloadStatDefinitionsForApp: function(appName) {
		var appIndex = this.indexOfApp(appName);
		if (appIndex>-1) {
			new Ajax.Request('?ajax', {
				method:'get',
				parameters: { action:'get.data.many', app:appName, graphs:true },
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);

					if (theResponse.wasOK && theResponse.apps && theResponse.apps.length>0) {
						var theApp = theResponse.apps[0];
						apps.updateAppCacheWithStatDefinitions( appIndex, theApp.params, theApp.graphs );
						boxes.redrawAllGraphChoosersForCurrentApp();
					}
				}
			});
		}
	},
	
	cachedStatDefinitionsForApp: function(appName) {
		var appIndex = this.indexOfApp(appName);
		if (appIndex>-1) {
			var params = this.knownApps[appIndex].params;
			var graphs = this.knownApps[appIndex].graphs;
			if (params && graphs) return {params:params, graphs:graphs};
		}
		return false;
	},
	
	updateAppCacheWithStatDefinitions: function(appIndex, params, graphs) {
		this.knownApps[appIndex].params = params;
		this.knownApps[appIndex].graphs = graphs;
	},
	
	alterSelectedApp: function(direction) {
		this.currentAppIndex = parseInt(this.currentAppIndex) + parseInt(direction);

		var knownAppsCount = this.knownAppsCount();

		if (this.currentAppIndex<0) {
	    	this.currentAppIndex= knownAppsCount-1;
		} else if (this.currentAppIndex > knownAppsCount-1 ) {
			this.currentAppIndex = 0;
		}

		this.switchToAppAtIndex(apps.currentAppIndex);
	},

	appClicked: function(theDiv) {
		var appDetails = appsUI.appNameFromClickedApplistItem(theDiv);
		if (appDetails) {
			var clickedAppName = appDetails.name;
			this.switchApp(clickedAppName);
			versionsUI.hideNewVersionForm();
		}
	},
	
	deleteApp: function(appName) {
		if (appName.replace(" ","").length>0) {
			if ( confirm("Are you sure you want '" + appName + "' to be deleted?\nAll associated versions and appcasts will no longer function.") ) {
				notify.update('Deleting app \'' + appName + '\'...',0);
				new Ajax.Request('?ajax', {
					method:'get',
					parameters: {action: 'app.delete', app_name: appName},
					onSuccess: function(transport) {
						var response = transport.responseText;
						var theResponse = JSON.parse(response);
						if (theResponse.wasOK) {
							apps.reloadAppList();
							notify.update('App \'' + appName + '\' deleted', 5);
						}
					},
					onFailure: function(){ alert('Something went wrong...') }
				});
			}
		}
	},
	
	nameIsValid: function(appName) {
		if (appName.length > 0) return true;
		return false;
	},
	
	loadInitialData: function() {
		var initialParameters = {action:'get.data.many', applist:true, versions:true, graphs:true, graphdata:true, noteslist:true};
		var currentHash = Shimmer.state.getAppNameFromURL();
		if (currentHash && currentHash.length>0) initialParameters.app = currentHash;

		new Ajax.Request('?ajax', {
			method:'get',
			parameters: initialParameters,
			onSuccess: function(transport) {
				var response = transport.responseText;
				var theResponse = JSON.parse(response);
				if (theResponse.wasOK) {
					Shimmer.knownNotesThemes = theResponse.noteslist;
					
					apps.processReceivedAppList(theResponse.allApps, false);
					
					if (theResponse.apps && theResponse.apps.length>0) {
						var chosenApp = theResponse.apps[0];
						var chosenAppName = chosenApp.name;
						var appIndex = apps.indexOfApp(chosenAppName);
					
						// Cache and Draw the Graph List for the chosen App
						apps.updateAppCacheWithStatDefinitions(appIndex, chosenApp.params, chosenApp.graphs);
						boxes.redrawAllGraphChoosersForCurrentApp();
					
						// Reload the Versions table for the chosen App
						versions.reloadVersionsForApp_handleResponse(chosenApp.allVersions);
					
						// Draw the 4 Graphs for the chosen App
						processGraphData(chosenApp.stats);
					}
				}
			}
		});
	},
	
	testMasks: function() {
		var downloadMask	= $('download-mask').value;
		var notesMask		= $('notes-mask').value;
		$('test-masks-message').innerHTML = 'Testing Masks...';
		$('test-masks-link').hide();
		$('test-masks-message').show();
		new Ajax.Request('?ajax&type=pref', {
			method: 'get',
			parameters: {
				action:			"prefs.masks.test",
				downloadMask:	downloadMask,
				notesMask:		notesMask
			},
			onSuccess: function(transport) {
				var response = transport.responseText;
				var theResponse = JSON.parse(response);
				if (theResponse.wasOK) {
					var message = '';
					if (theResponse.downloadWorking && theResponse.notesWorking) {
						message = 'Both masks are working!';
					} else if (!theResponse.downloadWorking && !theResponse.notesWorking) {
						message = 'Neither of the masks are working';
					} else {
						var notWorkingMaskName	= (!theResponse.downloadWorking	? 'Download' : 'Notes');
						message = notWorkingMaskName + " mask is not working";
						$(!theResponse.downloadWorking ? 'download-mask' : 'notes-mask').focus();
					}
					$('test-masks-message').innerHTML = message;
					$('test-masks-link').hide();
					$('test-masks-message').show();
				}
			},
			onFailure: function() {
				$('test-masks-message').innerHTML = 'Test failed &rarr;&nbsp;';
				$('test-masks-link').show();
				$('test-masks-message').show();
			}
		});
	}
	
	
}