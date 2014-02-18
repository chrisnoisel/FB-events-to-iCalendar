<?php

require('fb2cal.conf.php'); // contains access token ($token)
$id = "369191986522047"; // Facebook group ID

///////////////////////////////////////////


//header('Content-Type: text/html; charset=utf-8');
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=cal_'.$id.'.ics');

$baseurl = "https://graph.facebook.com/";
$params = "/events?fields=description,name,start_time,end_time,location,owner,id,venue,updated_time";
$endurl = "&method=GET&format=json&suppress_http_code=1&access_token=";
$appendurl = "https://www.facebook.com/events/";

$json = json_decode(file_get_contents($baseurl.$id.$params.$endurl.$token), true);
$data = $json['data'];

?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Facebook//NONSGML Facebook Events V1.0//EN
<?php

function formatDate($fbDate) {
	$time = strtotime($fbDate);
	return gmdate('Ymd\THis\Z', $time);
}

foreach($data as $val) {

	echo "BEGIN:VEVENT".chr(10);
	echo "UID:e".$val['id']."@facebook.com".chr(10);
	echo "SUMMARY:".$val['name'].chr(10);
	echo "LOCATION:".$val['location'].chr(10);
	
	echo "LAST-MODIFIED:".formatDate($val['updated_time']).chr(10);
	echo "DTSTART:".formatDate($val['start_time']).chr(10);
	echo "DTEND:".formatDate($val['end_time']).chr(10);

	$description = str_replace(',', '\,', $val['description']);
	$description = str_replace(chr(10), '\n', $description);
	$description = $description.'\n\n'.$appendurl.$val['id'];
	echo "DESCRIPTION:".$description.chr(10);

	echo "END:VEVENT".chr(10);
}

?>
END:VCALENDAR
