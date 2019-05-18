<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class PagesController extends Controller
{
    public function home()
    {
        return view('welcome');
    }

    public function flights(Request $request)
    {

        $flights = [];

        $response = [
            "count" => count($flights),
            "flights" => [
                $flights
            ]
        ];



        // $airFile = $request->file('air');
        // $airXML = simplexml_load_file($airFile);

        $response = $request->file('air');
        return $response;
    }
}
