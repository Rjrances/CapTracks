@extends('layouts.coordinator')

@section('title', 'Milestone Templates')

@section('content')
<div class="max-w-7xl mx-auto p-6 text-gray-900">
    <h1 class="text-4xl font-semibold mb-8 border-b pb-3">Milestone Templates</h1>

    <a href="{{ route('coordinator.milestones.create') }}" 
       class="inline-block bg-blue-600 hover:bg-blue-700 text-black font-semibold px-6 py-2 rounded mb-8 shadow transition">
        + Create New Milestone
    </a>

    @if (session('success'))
        <div class="mb-6 p-4 rounded bg-green-100 text-green-800 border border-green-300 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-6 kanban-board bg-gradient-to-br from-gray-50 to-gray-200 rounded-lg p-4 shadow-inner min-h-[500px]">
        @php
            $statuses = [
                'todo' => 'To Do',
                'in_progress' => 'In Progress',
                'done' => 'Done',
            ];
            $statusColors = [
                'todo' => 'border-blue-400',
                'in_progress' => 'border-yellow-400',
                'done' => 'border-green-400',
            ];
        @endphp
        @foreach ($statuses as $statusKey => $statusLabel)
            <div class="flex-1 bg-white rounded-lg shadow-md p-4 min-h-[400px] kanban-column border-t-4 {{ $statusColors[$statusKey] }} transition-all duration-200 relative overflow-y-auto max-h-[70vh]" data-status="{{ $statusKey }}">
                <h2 class="text-xl font-bold mb-4 text-center tracking-wide text-gray-700">{{ $statusLabel }}</h2>
                <div class="kanban-list space-y-4" id="kanban-{{ $statusKey }}">
                    @foreach ($milestonesByStatus[$statusKey] as $milestone)
                        <div class="kanban-card bg-white rounded-lg shadow hover:shadow-lg p-4 cursor-grab border border-gray-200 hover:border-blue-400 transition-all duration-200 select-none group" 
                             data-id="{{ $milestone->id }}">
                            <div class="font-semibold text-lg text-black group-hover:text-blue-700">{{ $milestone->name }}</div>
                            <div class="text-black text-sm mb-2">{{ $milestone->description ?? '-' }}</div>
                            <div class="text-xs text-black mb-2">Tasks: {{ $milestone->tasks->count() }}</div>
                            <div class="flex gap-2 mt-2">
                                <a href="{{ route('coordinator.milestones.edit', $milestone->id) }}" 
                                   class="bg-blue-500 hover:bg-blue-600 text-black font-semibold px-3 py-1 rounded shadow transition text-xs">Edit</a>
                                <form action="{{ route('coordinator.milestones.destroy', $milestone->id) }}" method="POST" class="inline">
                                    @csrf 
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="bg-red-500 hover:bg-red-600 text-black font-semibold px-3 py-1 rounded shadow transition text-xs" 
                                            onclick="return confirm('Are you sure you want to delete this milestone?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const statuses = ['todo', 'in_progress', 'done'];
    let dragging = null;
    statuses.forEach(status => {
        new Sortable(document.getElementById('kanban-' + status), {
            group: 'milestones',
            animation: 250,
            ghostClass: 'kanban-ghost',
            chosenClass: 'kanban-chosen',
            dragClass: 'kanban-drag',
            onStart: function (evt) {
                dragging = evt.item;
                evt.item.classList.add('ring-2', 'ring-blue-400', 'scale-105');
            },
            onEnd: function (evt) {
                if (dragging) {
                    dragging.classList.remove('ring-2', 'ring-blue-400', 'scale-105');
                    dragging = null;
                }
                document.querySelectorAll('.kanban-column').forEach(col => col.classList.remove('bg-blue-50'));
            },
            onMove: function (evt) {
                document.querySelectorAll('.kanban-column').forEach(col => col.classList.remove('bg-blue-50'));
                if (evt.to && evt.to.parentNode.classList.contains('kanban-column')) {
                    evt.to.parentNode.classList.add('bg-blue-50');
                }
                return true;
            },
            onAdd: function (evt) {
                const card = evt.item;
                const milestoneId = card.getAttribute('data-id');
                const newStatus = evt.to.parentNode.getAttribute('data-status');
                // AJAX PATCH request
                fetch(`/coordinator/milestones/${milestoneId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Failed to update milestone status.');
                        location.reload();
                    }
                })
                .catch(() => {
                    alert('Error updating milestone status.');
                    location.reload();
                });
            }
        });
    });
</script>
<style>
    .kanban-ghost {
        opacity: 0.5;
        background: #e0e7ff;
        border: 2px dashed #60a5fa;
    }
    .kanban-chosen {
        box-shadow: 0 0 0 4px #60a5fa33;
        background: #f0f9ff;
    }
    .kanban-drag {
        opacity: 0.8;
        transform: scale(1.05);
    }
    .kanban-board {
        scrollbar-width: thin;
    }
    .kanban-column {
        transition: background 0.2s;
    }
</style>
@endsection
