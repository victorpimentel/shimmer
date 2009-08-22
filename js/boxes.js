// Boxes: Controls the interaction and settings of the 4 main Graphs

boxes = {
	redrawAllGraphChoosersForCurrentApp: function() {
		var appName = apps.currentApp;
		var cachedStatDefinitions = apps.cachedStatDefinitionsForApp(appName);
		if (!cachedStatDefinitions) {
			apps.reloadStatDefinitionsForApp(appName);
			
		} else {
			// Used for all 'Graphs' Parameters
			var optionsCode = '<li alt="downloads">Downloads</li><li alt="users">Users</li>';
			// Used for all User-Created Parameters
			var customOptionsCode = "";
			// Append a new <li> to the appropriate string
			for (var i=0; i < cachedStatDefinitions.graphs.length; i++) {
				var currentGraph = cachedStatDefinitions.graphs[i];
				if (currentGraph.stock) {
					optionsCode += '<li alt="' + currentGraph.id + '">' + currentGraph.name + '</li>'
				} else {
					customOptionsCode += '<li' + (customOptionsCode.length==0 ? ' class="first-custom-graph"' : '') + ' alt="' + currentGraph.id + '">' + currentGraph.name + '</li>'
				}
			};
			optionsCode += customOptionsCode;

			// Put the new <li> items in each Graph Chooser
			$$('ul.box_data_selector').each( function(list) { list.innerHTML = optionsCode; });
			$$('ul.box_data_selector li').each( function(item) { item.observe('click', function() { boxes.switchBoxTarget(item); }); });
		}
	},
	
	switchBoxTarget: function(sender) {
		var theList = sender.parentNode;
		if (theList) {
			var theLocation = theList.readAttribute('alt');
			var theId = sender.readAttribute('alt');
			if (theLocation && theId) {
				$$('#' + theLocation + ' .box_title span.menu_title_text')[0].innerHTML = sender.innerHTML;
				new Ajax.Request('?ajax&type=pref', {
					method: 'post',
					parameters: { action:"prefs.box.update", box:theLocation, id:theId, app:apps.currentApp },
					onSuccess: function(transport) {
						var response = transport.responseText;
						var theResponse = JSON.parse(response);
						if (theResponse.wasOK) processGraphData(theResponse.stats);
					}
				});
			}
		}
	},
	
	// Toggles all 4 boxes between Graph and Table display
	toggleStatsDisplay: function() {
		$$('div.switchstats').each( function(link) {
			var theCommand = link.readAttribute("onclick");
			if (theCommand) Shimmer.util.runInlineCommand(theCommand);
		});
	},
	
	fadeDashedBoxesToOpacity: function(theOpacity) {
		var dashMorphs = new Array();
		var opacityStyle = "opacity:" + theOpacity + ";";
		$$('.dashed_box, .dashed_box_trip').each( function(dash) {
			dashMorphs.push( new Effect.Morph(dash, {
				sync:true,
				style:opacityStyle
			}) );
		});
		new Effect.Parallel( dashMorphs, {duration:0.5} );
	}
	
}