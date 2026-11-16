@include('partials.detail_styles')
<div class="mb-3">
    <h2 class="dv-title">
        {{ $user->name }}
        @if($user->role_name)<span class="badge badge-info align-middle ml-1">{{ $user->role_name }}</span>@endif
    </h2>
    <div class="dv-sub">{{ $user->email }}</div>
</div>
<div class="dv-section">
    <div class="dv-section-title"><i class="fa fa-user-o mr-1"></i> {{ __('detail.account') }}</div>
    <dl>
        @include('partials.dv_row', ['label' => __('user.email'), 'value' => $user->email])
        @include('partials.dv_row', ['label' => __('user.role'), 'value' => $user->role_name])
        @include('partials.dv_row', ['label' => __('user.created_at'), 'value' => $user->created_at])
        @include('partials.dv_row', ['label' => __('user.updated_at'), 'value' => $user->updated_at])
    </dl>
</div>
@include('partials.detail_scripts')
