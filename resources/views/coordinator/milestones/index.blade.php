@extends('layouts.coordinator')

@section('title', 'Milestone Templates')

@section('content')
<div class="max-w-5xl mx-auto p-6 text-gray-900">
    <h1 class="text-4xl font-semibold mb-8 border-b pb-3">Milestone Templates</h1>

    <a href="{{ route('coordinator.milestones.create') }}" 
       class="inline-block bg-blue-600 hover:bg-blue-700 text-black font-semibold px-6 py-2 rounded mb-8">
        + Create New Milestone
    </a>

    @if (session('success'))
        <div class="mb-6 p-4 rounded bg-green-100 text-green-800 border border-green-300 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
        <table class="min-w-full text-left text-gray-700" style="table-layout: fixed; width: 100%;">
            <thead class="bg-gray-100 border-b border-gray-300 uppercase text-sm font-semibold tracking-wide">
                <tr>
                    <th class="px-4 py-3 w-1/4">Name</th>
                    <th class="px-4 py-3 w-1/2">Description</th>
                    <th class="px-6 py-4 w-32 text-center"># of Tasks</th>
                    <th class="px-4 py-3 w-40">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($milestones as $milestone)
                    <tr class="hover:bg-gray-50 border-b border-gray-200">
                        <td class="px-4 py-3 w-1/4 font-medium whitespace-nowrap" style="overflow-wrap: break-word;">
                            {{ $milestone->name }}
                        </td>
                        <td class="px-4 py-3 w-1/2" style="overflow-wrap: break-word;">
                            {{ $milestone->description ?? '-' }}
                        </td>
                        <td class="px-4 py-3 w-20 text-center">
                            {{ $milestone->tasks->count() }}
                        </td>
                        <td class="px-4 py-3 w-40 flex justify-center space-x-4">
                    <a href="{{ route('coordinator.milestones.edit', $milestone->id) }}" 
                    class="bg-red-600 hover:bg-red-700 text-black font-semibold px-4 py-1 rounded shadow transition">
                        Edit
                    </a>
                    <form action="{{ route('coordinator.milestones.destroy', $milestone->id) }}" method="POST" class="inline">
                        @csrf 
                        @method('DELETE')
                        <button type="submit" 
                                class="bg-red-600 hover:bg-red-700 text-black font-semibold px-4 py-1 rounded shadow transition" 
                                onclick="return confirm('Are you sure you want to delete this milestone?')">
                            Delete
                        </button>
                    </form>
                </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 italic">No milestones found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8">
        {{ $milestones->links() }}
    </div>
</div>
@endsection
