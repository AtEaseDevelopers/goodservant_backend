{{--
    Drivers, kelindans, agents and supervisors.
    Params: $person (model), $g (language group), $password (bool, show masked app password),
            $extra (array of [label, value] for the work section, optional)
--}}
@include('partials.detail_styles')
@php
    $health = array_filter([
        __('detail.first_vaccine') => $person->firstvaccine ?? null,
        __('detail.second_vaccine') => $person->secondvaccine ?? null,
        __('detail.body_temperature') => $person->temperature ?? null,
    ], fn($v) => !(empty($v) || (is_numeric($v) && (float) $v == 0)));
@endphp
<div class="mb-3">
    <h2 class="dv-title">
        {{ $person->name }}
        <span class="badge badge-light border align-middle ml-1">{{ $person->employeeid }}</span>
        @include('partials.dv_status', ['active' => $person->status == 1])
    </h2>
    @if($person->phone)<div class="dv-sub"><i class="fa fa-phone"></i> {{ $person->phone }}</div>@endif
</div>
<div class="row">
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-id-card-o mr-1"></i> {{ __('detail.personal') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __($g . '.employee_id'), 'value' => $person->employeeid])
            @include('partials.dv_row', ['label' => __($g . '.ic'), 'value' => $person->ic])
            @include('partials.dv_row', ['label' => __($g . '.phone'), 'value' => $person->phone])
            @if(!empty($password))
                <dt>{{ __('detail.app_password') }}</dt>
                <dd>
                    @if($person->password)
                        <span class="dv-secret" data-secret="{{ $person->password }}">••••••••</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ml-1 align-baseline"
                                onclick="var s=this.previousElementSibling; s.textContent=s.dataset.secret; this.remove();">{{ __('detail.show') }}</button>
                    @else
                        —
                    @endif
                </dd>
            @endif
        </dl>
    </div></div>
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-briefcase mr-1"></i> {{ __('detail.work') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __('detail.commission_rate'), 'value' => $person->commissionrate !== null && $person->commissionrate !== '' ? $person->commissionrate . ' %' : null])
            @foreach(($extra ?? []) as [$label, $value])
                @include('partials.dv_row', ['label' => $label, 'value' => $value])
            @endforeach
            @include('partials.dv_row', ['label' => __($g . '.remark'), 'value' => $person->remark])
        </dl>
    </div></div>
    <div class="col-lg-4 mb-3"><div class="dv-section">
        <div class="dv-section-title"><i class="fa fa-university mr-1"></i> {{ __('detail.bank') }}</div>
        <dl>
            @include('partials.dv_row', ['label' => __($g . '.bank_details_1'), 'value' => $person->bankdetails1])
            @include('partials.dv_row', ['label' => __($g . '.bank_details_2'), 'value' => $person->bankdetails2])
        </dl>
    </div></div>
</div>
@if($health)
<div class="dv-section mb-3">
    <div class="dv-section-title"><i class="fa fa-heartbeat mr-1"></i> {{ __('detail.health') }}</div>
    <dl class="row mx-0">
        @foreach($health as $label => $value)
            <div class="col-md-4"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
</div>
@endif
@include('partials.detail_scripts')
