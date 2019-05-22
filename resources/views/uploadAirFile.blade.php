@extends('layout')

@section('title', $title)

@section('content')
<div class="title m-b-md">{{ $title }}</div>
<form action="/file_upload/air" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div>
        <label for="air"><h3>Select Air XML:</h3></label>
        <input type="file" name="air">
    </div>
    <br>
    <div>
        <button type="submit">Submit</button>
    </div>
</form>
@endsection
