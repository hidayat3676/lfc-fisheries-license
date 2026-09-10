@extends('layouts.admin')

@section('title', 'Licence categories — '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Licence categories</h1>
        <p class="text-secondary mb-0">Fees, duration, and expiry rules</p>
    </div>
    @if (auth()->user()->hasModuleAction('license_categories', 'create'))
        <a href="{{ route('admin.categories.create') }}" class="btn btn-rflms">Add category</a>
    @endif
</div>

<div class="bg-white rounded-4 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Fee</th>
                    <th>Duration</th>
                    <th>Expiry rule</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr>
                        <td class="fw-semibold">{{ $category->code }}</td>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->feeLabel() }}</td>
                        <td class="small">{{ $category->duration_type }}@if($category->duration_days) ({{ $category->duration_days }}d)@endif</td>
                        <td class="small">{{ $category->expiry_rule }}@if($category->fixed_expiry_month_day) / {{ $category->fixed_expiry_month_day }}@endif</td>
                        <td><span class="badge {{ $category->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $category->is_active ? 'Active' : 'Off' }}</span></td>
                        <td class="text-end">
                            @if (auth()->user()->hasModuleAction('license_categories', 'edit'))
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $categories->links() }}</div>
@endsection
