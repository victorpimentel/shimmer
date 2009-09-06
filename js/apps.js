// APPS: Used to control/modify apps

apps = {
	
	// Helpful variables
	'hasReloadedAppList': false,
	
	// Stores a list of known apps, and useful functions
	appsHub: {
		list: new Array(),
		
		addApp: function(id, info) {
			apps.appsHub.list.push({id:id, info:info});
		},
		
		appCount: function() {
			return apps.appsHub.list.length;
		},
		
		clearAppList: function() {
			apps.appsHub.list.length = 0;
		},
		
		// Returns an array of {id:x, name:x, variant:x} items
		knownApps: function() {
			var knownApps = [];
			for (var i=0; i < apps.appsHub.list.length; i++) {
				var app = apps.appsHub.list[i];
				if (app.info.name.length>0) knownApps.push({
					id:      app.id,
					name:    app.info.name,
					variant: app.info.variant
				});
			};
			return knownApps;
		},
		
		currentAppID: false,
		
		idForAppWithNameAndVariant: function(name, variant) {
			for (var i=0; i < apps.appsHub.list.length; i++) {
				if (apps.appsHub.list[i].info.name==name) {
					if (variant.length>0) {
						if (apps.appsHub.list[i].info.variant==variant) return apps.appsHub.list[i].id;
					} else return apps.appsHub.list[i].id;
				}
			};
		},
		
		dictionaryForAppWithID: function(id) {
			for (var i=0; i < apps.appsHub.list.length; i++) {
				if (apps.appsHub.list[i].id==id) return apps.appsHub.list[i];
			};
			return false;
		},
		
		// Returns app name and variant
		titleForAppWithID: function(id) {
			var dict = apps.appsHub.dictionaryForAppWithID(id);
			if (dict) {
				var title = dict.info.name;
				if (dict.info.variant.length>0) title += ' (' + dict.info.variant + ')';
				return title;
			}
			return false;
		},
		
		// Returns only the app name (no variant)
		nameForAppWithID: function(id) {
			var dict = apps.appsHub.dictionaryForAppWithID(id);
			if (dict) {
				return dict.info.name;
			}
			return false;
		},
		
		appExistsWithID: function(id) {
			return (apps.appsHub.dictionaryForAppWithID(id) ? true : false);
		},
		
		setGraphsAndParamsForAppWithID: function(appID, params, graphs) {
			for (var i=0; i < apps.appsHub.list.length; i++) {
				if (apps.appsHub.list[i].id==appID) {
					apps.appsHub.list[i].info.params = params;
					apps.appsHub.list[i].info.graphs = graphs;
					break;
				}
			};
		},
		
		graphsForAppWithID: function(id) {
			for (var i=0; i < apps.appsHub.list.length; i++) {
				if (apps.appsHub.list[i].id==id) {					
					return apps.appsHub.list[i].info.graphs;
				}
			};
			return false;
		}
		
	},
	
	changeToApp: function(appID) {
		if (!versions.isReloadingVersions) {
			var theApp = apps.appsHub.dictionaryForAppWithID(appID);
			if (theApp) {
				apps.appsHub.currentAppID = theApp.id;
				$('current_app_title').innerHTML = theApp.info.name + ' <small>' + theApp.info.count + ' users</small>';
				versions.reloadVersionsForApp(apps.appsHub.currentAppID);
				
				var newHash  = theApp.id + ':' + theApp.info.name;
				var newTitle = 'Shimmer: ' + theApp.info.name + (theApp.info.variant.length>0 ? (' (' + theApp.info.variant + ')') : '')
				Shimmer.state.setPageTitle(newHash, newTitle);
				
				if (1==1 || reloadVersionsToo) {
					new Ajax.Request('?ajax', {
						method:'get',
						parameters: {action:'get.data.many', appID:apps.appsHub.currentAppID, versions:true, graphs:true, graphdata:true},
						onSuccess: function(transport) {
							var response = transport.responseText;
							var theResponse = JSON.parse(response);
							if (theResponse.wasOK) {
								apps.handleMassDataGrab(theResponse);
								versionsUI.table.scroll.constrainer.reapplyConstraint();
								versionsUI.table.scroll.slider.setTop(0);
							}
						}
					});
				}
			} else if (apps.appsHub.appCount()>0) {
				apps.changeToFirstApp();
			} else if (apps.appsHub.appCount() == 0) {
				apps.appsHub.currentAppID = false;
				$('current_app_title').innerHTML = 'No Apps';
				Shimmer.showWelcomeArea();
			}
		}
	},
	
	changeToNextApp: function() {
		for (var i=0; i < apps.appsHub.list.length; i++) {
			if (apps.appsHub.list[i].id==apps.appsHub.currentAppID) {
				if (i+1>=apps.appsHub.list.length) i=-1;
				apps.changeToApp(apps.appsHub.list[i+1].id);
				break;
			}
		};
	},
	
	changeToPreviousApp: function() {
		for (var i=0; i < apps.appsHub.list.length; i++) {
			if (apps.appsHub.list[i].id==apps.appsHub.currentAppID) {
				if (i-1<0) i=apps.appsHub.list.length;
				apps.changeToApp(apps.appsHub.list[i-1].id);
				break;
			}
		};
	},
	
	changeToFirstApp: function() {
		if (apps.appsHub.list.length>0) {
			apps.changeToApp(apps.appsHub.list[0].id);
		}// else show welcome message
	},
	
	// Refreshes graphs every 5 minutes
	'refreshVersionsTimer': false,
	
	// Reloads the app list, which then triggers a graph refresh
	reloadAppList: function() {
		clearTimeout(this.refreshVersionsTimer);
		new Ajax.Request('?ajax', {
			parameters: {
				action:  'get.data.many',
				appID:   apps.appsHub.currentAppID,
				applist: true,
				graphs:  true
			},
			method:'get',
			onSuccess: function(transport) {
				var response = transport.responseText;
				var theResponse = JSON.parse(response);
				if (theResponse.wasOK) {
					apps.handleMassDataGrab(theResponse);
				}
			}
		});
	},
	
	reloadCurrentAppVersionsAndGraphs: function() {
		apps.changeToApp(apps.appsHub.currentAppID, true);
	},
	
	// Generates new app lists from updated app list data
	processReceivedAppList: function(foundApps, reloadVersionsToo) {
		if (typeof reloadVersionsToo == "undefined") reloadVersionsToo=true;
		appsUI.regenerateAppList(foundApps, reloadVersionsToo);
		this.hasReloadedAppList = true;
	},
	
	// Restarts the reload timer. Defaults to 5 minutes.
	refreshReloadTimer: function(delay) {
		if (typeof delay == "undefined") delay=5*60;
		clearTimeout(this.refreshVersionsTimer);
		this.refreshVersionsTimer = setTimeout("versions.reloadVersionsForApp(apps.appsHub.currentAppID)", delay*1000);
	},
	
	reloadStatDefinitionsForApp: function(appID) {
		if (apps.appsHub.appExistsWithID(appID)) {
			new Ajax.Request('?ajax', {
				method:'get',
				parameters: { action:'get.data.many', appID:appID, graphs:true },
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);

					if (theResponse.wasOK) {
						apps.handleMassDataGrab(theResponse);
					}
				}
			});
		}
	},
	
	appClicked: function(theDiv) {
		var appDetails = appsUI.appInfoFromClickedApplistItem(theDiv);
		if (appDetails) {
			var clickedAppId = appDetails.id;
			apps.changeToApp(clickedAppId);
			versionsUI.hideNewVersionForm();
		}
	},
	
	deleteApp: function(appID, appName) {
		if (appName.replace(" ","").length>0) {
			if ( confirm("Are you sure you want '" + appName + "' to be deleted?\nAll associated versions and appcasts will no longer function.") ) {
				notify.update('Deleting app \'' + appName + '\'...',0);
				new Ajax.Request('?ajax', {
					method:'get',
					parameters: {action: 'app.delete', appID: appID},
					onSuccess: function(transport) {
						var response = transport.responseText;
						var theResponse = JSON.parse(response);
						if (theResponse.wasOK) {
							apps.reloadAppList();
							notify.update('App \'' + appName + '\' deleted', 5);
						}
					}
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
		var initialId = Shimmer.state.getAppIdFromHash();
		if (initialId) initialParameters.appID = initialId;

		new Ajax.Request('?ajax', {
			method:'get',
			parameters: initialParameters,
			onSuccess: function(transport) {
				var response = transport.responseText;
				var theResponse = JSON.parse(response);
				if (theResponse.wasOK) {
					apps.handleMassDataGrab(theResponse);
				}
			}
		});
	},
	
	handleMassDataGrab: function(theResponse) {
		if (theResponse.noteslist) Shimmer.knownNotesThemes = theResponse.noteslist;
		if (theResponse.allApps) apps.processReceivedAppList(theResponse.allApps, false);

		if (theResponse.apps && theResponse.apps.length>0) {
			var chosenApp = theResponse.apps[0];
		
			// Cache and Draw the Graph List for the chosen App
			if (chosenApp.params && chosenApp.graphs) {
				apps.appsHub.setGraphsAndParamsForAppWithID(chosenApp.id, chosenApp.params, chosenApp.graphs)
				boxes.redrawAllGraphChoosersForCurrentApp();
			}
		
			// Reload the Versions table for the chosen App
			if (chosenApp.allVersions) versions.reloadVersionsForApp_handleResponse(chosenApp.allVersions);
		
			// Draw the 4 Graphs for the chosen App
			if (chosenApp.stats) processGraphData(chosenApp.stats);
		}
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