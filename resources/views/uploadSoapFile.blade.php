@extends('layout')

@section('title', $title)

@section('content')

<div class="title m-b-md">{{ $title }}</div>
<form action="/file_upload/soap" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div>
        <label for="s"><h3>Select S XML:</h3></label>
        <input type="file" name="s">
    </div>
    <br>
    <div>
        <button type="submit">Submit</button>
    </div>
</form>

@endsection
