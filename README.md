# Description
Export Facebook events to iCalendar/vCalendar format

# Setup	
Rename fbcalendar.conf.php.example to fbcalendar.conf.php and put your 
own Facebook API token in it.

# Use
fbcalendar.php?page-id=<facebook page id>
Get every event related to the facebook page.

fbcalendar.php?event-id=<facebook event id>
Only get one event.

adding &text to the URL parameters allows to see the calendar without downloading it.

# Tools
check calendars : http://severinghaus.org/projects/icv/
Find page ids : http://www.findmyfbid.com/
