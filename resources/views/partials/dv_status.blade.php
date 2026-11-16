{{-- Active / inactive badge. Param: $active (bool) --}}
@if($active)
    <span class="badge badge-success align-middle">{{ __('detail.active') }}</span>
@else
    <span class="badge badge-secondary align-middle">{{ __('detail.inactive') }}</span>
@endif
