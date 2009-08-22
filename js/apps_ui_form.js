appsUI.form = {
	
	/////////////// SHOW APP FORM /////////////
	///// Show the form that allows the ///////
	///// user to create a new app,     ///////
	///// or edit an existing app       ///////
	///////////////////////////////////////////
	
	sparkleParameters: ["lang", "osVersion", "cputype", "cpu64bit", "cpusubtype", "model", "ncpu", "ramMB", "cpuFreqMHz"],
	sparkleGraphs: ["sparkle-language", "sparkle-major-os", "sparkle-os", "sparkle-ram", "sparkle-cpu-count", "sparkle-cpu-type", "sparkle-cpu-frequency", "sparkle-computer-model"],
 
	startForm: function(appName) {
		if (appName) {
			new Ajax.Request('?ajax', {
				parameters: {
					action:      'get.data.many',
					app:         appName,
					versions:    true,
					graphs:      true,
					masks:       true,
					notestheme:  true,
					usessparkle: true,
					identifier:  true,
					keyStatus:   true
				},
				method:'get',
				onSuccess: function(transport) {
					var response = transport.responseText;
					var theResponse = JSON.parse(response);
					if (theResponse.wasOK && theResponse.apps && theResponse.apps.length>0) {
						var theApp = theResponse.apps[0];
						appsUI.form.versionImporter.knownVersions = (theApp.allVersions ? theApp.allVersions : false);
						appsUI.form.showForm(appName, theApp.params, theApp.graphs, theApp.notesTheme, theApp.masks.notes, theApp.masks.download, theApp.usesSparkle, false, theApp.identifier, theApp.keystatus);
					}
				}
			});
		} else {
			appsUI.form.versionImporter.knownVersions = new Array();
			appsUI.form.showForm('New App', false, false, false, '', '', true, true, '');
		}
	},
	
	slideToPane: function(paneNumber) {
		$$('div.appsform-pane').each( function(pane) {
			pane.setStyle({visibility:'visible'});
		});
		var leftValue = (paneNumber-1)*-360;
		new Effect.Move($('appsform-pane-holder'), {
			x:			leftValue,
			y:			0,
			mode:		'absolute',
			duration:	0.5,
			afterFinish:function() {
				// we hide all other panes so they can't be tabbed to using the keyboard
				var targetPane = 'appsform-pane-' + paneNumber;
				$$('div.appsform-pane').each( function(pane) {
					pane.setStyle({visibility:(pane.readAttribute('id')==targetPane ? 'visible' : 'hidden')});
				});
			}
		});
		$$('#focus_content ul#appsform-tabs li.appsform-tab').each( function(tab, index) {
			if (index+1 != paneNumber) {
				tab.removeClassName('active');
			} else {
				tab.addClassName('active');
			}
		});
	},
	
	showForm: function(appName, params, graphs, notesTheme, notesMask, downloadMask, usesSparkle, isNew, identifier, keyStatus) {
		var code = "";
		code += '  <ul id="appsform-tabs">';
		code += '    <li class="appsform-tab active" id="appsform-tabs-appdetails"><a href="#AppDetails" onclick="appsUI.form.slideToPane(1);return false;"><img src="img/gear.png" /> App Details</a></li>';
		code += '    <li class="appsform-tab" id="appsform-tabs-stats"><a href="#Stats" onclick="appsUI.form.slideToPane(2);return false;"><img src="img/tab_graph.png" /> Parameters &amp; Graphs</a></li>';
		code += '    <li class="appsform-tab last" id="appsform-tabs-import"><a href="#Import" onclick="appsUI.form.slideToPane(3);return false;"><img src="img/tab_import.png" /> Import Versions</a></li>';
		code += '  </ul>';
		code += '  <input type="hidden" id="existingAppName" value="' + appName + '" />';
		code += '  <div id="stat_edit">';
		code += '  <div id="appsform-pane-cutoff"><div id="appsform-pane-holder">';
		
		// PANE #1: APP DETAILS
		
		code += '    <div class="appsform-pane" id="appsform-pane-1">';
		code += '      <h6 class="first">App Details</h6>';
		code += '      <table>';
		code += '        <tr><th>Name</th><td><input type="text" id="appedit_name" value="' + appName + '"></td></tr>';
		code += '        <tr><th>Notes</th><td><select title="Choose the notes template used when displaying Release Notes" id="notes-theme-chooser"><option value="default.shimmer.xml">Default</option>';
		
		if (Shimmer.knownNotesThemes) {
			code += '<hr />';
			Shimmer.knownNotesThemes.each( function(theme) {
				if (theme.file != 'default.shimmer.xml') {
					code += '<option value="' + theme.file + '"' + (theme.file==notesTheme ? ' SELECTED' : '') + '>' + theme.name + '</option>';
				}
			});
		}
		
		code += '        </select></td></tr>';
		code += '        <tr><th>Sparkle</th><td><input type="checkbox" id="app-uses-sparkle" onclick="appsUI.form.toggleSparkleMessages();" ' + (usesSparkle ? ' CHECKED' : '') + ' /><label for="app-uses-sparkle">App uses Sparkle</label></td></tr>';
		code += '        <tr><th>Identifier</th><td><select id="app-identifier">';
		code += '          <option value="ip">IP Address</option>';
		
		if (params) {
			var identifierOptions = "";
			for (var i=0; i < params.length; i++) {
				if (appsUI.form.sparkleParameters && appsUI.form.sparkleParameters.indexOf(params[i])<0) {
					identifierOptions += '<option value="' + params[i] + '"' + (identifier==params[i] ? ' SELECTED' : '') + '>' + params[i] + '</option>';
				}
			};
			if (identifierOptions.length>0) code += '<hr />' + identifierOptions;
		}
		
		code += '        </select></td></tr>';
		code += '      </table>';
		
		var pubSet  = false;
		var privSet = false;
		if (keyStatus) {
			pubSet  = keyStatus.public;
			privSet = keyStatus.private;
		}
		
		code += '      <h6>DSA Keys <a href="#GenerateDSA" id="generate-dsa-link" class="focus-right-link" onclick="return false;" style="opacity:0.3;">Generate DSA Keys</a></h6>';		
		code += '      <table>';
		
		// Public Key
		code += '        <tr><th>Public&nbsp;Key</th><td>';
		code += '          <form action="?ajax&type=dsa&action=upload.public" method="post" target="' + dsa.publicFrame + '" id="' + dsa.publicForm + '" enctype="multipart/form-data">';
		code += '            <span id="' + dsa.publicKeyStatusId + '">';
		if (!pubSet) {
			code += '<a href="#ChooseDSA" onclick="dsa.showChooser(dsa.pub);return false;" class="dsa-choose-link">Choose...</a>';
		} else {
			code += 'Already Set. <a href="#ChooseDSA" onclick="dsa.showChooser(dsa.pub);return false;" class="dsa-choose-link">Choose again...</a>';
		}
		code += '            </span>';
		code += '            <input type="hidden" id="' + dsa.publicTimestampInput + '" name="' + dsa.timestampInputBase + '" />';
		code += '            <input type="hidden" id="' + dsa.publicUsedSessionKeyInput + '" name="' + dsa.publicUsedSessionKeyInput + '" value="" />';
		code += '            <input type="file" value="/" id="' + dsa.publicKeyInput + '" name="' + dsa.publicKeyInput + '" onchange="dsa.initUpload(dsa.pub)" style="display:none" />';
		code += '          </form>';
		code += '          <iframe name="' + dsa.publicFrame + '" id="' + dsa.publicFrame + '" src="" style="border:0;height:0;width:0;padding:0;position:absolute;visibility:hidden;"></iframe>';
		code += '        </td></tr>';
		
		// Private Key
		code += '        <tr><th>Private&nbsp;Key</th><td>';
		code += '          <form action="?ajax&type=dsa&action=upload.private" method="post" target="' + dsa.privateFrame + '" id="' + dsa.privateForm + '" enctype="multipart/form-data">';
		code += '            <span id="' + dsa.privateKeyStatusId + '">';
		if (!privSet) {
			code += '<a href="#ChooseDSA" onclick="dsa.showChooser(dsa.priv);return false;" class="dsa-choose-link">Choose...</a>';
		} else {
			code += 'Already Set. <a href="#ChooseDSA" onclick="dsa.showChooser(dsa.priv);return false;" class="dsa-choose-link">Choose again...</a>';
		}
		code += '            </span>';
		code += '            <input type="hidden" id="' + dsa.privateTimestampInput + '" name="' + dsa.timestampInputBase + '" />';
		code += '            <input type="hidden" id="' + dsa.privateUsedSessionKeyInput + '" name="' + dsa.privateUsedSessionKeyInput + '" value="" />';
		code += '            <input type="file" id="' + dsa.privateKeyInput + '" name="' + dsa.privateKeyInput + '" onchange="dsa.initUpload(dsa.priv)" style="display:none" />';
		code += '          </form>';
		code += '          <iframe name="' + dsa.publicFrame  + '" id="' + dsa.publicFrame  + '" src="" style="border:0;height:0;width:0;padding:0;position:absolute;visibility:hidden;"></iframe>';
		code += '          <iframe name="' + dsa.privateFrame + '" id="' + dsa.privateFrame + '" src="" style="border:0;height:0;width:0;padding:0;position:absolute;visibility:hidden;"></iframe>';
		code += '        </td></tr>';
		code += '      </table>';



		code += '      <h6>URL Masks <a href="#TestMasks" id="test-masks-link" class="focus-right-link" onclick="apps.testMasks();return false;">Tests Masks...</a><span id="test-masks-message" style="display:none">Testing Masks...</span></h6>';
		code += '      <table>';
		code += '        <tr><th>Download</th><td><input type="text" id="download-mask" value="'	+ downloadMask	+ '" onkeyup="$(\'test-masks-message\').hide();$(\'test-masks-link\').show();" /></td></tr>';
		code += '        <tr><th>Notes</th><td><input type="text" id="notes-mask" value="'	+ notesMask		+ '" onkeyup="$(\'test-masks-message\').hide();$(\'test-masks-link\').show();" /></td></tr>';
		code += '      </table>';
		code += '    </div>';
		
		// PANE #2: PARAMETERS & GRAPHS
		
		code += '    <div class="appsform-pane" id="appsform-pane-2">';
		code += '      <h6 class="first">Parameters <span id="sparkle-parameters" class="sparkle-message">Sparkle Parameters added</span></h6>';
		code += '      <ul class="param-or-graph-list" id="parameter-listing">';
		
		if (params) {
			for (var i=0; i < params.length; i++) {
				if (appsUI.form.sparkleParameters && appsUI.form.sparkleParameters.indexOf(params[i])<0) {
					code += appsUI.form.params.showNewParameterWithDetails(params[i],false,true,true);
				}
			};
		}

		code += '      </ul>';
		code += '      <a href="#AddParameter" onclick="javascript:appsUI.form.params.showNewParameter();return false;" class="add-stat-plus">New Parameter...</a>';

		code += '      <h6>Graphs <span id="sparkle-graphs" class="sparkle-message">Sparkle Graphs added</span></h6>';
		code += '      <ul class="param-or-graph-list" id="graph-listing">';
		
		if (graphs) {
			for (var i=0; i < graphs.length; i++) {
				if (appsUI.form.sparkleGraphs && appsUI.form.sparkleGraphs.indexOf(graphs[i].id)<0) {
					var theGraphID = (graphs[i].id ? graphs[i].id : false);
					code += appsUI.form.graphs.showNewGraphWithDetails(graphs[i].name,graphs[i].type,false,params,graphs[i].parameter,graphs[i].values,true,theGraphID);
				}
			};
		}

		code += '      </ul>';
		code += '      <a href="#AddGraph" onclick="javascript:appsUI.form.graphs.showNewGraph();return false;" class="add-stat-plus">New Graph...</a>';
		code += '    </div>';
		
		// PANE #3: IMPORT VERSIONS
		
		code += '    <div class="appsform-pane" id="appsform-pane-3">';
		code += '      <h6 class="first">Import Versions from Appcast</h6>';
		code += '    <div class="param-or-graph-msg">';
		code += '      Use your existing appcast to import version info.';
		code += '    </div>'
		code += '    <div id="appcast-import-container">';
		code += '      <form class="stat_edit_form" action="?ajax" target="_new">';
		code += '        <table><tr><td><input type="text" id="appedit_appcast" value="http://scrobblepod.com/appcast/all" spellcheck="false" /></td><td><input type="submit" onclick="javascript:appsUI.form.versionImporter.startFetch();return false;" id="get-versions-button" value="Get Versions" /></td></tr></table>';
		code += '      </form>';
		code += '      <div id="import-version-url-display" style="display:none"><span id="import-version-url-display-text">Versions for http://yourapp.com/appcast.xml</span></div>';
		code += '      <div id="select-import-versions" style="display:none">';
		code += '        <div id="select-import-versions-list">Fetching list of versions...</div>';
		code += '      </div>';
		code += '      <div id="import-version-count" style="opacity:0.0;"><span id="import-version-count-number">0 versions</span> will be imported when saving.</div>';
		code += '    </div>';
		code += '  </div></div>';
		code += '    <div id="save_focus">';
		
		
		if (isNew) {
			code += focusBox.standardSaveCode('appsUI.form.save.saveNewApp();return false;');
		} else {
			code += focusBox.standardSaveCode('appsUI.form.save.saveEditedApp();return false;');
		}
		code += '    </div>';
		code += '  </div>';

		appsUI.form.versionImporter.lastAppcast = false;

		if (isNew) {
			focusBox.present({titleString:"New Application", titleImage:'title_newapplication.png', content:code, width:362});
		} else {
			focusBox.present({titleString:"Edit Application", titleImage:'title_editapplication.png', content:code, width:362});
		}
		
		appsUI.form.toggleSparkleMessages(false);
	},
	
	toggleSparkleMessages: function(showMessage) {
		if (typeof showMessage == "undefined") showMessage=true;
		if ($('app-uses-sparkle').checked) {
			$('sparkle-parameters').show();
			$('sparkle-graphs').show();
			if (showMessage) notify.update('Sparkle stats have been added to the \'Parameters &amp; Graphs\' tab', 5);
		} else {
			$('sparkle-parameters').hide();
			$('sparkle-graphs').hide();
			if (showMessage) notify.update('Sparkle stats have been removed from the \'Parameters &amp; Graphs\' tab', 5);
		}
	},
	
	////////////// VERSION IMPORTER ////////////
	///// Functions that control the       /////
	///// importing, display and selection /////
	///// of external appcast versions     /////
	////////////////////////////////////////////

	versionImporter: {
		versionContainer:	false,
		contentContainer:	false,
		isUpdating:			false,
		isAnimating:		false,
		lastAppcast:		false,
		knownVersions:		false,
		
		// Starts the ball rolling
		startFetch: function() {
			if (!this.isUpdating) {
				this.isUpdating = true;
				this.versionContainer = $('select-import-versions');
				this.contentContainer = $('select-import-versions-list');
				this.fetchVersions();
				this.hideCurrentURL();
			}
		},
		
		// Show the 'fetching...' message, ask server for version list
		fetchVersions: function() {
			appsUI.form.versionImporter.tally.hideSentence(function() {
				appsUI.form.versionImporter.showMessage('<span class="small-message">Fetching versions from appcast...</span>', function() {
					var fetchURL = $('appedit_appcast').value;
					new Ajax.Request('?ajax', {
						method:'post',
						parameters: { action: 'appcast.getversions', url:fetchURL },
				    	onSuccess: function(transport) {
							var response = transport.responseText;
							var theResponse = JSON.parse(response);
							if (theResponse.wasOK) {
								appsUI.form.versionImporter.showCurrentURL(fetchURL);
								appsUI.form.versionImporter.lastAppcast = fetchURL;
								var versions = theResponse.versions;
								if (versions.length>0) {
									var optionsHTML = '<table id="select-import-versions-table" cellpadding="0" cellspacing="0">';
									for (var i=0; i<versions.length; i++) {
										var version = versions[i];
										var disabled = ((appsUI.form.versionImporter.knownVersions && appsUI.form.versionImporter.knownVersions.indexOf(version) >- 1) ? true : false);
										optionsHTML += '<tr class="' + (i%2!=0 ? 'alternate' : '') + (i==0 ? ' first' : '') + '">';
										optionsHTML += '<td width="1%" class="checkbox"><input type="checkbox" id="' + version + '" class="select_app_checkbox" value="' + version + '" ' + (disabled ? 'DISABLED' : 'CHECKED') + '></td>';
										optionsHTML += '<td class="import-label' + (disabled ? '  disabled' : '') + '"><label for="' + version + '">' + version + '</label></td>';
										optionsHTML += '</tr>';
									}
									optionsHTML += '</table>';
									appsUI.form.versionImporter.showMessage(optionsHTML, function() {
										var allCheckboxes = $$('#select-import-versions input');
										if (allCheckboxes.length>0) {
											allCheckboxes.each( function(checkbox) {
												checkbox.observe('change', function() {
													appsUI.form.versionImporter.tally.update();
												});
											});
											allCheckboxes[0].focus();
										}
										appsUI.form.versionImporter.tally.update(true, function() {
											appsUI.form.versionImporter.isUpdating = false;
										});
									});
								} else {
									appsUI.form.versionImporter.showMessage('<span class="small-error">No versions found</span>', function() {
										appsUI.form.versionImporter.tally.update(true, function() {
											appsUI.form.versionImporter.isUpdating = false;
										})
									});
								}
							} else {
								appsUI.form.versionImporter.showMessage('<span class="small-error">Could not fetch versions</span>', function() {
									appsUI.form.versionImporter.tally.update(true, function() {
										appsUI.form.versionImporter.isUpdating = false;
									})
								});
							}
						},
						onFailure: function(transport) {
							appsUI.form.versionImporter.showMessage('<span class="small-error">Could not fetch versions</span>', function() {
								appsUI.form.versionImporter.tally.update(true, function() {
									appsUI.form.versionImporter.isUpdating = false;
								})
							});
						}
					});
				});
			});
		},
		
		selectedVersions: function() {
			var versionList = new Array();

			var allCheckboxes = $$('#select-import-versions input');
			if (allCheckboxes.length>0) {
				allCheckboxes.each( function(checkbox) {
					if (checkbox.checked) {
						var versionNumber = checkbox.readAttribute('value');
						if (versionNumber) versionList.push(versionNumber);
					}
				});
			}
			
			return versionList;
		},
		
		// Convenience: Show a message, slide up old message first if needed
		showMessage: function(message, callback) {
			if (typeof callback	== "undefined")	callback = false;
			var updateFunction = function() { appsUI.form.versionImporter.slideContainerDown(message, callback); }
			if (appsUI.form.versionImporter.versionContainer.visible()) {
				appsUI.form.versionImporter.slideContainerUp(updateFunction);
			} else {
				updateFunction();
			}
		},
		
		// Utility: Slide up the current message
		slideContainerUp: function(callback) {
			this.isAnimating = true;
			if (typeof callback == "undefined") callback=false;
			
			slideParams = {duration: 0.4};
			if (callback) slideParams.afterFinish = function() {
				appsUI.form.versionImporter.isAnimating = false;
				if (callback) callback();
			}
			
			new Effect.BlindUp(this.versionContainer, slideParams);
		},
		
		// Utility: Slide down a new message
		slideContainerDown: function (message, callback) {
			this.isAnimating = true;
			if (typeof message	== "undefined")	message		= false;
			if (typeof callback	== "undefined")	callback	= false;
			
			slideParams = {duration: 0.4};
			slideParams.afterFinish = function() {
				appsUI.form.versionImporter.isAnimating = false;
				if (callback) callback();
			}
			
			if (message) this.contentContainer.innerHTML = message;
			new Effect.BlindDown(this.versionContainer, slideParams);
		},
		
		hideCurrentURL: function(callback) {
			if (typeof callback	== "undefined")	callback = false;
			var container = $('import-version-url-display');
			
			if (container.visible()) {
				var hideParams = { duration:0.3 };
				if (callback) hideParams.afterFinish = callback;
			
				new Effect.Parallel([
					new Effect.Fade(container,		{sync:true}),
					new Effect.SlideUp(container,	{sync:true})
				], hideParams);
			} else if (callback) {
				callback();
			}
		},
		
		showCurrentURL: function(url) {
			var container = $('import-version-url-display');
			var show = function() {
				$('import-version-url-display-text').innerHTML = 'Versions for ' + url;
				new Effect.Parallel([
					new Effect.Appear(container, 	{sync:true}),
					new Effect.BlindDown(container, {sync:true})
				], {duration:0.3});
			}
			if (container.visible()) {
				// hide
			} else show();
		},
		
		tally: {
			update: function(fade, callback) {
				appsUI.form.versionImporter.tally.showSentence(function() {
					if (typeof fade	== "undefined")	fade = true;
					if (typeof callback	== "undefined")	callback = false;
					var allCheckboxes = $$('#select-import-versions input');
					var tally = 0;
					if (allCheckboxes.length>0) {
						allCheckboxes.each( function(checkbox) {
							if (checkbox.checked) tally++;
						});
					}
				
					if (fade) {
						appsUI.form.versionImporter.tally.fade(function() {
							appsUI.form.versionImporter.tally.setTally(tally);
							appsUI.form.versionImporter.tally.appear(function() {
								appsUI.form.versionImporter.tally.update(false);
								if (callback) callback();
							});
						});
					} else {
						appsUI.form.versionImporter.tally.setTally(tally);
						if (callback) "Calling callback " + callback;
						if (callback) callback();
					}
				});
			},
			
			setTally: function(tally) {
				$('import-version-count-number').innerHTML = (tally==0 ? 'No' : tally) + ' version' + (tally==1 ? '' : 's');
			},
			
			fade: function(callback) {
				$('import-version-count-number').setStyle({opacity:'0.4'});
				if (callback) callback();
				// if (appsUI.form.versionImporter.isAnimating == false) {
				// 	appsUI.form.versionImporter.isAnimating = true;
				// 	if (typeof callback	== "undefined")	callback = false;
				// 	fadeParams = {duration: 0.3, to:0.6};
				// 	fadeParams.afterFinish = function() {
				// 		appsUI.form.versionImporter.isAnimating = false;
				// 		if (callback) callback();
				// 	}
				// 
				// 	new Effect.Fade($('import-version-count-number'), fadeParams);
				// }
			},
				
			appear: function(callback) {
				if (appsUI.form.versionImporter.isAnimating == false) {
					appsUI.form.versionImporter.isAnimating = true;
					if (typeof callback	== "undefined")	callback = false;
					fadeParams = {duration: 0.5, to:1.0};
					fadeParams.afterFinish = function() {
						appsUI.form.versionImporter.isAnimating = false;
						if (callback) callback();
					}

					new Effect.Appear('import-version-count-number', fadeParams);
				}
			},
			
			showSentence: function(callback) {
				if (typeof callback	== "undefined")	callback = false;
				if (!parseInt($('import-version-count').getStyle('opacity'))==1) {
					slideParams = {duration: 0.5};
					if (callback) slideParams.afterFinish = callback;
					new Effect.Appear($('import-version-count'), slideParams);
				} else {
					if (callback) callback();
				}
			},
			
			hideSentence: function(callback) {
				if (typeof callback	== "undefined")	callback = false;
				if (!parseInt($('import-version-count').getStyle('opacity'))==0) {
					slideParams = {duration: 0.2, to:0.5};
					if (callback) slideParams.afterFinish = callback;
					new Effect.Fade($('import-version-count'), slideParams);
				} else {
					if (callback) callback();
				}
			}
		}
	},
	
	////////////////// PARAMS /////////////////
	///// Functions that control the      /////
	///// creation, display and animation /////
	///// of Parameters in the new/edit   /////
	///// app form.                       /////
	///////////////////////////////////////////
	
	params: {
		// Used in the New/Edit App form when displaying Parameters for selection in the Graph section
		allVisibleParameters: function() {
			var allParams = new Array();
			var allSpans = $$('span.param-name');
			if (allSpans.length>0) {
				allSpans.each( function(span) {
					allParams.push(span.innerHTML);
				});
			}
			return allParams;
		},
		
		showNewParameter: function() {
			appsUI.form.params.showNewParameterWithDetails('newParameter',true)
		},

		showNewParameterWithDetails: function(name,editing,justReturnCode,includeHiddenOldNameField) {
			if (typeof justReturnCode == "undefined") justReturnCode=false;
			if (typeof includeHiddenOldNameField == "undefined") includeHiddenOldNameField=false;
			var theParamNameID = "paramname_" + appsUI.form.graphs.randomGroupingId();
			var code = ""
			code += '<li class="param-title" id="titleli_' + theParamNameID + '"><span class="param-name" id="titletext_' + theParamNameID + '">' + name + '</span><img src="img/pencil_small.png" class="edit-button" onclick="javascript:appsUI.form.params.toggleParamEditLi(\'' + theParamNameID + '\');" /><img src="img/common/cross_small.png" class="delete-button" onclick="javascript:appsUI.form.graphs.deleteGrouping(\'editli_' + theParamNameID + '\');appsUI.form.graphs.deleteGrouping(\'titleli_' + theParamNameID + '\');" /></li>';
			code += '<li class="stat-edit" id="editli_' + theParamNameID + '" style="display:none;"><div class="stat-edit-inner">';
			code += '  <table cellpadding=0 cellspacing=0 class="parameter-set">';
			code += '    <tr>';
			code += '      <th>Name<th>';
			code += '      <td>';
			if (includeHiddenOldNameField) code += '<input type="hidden" class="old-value" value="' + name + '" />';
			code += '        <input type="text" class="param-value" value="' + name + '" onkeyup="javascript:$(\'titletext_' + theParamNameID + '\').innerHTML=this.value;" id="' + theParamNameID + '" />';
			code += '      </td>';
			code += '    </tr>';
			code += '  </table>';
			code += '</div></li>';
			if (justReturnCode) {
				return code;
			} else {
				Element.insert($('parameter-listing'), {bottom:code} );
				if (editing) appsUI.form.params.toggleParamEditLi(theParamNameID);
			}
		},
		
		toggleParamEditLi: function(itemIdentifier) {
			var theItem = $('editli_' + itemIdentifier);
			var theTitle = $('titleli_' + itemIdentifier);
			if (theItem.visible()) {
				new Effect.Parallel( [
					new Effect.BlindUp(theItem, {sync:true}),
					new Effect.Fade(theItem, {sync:true})
					], {duration:0.5}
				);
				theTitle.removeClassName('editing');
			} else {
				new Effect.Parallel( [
					new Effect.BlindDown(theItem, {sync:true}),
					new Effect.Appear(theItem, {sync:true})
					], {
						duration:0.5,
						afterFinish: function() {
							$(itemIdentifier).focus();
						}
					}
				);
				theTitle.addClassName('editing');
			}
		},
		
		// Read Params from DOM
		readParamsFromForm: function() {
			var paramSets = new Array();
			var allParameterSets = $$('table.parameter-set');
			allParameterSets.each(function(set) {
				var paramSet = {};
				paramSet.name = set.select('input.param-value')[0].value.replace(/^\s+|\s+$/g, '');
				var oldParamNameField = set.select('input.old-value');
				if (oldParamNameField.length>0) paramSet.oldName = oldParamNameField[0].value;
				paramSets.push(paramSet);
			});
			return paramSets;
		},
		
		// Checks that no Parameters have been entered that use reserved Param name. ie. 'ip', etc.
		checkForReservedUsage: function() {
			var reservedParams = ['ip', 'last_version', 'first_version', 'last_seen', 'first_seen'];
			for (var s=0; s < appsUI.form.sparkleParameters.length; s++) {
				reservedParams.push(appsUI.form.sparkleParameters[s].toLowerCase());
			};
			
			var enteredParams = appsUI.form.params.readParamsFromForm();
			for (var i=0; i < enteredParams.length; i++) {
				if (reservedParams.indexOf(enteredParams[i].name.toLowerCase())>-1) {
					notify.update('Parameter \'' + enteredParams[i].name + '\' is used by Shimmer. Please enter a different name.', 5);
					return false;
				}
			};
			return true;
		},
		
		// Checks for duplicate Parameter names. If found, notifies users and returns false
		checkForDuplicates: function() {
			var enteredParams = appsUI.form.params.readParamsFromForm();
			for (var i=1; i < enteredParams.length; i++) {
				for (var k=0; k < i; k++) {
					if (enteredParams[i].name.toLowerCase() == enteredParams[k].name.toLowerCase()) {
						notify.update('Parameter \'' + enteredParams[i].name + '\' is already in use.', 5);
						return false;
					}
				};
			};
			return true;
		}
	},
	
	graphs: {
		showNewGraph: function() {
			appsUI.form.graphs.showNewGraphWithDetails('New Graph',1,true);
		},
		
		showNewGraphWithDetails: function(name,type,editing,allParams,chosenParam,groupings,justReturnCode,graphID) {
			if (typeof justReturnCode == "undefined") justReturnCode=false;
			if (typeof groupings == "undefined") groupings=false;
			if (typeof allParams == "undefined") allParams=appsUI.form.params.allVisibleParameters();
			if (typeof chosenParam == "undefined") chosenParam=false;
			if (typeof graphID == "undefined") graphID=false;
			var theParamNameID = "paramname_" + appsUI.form.graphs.randomGroupingId();
			var code = ""
			code += '      <li class="param-title" id="titleli_' + theParamNameID + '"><span id="titletext_' + theParamNameID + '">' + name + '</span><img src="img/pencil_small.png" class="edit-button" onclick="javascript:appsUI.form.graphs.toggleGraphEditLi(\'' + theParamNameID + '\');" /><img src="img/common/cross_small.png" class="delete-button" onclick="javascript:appsUI.form.graphs.deleteGrouping(\'editli_' + theParamNameID + '\');appsUI.form.graphs.deleteGrouping(\'titleli_' + theParamNameID + '\');" /></li>';
			code += '      <li class="stat-edit graph-edit" id="editli_' + theParamNameID + '" style="display:none;"><div class="stat-edit-inner">';
			code += '        <table cellpadding=0 cellspacing=0>';
			code += '          <tr>';
			code += '            <th>Name<th>';
			code += '            <td>';
			code += '              <input type="text" class="graph-name" value="' + name + '" onkeyup="javascript:$(\'titletext_' + theParamNameID + '\').innerHTML=this.value;" />';

			if (graphID) code += ' <input type="hidden" class="optional-graph-id" value="' + graphID + '" />';

			code += '            </td>';
			code += '          </tr>';
			code += '          <tr>';
			code += '            <th>Parameter<th>';
			code += '            <td><select class="graph-parameter">';

			// TODO only include Sparkle Params if app 'usesSparkle'
			var appUsesSparkle = true;
			if (appUsesSparkle) {
				for (var i=0; i < appsUI.form.sparkleParameters.length; i++) {
					code += '<option' + (chosenParam==appsUI.form.sparkleParameters[i] ? ' SELECTED' : '') + '>' + appsUI.form.sparkleParameters[i] + '</option>';
				};
			}
			
			var extraParamsCode = "";
			for (var i=0; i < allParams.length; i++) {
				if (appsUI.form.sparkleParameters && appsUI.form.sparkleParameters.indexOf(allParams[i])<0) {
					extraParamsCode += '<option' + (chosenParam==allParams[i] ? ' SELECTED' : '') + '>' + allParams[i] + '</option>';
				}
			};
			if (extraParamsCode.length>0) code += '<hr />' + extraParamsCode;

			code += '           </select></td>';
			code += '          </tr>';
			code += '          <tr>';
			code += '            <th>Type<th>';
			code += '            <td><select class="graph-type"><option value="1"' + (type=='1' ? ' SELECTED' : '') + '>Text</option><option value="2"' + (type=='2' ? ' SELECTED' : '') + '>Numerical</option></td>';
			code += '          </tr>';
			code += '        </table>';
			code += '        <strong>Groupings <a href="#AddGraph" onclick="javascript:appsUI.form.graphs.addGrouping(this);return false;" class="new-grouping-plus">New Grouping...</a></strong>';
			code += '        <div class="all-groupings">';

			if (groupings) {
				for (var v=0; v < groupings.length; v++) {
					var theValueSet = groupings[v];
					if (theValueSet) {
						if (type==1) { // text-based grouping
							if (theValueSet.label && theValueSet.match) {
								var theSearchSet = theValueSet.match.replace(/\&/g,'&amp;');
								code += appsUI.form.graphs.graphGroupingCode(type, appsUI.form.graphs.randomGroupingId(), {
									label:    theValueSet.label,
									searches: theSearchSet
								});
							}
						} else if (type==2) { // numerical grouping & operations
							code += appsUI.form.graphs.graphGroupingCode(type, appsUI.form.graphs.randomGroupingId(), {
								conditions:	theValueSet.conditions,
								operations:	theValueSet.modifications,
								round:		theValueSet.round,
								unit:		theValueSet.unit
							});
						}
					}
				};
			}

			code += '        </div>';
			code += '      </div></li>';

			if (justReturnCode) {
				return code;
			} else {
				Element.insert($('graph-listing'), {bottom:code} );
				if (editing) appsUI.form.graphs.toggleGraphEditLi(theParamNameID);
			}
		},
		
		toggleGraphEditLi: function(itemIdentifier) {
			var theItem = $('editli_' + itemIdentifier);
			var theTitle = $('titleli_' + itemIdentifier);
			if (theItem.visible()) {
				new Effect.Parallel( [
					new Effect.BlindUp(theItem, {sync:true}),
					new Effect.Fade(theItem, {sync:true})
					], {duration:1.0}
				);
				theTitle.removeClassName('editing');
			} else {
				new Effect.Parallel( [
					new Effect.BlindDown(theItem, {sync:true}),
					new Effect.Appear(theItem, {sync:true})
					], {
						duration:0.5,
						afterFinish: function() {
							$(itemIdentifier).focus();
						}
					}
				);
				theTitle.addClassName('editing');
			}
		},
		
		randomGroupingId: function() {
			var now = new Date();
			var timestamp = Math.round(now.getTime()/1000.0);
			var theId = "animation_" + timestamp + parseInt(1000*Math.random());
			return theId;
		},
		
		addGrouping: function(sender) {
			var theId = appsUI.form.graphs.randomGroupingId();
			var graphType = 1;
			var graphTypeSelector = sender.parentNode.parentNode.select('.graph-type')[0];
			if (graphTypeSelector) graphType = graphTypeSelector.value = parseInt(graphTypeSelector.value);

			var newGroupingHTML = appsUI.form.graphs.graphGroupingCode(graphType, theId, {label:"", searches:"", hideDiv:true})
			Element.insert( sender.parentNode.parentNode.select('div.all-groupings')[0], {bottom:newGroupingHTML} );
			// updateConditionHeader();
			new Effect.BlindDown(theId,{duration:0.5});
		},
		
		graphGroupingCode: function(theType, id, options) {
			if (typeof options == "undefined") options = {};
			if (typeof options.hideDiv == "undefined") options.hideDiv = false;
			if (typeof options.includeContainer == "undefined") options.includeContainer = true;
			if (typeof options.round == "undefined") options.round = 0;
			if (typeof options.unit == "undefined") options.unit = "GHz";

			var toggleHashText		= "{label:'',searches:'',includeContainer:false}";
			var toggleHashNumber	= "{numtest:true,includeContainer:false}";

			var code = "";
			if (options.includeContainer == true) code += '<div class="stat_grouping_container" id="' + id + '"' + (options.hideDiv?' style="display:none;"':'') + '>';
			code += '<div class="stat_grouping_delete" onclick="javascript:appsUI.form.graphs.deleteGrouping(\'' + id + '\');" title="Delete this stat grouping"></div>'
			code += '<table class="small" cellpadding="0" cellspacing="0">';

			if (theType==1) {
				code += '<tr>';
				code += '  <th>Label</th>';
				code += '  <td><input type="text" value="' + options.label + '" id="' + id + '_label"></td>';
				code += '</tr>';
				code += '<tr>';
				code += '  <th>Matches</th>';
				code += '  <td><input type="text" value="' + options.searches + '" id="' + id + '_searches"></td>';
				code += '</tr>';
			} else {
				if (options.conditions && options.conditions.length>0) {
					options.conditions.each( function(condition, conditionIndex) {
						code += appsUI.form.graphs.groupingConditionRowCode(condition, conditionIndex);
					});
				} else {
					code += appsUI.form.graphs.groupingConditionRowCode({},0);
				}

				if (options.operations && options.operations.length>0) {
					options.operations.each( function(modification, modificationIndex) {
						code += appsUI.form.graphs.groupingOperationRowCode(modification,modificationIndex);
					});
				} else {
					code += appsUI.form.graphs.groupingOperationRowCode({},0);
				}
				code += '<tr>';
				code += '  <th>Round</th>';
				code += '  <td>';
				code += '    <input type="text" value="' + options.round + '" class="round-value" /> decimal places';
				code += '  </td>';
				code += '</tr>';
				code += '<tr>';
				code += '  <th>Unit</th>';
				code += '  <td>';
				code += '    <input type="text" value="' + options.unit + '" class="unit-value" />';
				code += '  </td>';
				code += '</tr>';

			}
			code += "</table>";
			if (options.includeContainer == true) code += "</div>";
			return code;
		},
		
		deleteGrouping: function(theId) {
			new Effect.Parallel([
				new Effect.BlindUp( theId, { sync: true } ),
				new Effect.Fade   ( theId, { sync: true } )
			], {duration: 0.3, afterFinish:function() { Element.remove(theId); } });
		},
		
		updateConditionHeader: function(id) {
			var condRows = $$('div#' + id + ' tr.condition-row');
			if (condRows) {
				condRows.each( function(row,index) {
					var headerField = row.select('th')[0];
					if (headerField) headerField.innerHTML = (index==0 ? 'Conditions' : '');
				});
			} else {
				// alert('No rows found!!! WTF');
			}
		},
		
		addGroupingOperationRow: function(senderRow) {
			var groupingTable = senderRow.parentNode;
			var opRows = groupingTable.select('tr.operation-row-pair').length;

			code = appsUI.form.graphs.groupingOperationRowCode({operator:'add', value:'100'});
			Element.insert( senderRow, {after:code} );

			var theId = senderRow.parentNode.parentNode.parentNode.readAttribute('id');

			if (opRows==0) Element.remove(senderRow);

			if (theId) appsUI.form.graphs.updateOperationHeader(theId);
		},
		
		deleteGroupingOperationRow: function(senderRow) {
			var theId = senderRow.parentNode.parentNode.parentNode.readAttribute('id');

			var groupingTable = senderRow.parentNode;
			var condRows = groupingTable.select('tr.operation-row').length;

			if (condRows==1) {
				var code = appsUI.form.graphs.groupingOperationRowCode();
				Element.insert( senderRow, {after:code} );
			}

			Element.remove(senderRow);

			if (theId) appsUI.form.graphs.updateOperationHeader(theId);
		},
		
		groupingOperationRowCode: function(values,modificationIndex) {
			var valuesAreSet = (typeof values != "undefined" && values.operator && values.value);
			var code = '<tr class="operation-row' + (valuesAreSet ? ' operation-row-pair' : '') + '">';
			code += '  <th>';
			if (typeof modificationIndex != "undefined" && modificationIndex==0) code += 'Operations';
			code += '  </th>';
			code += '  <td style="position:relative;">';
			code += '    <div class="delete-grouping-subset" onclick="appsUI.form.graphs.deleteGroupingOperationRow(this.parentNode.parentNode);">Delete Operation</div>';
			code += '    <div class="new-grouping-subset" onclick="appsUI.form.graphs.addGroupingOperationRow(this.parentNode.parentNode);">Add Operation</div>';
			code += '    <span>';
			if (valuesAreSet) {
				code += '    <select class="operation-select">';
				code += '      <option value="plus"' + (values.operator=="plus"?" SELECTED":"") + '>+</option>';
				code += '      <option value="minus"' + (values.operator=="minus"?" SELECTED":"") + '>&minus;</option>';
				code += '      <option value="multiply"' + (values.operator=="multiply"?" SELECTED":"") + '>&times;</option>';
				code += '      <option value="divide"' + (values.operator=="divide"?" SELECTED":"") + '>&divide;</option>';
				code += '    </select>';
				code += '    <input type="text" value="' + values.value + '" class="operation-value" />';
			} else {
				code += '<span class="no-grouping">no operations</span>';
			}
			code += '    </span>';
			code += '  </td>';
			code += '</tr>';
			return code;
		},
		
		updateOperationHeader: function(id) {
			var opRows = $$('div#' + id + ' tr.operation-row');
			if (opRows) {
				opRows.each( function(row,index) {
					var headerField = row.select('th')[0];
					if (headerField) headerField.innerHTML = (index==0 ? 'Operations' : '');
				});
			} else {
				// alert('No rows found!!! WTF');
			}
		},
		
		addGroupingConditionRow: function(senderRow) {
			var groupingTable = senderRow.parentNode;
			var condRows = groupingTable.select('tr.condition-row-pair').length;

			var code = appsUI.form.graphs.groupingConditionRowCode({operator:'greater', value:'100'});
			Element.insert(senderRow, {after:code});

			var theId = senderRow.parentNode.parentNode.parentNode.readAttribute('id');

			if (condRows==0) Element.remove(senderRow);

			if (theId) appsUI.form.graphs.updateConditionHeader(theId);
		},
		
		deleteGroupingConditionRow: function(senderRow) {
			var theId = senderRow.parentNode.parentNode.parentNode.readAttribute('id');

			var groupingTable = senderRow.parentNode;
			var condRows = groupingTable.select('tr.condition-row').length;

			if (condRows==1) {
				var code = appsUI.form.graphs.groupingConditionRowCode();
				Element.insert( senderRow, {after:code} );
			}

			Element.remove(senderRow);

			if (theId) appsUI.form.graphs.updateConditionHeader(theId);
		},
		
		groupingConditionRowCode: function(values,conditionIndex) {
			var valuesAreSet = (typeof values != "undefined" && values.operator && values.value);
			var code = '<tr class="condition-row' + (valuesAreSet ? ' condition-row-pair' : '') + '">';
			code += '  <th>';
			if (typeof conditionIndex != "undefined" && conditionIndex==0) code += 'Conditions';
			code += '</th>';
			code += '  <td>';
			code += '    <div class="delete-grouping-subset" onclick="appsUI.form.graphs.deleteGroupingConditionRow(this.parentNode.parentNode);">Delete Condition</div>';
			code += '    <div class="new-grouping-subset" onclick="appsUI.form.graphs.addGroupingConditionRow(this.parentNode.parentNode);">Add Condition</div>';
			code += '    <span>';
			if (valuesAreSet) {
				code += '    <select class="condition-select">';
				code += '      <option value="greater"' + (values.operator=="greater"?" SELECTED":"") + '>&gt;</option>';
				code += '      <option value="less"' + (values.operator=="less"?" SELECTED":"") + '>&lt;</option>';
				code += '      <option value="greater_or_equal"' + (values.operator=="greater_or_equal"?" SELECTED":"") + '>&ge;</option>';
				code += '      <option value="less_or_equal"' + (values.operator=="less_or_equal"?" SELECTED":"") + '>&le;</option>';
				code += '    </select>';
				code += '    <input type="text" value="' + values.value + '" class="condition-value" />';
			} else {
				code += '<span class="no-grouping">no conditions</span>';
			}
			code += '    </span>';
			code += '  </td>';
			code += '</tr>';
			return code;
		},
		
		readGraphsFromForm: function() {
			var graphSets = new Array();
			$$('li.graph-edit').each(function(graph) {
				var graphName		= graph.select('input.graph-name')[0].value;				
				var graphParameter	= graph.select('select.graph-parameter')[0].value;
				var graphType		= graph.select('select.graph-type')[0].value;
				var graphSet		= {name:graphName, key:graphParameter, type:graphType};
				
				var graphIDField	= graph.select('input.optional-graph-id');
				if (graphIDField.length>0) graphSet.id = graphIDField[0].value;

				// Add any groupings that are found
				var groupings = new Array();
				graph.select('div.stat_grouping_container').each( function(groupingContainer) {
					var graphId = groupingContainer.readAttribute('id');
					if (graphId) {
						if (graphType == "1") { // text-based replacement
							var labelInput = groupingContainer.select('#' + graphId + '_label')[0];
							var matchInput = groupingContainer.select('#' + graphId + '_searches')[0];
							if (labelInput && matchInput) {
								var groupingset = {label:labelInput.value, match:matchInput.value};
								groupings.push(groupingset);
							}
						} else if (graphType == "2") { // number-based replacement
							var conditions = new Array();
							var conditionRows = groupingContainer.select('tr.condition-row-pair');
							if (conditionRows) conditionRows.each( function(conditionRow) {
								var conditionSelect = conditionRow.select('select.condition-select')[0];
								var conditionValue  = conditionRow.select('input.condition-value')[0];
								if (conditionSelect && conditionValue) {
									var conditionSet = {operator:conditionSelect.value, value:conditionValue.value };
									conditions.push(conditionSet);
								}
							});

							var operations = new Array();
							var operationRows = groupingContainer.select('tr.operation-row-pair');
							if (operationRows) operationRows.each( function(operationRow) {
								var operationSelect = operationRow.select('select.operation-select')[0];
								var operationValue  = operationRow.select('input.operation-value')[0];
								if (operationSelect && operationValue) {
									var operationSet = {operator:operationSelect.value, value:operationValue.value };
									operations.push(operationSet);
								}
							});

							var roundValue = groupingContainer.select('input.round-value')[0];
							var unitValue = groupingContainer.select('input.unit-value')[0];
							var groupingSet = {conditions:conditions, modifications:operations, round:roundValue.value, unit:unitValue.value};
							groupings.push(groupingSet);
						}
					}

				});
				
				graphSet.values = groupings;

				// Add the new graph set to the list
				graphSets.push(graphSet);
			});
			return graphSets;
		}
	},
	
	save: {
		saveNewApp: function() {
			var appName   = $('appedit_name').value;
			if (apps.nameIsValid(appName) && appsUI.form.params.checkForReservedUsage() && appsUI.form.params.checkForDuplicates()) {
				notify.update('Creating app...', 0);
				var paramSets		= appsUI.form.params.readParamsFromForm();
				var graphSets		= appsUI.form.graphs.readGraphsFromForm();
				var importVersions	= appsUI.form.versionImporter.selectedVersions();

				new Ajax.Request('?ajax', {
					method:'post',
					parameters: {
						action:			'app.save',
						new_name:		appName,
						parameters:		JSON.stringify(paramSets),
						graphs:			JSON.stringify(graphSets),
						notes: 			$('notes-theme-chooser').value,
						notesMask:		$('notes-mask').value,
						downloadMask:	$('download-mask').value,
						appcastURL:		appsUI.form.versionImporter.lastAppcast,
						importVersions:	JSON.stringify(importVersions),
						usesSparkle:	($('app-uses-sparkle').checked ? 1 : 0),
						identifier:		$('app-identifier').value,
						publicKey:      dsa.usedStampForType(dsa.pub).value,
						privateKey:     dsa.usedStampForType(dsa.priv).value
					},
					onSuccess: function(transport) {
						var createResponse = transport.responseText;
						var theCreateResponse = JSON.parse(createResponse);
						if (theCreateResponse.wasOK) {
							notify.update('App created successfully', 5);
							// apps.currentApp      = theCreateResponse.createdAppName;
							apps.currentAppIndex = theCreateResponse.createdIndex;
							apps.processReceivedAppList(theCreateResponse.allApps, true);
							focusBox.hide();
						}
					}
				});
			}
		},
		
		saveEditedApp: function() {
			var oldAppName = $('existingAppName').value;
			var newAppName   = $('appedit_name').value;
			if (apps.nameIsValid(oldAppName) && apps.nameIsValid(newAppName) && appsUI.form.params.checkForReservedUsage() && appsUI.form.params.checkForDuplicates()) {
				notify.update('Saving app...', 0);
				var paramSets		= appsUI.form.params.readParamsFromForm();
				var graphSets		= appsUI.form.graphs.readGraphsFromForm();
				var importVersions	= appsUI.form.versionImporter.selectedVersions();
				new Ajax.Request('?ajax', {
					method:'post',
					parameters: {
						action:			'app.save',
						old_name:		oldAppName,
						new_name:		newAppName,
						parameters:		JSON.stringify(paramSets),
						graphs:			JSON.stringify(graphSets),
						notes: 			$('notes-theme-chooser').value,
						notesMask:		$('notes-mask').value,
						downloadMask:	$('download-mask').value,
						appcastURL:		appsUI.form.versionImporter.lastAppcast,
						importVersions:	JSON.stringify(importVersions),
						usesSparkle:	($('app-uses-sparkle').checked ? 1 : 0),
						identifier:		$('app-identifier').value,
						publicKey:      dsa.usedStampForType(dsa.pub).value,
						privateKey:     dsa.usedStampForType(dsa.priv).value
					},
					timeout:30,
					onSuccess: function(transport) {
						var createResponse = transport.responseText;
						var theCreateResponse = JSON.parse(createResponse);
						if (theCreateResponse.wasOK) {
							if (theCreateResponse.importCount) {
								notify.update('App updated and ' + theCreateResponse.importCount + ' versions imported successfully', 5);
							} else {
								notify.update('App updated successfully', 5);
							}
							focusBox.hide();
							apps.reloadAppList();
						}
					}
				});
			}
		}
		
	}
	
}