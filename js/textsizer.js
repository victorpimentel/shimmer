textSizer = {
	'add': 	function(inputField) {
				if (inputField) {
					var resizeFunction = function() {
												var currentValue = inputField.value;
												if (currentValue.length < 1) {
													var placeholder = inputField.readAttribute("placeholder");
													if (placeholder && placeholder.length>0) {
														currentValue     = placeholder;
														inputField.value = currentValue;
													}
												}

												var theCanvas = $("canvas");

												var fontSize = getStyle(inputField,"font-size");
												theCanvas.style.fontSize = fontSize;

												var fontName = getStyle(inputField,"font-family");
												theCanvas.style.fontFamily = fontName;

												theCanvas.innerHTML = "<pre>" + currentValue + "</pre>";
												var newWidth = parseInt(getStyle(theCanvas,"width")) + 10;
												theCanvas.innerHTML = "";
												inputField.style.width = newWidth + "px";
											}
					inputField.observe("keypress", resizeFunction);
					inputField.observe("keyup", resizeFunction);
					inputField.observe("keydown", resizeFunction);
					inputField.observe("textSizer:resize", resizeFunction);
					resizeFunction();
				} else {
					// alert("TextSizer Error: Input does not exist.")
				}
			},
	'remove':	function(inputField) {
					inputField.stopObserving("keypress");
					inputField.stopObserving("keyup");
					inputField.stopObserving("keydown");
				}
}

function getStyle(x,styleProp) {
	if (x.currentStyle) {
		var y = x.currentStyle[styleProp];
	} else if (window.getComputedStyle) {
		var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
	}
	return y;
}