@include('partials.detail_styles')
@php
    $permissions = method_exists($role, 'permissions') ? $role->permissions->pluck('name') : collect();
    if ($permissions->isEmpty() && $role->permission_name) {
        $permissions = collect([$role->permission_name]);
    }
@endphp
<div class="mb-3">
    <h2 class="dv-title">{{ $role->name }}</h2>
</div>
<div class="dv-section">
    <div class="dv-section-title"><i class="fa fa-key mr-1"></i> {{ __('role.permissions') }} ({{ $permissions->count() }})</div>
    <div class="p-3">
        @forelse($permissions->sort() as $permission)
            <span class="badge badge-light border mr-1 mb-1 p-2">{{ $permission }}</span>
        @empty
            <span class="text-muted">—</span>
        @endforelse
    </div>
</div>
@include('partials.detail_scripts')
