@extends('layouts.coordinator')

@section('title', 'Milestone Tasks')

@section('content')
<div class="max-w-5xl mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">
        Tasks for: {{ $milestone->name }}
    </h2>

    {{-- Create Task --}}
    <form action="{{ route('coordinator.milestones.tasks.store', $milestone->id) }}" method="POST" class="mb-8 bg-white shadow p-6 rounded">
        @csrf
        <div class="grid gap-4">
            <div>
                <label class="block text-gray-700 font-medium">Task Name</label>
                <input type="text" name="name" required class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-gray-700 font-medium">Description</label>
                <textarea name="description" rows="3" class="w-full mt-1 border-gray-300 rounded-md shadow-sm"></textarea>
            </div>
            <div>
                <label class="block text-gray-700 font-medium">Order</label>
                <input type="number" name="order" value="0" class="w-24 mt-1 border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                    Add Task
                </button>
            </div>
        </div>
    </form>

    {{-- Task List --}}
    @if ($tasks->isEmpty())
        <p class="text-gray-500 italic">No tasks found for this milestone.</p>
    @else
        <table class="w-full bg-white shadow rounded overflow-hidden">
            <thead class="bg-gray-100 text-sm text-gray-600 uppercase">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $task->name }}</td>
                    <td class="px-4 py-3">{{ $task->description }}</td>
                    <td class="px-4 py-3">{{ $task->order }}</td>
                    <td class="px-4 py-3 space-x-2">
                        <a href="{{ route('coordinator.milestones.tasks.edit', [$milestone->id, $task->id]) }}" class="text-blue-600 hover:underline">Edit</a>
                        <form action="{{ route('coordinator.milestones.tasks.destroy', [$milestone->id, $task->id]) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Delete this task?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
