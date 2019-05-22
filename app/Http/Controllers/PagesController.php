<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class PagesController extends Controller
{
    public function home()
    {
        return view('welcome', ['title' => 'Welcome!']);
    }
}
