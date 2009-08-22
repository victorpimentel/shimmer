

function currentUnixTimestamp() {
	var now = new Date();
	return Math.round(Date.UTC(now.getUTCFullYear(),now.getUTCMonth(),now.getUTCDate(),now.getUTCHours(),now.getUTCMinutes(),now.getUTCSeconds(),now.getUTCMilliseconds())/1000);
}

function strtotime(year,month,day,hour,minute,second) {
    var humDate = new Date( Date.UTC(
        parseInt(year),
        parseInt(month)-1,
        parseInt(day),
        parseInt(hour),
        parseInt(minute),
        parseInt(second)));
    return Math.round(humDate.getTime()/1000.0);
}

function doRelativeDate(year,month,day,hour,minute,second) {
    var RIGHTNOW = "Just now";
    in_seconds = strtotime(year,month,day,hour,minute,second);
    diff = currentUnixTimestamp()-in_seconds;

    // if server-provided time is greater than current time, show just now
    if (diff<0) return RIGHTNOW;


    months = Math.floor(diff/2592000);
    diff -= months*2419200;
    weeks = Math.floor(diff/604800);
    diff -= weeks*604800;
    days = Math.floor(diff/86400);
    diff -= days*86400;
    hours = Math.floor(diff/3600);
    diff -= hours*3600;
    minutes = Math.floor(diff/60);
    diff -= minutes*60;
    seconds = diff;

    var relative_date = "";

    if (months>0) {
        // over a month old, just show date (mm/dd/yyyy format)

        return day + " " + monthFromNumber(month-1);
    } else {
        if (weeks>0) {
            // weeks and days
            relative_date += weeks + ' week' + (weeks>1?'s':'');
            //relative_date += ( days>0 ? ', ' + days + ' day' + (days>1?'s':'') : '' );
        } else if (days>0) {
            // days and hours
            relative_date += (relative_date ?', ':'') + days + ' day' + (days>1?'s':'');
            //relative_date += ( hours>0 ? ', ' + hours + ' hour' + ( hours>1 ? 's':'' ) : '');
        } else if (hours>0) {
            // hours and minutes
            relative_date += (relative_date ?', ':'') + hours + ' hour' + (hours>1?'s':'');
            //relative_date += ( minutes>0 ? ', ' + minutes + ' minute' + ( minutes>1 ? 's':'' ) : '' );
        } else if (minutes>1) {
            // minutes only
            relative_date += (relative_date ?', ':'') + minutes + ' minute' + (minutes > 1 ? 's' : '' );
        } else {
            // seconds only
//            relative_date += (relative_date ?', ':'') + seconds + ' second' + (seconds>1?'s':'');
	    return RIGHTNOW;
        }
    }
    // show relative date and add proper verbiage
    return relative_date + ' ago';
}