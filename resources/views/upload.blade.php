@extends('layout')

@section('title', $title)

@section('content')
<div class="flex-center position-ref full-height">
    <div class="content">
        <h1 class="title m-b-md">{{ $title }}</h1>
    <form action="/upload_files" method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div>
                <label for="air"><h3>Select Air XML:</h3></label>
                <input type="file" name="air">
            </div>
            <div>
                <label for="s"><h3>Select S XML:</h3></label>
                <input type="file" name="s">
            </div>
            <br>
            <div>
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</div>
@endsection
