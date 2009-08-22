

inPlace = {
	'add': 	function(span,options) { //inputSelector,saveCallback
				if (typeof options == "undefined") options = {};
				if (typeof options.inputSelector == "undefined") options.inputSelector = "input";
				var input = span.parentNode.select(options.inputSelector)[0];
				if (span && input) {
					input.hide();
				
					// SHOW AND HIDE ELEMENTS
					var setTextDisplayed = function(display) {
						if (display) {
							input.blur();
							input.hide();
							if (options.inputSelector.indexOf("input")==0) textSizer.remove(input);
							span.show();
						} else {
							span.hide();
							input.show();
							input.focus();
							if (options.inputSelector.indexOf("input")==0) textSizer.add(input);
						}
					}
				
					// SAVE THE NEW VALUE
					var saveFunction = function() {
						if ( !isSet(options.replaceLabel) || options.replaceLabel==true ) span.innerHTML = input.value.replace(/^\s*|\s*$/g,'');
						if (options.saveCallback) options.saveCallback(input,span);
					};
				
					var spanClicked = function() {
						if ( span.visible() ) {
							if (options.inputSelector.indexOf("select")==0) {
								for (var i=0; i < input.options.length; i++) {
									if (input.options[i].value == span.innerHTML) {
										input.options[i].selected=true;
										break;
									}
									input.options[0].selected=true;
								};
							} else {
								input.value = span.innerHTML.replace(/&lt;/g,"<").replace(/&gt;/g,">");
							}
						
							setTextDisplayed(false);
					
							// ESCAPE & ENTER
							input.observe('keydown', function(e) {
								if (e.keyCode == Event.KEY_ESC) {
									this.stopObserving('blur');
									this.stopObserving('keydown');
									setTextDisplayed(true);
									Event.stop(e);
								} else if (e.keyCode == Event.KEY_RETURN) {
									this.stopObserving('blur');
									if (options.inputSelector.indexOf("select")==0) this.stopObserving('change');
									setTextDisplayed(true);
									this.stopObserving('keydown');
									saveFunction();
								}
							});
					
							// BLUR
							input.observe('blur', function(e) {
								if (options.inputSelector.indexOf("select")==0) this.stopObserving('change');
								setTextDisplayed(true);
								saveFunction();
							});
						
							// SELECT CHANGE
							if (options.inputSelector.indexOf("select")==0) input.observe('change', function(e) {
								this.stopObserving('blur');
								this.stopObserving('change');
								setTextDisplayed(true);
								saveFunction();
							});
					
						} else {
							setTextDisplayed(true);
						}
					}
				
					span.observe('click', spanClicked);
					span.observe('editinplace:spanclicked', spanClicked);
				}
			},
	'remove':	function(span,inputSelector) {
					if (typeof inputSelector == "undefined") inputSelector = "input";
					if (span && inputSelector) {
						var input = span.parentNode.select(inputSelector)[0];
						input.stopObserving('keydown');
						input.stopObserving('blur');
						input.stopObserving('change');
						span.stopObserving('click');
						span.stopObserving('editinplace:spanclicked');
				
						input.hide();
						span.show();
					}
				}
}