<?php
error_reporting(E_ERROR | E_PARSE);

require('fbcalendar.conf.php'); // contains access token ($token)
$id = "369191986522047"; // Default Facebook group ID

//// log requests (usefull to see when Google refreshes google calendar : each 6-8 hours)
//$date = date(DATE_RFC2822);
//file_put_contents("fb2cal.log.txt", $date."  ".$_SERVER['REMOTE_ADDR']."  ".$_SERVER['HTTP_USER_AGENT'].PHP_EOL, FILE_APPEND);
////

// v2.5 available until "At least until April 2018" (https://developers.facebook.com/docs/apps/changelog)
define("FACEBOOK_API", "https://graph.facebook.com/v2.5");

///////////////////////////////////////////

function setHTTPHeaders($filename = NULL) {
	if ($filename == NULL)
		header('Content-Type: text/html; charset=utf-8');
	else {
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename=cal_'.$filename.'.ics');
	}
}

function requestOwner($id, $token) {
	$url = FACEBOOK_API."/".$id."?access_token=".$token;
	$json = json_decode(file_get_contents($url), true);
	return $json['name'];
}

function requestEvents($id, $token) {
	$url = FACEBOOK_API."/".$id."/events?access_token=".$token;
	$json = json_decode(file_get_contents($url), true);
	return $json['data'];
}

function requestEvent($id, $token) {
	$url = FACEBOOK_API."/".$id."?access_token=".$token;
	return json_decode(file_get_contents($url), true);
}

function getVCALHeader($name = NULL) {
	$vcard = "BEGIN:VCALENDAR".chr(13).chr(10);
	$vcard .= "PRODID:-//Facebook//NONSGML Facebook Events V1.0//EN".chr(13).chr(10);
	if(isset($name) && !empty($name)) 
		$vcard .= "X-WR-CALNAME:".$name." - Facebook".chr(13).chr(10);
	$vcard .= "X-PUBLISHED-TTL:PT2H".chr(13).chr(10);
	$vcard .= "VERSION:2.0".chr(13).chr(10);
	$vcard .= "CALSCALE:GREGORIAN".chr(13).chr(10);
	$vcard .= "METHOD:PUBLISH".chr(13).chr(10);
	
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
	if (isset($val['event_times']) && count($val['event_times']) > 0)
		foreach ($val['event_times'] as $time)
			print_event_with_time($val, $time);
	else
		print_event_with_time($val, $val);
}

function print_event_with_time($val, $timearray) {
	$appendurl = "https://www.facebook.com/events/";

	echo "BEGIN:VEVENT".chr(13).chr(10);
	echo "UID:e".$timearray['id']."@facebook.com".chr(13).chr(10);
	echo "SUMMARY:".$val['name'].chr(13).chr(10);
	echo "URL:".$appendurl.$timearray['id'].chr(13).chr(10);
	
	// place
	if(isset($val['place'])) {
		$place = $val['place'];
		$location = "";
		
		if(isset($place["name"]))
			$location .= $place["name"];
			
		if(isset($place["location"])) {
			if(!empty($place["location"]["street"]))
				$location .= ", ".$place["location"]["street"];
				
			if(!empty($place["location"]["zip"]))
				$location .= ", ".$place["location"]["zip"];
				
			if(!empty($place["location"]["city"]))
				$location .= ", ".$place["location"]["city"];
				
			if(!empty($place["location"]["country"]))
				$location .= ", ".$place["location"]["country"];
		}
		
		echo "LOCATION:".$location.chr(13).chr(10);
	}
	
	// dates
	echo formatDate("DTSTART", $timearray['start_time']).chr(13).chr(10);
	if (isset($val['updated_time']))
		echo formatDate("LAST-MODIFIED", $val['updated_time']).chr(13).chr(10);
	if (isset($timearray['end_time']))
		echo formatDate("DTEND", $timearray['end_time']).chr(13).chr(10);

	// desc
	$description = str_replace(',', '\,', $val['description']);
	$description = str_replace(chr(10), '\n', $description);
	$description = $description.'\n\n'.$appendurl.$timearray['id'];
	echo "DESCRIPTION:".$description.chr(13).chr(10);

	// closure
	echo "END:VEVENT".chr(13).chr(10);
}

$event_only = false;

if (isset($_GET['event-id']) && is_numeric($_GET['event-id'])) {
	
	setHTTPHeaders(isset($_GET['text']) ? null : "e".$_GET['event-id']);
	echo getVCALHeader();
	print_event(requestEvent($_GET['event-id'], $token));
}
else {
	if (isset($_GET['page-id']) && is_numeric($_GET['page-id']))
		$id = $_GET['page-id'];
	else if (isset($_GET['id']) && is_numeric($_GET['id']))
		$id = $_GET['id']; 
	
	setHTTPHeaders(isset($_GET['text']) ? null : "c".$id);
	
	$name = requestOwner($id, $token);
	echo getVCALHeader($name);
	
	$events = requestEvents($id, $token);
	if (!empty($events)) foreach($events as $event) {
		print_event($event);
	}
}
?>
END:VCALENDAR
