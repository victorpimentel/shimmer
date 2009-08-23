

calendar = {
	container:    'editDateCalendar',
	
	// Which date are we currently viewing?
	currentYear:  2009,
	currentMonth: 8,
	
	// White date has been actually chosen?
	chosenYear: 2009,
	chosenMonth: 8,
	chosenDay: 17,
	
	setChosenDate: function(theDate) {
		calendar.chosenYear  = theDate.getFullYear();
		calendar.chosenMonth = theDate.getMonth();
		calendar.chosenDay   = theDate.getDate();
		calendar.setCurrentDate(theDate);
	},
	
	setCurrentDate: function(theDate) {
		calendar.currentYear  = theDate.getFullYear();
		calendar.currentMonth = theDate.getMonth();
	},
	
	previousMonth: function() {
		calendar.currentMonth--;
		if (calendar.currentMonth<0) {
			calendar.currentMonth = 11;
			calendar.currentYear--;
		}
		calendar.newDraw();
	},
	
	nextMonth: function() {
		calendar.currentMonth++;
		if (calendar.currentMonth>=12) {
			calendar.currentMonth = 0;
			calendar.currentYear++;
		}
		calendar.newDraw();
	},
	
	// How many days from last many fill in the start of the calendar for this month?
	lastMonthsDaysAtStartOfMonth: function(month, year) {
		var test = new Date();
		test.setYear(year);
		test.setMonth(month);
		test.setDate(1);
		var dayNum = test.getDay();
		
		if (dayNum==0) return 6;
		
		return dayNum-1;
	},
	
	newDraw: function() {
		var o = $(calendar.container);

		// Create a Date object for today
		var today = new Date();
		var currentDate =  today.getDate();
		var currentMonth = today.getMonth();
		var currentYear =  today.getFullYear();
		var calendarTitle = monthFromNumber(calendar.currentMonth) + " " + calendar.currentYear;

		var code = '';
		code += '<div class="cal_container">';
		code += '  <div class="shadowRight"></div><div class="shadowLeft"></div><div class="shadowBottomRight"></div><div class="shadowBottom"></div><div class="shadowBottomLeft"></div>';
		code += '    <table class="cal" cellpadding="0" cellspacing="0" border="0">';
		code += '      <tr><th onclick="calendar.previousMonth();" class="monthNav">&lt;</th><th colspan="5" class="calendarTitle" onclick="">' + calendarTitle + '</th><th class="monthNav" onclick="calendar.nextMonth();">&gt;</th></tr>';
		code += '      <tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr>'
		code += '      <tr>';

		// Create a Date object which we increment from day-to-day
		var incrementDate = new Date();
		incrementDate.setYear(calendar.currentYear);
		incrementDate.setMonth(calendar.currentMonth);
		incrementDate.setDate(1);

		// Step 1: Work out how many days at the start of the calendar actually belong to the previous month.
		var difference = calendar.lastMonthsDaysAtStartOfMonth(calendar.currentMonth, calendar.currentYear);

		incrementDate.setDate(incrementDate.getDate() - difference);
		for (var i=0; i < difference; i++) {
			var dayNum = incrementDate.getDate();
			code += '<td class="other" onclick="calendar.previousMonth();">' + incrementDate.getDate() + '</td>';
			incrementDate.setDate(incrementDate.getDate()+1);
		};
		
		// Step 2: Loop through each day of the month
		var row = 1;
		var originalMonth = incrementDate.getMonth();
		while (incrementDate.getDate()<32 && incrementDate.getMonth()==originalMonth) {
			if (incrementDate.getDay()==1) {
				code += '</tr><tr>';
			}

			var classString = "";
			if ( incrementDate.getDate()==currentDate && incrementDate.getMonth()==currentMonth && incrementDate.getFullYear()==currentYear ) {
				classString = ' class="today"';
			} else if ( incrementDate.getDate()==calendar.chosenDay && incrementDate.getMonth()==calendar.chosenMonth && incrementDate.getFullYear()==calendar.chosenYear ) {
				classString = ' class="chosen"';
			}

			code += '<td' + classString + ' onclick="">' + incrementDate.getDate() + '</td>';
	
			incrementDate.setDate(incrementDate.getDate()+1);
		};

		// Step 3: Add days for the next month if needed
		while(incrementDate.getDay() != 1) {
			code += '<td class="other" onclick="calendar.nextMonth();">' + incrementDate.getDate() + '</td>';
			incrementDate.setDate(incrementDate.getDate()+1);
		}

		code += '    </tr>';
		code += '  </table>';
		code += '</div>';

		o.innerHTML = code;
		
		o.observe('mousewheel',     calendar.scroll.handleMouseWheel);
		o.observe('DOMMouseScroll', calendar.scroll.handleMouseWheel);
	},
	
	scroll: {
		
		throttle: {
			back:    1,
			forward: 2,
			
			lastDirection: false,
			tally: 0,
			addTallyForDirection: function(direction) {
				if (calendar.scroll.throttle.lastDirection != direction) {
					calendar.scroll.throttle.lastDirection = direction;
					calendar.scroll.throttle.tally = 1;
				} else {
					calendar.scroll.throttle.tally++;
				}
				
				if (calendar.scroll.throttle.tally > 15) {
					if (calendar.scroll.throttle.lastDirection == calendar.scroll.throttle.back) {
						calendar.previousMonth();
					} else if (calendar.scroll.throttle.lastDirection == calendar.scroll.throttle.forward) {
						calendar.nextMonth();
					}
					calendar.scroll.throttle.tally = 0;
				}
			}
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
				if (delta>0) {
					calendar.scroll.throttle.addTallyForDirection(calendar.scroll.throttle.back);
					// calendar.previousMonth();
				} else if (delta<0) {
					calendar.scroll.throttle.addTallyForDirection(calendar.scroll.throttle.forward);
					// calendar.nextMonth();
				}
			}
			Event.stop(event);
		},
		
	}
}

function monthFromNumber(monthNum) {
	var now = new Date();
	var isMovember = now.getMonth()==10;
	
	var month=new Array(12);
	month[0]="January";
	month[1]="February";
	month[2]="March";
	month[3]="April";
	month[4]="May";
	month[5]="June";
	month[6]="July";
	month[7]="August";
	month[8]="September";
	month[9]="October";
	month[10]=isMovember ? "Movember" : "November";
	month[11]="December";
	return month[monthNum];
}