<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'PagesController@home');

Route::get('/upload-file/{file_type}', 'UploadController@upload_file');

Route::post('/file_upload/{file_type}', 'UploadController@upload_files');

Route::post('/flights', 'PagesController@flights');

Route::get('/php-info', function () {
    dd(phpinfo());
});
