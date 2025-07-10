@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-4">{{ $event->title }}</h1>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($event->date)->format('Y-m-d') }}</p>
    <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($event->time)->format('h:i A') }}</p>
    <p class="mt-4">{{ $event->description ?? 'No description available.' }}</p>

    <a href="{{ url()->previous() }}" class="mt-6 inline-block text-blue-600 hover:underline">Back to dashboard</a>
</div>
@endsection
