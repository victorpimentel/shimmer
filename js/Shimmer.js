Shimmer = {
	
	loaded: false,
	
	window: {
		lastWindowResize: 0,
		resized: function() {
			now = new Date();
			currentDate = now.getTime();
			if (currentDate-Shimmer.window.lastWindowResize>100 ) {
				redrawAllGraphsFromCache();
				Shimmer.window.lastWindowResize = currentDate;
			}
		},
	},
	
	initialize: {
		start: function() {
			Shimmer.initialize.addShortcuts();
			apps.loadInitialData();

			// Shimmer.ajaxHistory.setupAjaxHistory();

			window.onresize = Shimmer.window.resized;
			
			versionsUI.table.scroll.setup();
			
			// setTimeout('appsUI.form.startForm(apps.currentApp)',500);
		},
		
		addShortcuts: function() {
			if (shortcut) {
				var upDownOptions = { "disable_in_input":true };
				shortcut.add("j",			function() { versions.alterSelectedVersion(+1); 		}, upDownOptions );
				shortcut.add("k",			function() { versions.alterSelectedVersion(-1);			}, upDownOptions );
				shortcut.add("r",			function() { versions.toggleVersionRates(); 			}, upDownOptions );
				shortcut.add("q",			function() { boxes.toggleStatsDisplay(); 				}, upDownOptions );
				shortcut.add("ctrl+left",	function() { apps.alterSelectedApp(+1); 				}, upDownOptions );
				shortcut.add("ctrl+right",	function() { apps.alterSelectedApp(-1); 				}, upDownOptions );
				shortcut.add("enter",		function() { versions.openSelectedVersion(); 			}, upDownOptions );
				shortcut.add("l",			function() { versions.flipSelectedVersionLive(); 		}, upDownOptions );
				shortcut.add("u",			function() { apps.reloadCurrentAppVersionsAndGraphs();	}, upDownOptions );
				shortcut.add("escape",		function() { Shimmer.escapePressed();					}  );
				shortcut.add("control+s",	function() { versions.saveVersionButtonClicked();		}  );
				shortcut.add("control+d",	function() { versions.deleteSelectedVersion();			}  );
				shortcut.add("control+n",	function() { versionsUI.showEmptyNewVersionForm();		}  );
			}
		}
	},
	
	knownNotesThemes: false,
	
	util: {
		timestamp: function() {
			var now = new Date();
			return Math.round(now.getTime()/1000.0).toString();
		},
		
		runInlineCommand: function(theCommand) {
			if (theCommand) eval(theCommand.replace(/javascript:|return false;/g,""));
		},
		
		setElementValue: function(theElement,theValue) {
			if (theElement.nodeName=="INPUT" || theElement.nodeName=="TEXTAREA") {
				theElement.value = theValue;
			} else if (theElement.nodeName=="H3") {
				theElement.innerHTML = theValue;
			}
		},
		
		removeStyleTag: function(theElement) {
			theElement.style.background = "";
		},
		
		findMax: function(array) {
			var max = array[0];
			for (var i=1; i < array.length; i++) {
				if (array[i]>max) max=array[i];
			};
			return max;
		},

		findMin: function(array) {
			var min = array[0];
			for (var i=1; i < array.length; i++) {
				if (array[i]<min) min=array[i];
			};
			return min;
		},
		
		findWidth: function(element) {
		    currentWidth = element.offsetWidth;
		    while(element.offsetParent != null) {
		        element = element.offsetParent;
		        currentWidth += element.offsetWidth
		    }
		    return currentWidth;
		},
		
		baseLocation: function() {
			return document.location.href.match(/[^#?]+/)[0];
		},
		
		isSafari: function() {
			return (navigator.userAgent.toLowerCase().indexOf('safari')>-1);
		},
		
		isUrl: function(input) {
			var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
			return regexp.test(input);
		}
		
	},
	
	ajaxHistory: {
		setupAjaxHistory: function() {
			lastHash = "";
			ajaxHistoryTimer = setInterval("Shimmer.ajaxHistory.checkAjaxHistory();",200);
		},

		checkAjaxHistory: function() {
			var currentHash = Shimmer.state.getAppNameFromURL();
			if (currentHash) {
				if (lastHash.length>0 && currentHash.length>0 && currentHash != lastHash) {
					lastHash = currentHash;
					apps.switchApp(currentHash);
				}
			}
		}
	},
	
	blob: {
		hide: function() {
			$('blob').hide()
		},

		show: function(element) {
			var blob = $('blob');
			if (blob) {
				blob.show();
				$('blobtext').innerHTML = element.readAttribute('alt');
				var offsets = element.cumulativeOffset();
				var columnCenter = offsets.left + (element.getWidth()/2);

				var leftOffset = columnCenter - (blob.getWidth()/2);
				blob.setStyle({
					left: (leftOffset-5)   +'px',
					top:  (offsets.top-25) +'px'
				});
			}
		}
	},
	
	progress: {
		showProgress: function() { $('loading_icon').style.visibility = "visible"; },
		hideProgress: function() { $('loading_icon').style.visibility = "hidden";  },

		updateLoadingCountDisplay: function() {
			if (Ajax.activeRequestCount == 0) {
				Shimmer.progress.hideProgress();
			} else {
				Shimmer.progress.showProgress();
			}
		}
	},
	
	state: {
		setPageTitle: function(title,forceWindowTitle) {
			if (typeof forceWindowTitle == "undefined") forceWindowTitle = "Shimmer: " + title;
			var currentBaseURL = document.location.href.split("#")[0];
			document.location.href = currentBaseURL + "#" + title;
			lastHash = title;
			document.title = forceWindowTitle;
		},
		getAppNameFromURL: function() {
			var currentURL = document.location.href;
			var twoParts = currentURL.split("#");
			if (twoParts.length == 2 && twoParts[1].length > 0) {
				return unescape(twoParts[1]);
			}
			return undefined;
		}
	},
	
	account: {
		logout: function() {
			document.location.href = document.location.href.replace(/\/[^\/]*$/,"/?logout");
		}
	},
	
	blurAll: function() {
		$$('input').each(function(input) {  input.blur();	});
		$$('textarea').each(function(input) {  input.blur();	});
	},
	
	showWelcomeArea: function() {
		$('row1').show();
		$('row2').show();
		$$('.dashed_box, .dashed_box_trip').each( function(dash) {
			dash.show();
		})
		$$('.box, .box_trip').each( function(dash) {
			dash.hide();
		})

		Shimmer.state.setPageTitle('WelcomeToShimmer', 'Welcome to Shimmer');
		$('welcome').innerHTML = '<h2>Welcome to Shimmer</h2><p>You can now add your first app. If you already have an appcast, Shimmer can import version info automatically.</p><div id="welcome-button" onclick="javascript:appsUI.form.startForm();return false;" />';
		new Effect.Appear('welcome', {duration:0.5});
		boxes.fadeDashedBoxesToOpacity('0.2');
	},
	
	hideWelcomeArea: function(callback) {
		if ($('welcome').visible()) {
			new Effect.Fade('welcome', 	{
				duration:1.0,
				afterFinish: function() {
					if (typeof callback != "undefined") callback();
				}
			});
			boxes.fadeDashedBoxesToOpacity('0.4');
		}
	},
	
	escapePressed: function() {
		versionsUI.hideNewVersionForm();
		if ( $('focusBox').visible() ) {
			focusBox.hide({dropout:true});
			notify.hide();
		}
	}
	
}

window.onload = Shimmer.initialize.start;