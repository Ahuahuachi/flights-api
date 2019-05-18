<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function return_view()
    {
        $title = "Upload XML files";

        return view('upload', [
            'title' => $title
        ]);
    }

    public function upload_files(Request $request)
    {

        // Get errors, if any, from loading files
        $err = [
            "air_err_code" => $request->file('air')->getError(),
            "air_err_message" => $request->file('air')->getErrorMessage(),
            "s_err_code" => $request->file('s')->getError(),
            "s_err_message" => $request->file('s')->getError(),
        ];

        // If any file loading error return details
        if ($err["air_err_code"] !== 0 || $err["s_err_code"] !== 0) {

            $response = [
                'success' => false,
                'error' => $err,
            ];

            return $response;
        }

        // Translate xml files to xml objects
        $air_file = $request->file('air');
        $s_file = $request->file('s');
        $air_xml = simplexml_load_file($air_file, null, null, "air", true);
        $s_xml = simplexml_load_file($s_file, null, null, "s", true)->children('s', true)->Body->children('');

        // Get information from xml objects

        // Get flight details
        $flight_details_list = $air_xml->FlightDetailsList->FlightDetails;

        foreach ($flight_details_list as $details) {
            $details_attr = $details->attributes();
            $flight_details_key = strval($details_attr['Key']);

            $flights_details[$flight_details_key] = [
                'Origin' => strval($details_attr['Origin']),
                'Destination' => strval($details_attr['Destination']),
                'DepartureTime' => strval($details_attr['DepartureTime']),
                'ArrivalTime' => strval($details_attr['ArrivalTime']),
                'FlightTime' => strval($details_attr['FlightTime']),
                'Equipment' => strval($details_attr['Equipment']),
                'OriginTerminal' => strval($details_attr['OriginTerminal']),
                'DestinationTerminal' => strval($details_attr['DestinationTerminal']),
            ];
        }

        // Get air segment details
        $air_segments_list = $air_xml->AirSegmentList->AirSegment;

        foreach ($air_segments_list as $segment) {
            $segment_attr = $segment->attributes();
            $segment_key = strval($segment_attr['Key']);
            $codeshare_info = $segment->CodeshareInfo;
            $flight_details_ref = $segment->FlightDetailsRef;

            if ($codeshare_info) {
                $has_codeshare_info = true;
                $codeshare_attr = $codeshare_info->attributes();
            } else {
                $has_codeshare_info = false;
                $codeshare_attr = [
                    'OperatingCarrier' => '',
                    'OperatingFlightNumber' => '',
                ];
            }

            $flight_details_ref_key = ($flight_details_ref) ? strval($flight_details_ref->attributes()['Key']) : '';



            $air_segments[$segment_key] = [
                'Carrier' => strval($segment_attr['Carrier']),
                'FlightNumber' => strval($segment_attr['FlightNumber']),
                'Origin' => strval($segment_attr['Origin']),
                'Destination' => strval($segment_attr['Destination']),
                'DepartureTime' => strval($segment_attr['DepartureTime']),
                'ArrivalTime' => strval($segment_attr['ArrivalTime']),
                'FlightTime' => strval($segment_attr['FlightTime']),
                'Equipment' => strval($segment_attr['Equipment']),
                'hasCodeshareInfo?' => $has_codeshare_info,
                'CodeshareInfo' => [
                    'OperatingCarrier' => strval($codeshare_attr['OperatingCarrier']),
                    'OperatingFlightNumber' => strval($codeshare_attr['OperatingFlightNumber']),

                ],
                'FlightDetailsRefKey' => $flight_details_ref_key
            ];
        }

        // Get air pricing solutions
        $pricing_solutions_list = $air_xml->AirPricingSolution;

        foreach ($pricing_solutions_list as $pricing_solution_element) {

            $pricing_solution = [
                'TotalPrice' => $pricing_solution_element->attributes()['TotalPrice']->__toString(),
            ];

            $pricing_solutions[] = $pricing_solution;
        }

        // Debug
        dd([
            "air_xml" => $air_xml,
            "flights_details" => $flights_details,
            'air_segments' => $air_segments,

        ]);

        // Build response

        $response = [
            'success' => true,
            'payload' => [],
        ];

        return $response;
    }
}
