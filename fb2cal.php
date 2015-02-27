<?php
require('fb2cal.conf.php'); // contains access token ($token)
$id = "369191986522047"; // Default Facebook group ID

//// log requests (usefull to see when Google refreshes google calendar : each 6-8 hours)
//$date = date(DATE_RFC2822);
//file_put_contents("fb2cal.log.txt", $date."  ".$_SERVER['REMOTE_ADDR']."  ".$_SERVER['HTTP_USER_AGENT'].PHP_EOL, FILE_APPEND);
////

///////////////////////////////////////////

function setHTTPHeaders($text_only) {
	if ($text_only)
		header('Content-Type: text/html; charset=utf-8');
	else {
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename=cal_'.$id.'.ics');
	}
}

function requestOwner($id, $token) {
	$url = "https://graph.facebook.com/[id]?method=GET&format=json&suppress_http_code=1&access_token=[token]";
	$url = str_replace("[id]", $id, $url);
	$url = str_replace("[token]", $token, $url);
	$json = json_decode(file_get_contents($url), true);
	return $json['name'];
}

function requestEvents($id, $token) {
	$fields = "description,name,start_time,end_time,location,owner,id,venue,updated_time";
	$url = "https://graph.facebook.com/[id]/events?fields=[fields]&method=GET&format=json&suppress_http_code=1&access_token=[token]";
  $url = str_replace("[id]", $id, $url);
	$url = str_replace("[token]", $token, $url);
	$url = str_replace("[fields]", $fields, $url);

	$json = json_decode(file_get_contents($url), true);
	return $json['data'];
}

function requestEvent($id, $token) {
	$fields = "description,name,start_time,end_time,location,owner,id,venue,updated_time";
	$url = "https://graph.facebook.com/[id]?fields=[fields]&method=GET&format=json&suppress_http_code=1&access_token=[token]";
	$url = str_replace("[id]", $id, $url);
	$url = str_replace("[token]", $token, $url);
	$url = str_replace("[fields]", $fields, $url);

	return json_decode(file_get_contents($url), true);
}

function getVCALHeader($name = NULL) {
	$vcard = "BEGIN:VCALENDAR".chr(10);
	$vcard .= "VERSION:2.0".chr(10);
	$vcard .= "PRODID:-//Facebook//NONSGML Facebook Events V1.0//EN".chr(10);
	
	if(isset($name) && !empty($name))
		$vcard .= "X-WR-CALNAME:".$name." - Facebook".chr(10);
	
	return $vcard;
}

function formatDate($fieldName, $fbDate, $offset=0) {
	$time = strtotime($fbDate)+$offset;
	
	if (strstr($fbDate, "T") !== FALSE)
		return $fieldName.":".gmdate('Ymd\THis\Z', $time);
	else
		return $fieldName.":".gmdate('Ymd', $time);
//		return $fieldName.";VALUE=DATE:".date('Ymd', $time);
}

function print_event($val) {
	$appendurl = "https://www.facebook.com/events/";

	echo "BEGIN:VEVENT".chr(10);
	echo "UID:e".$val['id']."@facebook.com".chr(10);
	echo "SUMMARY:".$val['name'].chr(10);
	echo "LOCATION:".$val['location'].chr(10);
	
	echo formatDate("LAST-MODIFIED", $val['updated_time']).chr(10);
	echo formatDate("DTSTART", $val['start_time']).chr(10);

	if (isset($val['end_time']))
		echo formatDate("DTEND", $val['end_time']).chr(10);
	else
		echo formatDate("DTEND", $val['start_time'], 3600*24).chr(10);

	$description = str_replace(',', '\,', $val['description']);
	$description = str_replace(chr(10), '\n', $description);
	$description = $description.'\n\n'.$appendurl.$val['id'];
	echo "DESCRIPTION:".$description.chr(10);

	echo "END:VEVENT".chr(10);
}

$baseurl = "https://graph.facebook.com/";
$event_only = false;

setHTTPHeaders(isset($_GET['text']));

if (isset($_GET['event-id']) && is_numeric($_GET['event-id'])) {
	echo getVCALHeader();
	print_event(requestEvent($_GET['event-id'], $token));	
}
else {
	if (isset($_GET['page-id']) && is_numeric($_GET['page-id']))
		$id = $_GET['page-id'];
	else if (isset($_GET['id']) && is_numeric($_GET['id']))
		$id = $_GET['id']; 
	
	$name = requestOwner($id, $token);
	echo getVCALHeader($name);
	
	$events = requestEvents($id, $token);
	if (!empty($events)) foreach($events as $event) {
		print_event($event);
	}
}

?>
END:VCALENDAR
