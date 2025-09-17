@extends('layouts.chairperson')
@section('content')
    <h1>Welcome Chairperson {{ auth()->user()->name }}</h1>
    {{-- dashboard-specific content here --}}
@endsection
