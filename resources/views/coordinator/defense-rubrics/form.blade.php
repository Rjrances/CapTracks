@extends('layouts.coordinator')
@section('title', $template->exists ? 'Edit Defense Rubric' : 'Create Defense Rubric')

@section('content')
<div class="container-fluid">
    <x-coordinator.intro description="Set the rubric used by panelists when scoring defenses.">
        <a href="{{ route('coordinator.defense-rubrics.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Rubrics
        </a>
    </x-coordinator.intro>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $template->exists ? route('coordinator.defense-rubrics.update', $template) : route('coordinator.defense-rubrics.store') }}">
        @csrf
        @if($template->exists)
            @method('PUT')
        @endif

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Rubric Name</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $template->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Defense Stage</label>
                        <select class="form-select" name="stage" required>
                            @php($selectedStage = old('stage', $template->stage ?? 'proposal'))
                            <option value="proposal" {{ $selectedStage === 'proposal' ? 'selected' : '' }}>Proposal</option>
                            <option value="60" {{ $selectedStage === '60' ? 'selected' : '' }}>60%</option>
                            <option value="100" {{ $selectedStage === '100' ? 'selected' : '' }}>100%</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" @checked(old('is_active', $template->is_active))>
                            <label class="form-check-label" for="is_active">Set as active for this stage</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description (optional)</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $template->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Criteria</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-criterion-btn">
                    <i class="fas fa-plus me-1"></i>Add Criterion
                </button>
            </div>
            <div class="card-body">
                <div id="criteria-list">
                    @foreach($initialCriteria as $index => $criterion)
                        <div class="row g-2 align-items-end mb-2 criterion-row">
                            <div class="col-md-6">
                                <label class="form-label">Criterion</label>
                                <input type="text" name="criteria[{{ $index }}][name]" class="form-control" value="{{ $criterion['name'] }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Scope</label>
                                <select name="criteria[{{ $index }}][scope]" class="form-select" required>
                                    <option value="group" {{ ($criterion['scope'] ?? 'group') === 'group' ? 'selected' : '' }}>Group</option>
                                    <option value="individual" {{ ($criterion['scope'] ?? 'group') === 'individual' ? 'selected' : '' }}>Individual</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Max Points</label>
                                <input type="number" step="0.1" min="1" max="1000" name="criteria[{{ $index }}][max_points]" class="form-control" value="{{ $criterion['max_points'] }}" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100 remove-criterion-btn" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <button class="btn btn-primary mt-3" type="submit">
            {{ $template->exists ? 'Update Rubric' : 'Create Rubric' }}
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const criteriaList = document.getElementById('criteria-list');
    const addBtn = document.getElementById('add-criterion-btn');

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-criterion-btn').forEach((btn) => {
            btn.onclick = function () {
                const rows = criteriaList.querySelectorAll('.criterion-row');
                if (rows.length <= 1) return;
                this.closest('.criterion-row').remove();
                reindex();
            };
        });
    }

    function reindex() {
        [...criteriaList.querySelectorAll('.criterion-row')].forEach((row, index) => {
            row.querySelectorAll('input, select').forEach((field) => {
                field.name = field.name.replace(/criteria\[\d+\]/, `criteria[${index}]`);
            });
        });
    }

    addBtn.addEventListener('click', function () {
        const index = criteriaList.querySelectorAll('.criterion-row').length;
        const wrapper = document.createElement('div');
        wrapper.className = 'row g-2 align-items-end mb-2 criterion-row';
        wrapper.innerHTML = `
            <div class="col-md-6">
                <label class="form-label">Criterion</label>
                <input type="text" name="criteria[${index}][name]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Scope</label>
                <select name="criteria[${index}][scope]" class="form-select" required>
                    <option value="group" selected>Group</option>
                    <option value="individual">Individual</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Max Points</label>
                <input type="number" step="0.1" min="1" max="1000" name="criteria[${index}][max_points]" class="form-control" value="10" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100 remove-criterion-btn" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        criteriaList.appendChild(wrapper);
        bindRemoveButtons();
    });

    bindRemoveButtons();
});
</script>
@endsection

