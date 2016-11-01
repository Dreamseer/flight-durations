<?php
require(dirname(__FILE__) . '/../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

// get request parameters
$request = Request::createFromGlobals();
$from = $request->query->get('from');
$to = $request->query->get('to');
$departureAirport = $request->query->get('departureAirport');
$destinationAirport = $request->query->get('destinationAirport');

function getTimezones() {
	$timezones = array();
	$flightData = array_map('str_getcsv', file(dirname(__FILE__) . '/../vendor/openflights/airports.dat'));
	foreach ($flightData as $data) {
		if ($data[11] !== '\N') {
			$timezones[$data[4]] = $data[11];
		}
	}
	return $timezones;
}

function getTimezoneForAirport($airport) {
	global $timezones;
	$timezone = new DateTimeZone($timezones[$airport]);
	return $timezone->getName();
}

function getDuration($from, $to, $departureAirport, $destinationAirport) {
	// set local times ...
	$timezone = getTimezoneForAirport($departureAirport);
	$fromLocal = new DateTime($from . ' ' . $timezone);
	$timezone = getTimezoneForAirport($destinationAirport);
	$toLocal = new DateTime($to . ' ' . $timezone);

	// ... and calculate difference
	$diff = $toLocal->diff($fromLocal, true);

	return array(
		'from'     => $fromLocal->format("Y-m-d\TH:iP"),
		'to'       => $toLocal->format("Y-m-d\TH:iP"),
		'duration' => $diff->format("P%dDT%hH%iM"),
	);
}

function getData($from, $to, $departureAirport, $destinationAirport) {
	$data = null;
	// @TODO: check for valid datetime strings
	// @TODO: check for valid IATA codes
	if ($from !== null && $to !== null && $departureAirport !== null && $destinationAirport !== null) {
		$data = getDuration($from, $to, $departureAirport, $destinationAirport);
	}
	return $data;
}

$timezones = getTimezones();
$data = getData($from, $to, $departureAirport, $destinationAirport);

$response = new JsonResponse(
	$data,
	$data ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_REQUEST,
	array('content-type' => 'application/json')
);
$response->setCharset('utf-8');
$response->prepare($request);
$response->send();
