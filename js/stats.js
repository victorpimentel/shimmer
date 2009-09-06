var smallGraphHeight = 150;

graphManager = { };

function processGraphData(allStats) {
	var allFades = new Array();
	var drawLocations = new Array();
	
	if ( !$('box_versions').visible() ) {
		$('dashed_r1b0').hide();
		$('box_versions').setStyle({display:'block',opacity:0.0});
		allFades.push( new Effect.Appear( $('box_versions'), { sync:true } ) );
	}
	
	for (var i=0; i < allStats.length; i++) {
		var currentStat = allStats[i];
		
		var graphId = currentStat.location;
		if (graphId) {
			var theLocation = currentStat.location;
			var graphRow = theLocation.split('r')[1].split('b')[0];
			if (graphRow && $('row' + graphRow).visible() ) {
				var displayType = statType(graphId);
		        graphManager[theLocation] = {
											targ: '#' + theLocation + ' .box_data',
											data: currentStat.values,
											size: {
												width:  "$$('#" + theLocation + " .box_data')[0].getWidth() - 40;",
												height: (graphRow==1 ? "versionTableHeight();" : "" + smallGraphHeight + "")
											},
											conf: {
												max:         (graphRow == 1 ? 10 : 5),
												minColWidth: (graphRow == 1 ? 60 : 40),
												flipInput:   true,
												flipOutput:  true,
												axisData:    { vertical:currentStat.axis.vertical, horizontal:currentStat.axis.horizontal, relationship:currentStat.hover }
											},
											type: displayType
										};
				var dashedBox = $('dashed_'+theLocation);
				if (dashedBox) dashedBox.hide();
				$$('#' + theLocation + ' .box_title span.menu_title_text')[0].innerHTML = currentStat.name;
				if ( $(theLocation).getStyle('display') == "none" ) {
					$(theLocation).setStyle({display:'block',opacity:0.0});
					allFades.push( new Effect.Appear( $(theLocation), { sync:true } ) );
				}
				drawLocations.push(theLocation);
			}
		}

	};
	
	new Effect.Parallel(allFades, { duration: 0.5 } );
	for (var i=0; i < drawLocations.length; i++) {
		drawStat(drawLocations[i]);
	};
}

function versionTableHeight() {
	var vcHeight = $('versions_content').getHeight()
	return (vcHeight>150 ? (vcHeight - 25) : 150);
}

function isSet(variable) {
    return( typeof(variable) != 'undefined' );
}

function statType(key) {
	return ( (isSet(graphManager[key]) && isSet(graphManager[key].type) ) ? graphManager[key].type : 1 );
}

function drawStat(key, disableAnimation) {
	if (typeof disableAnimation == "undefined") disableAnimation=false;
	statType(key) == 1 ? drawGraph(key, disableAnimation) : drawTable(key);
}

function drawGraph(key, disableAnimation) {
	if (typeof disableAnimation == "undefined") disableAnimation=false;
	var entry = graphManager[key];
	if (entry) {
		var graphHeight = eval(entry.size.height);
		var graphWidth  = eval(entry.size.width);
		doGraph(entry.targ, graphHeight, graphWidth, entry.data, entry.conf.minColWidth, entry.conf.max, entry.conf.flipInput, entry.conf.flipOutput, entry.conf.axisData, disableAnimation);
		if (entry.targ.indexOf('#r1b1')>-1) {
			$('row1').setStyle({height:$('r1b1').getHeight()+'px'});
		}
		//$('row1').setStyle({height:$('r1b1').getHeight()+'px'});
	} else document.title = "Entry not found for '" + key + "'.";
}

function drawTable(key) {
    var entry = graphManager[key];
    if (entry) {
        doStatsTable(entry.targ, entry.data, entry.conf.flipInput, entry.conf.flipOutput, entry.conf.axisData);
    } else document.title = "Entry not found for '" + key + "'.";
}

function doGraph(targ,height,width,jsonData,minColWidth, max,flipInput,flipOutput,axisData,disableAnimation) {
	if (typeof disableAnimation == "undefined") disableAnimation=false;
	var target = $$(targ)[0];
    var dataSets = new Array();

	var limit = (jsonData.length > max ? max : jsonData.length);

	if (flipInput == false) {
    	for (var i=0; i<limit; i++) dataSets.push( {name:jsonData[i].label, value:jsonData[i].value} );
	} else {
    	for (var i=jsonData.length-1; i>=jsonData.length-limit; i--) dataSets.push( {name:jsonData[i].label, value:jsonData[i].value} );
	}

    if (flipOutput) dataSets.reverse();

    // Create the input data object
    var graphData = {
                    axis:axisData,
                    dataSets: dataSets
                    };

    // Create the display options object
    var uiOptions = {
                    dimensions : {  width:width, height:height },
                                    hoverHighlight: true,

                                    // table
                                    tableBorderWidth: 0,
                                    tableHorizontalPadding: 0,
                                    tableVerticalPadding: 0,

                                    // columns
                                    columnHorizontalPadding: 4,
                                    columnBorderWidth: 1,
									minColWidth: minColWidth,
									disableAnimation: disableAnimation,

                                    // labels
                                    labelTopPadding: 5,
                                    labelHeight: 12,

									minColWidth: minColWidth
                     };
    // Create the graph
    graph.create(target, graphData, uiOptions);

}

function drawSparkline(canvas,w,h,points) {
	if (points.length > 0) {
		if (canvas.getContext) {
			var ctx = canvas.getContext("2d");
			ctx.beginPath();

			// This code was originally used to graph the total download numbers for all time (ie. not deltas)
			// but was later replaced with the code below to show deltas instead. This is because it is easier
			// to view trend changes when the numbers in use are smaller.
			
			var colWidth = w / points.length;
			var max = Shimmer.util.findMax(points);
			var min = Shimmer.util.findMin(points);
			
			var verticalAdjust = ( min<0 ? Math.abs(min) : (-1*min) );
			if (verticalAdjust<0) max += verticalAdjust;
			if (max==0) max=1;
			
			for (var i=0; i < points.length; i++) {
					var verticalPoint = points[i]==0 ? h : h- (h* ((points[i]+verticalAdjust) /max));
					if (i==0) {
						ctx.moveTo(0,verticalPoint);
					} else {
						ctx.lineTo((i+1)*colWidth,verticalPoint);
					}
			};
			
			ctx.lineWidth = 1.0;
			ctx.strokeStyle = "#888";
			ctx.stroke();
		} // else alert("no context");
	} // else alert("no points");
}

function doStatsTable(targ,jsonData,flipInput,flipOutput,axisData) {
	var target = $$(targ)[0];
	
	if (jsonData.length > 0) {
		var dataSets = new Array();

		if (flipInput == false) {
			for (var i=0; i<jsonData.length; i++) dataSets.push( {name:jsonData[i].label, value:jsonData[i].value} );
		} else {
			for (var i=jsonData.length-1; i>=0; i--) dataSets.push( {name:jsonData[i].label, value:jsonData[i].value} );
		}

	    if (!flipOutput) dataSets.reverse();

	    // Create the display options object
	
		var tableCode = '<table cellpadding="0" cellspacing="0" class="stats">'
		tableCode += '<tr><th>' + axisData.horizontal + '</th><th>' + axisData.vertical + '</th></tr>';
		for (var i=0; i < dataSets.length; i++) {
			var currentSet = dataSets[i];
			tableCode += '<tr><td>' + currentSet.name + '</td><td>' + currentSet.value + '</td></tr>';
		};
		tableCode += '</table>';
	} else {
		tableCode = '<table class="needmorestatdata"><tr><td valign="middle">We need a bit more data for this table.</td></tr></table>';
	}
	target.innerHTML = tableCode;

}

function toggleStatsType(targ) {
	var target = $$(targ)[0];
	for (var key in graphManager) {
		if (graphManager[key].targ == targ) {
			graphManager[key].type = (graphManager[key].type==1 ? 2 : 1);
			drawStat(key);
		}
	}
}

function redrawAllGraphsFromCache(disableAnimation) {
	if (typeof disableAnimation == "undefined") disableAnimation=false;
	for (var key in graphManager) drawStat(key, disableAnimation);
}