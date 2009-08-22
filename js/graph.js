

graph = {
	create: function(container,graphData, displayOptions) {
		var dimensions = displayOptions.dimensions;
		var maxValue = -1;
		for (var i=0; i<graphData.dataSets.length; i++) {
			if (parseInt(graphData.dataSets[i].value) > maxValue) maxValue = parseInt(graphData.dataSets[i].value);
		}
		if (graphData.dataSets.length==0 || maxValue<5) {
			var dataSets = new Array();
			for (var i=1; i <= 5; i++) {
				dataSets.push( {name:"", value:i*20} );
			};
			
			graphData =	{
							axis: { vertical:"", horizontal:"", relationship:"" },
							dataSets: dataSets
						};
			maxValue = 100;
			displayOptions.disableBlob = true;
			displayOptions.noDataMessage = true;
			container.addClassName('nodata');
		} else {
			container.removeClassName('nodata');
		}
		
		if (graphData.dataSets.length>0 && maxValue>5) {
			
			var firstOffset = 8;
			var graphHTML = "";
			var graphHeight = dimensions.height;
			var dataSets = graphData.dataSets;
			
			// If anybody can move this into one loop (elegantly, by my terms of judgment!), I will give you a free copy of Shimmer
			var columnWidth = Math.floor( ( (dimensions.width-firstOffset) - (2*displayOptions.tableHorizontalPadding) - (displayOptions.tableBorderWidth*2) ) / dataSets.length) - (displayOptions.columnHorizontalPadding + (displayOptions.columnBorderWidth*2) );
			var dataSetLength = dataSets.length;
			var itemsToCut = 0;
			while (columnWidth < displayOptions.minColWidth && dataSetLength >= 3) {
				dataSetLength--;
				itemsToCut++;
				columnWidth = Math.floor( ( (dimensions.width-firstOffset) - (2*displayOptions.tableHorizontalPadding) - (displayOptions.tableBorderWidth*2) ) / dataSetLength) - (displayOptions.columnHorizontalPadding + (displayOptions.columnBorderWidth*2) );
			}
			dataSets.splice(0,itemsToCut);
			
			maxValue = -1;
			for (var i=0; i<graphData.dataSets.length; i++) {
				if (parseInt(graphData.dataSets[i].value) > maxValue) maxValue = parseInt(graphData.dataSets[i].value);
			}

			var columnWidthStyle = "width:" + columnWidth + "px;";

			var actualGraphHeight = graphHeight - ( displayOptions.labelTopPadding + displayOptions.labelHeight + (2 * displayOptions.tableVerticalPadding) + (2 * displayOptions.columnBorderWidth) + (2 * displayOptions.tableBorderWidth) );
			
			if (maxValue<=10) {
				maxValue = 10;
			} else if (maxValue<=50) {
				maxValue = 50;
			} else if (maxValue<=100) {
				maxValue = 100;
			} else {
				hundreds = Math.ceil(maxValue/100);
				maxValue = 100*(hundreds);
			}

			graphHTML += '<div class="graph_container">';
			
			var guideCount = 5;
			
			graphHTML += '<div class="guides_container">';
			for (var i=0; i < guideCount; i++) {
				var offset = Math.floor(i*((actualGraphHeight)/guideCount));
				graphHTML += '<div style="top:' + offset + 'px;"></div>';
			};
			graphHTML += '</div>';
			
			graphHTML += '<div class="guide_labels_container">';
			var labelDifference = Math.floor(maxValue/guideCount);
			for (var i=0; i < guideCount; i++) {
				var offset = Math.floor(i*((actualGraphHeight)/guideCount))+1;
				graphHTML += '<div style="top:' + offset + 'px;">' + (i==0 ? maxValue : (labelDifference*(guideCount-i)) ) + '</div>';
			};
			graphHTML += '</div>';

			graphHTML += '<div class="graph_table_container">';
			
			if (displayOptions.noDataMessage) graphHTML += '<div class="graph_no_data_message"></div>'
			
			graphHTML += '<table style="width:' + dimensions.width + 'px;height:' + dimensions.height + 'px;padding:' + displayOptions.tableVerticalPadding + 'px ' + displayOptions.tableHorizontalPadding + 'px;border-style:solid;border-width:' + displayOptions.tableBorderWidth + 'px;" class="graph" cellpadding="0" cellspacing="0">';

			// Table Columns

			graphHTML += '<tr>';
			
			var columnGrows = new Array();
			
			for (var i=0; i<dataSets.length; i++) {
				var dataSet   = dataSets[i];
				var dataName  = dataSet.name;
				var dataValue = parseInt(dataSet.value);

				// Calculate height of the bar
				var tdHeight     = Math.ceil(actualGraphHeight * (dataValue / maxValue));
				var columnHeight = (dataValue>0 ? tdHeight : 0 );
				var fillerHeight = actualGraphHeight - columnHeight;

				var columnTitle = graphData.axis.relationship;
				columnTitle = columnTitle.split("<<COUNT>>").join(dataValue);
				columnTitle = columnTitle.split("<<LABEL>>").join(dataName);
				columnTitle = columnTitle.split("<<APPNAME>>").join(apps.currentApp);
				columnTitle = columnTitle.split("<<PLURAL>>").join( (dataValue==1 ? "" : "s") );
				
				var columnID = container.parentNode.id + "-col" + i;
				graphHTML += '<td valign="bottom" class="graph_data' + (displayOptions.hoverHighlight ? ' hoverHighlight' : '') + '" style="' + columnWidthStyle + (i==0 ? 'padding-left:' + firstOffset + 'px;' : '') + '">';

//				if (fillerHeight>10) graphHTML += '<div style="border-style:solid;border-width:1px;' + columnWidthStyle + 'height:' + fillerHeight + 'px;" class="filler">&nbsp;</div>';
				if (columnHeight> 0) {
					var startHeight = '1';
					if (!displayOptions.wantGrow) startHeight = columnHeight;
					graphHTML += '<div id="' + columnID + '" style="' + columnWidthStyle + 'height:' + startHeight + 'px;border-style:solid;border-width:' + displayOptions.columnBorderWidth + 'px;border-bottom-style:none;' + (columnHeight<5 ? 'background-image:none;' : '') + '" class="bar" alt="' + columnTitle + '"';
				 	if (!displayOptions.disableBlob) graphHTML += ' onmouseover="javascript:Shimmer.blob.show(this);" onmouseout="javascript:Shimmer.blob.hide();"';
					graphHTML += '>&nbsp;</div>';
					if (displayOptions.wantGrow) columnGrows.push({id:columnID, height:columnHeight});
				}

				graphHTML += '</td>';

			}

			graphHTML += '</tr>';
			
			graphHTML += '<tr><td class="col_bottom_border" colspan="' + dataSets.length + '"></td></tr>';
		
			// Table Headers

			graphHTML += '<tr>';
			for (var i=0; i<dataSets.length; i++) {
				graphHTML += '<th class="graph_column_label" style="' + columnWidthStyle + 'overflow:hidden;height:' + displayOptions.labelHeight + 'px;' + (i==0 ? 'padding-left:' + firstOffset + 'px' : '') + '"><span style="height:' + displayOptions.labelHeight + 'px;padding-top:' + displayOptions.labelTopPadding + 'px;' + columnWidthStyle + '">' + dataSets[i].name + '</span></th>';
			}
			graphHTML += '</tr>';

			graphHTML += '</table>';
			
			graphHTML += '</div>';
			graphHTML += '</div>';

		} else graphHTML = '<table class="needmorestatdata"><tr><td valign="middle">We need a bit more data for this graph.</td></tr></table>';

		container.innerHTML = graphHTML;
		
		var growMorphs = new Array();
		
		columnGrows.each( function(growSet) {
			growMorphs.push(new Effect.Morph( $(growSet.id), {
				sync:true,
				style:"height:" + growSet.height + "px;"
			}));
		});
		
		new Effect.Parallel(growMorphs, {duration:0.65} );
		
	}
};