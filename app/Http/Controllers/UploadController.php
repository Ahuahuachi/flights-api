<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classes\FlightOptions;


class UploadController extends Controller
{
    public function return_view()
    {
        $title = "Upload XML files";

        return view('uploadAirFile', [
            'title' => $title
        ]);
    }

    public function upload_file($file_type)
    {

        switch ($file_type) {
            case 'Air':
                $title = 'Upload ' . $file_type . ' file';
                return view('upload' . $file_type . 'File', [
                    'title' => $title,
                ]);
                break;

            case 'Soap':
                $title = 'Upload ' . $file_type . ' file';
                return view('upload' . $file_type . 'File', [
                    'title' => $title,
                ]);
                break;

            default:
                return 'File not supported';
                break;
        }
    }

    public function upload_files(Request $request, $file_type)
    {

        // Get errors, if any, from loading files
        // $err = [
        //     "air_err_code" => $request->file('air')->getError(),
        //     "air_err_message" => $request->file('air')->getErrorMessage(),
        //     "s_err_code" => $request->file('s')->getError(),
        //     "s_err_message" => $request->file('s')->getError(),
        // ];

        // If any file loading error return details
        // if ($err["air_err_code"] !== 0 || $err["s_err_code"] !== 0) {

        //     $response = [
        //         'success' => false,
        //         'error' => $err,
        //     ];

        //     return $response;
        // }

        // Translate xml files to SimpleXMLElement
        switch ($file_type) {
            case 'air':
                $air_file = $request->file('air');
                $air_xml = simplexml_load_file($air_file, null, null, "air", true);
                $response = [
                    'success' => true,
                    'payload' => FlightOptions::getFlightOptionsAir($air_xml),
                ];
                break;

            case 'soap':
                $s_file = $request->file('s');
                $s_xml = simplexml_load_file($s_file, null, null, "s", true)->children('s', true)->Body->children('');
                $response = [
                    'success' => true,
                    'payload' => FlightOptions::getFlightOptionsSoap($s_xml)
                ];
                break;
            default:
                $response = [
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid file',
                    ],
                ];
                break;
        }



        return $response;
    }
}
