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
