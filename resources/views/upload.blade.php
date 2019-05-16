@extends('layout')

@section('title', $title)

@section('content')
<div>
    <h1>{{ $title }}</h1>
    <form action="/api/flights" method="post">
        <div>
            <label for="air">Upload air xml</label>
            <input type="file" name="air" id="input-air">
        </div>
        <div>
            <label for="s">Upload s xml</label>
            <input type="file" name="s" id="input-s">
        </div>
        <div>
            <button type="submit">Submit</button>
        </div>
    </form>
</div>
@endsection
