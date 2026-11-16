@include('partials.detail_styles')
@php $address = array_filter([$company->address1, $company->address2, $company->address3, $company->address4]); @endphp
<div class="mb-3">
    <h2 class="dv-title">
        {{ $company->name }}
        <span class="badge badge-light border align-middle ml-1">{{ $company->code }}</span>
    </h2>
</div>
<div class="row">
    <div class="col-lg-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-building-o mr-1"></i> {{ __('detail.company') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('companies.ssm'), 'value' => $company->ssm])
            @include('partials.dv_row', ['label' => __('companies.group'), 'value' => $company->group->description ?? null])
        </dl>
    </div></div>
    <div class="col-lg-6 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-map-marker mr-1"></i> {{ __('detail.address') }}</div>
        <dl><dd class="mt-1">@forelse($address as $line){{ $line }}<br>@empty — @endforelse</dd></dl>
    </div></div>
</div>
@include('partials.detail_scripts')
