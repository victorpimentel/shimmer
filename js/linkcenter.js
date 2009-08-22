linkcenter = {
	show: function() {
		// Load the app list (including masks, versions), and then show the form
		var knownApps = apps.knownAppNames();
		if (knownApps.length>0) {
			new Ajax.Request('?ajax', {
				method:'get',
				parameters: {
					action:	   'get.data.many',
					apps:      knownApps.join(','),
					versions:  true,
					masks:     true
				},
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);
					if (theResponse.wasOK) {
						if (theResponse.apps && theResponse.apps.length>0) {
							linkcenter.cache.setCache(theResponse.apps);
							linkcenter.setSelectedApp(apps.currentApp, false);
							linkcenter.showForm();
						} else {
							// TODO add showNoAppsForm() method
							linkcenter.showNoAppsForm();
						}
					}
				}
			});
		}
	},
	
	selectedApp: false,
	
	setSelectedApp: function(name, updateAppPopups) {
		if (typeof updateAppPopups == "undefined") updateAppPopups=true;
		linkcenter.selectedApp = name;
		if (updateAppPopups) {
			linkcenter.appcasts.updateAppSelect();
			linkcenter.downloads.updateAppSelect();
			linkcenter.notes.updateAppSelect();
			linkcenter.api.updateAppSelect();
		}
	},
	
	// For the supplied element ID, the correct option is chosen based on the stored current app name
	updateAppSelect: function(appSelectId) {
		var options = $$('#' + appSelectId + ' option');
		if (options && options.length>0) {
			for (var i=0; i < options.length; i++) {
				if (options[i].readAttribute('value')==linkcenter.selectedApp) {
					$(appSelectId).selectedIndex = i;
					break;
				}
			};
		}
	},
	
	fillAllMasks: function() {
		linkcenter.appcasts.fillMask();
		linkcenter.downloads.fillMask();
		linkcenter.notes.fillMask();
		linkcenter.api.fillMask();
	},
	
	slideToPane: function(paneNumber) {
		$$('div.linkcenter-pane').each( function(pane) {
			pane.setStyle({visibility:'visible'});
		});
		var leftValue = (paneNumber-1)*-310;
		new Effect.Move($('linkcenter-pane-holder'), {
			x:			leftValue,
			y:			0,
			mode:		'absolute',
			duration:	0.5,
			afterFinish:function() {
				// we hide all other panes so they can't be tabbed to using the keyboard
				var targetPane = 'linkcenter-pane-' + paneNumber;
				$$('div.linkcenter-pane').each( function(pane) {
					pane.setStyle({visibility:(pane.readAttribute('id')==targetPane ? 'visible' : 'hidden')});
				});
			}
		});
		$$('#focus_content ul#linkcenter-tabs li.linkcenter-tab').each( function(tab, index) {
			if (index+1 != paneNumber) {
				tab.removeClassName('active');
			} else {
				tab.addClassName('active');
			}
		});
	},
	
	showForm: function() {
		var code = '';
		code += '  <ul id="linkcenter-tabs">';
		code += '    <li class="linkcenter-tab active" id="linkcenter-tabs-appcast"><a href="#Appcast" onclick="linkcenter.slideToPane(1);return false;"><img src="img/tab_rss.png" /> Appcast</a></li>';
		code += '    <li class="linkcenter-tab" id="linkcenter-tabs-downloads"><a href="#Downloads" onclick="linkcenter.slideToPane(2);return false;"><img src="img/tab_import.png" /> Downloads</a></li>';
		code += '    <li class="linkcenter-tab" id="linkcenter-tabs-notes"><a href="#Notes" onclick="linkcenter.slideToPane(3);return false;"><img src="img/tab_notes.png" /> Notes</a></li>';
		code += '    <li class="linkcenter-tab last" id="linkcenter-tabs-api"><a href="#API" onclick="linkcenter.slideToPane(4);return false;"><img src="img/tab_backup.png" /> API</a></li>';
		code += '  </ul>';
		code += '  <div id="stat_edit">';
		code += '  <div id="linkcenter-pane-cutoff"><div id="linkcenter-pane-holder">';

		// PANE #1: APPCAST

		code += '    <div class="linkcenter-pane" id="linkcenter-pane-1">';
		code += '      <h6 class="first">Appcast</h6>';
		code += '      <div class="linkcenter-msg">';
		code += '        The appcast location for ' + linkcenter.cache.appSelectCode(linkcenter.appcasts.appSelectId);
		code += '      </div>';
		code += '      <table>';
		code += '        <tr><td><input type="text" id="' + linkcenter.appcasts.resultLocation + '" value="" /></td></tr>';
		code += '      </table>';
		code += '    </div>';

		// PANE #2: DOWNLOADS

		code += '    <div class="linkcenter-pane" id="linkcenter-pane-2">';
		code += '      <h6 class="first">Downloads</h6>';
		code += '      <div class="linkcenter-msg">';
		code += '        Give the following download link to your users:';
		code += '      </div>';
		code += '      <table>';
		code += '        <tr>';
		code += '          <th>App</th>';
		code += '          <td><div id="' + linkcenter.downloads.appSelectContainer + '">' + linkcenter.cache.appSelectCode(linkcenter.downloads.appSelectId) + '</div></td>';
		code += '        </tr>';
		code += '        <tr>';
		code += '          <th>Version</th>';
		code += '          <td><div id="' + linkcenter.downloads.versionSelectContainer + '">' + linkcenter.cache.versionSelectCode(linkcenter.cache.firstAppName(), linkcenter.downloads.versionSelectId, 'linkcenter.downloads.fillMask();') + '</div></td>';
		code += '        </tr>';
		code += '      </table>';
		code += '      <table>';
		code += '        <tr><td><input type="text" id="' + linkcenter.downloads.resultLocation + '" value="" /></td></tr>';
		code += '      </table>';
		code += '    </div>';

		// PANE #3: NOTES

		code += '    <div class="linkcenter-pane" id="linkcenter-pane-3">';
		code += '      <h6 class="first">Release Notes</h6>';
		code += '      <div class="linkcenter-msg">';
		code += '        Give the following release notes link to your users:';
		code += '      </div>';
		code += '      <table>';
		code += '        <tr>';
		code += '          <th>App</th>';
		code += '          <td><div id="' + linkcenter.notes.appSelectContainer + '">' + linkcenter.cache.appSelectCode(linkcenter.notes.appSelectId) + '</div></td>';
		code += '        </tr>';
		code += '        <tr>';
		code += '          <th>Version</th>';
		code += '          <td><div id="' + linkcenter.notes.versionSelectContainer + '">' + linkcenter.cache.versionSelectCode(linkcenter.cache.firstAppName(), linkcenter.notes.versionSelectId, 'linkcenter.notes.fillMask();') + '</div></td>';
		code += '        </tr>';
		code += '        <tr>';
		code += '          <th>Compare</th>';
		code += '          <td><div id="' + linkcenter.notes.compareSelectContainer + '">' + linkcenter.cache.versionSelectCode(linkcenter.cache.firstAppName(), linkcenter.notes.compareSelectId, 'linkcenter.notes.fillMask();') + '</div></td>';
		code += '        </tr>';
		code += '      </table>';
		code += '      <table>';
		code += '        <tr><td><input type="text" id="' + linkcenter.notes.resultLocation + '" value="" /></td></tr>';
		code += '      </table>';
		code += '    </div>';
		
		// PANE #4: API

		code += '    <div class="linkcenter-pane" id="linkcenter-pane-4">';
		code += '      <h6 class="first">API</h6>';
		
		code += '      <table>';
		code += '        <tr>';
		code += '          <th>Method</th>';
		code += '          <td><select id="' + linkcenter.api.methodSelectId + '">';
		code += '            <option value="version.info">version.info</option>';
		code += '            <option disabled>...more coming soon</option>';
		code += '          </select></td>';
		code += '        </tr>';
		code += '      </table>';
		
		code += '      <div id="' + linkcenter.api.configContainerId + '">';
		code +=          linkcenter.api.versionInfoHTML();
		code += '      </div>';
		
		code += '      <table>';
		code += '        <tr><td><input type="text" id="' + linkcenter.api.resultLocation + '" value="" /></td></tr>';
		code += '      </table>';
		
		code += '    </div>';
		
		// SAVE ROW
		
		code += '    </div>';
		code += '    <div id="save_focus">';

		code += focusBox.doneButtonCode('focusBox.hide();return false;');
		code += '    </div>';
		code += '  </div>';
		// code += '</form>';

		focusBox.present({titleString:"Link Center", titleImage:'title_linkcenter.png', content:code, width:312, beforeDisplay:linkcenter.fillAllMasks, autofocus:false});
	},
	
	// CACHE: Collection of methods for storing, processing and reading cached app info.
	cache: {
		// Stored list of apps
		cachedApps: [],
		
		// Updates the cachedApps variable
		setCache: function(cacheData) {
			linkcenter.cache.cachedApps = cacheData;
		},
		
		// Process the cachedApps variable, returning an array of app name strings
		appNames: function() {
			var names = [];
			for (var i=0; i < linkcenter.cache.cachedApps.length; i++) {
				var theName = linkcenter.cache.cachedApps[i].name;
				if (theName && theName.length>0) names.push(theName);
			};
			return names;
		},
		
		appSelectCode: function(id, includeAllOption) {
			if (typeof includeAllOption == "undefined") includeAllOption=false;
			var code = '<select id=' + id + ' onchange="linkcenter.setSelectedApp(this.value);">';

			if (includeAllOption) code += '<option value="all">All Apps</option><hr />';

			var names = linkcenter.cache.appNames();
			for (var i=0; i < names.length; i++) {
				code += '<option value="' + names[i] + '"' + (linkcenter.selectedApp==names[i] ? 'selected':'') + '>' + names[i] + '</option>';
			};
			code += '</select>';
			return code;
		},
		
		versionNumbers: function(appName) {
			var versions = [];
			for (var i=0; i < linkcenter.cache.cachedApps.length; i++) {
				if (linkcenter.cache.cachedApps[i].name==appName) {
					var app = linkcenter.cache.cachedApps[i];
					var foundVersions = app.allVersions;
					if (foundVersions && foundVersions.length>0) {
						for (var v=0; v < foundVersions.length; v++) {
							var currentVersion = foundVersions[v];
							var number = currentVersion.version;
							var build  = currentVersion.build;
							var text   = number;
							if (build && build.length>0) text += ' (build ' + build + ')';
							versions.push({version:number, build:build, text:text});
						};
					}
					break;
				}
			};
			return versions;
		},
		
		versionSelectCode: function(appName, id, change, extraOptions) {
			if (typeof extraOptions == "undefined") extraOptions=false;
			var code = '<select id="' + id + '" onchange="' + change + '">';
			if (extraOptions) {
				for (var i=0; i < extraOptions.length; i++) {
					code += '<option value="' + extraOptions[i][0] + '">' + extraOptions[i][1] + '</option>';
				};
				code += '<hr />';
			}
			var versions = linkcenter.cache.versionNumbers(appName);
			for (var i=0; i < versions.length; i++) {
				code += '<option>' + versions[i].text + '</option>';
			};
			code += '</select>'
			return code;
		},
		
		masks: function(appName) {
			for (var i=0; i < linkcenter.cache.cachedApps.length; i++) {
				if (linkcenter.cache.cachedApps[i].name==appName) {
					var app = linkcenter.cache.cachedApps[i];
					var foundMasks = app.masks;
					if (foundMasks && foundMasks.download && foundMasks.notes) {
						return {download:foundMasks.download, notes:foundMasks.notes};
					}
				}
			};
			return false;
		},
		
		firstAppName: function() {
			if (linkcenter.cache.cachedApps.length>0) return linkcenter.cache.cachedApps[0].name;
			return false;
		}
	},
	
	appcasts: {
		appSelectId: 'linkcenter-appcast-app-select',
		resultLocation: 'appcast-location-result',
		
		selectedApp: function() {
			return $(linkcenter.appcasts.appSelectId).value;
		},
		
		fillMask: function() {
			var appName = linkcenter.selectedApp;
			var mask    = Shimmer.util.baseLocation() + "?appcast&app=_APP_";
			mask = mask.replace(/_APP_/g, appName);
			mask = mask.replace(/ /g, '%20');
			$(linkcenter.appcasts.resultLocation).value = mask;
		},
		
		updateAppSelect: function() {
			linkcenter.updateAppSelect(linkcenter.appcasts.appSelectId);
			linkcenter.appcasts.fillMask();
		}
	},
	
	downloads: {
		appSelectContainer: 'linkcenter-download-app-select-container',
		appSelectId: 'linkcenter-download-app-select',
		
		versionSelectContainer: 'linkcenter-download-version-select-container',
		versionSelectId: 'linkcenter-download-version-select',
		
		resultLocation: 'download-location-result',
		
		selectedApp: function() {
			return $(linkcenter.downloads.appSelectId).value;
		},
		
		// Returns a two-element object: {version:'1.0', build:'2345'}
		selectedVersion: function() {
			var origValue = $(linkcenter.downloads.versionSelectId).value;
			if (origValue.indexOf(' (build ')>-1) {
				var parts     = origValue.split(' (build ');
				var buildPart = parts[1];
				return {version:parts[0], build:buildPart.substring(0, buildPart.length-1)};
			} else {
				return {version: origValue, build:''};
			}
		},
		
		refreshVersions: function() {
			$(linkcenter.downloads.versionSelectContainer).innerHTML = linkcenter.cache.versionSelectCode(linkcenter.selectedApp, linkcenter.downloads.versionSelectId ,'linkcenter.downloads.fillMask()');
		},
		
		fillMask: function() {
			var appName     = linkcenter.selectedApp;
			var versionInfo = linkcenter.downloads.selectedVersion();
			var mask = linkcenter.cache.masks(appName).download;
			mask = mask.replace(/_APP_/g, appName);
			mask = mask.replace(/_VER_/g, versionInfo.version);
			mask = mask.replace(/_BUILD_/g, versionInfo.build);
			mask = mask.replace(/ /g, '%20');
			$(linkcenter.downloads.resultLocation).value = mask;
		},
		
		updateAppSelect: function() {
			linkcenter.updateAppSelect(linkcenter.downloads.appSelectId);
			linkcenter.downloads.refreshVersions();
			linkcenter.downloads.fillMask();
		}
		
	},
	
	notes: {
		appSelectContainer: 'linkcenter-notes-app-select-container',
		appSelectId: 'linkcenter-notes-app-select',
		
		versionSelectContainer: 'linkcenter-notes-version-select-container',
		versionSelectId: 'linkcenter-notes-version-select',

		compareSelectContainer: 'linkcenter-notes-compare-select-container',
		compareSelectId: 'linkcenter-notes-compare-select',
		
		resultLocation: 'notes-location-result',
		
		selectedApp: function() {
			return $(linkcenter.notes.appSelectId).value;
		},
		
		// Returns a two-element object: {version:'1.0', build:'2345'}
		selectedVersion: function() {
			var origValue = $(linkcenter.notes.versionSelectId).value;
			if (origValue.indexOf(' (build ')>-1) {
				var parts     = origValue.split(' (build ');
				var buildPart = parts[1];
				return {version:parts[0], build:buildPart.substring(0, buildPart.length-1)};
			} else {
				return {version: origValue, build:''};
			}
		},
		
		// Returns a two-element object: {version:'1.0', build:'2345'}
		// TODO handle selection of 'none'
		selectedCompare: function() {
			var origValue = $(linkcenter.notes.compareSelectId).value;
			if (origValue.indexOf(' (build ')>-1) {
				var parts     = origValue.split(' (build ');
				var buildPart = parts[1];
				return {version:parts[0], build:buildPart.substring(0, buildPart.length-1)};
			} else {
				return {version: origValue, build:''};
			}
		},
		
		refreshVersions: function() {
			$(linkcenter.notes.versionSelectContainer).innerHTML = linkcenter.cache.versionSelectCode(linkcenter.selectedApp, linkcenter.notes.versionSelectId ,'linkcenter.notes.fillMask()');
			// TODO make compare selector have 'none' item at top
			$(linkcenter.notes.compareSelectContainer).innerHTML = linkcenter.cache.versionSelectCode(linkcenter.selectedApp, linkcenter.notes.compareSelectId ,'linkcenter.notes.fillMask()');
		},
		
		fillMask: function() {
			var appName     = linkcenter.selectedApp;
			var versionInfo = linkcenter.notes.selectedVersion();
			var compareInfo = linkcenter.notes.selectedCompare();
			var mask = linkcenter.cache.masks(appName).notes;
			mask = mask.replace(/_APP_/g, appName);
			mask = mask.replace(/_VER_/g, versionInfo.version);
			mask = mask.replace(/_BUILD_/g, versionInfo.build);
			mask = mask.replace(/ /g, '%20');
			
			mask += (mask.match(/\?([^=]+(=[^&]*)?)(&[^=]+(=[^&]*)?)*$/) ? '&' : '?') + 'appVersion=' + compareInfo.version;
			if (compareInfo.build && compareInfo.build.length>0) mask += '&appBuild=' + compareInfo.build;
			$(linkcenter.notes.resultLocation).value = mask;
		},
		
		updateAppSelect: function() {
			linkcenter.updateAppSelect(linkcenter.notes.appSelectId);
			linkcenter.notes.refreshVersions();
			linkcenter.notes.fillMask();
		}
		

	},
	
	api: {
		appSelectContainer:     'linkcenter-api-app-select-container',
		appSelectId:            'linkcenter-api-app-select',
		methodSelectId:         'linkcenter-api-method-select',
		configContainerId:      'linkcenter-api-config-container',
		versionSelectContainer: 'linkcenter-api-version-select-container',
		versionSelectId:        'linkcenter-api-version-select',
		resultLocation:         'linkcenter-api-result',
		callbackId:             'linkcenter-api-callback',
		fieldSelectClass:       'abcd',
		
		selectedMethod: function() { return $(linkcenter.api.methodSelectId).value; },
		selectedApp:    function() { return $(linkcenter.api.appSelectId).value;    },
		
		updateAppSelect: function() {
			linkcenter.updateAppSelect(linkcenter.api.appSelectId);
			linkcenter.api.refreshVersions();
			linkcenter.api.fillMask();
		},
		
		refreshConfigOptions: function() {
			var method = linkcenter.api.selectedMethod();
			if (method=='version.info') {
				$(linkcenter.api.configContainerId).innerHTML = linkcenter.api.versionInfoHTML();
			}
		},
		
		refreshVersions: function() {
			$(linkcenter.api.versionSelectContainer).innerHTML = linkcenter.cache.versionSelectCode(linkcenter.selectedApp, linkcenter.api.versionSelectId ,'linkcenter.api.fillMask()', linkcenter.api.extraVersionLabels);
		},
		
		extraVersionLabels: [
								['', 'All Versions'],
								['latest', 'Newest'],
								['oldest', 'Oldest'],
							],
		
		versionInfoHTML: function() {
			var code = "";
			code += '<table>';
			code += '  <tr>';
			code += '    <th>App</th>';
			code += '    <td><div id="' + linkcenter.api.appSelectContainer + '">' + linkcenter.cache.appSelectCode(linkcenter.api.appSelectId) + '</div></td>';
			code += '  </tr>';
			code += '  <tr>';
			code += '    <th>Version</th>';
			code += '    <td><div id="' + linkcenter.api.versionSelectContainer + '">' + linkcenter.cache.versionSelectCode(linkcenter.selectedApp, linkcenter.api.versionSelectId, 'linkcenter.api.fillMask();', linkcenter.api.extraVersionLabels) + '</div></td>';
			code += '  </tr>';
			code += '  <tr>';
			code += '    <th valign="top">Fields</th>';
			code += '    <td>';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-version" value="version" onclick="linkcenter.api.fillMask()" /> <label for="api-check-version">Version</label><br />';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-build" value="build" onclick="linkcenter.api.fillMask()" /> <label for="api-check-build">Build</label><br />';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-size" value="size" onclick="linkcenter.api.fillMask()" /> <label for="api-check-size">Size</label><br />';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-signature" value="signature" onclick="linkcenter.api.fillMask()" /> <label for="api-check-signature">Signature</label><br />';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-notes" value="notes" onclick="linkcenter.api.fillMask()" /> <label for="api-check-notes">Notes</label><br />';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-users" value="users" onclick="linkcenter.api.fillMask()" /> <label for="api-check-users">User Count</label><br />';
			code += '      <input type="checkbox" CHECKED class="' + linkcenter.api.fieldSelectClass + '" id="api-check-downloads" value="downloads" onclick="linkcenter.api.fillMask()" /> <label for="api-check-downloads">Download Count</label><br />';
			code += '    </td>';
			code += '  </tr>';
			code += '  <tr>';
			code += '    <th>Callback</th>';
			code += '    <td><input type="text" id="' + linkcenter.api.callbackId + '" placeholder="none" onchange="linkcenter.api.fixCallback();linkcenter.api.fillMask();" /></td>';
			code += '  </tr>';
			code += '</table>';
			return code;
		},
		
		selectedVersion: function() {
			var origValue = $(linkcenter.api.versionSelectId).value;
			if (origValue.indexOf(' (build ')>-1) {
				var parts     = origValue.split(' (build ');
				var buildPart = parts[1];
				return {version:parts[0], build:buildPart.substring(0, buildPart.length-1)};
			} else {
				return {version: origValue, build:''};
			}
		},
		
		selectedFields: function() {
			var fields = [];
			$$('.' + linkcenter.api.fieldSelectClass).each( function(field) {
				if (field.checked) fields.push(field.value);
			});
			return fields;
		},
		
		fixCallback: function() {
			var callback  = $(linkcenter.api.callbackId).value;
			callback = callback.replace(/[^a-zA-Z0-9]/g, '');
			$(linkcenter.api.callbackId).value = callback;
		},
		
		fillMask: function() {
			var method = linkcenter.api.selectedMethod();
			if (method=='version.info') {
				var appName     = linkcenter.selectedApp;
				var versionInfo = linkcenter.api.selectedVersion();
				var fields      = linkcenter.api.selectedFields();
				var callback    = $(linkcenter.api.callbackId).value;
				
				var mask = Shimmer.util.baseLocation() + "?api&app=_APP_&method=version.info&version=_VER_&build=_BUILD_&fields=_FIELDS_&callback=_CALLBACK_";
				mask = mask.replace(/_APP_/g,      appName);
				mask = mask.replace(/_VER_/g,      versionInfo.version);
				mask = mask.replace(/_BUILD_/g,    versionInfo.build);
				mask = mask.replace(/_FIELDS_/g,   fields.join(','));
				mask = mask.replace(/_CALLBACK_/g, callback);
				mask = mask.replace(/ /g,          '%20');
				$(linkcenter.api.resultLocation).value = mask;
			}			
		}
	}

}