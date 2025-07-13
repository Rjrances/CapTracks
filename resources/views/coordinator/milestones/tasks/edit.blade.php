@extends('layouts.coordinator')

@section('title', 'Edit Task')

@section('content')
<div class="max-w-3xl mx-auto py-10">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">
        Edit Task for: {{ $milestone->name }}
    </h2>

    <form action="{{ route('coordinator.milestones.tasks.update', [$milestone->id, $task->id]) }}" method="POST" class="bg-white shadow p-6 rounded">
        @csrf
        @method('PUT')

        <div class="grid gap-4">
            <div>
                <label class="block text-gray-700 font-medium">Task Name</label>
                <input type="text" name="name" value="{{ old('name', $task->name) }}" required class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Description</label>
                <textarea name="description" rows="3" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">{{ old('description', $task->description) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Order</label>
                <input type="number" name="order" value="{{ old('order', $task->order) }}" class="w-24 mt-1 border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="flex justify-between items-center mt-4">
                <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" class="text-gray-600 hover:underline">← Back to Tasks</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                    Update Task
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
