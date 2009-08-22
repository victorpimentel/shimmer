versionsUI.table = {
	
	setContent: function(newContent) {
		$('version-table-holder').innerHTML = newContent;
		versionsUI.table.scroll.constrainer.reapplyConstraint();
		versionsUI.table.scroll.slider.setTop(0);
	},
	
	scroll: {
		speed: 0.4,

		components: { slider:false, barcontainer:false, container:false, textcontainer:false, wheelRegion:false },

		setup: function() {
			versionsUI.table.scroll.components.slider        = $('scroll-knob');
			versionsUI.table.scroll.components.barcontainer  = $('scroll-bar');
			versionsUI.table.scroll.components.container     = $('scroll-cutoff');
			versionsUI.table.scroll.components.textcontainer = $('version-table-holder');
			versionsUI.table.scroll.components.wheelRegion   = $('versions_content');

			versionsUI.table.scroll.components.slider.observe('mousedown', function(downEvent) {
				versionsUI.table.scroll.tracker.start = versionsUI.table.scroll.topOffset() + downEvent.pointerY();
				document.observe('mousemove', function(moveEvent) {
					var eY        = moveEvent.pointerY();
					var boxTop    = versionsUI.table.scroll.topOffset();
					var boxHeight = versionsUI.table.scroll.content.availableHeight();

					// If the mouse is within the vertical container boundaries
					if (eY>=boxTop && eY <= boxTop + boxHeight) {
						var oldTop   = versionsUI.table.scroll.components.slider.positionedOffset().top;
						var newPoint = boxTop + eY;
						var newTop   = oldTop - (versionsUI.table.scroll.tracker.start - newPoint);
						versionsUI.table.scroll.tracker.start = newPoint;
						versionsUI.table.scroll.slider.setTop(newTop);
					} else {
						// If the mouse is above or below the vertical container boundaries, set to 0% or 100% respectively
						versionsUI.table.scroll.slider.setTop((eY < boxTop) ? 0 : (boxHeight - versionsUI.table.scroll.slider.height()));
					}
				});
				document.observe('mouseup', function(upEvent) {
					versionsUI.table.scroll.tracker.start = 0;
					document.stopObserving('mousemove');
					document.stopObserving('mouseup');

					versionsUI.table.scroll.slider.setTop(versionsUI.table.scroll.components.slider.positionedOffset().top, true);

	/*				// Slide right to the start/end if we are really close already
					var scrollPercent = versionsUI.table.scroll.slider.amountDown();
					if (scrollPercent<=0.05) {
						versionsUI.table.scroll.slider.setTop(0, true);
					} else if (scrollPercent>=0.95) {
						versionsUI.table.scroll.slider.setTop(versionsUI.table.scroll.content.availableHeight()-versionsUI.table.scroll.slider.height(), true);
					}
	*/
				});
				
				// Return false to prevent text selection
				return false;
			});
			versionsUI.table.scroll.components.barcontainer.observe('click', function(clickEvent) {
				if (clickEvent.element().readAttribute('id')==versionsUI.table.scroll.components.barcontainer.readAttribute('id')) {
					var centerPoint = clickEvent.pointerY() - versionsUI.table.scroll.topOffset();
					var newTop      = centerPoint - (0.5*versionsUI.table.scroll.slider.height());
					versionsUI.table.scroll.slider.setTop(newTop, true);
				}
			});

			versionsUI.table.scroll.components.wheelRegion.observe('mousewheel', versionsUI.table.scroll.handleMouseWheel);
			versionsUI.table.scroll.components.wheelRegion.observe('DOMMouseScroll', versionsUI.table.scroll.handleMouseWheel);

			versionsUI.table.scroll.constrainer.setWantedRows(10);
			versionsUI.table.scroll.constrainer.go();
		},

		handleMouseWheel: function(event) {
			// Taken from http://adomas.org/javascript-mouse-wheel/
			var delta = 0;
			if (!event) event = window.event; // For IE
			if (event.wheelDelta) { // IE/Opera
				delta = event.wheelDelta/120;
				if (window.opera) delta = -delta; // In Opera 9, delta differs in sign as compared to IE
			} else if (event.detail) {
				delta = -event.detail/3; // In Mozilla, sign of delta is different than in IE. Also, delta is multiple of 3
			}

			if (delta) { // Positive = up, Negative = down
				var sliderPosition = versionsUI.table.scroll.slider.amountDown();
				if ((delta<0 && sliderPosition<1) || (delta>0 && sliderPosition>0)) {
					var newTop = versionsUI.table.scroll.components.slider.positionedOffset().top - (delta*40);
					versionsUI.table.scroll.slider.setTop(newTop);
				}
			}
			Event.stop(event);
		},

		tracker: {
			start:false
		},

		refresh: function() {
			versionsUI.table.scroll.slider.refresh();
			versionsUI.table.scroll.content.refresh();
		},

		topOffset: function() {
			var element = versionsUI.table.scroll.components.container;
			var totalOffset = 0;
			while (element.offsetParent) {
				totalOffset += element.offsetTop;
				element = element.offsetParent;
			}
			return totalOffset;
		},

		content: {
			availableHeight: function() {
				return versionsUI.table.scroll.components.container.getHeight();
			},

			// Height of the content (text-container) div
			height: function() {
				return versionsUI.table.scroll.components.textcontainer.getHeight();
			},

			refresh: function(amountDown, animate) {
				if (typeof amountDown == "undefined") amountDown=versionsUI.table.scroll.slider.amountDown();
				if (typeof animate == "undefined") animate=false;
				var fullNegative = (versionsUI.table.scroll.content.height() - versionsUI.table.scroll.content.availableHeight()) * -1;
				var newTop = amountDown * fullNegative;
				if (!animate) {			
					versionsUI.table.scroll.components.textcontainer.setStyle({top:newTop+'px'});
				} else {
					versionsUI.table.scroll.content.animateToTop(newTop);
				}
			},
			
			internalOffset: function() {
				return versionsUI.table.scroll.components.textcontainer.positionedOffset().top;
			},

			animateToTop: function(top) {
				new Effect.Morph(versionsUI.table.scroll.components.textcontainer, {
					style: {top:top+'px'},
					duration:versionsUI.table.scroll.speed
				});			
			},
			
			ensureRowIsShown: function(rowNum) {
				var rHeight = versionsUI.table.scroll.constrainer.getRowHeight();
				var rows = versionsUI.table.scroll.components.container.select('div#versions-table div.versions-row');
				if (rows) {
					if (rowNum > rows.length-1) rowNum = rows.length-1;
					// var theRow = rows[rowNum];
					var availableHeight  = versionsUI.table.scroll.content.availableHeight();
					var internalOffset   = rowNum * rHeight;
					var scrollCompensate = versionsUI.table.scroll.content.internalOffset();
					var internalDiff     = availableHeight - internalOffset - scrollCompensate;
					if (internalDiff<=0 || internalDiff>=availableHeight) {
						var newSliderPos = versionsUI.table.scroll.slider.height()*(internalOffset/availableHeight);
						versionsUI.table.scroll.slider.setTop(newSliderPos,true)
					}
				}
			}
		},

		slider: {
			setTop: function(top, animate) {
				if (typeof animate == "undefined") animate=false;
				var fixedTop = versionsUI.table.scroll.slider.fixOverslide(top);
				if (!animate) {
					versionsUI.table.scroll.components.slider.setStyle({top:fixedTop+'px'});
					versionsUI.table.scroll.content.refresh(versionsUI.table.scroll.slider.amountDown(fixedTop));
				} else {
					new Effect.Morph(versionsUI.table.scroll.components.slider, {
						style: {top:fixedTop+'px'},
						duration:versionsUI.table.scroll.speed
					});
					versionsUI.table.scroll.content.refresh(versionsUI.table.scroll.slider.amountDown(fixedTop),true);
				}
			},

			height: function() {
				return versionsUI.table.scroll.components.slider.getHeight();
			},

			fixOverslide: function(newTop) {
				if (newTop+versionsUI.table.scroll.slider.height()>versionsUI.table.scroll.content.availableHeight()) {
					return versionsUI.table.scroll.content.availableHeight() - versionsUI.table.scroll.slider.height();
				} else if (newTop<0) {
					return 0;
				}
				return newTop;
			},

			amountDown: function(barTop) {
				if (typeof barTop == "undefined") barTop = versionsUI.table.scroll.components.slider.positionedOffset().top;
				var bottomLimit = versionsUI.table.scroll.content.availableHeight() - versionsUI.table.scroll.slider.height();
				return (barTop/bottomLimit);
			},

			refresh: function() {
				var contentHeight = versionsUI.table.scroll.content.height();
				var availableHeight = versionsUI.table.scroll.content.availableHeight();
				if (contentHeight > availableHeight) {
					versionsUI.table.scroll.slider.show();
					var visiblePercent = availableHeight / contentHeight;
					var newSliderHeight = availableHeight * visiblePercent;
					versionsUI.table.scroll.slider.setHeight(newSliderHeight);
				} else {
					versionsUI.table.scroll.slider.hide();
				}
			},

			hide: function() {
				versionsUI.table.scroll.components.slider.hide();
			},

			show: function() {
				versionsUI.table.scroll.components.slider.show();
			},

			setHeight: function(height) {
				versionsUI.table.scroll.components.slider.setStyle({height:height+'px'});
			}
		},

		// Fixes the scroll container to have a height which perfectly matches a given number of container table rows
		constrainer: {
			wantedRows:  10,
			wantedReset: 10,

			setWantedRows: function(wanted) {
				var rowCount = versionsUI.table.scroll.components.container.select('div#versions-table div.versions-row').length;
				if (wanted>rowCount) wanted = rowCount;	
				versionsUI.table.scroll.constrainer.wantedRows = wanted;
			},
			
			reapplyConstraint: function() {
				versionsUI.table.scroll.constrainer.setWantedRows(versionsUI.table.scroll.constrainer.wantedReset);
				versionsUI.table.scroll.constrainer.go();
			},

			go: function(animate) {
				if (typeof animate == "undefined") animate=false;
				if (versionsUI.table.scroll.constrainer.wantedRows>0) {
					var rHeight = versionsUI.table.scroll.constrainer.getRowHeight();
					var fullHeight = rHeight * versionsUI.table.scroll.constrainer.wantedRows;
					versionsUI.table.scroll.components.container.setStyle({height:fullHeight+'px'});
					versionsUI.table.scroll.refresh();
					redrawAllGraphsFromCache(animate);
				}
			},

			getRowHeight: function() {
				var rows = versionsUI.table.scroll.components.container.select('div#versions-table div.versions-row');
				if (rows && rows.length>0) {
					return rows[0].getHeight();
				}
			},
		}
	}

}