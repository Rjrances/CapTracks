@extends('layouts.coordinator')

@section('title', 'Edit Milestone Template')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white shadow rounded">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Edit Milestone Template</h1>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 text-red-700 p-4 rounded">
            <strong>There were some problems with your input:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('coordinator.milestones.update', $milestone->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block font-semibold text-gray-700 mb-2">Milestone Name</label>
            <input type="text" id="name" name="name" 
                   value="{{ old('name', $milestone->name) }}" 
                   class="w-full px-4 py-2 border border-gray-300 rounded focus:ring focus:ring-blue-200"
                   required>
        </div>

        <div class="mb-6">
            <label for="description" class="block font-semibold text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description"
                      class="w-full px-4 py-2 border border-gray-300 rounded focus:ring focus:ring-blue-200"
                      rows="4">{{ old('description', $milestone->description) }}</textarea>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('coordinator.milestones.index') }}" class="text-gray-600 hover:underline">Back to List</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-black px-6 py-2 rounded">
                Update Milestone
            </button>
        </div>
    </form>
</div>
@endsection
