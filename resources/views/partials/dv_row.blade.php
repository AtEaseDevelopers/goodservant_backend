{{-- One label/value pair inside a .dv-section <dl>. Empty values show a dash. --}}
<dt>{{ $label }}</dt>
<dd>{{ (isset($value) && $value !== '' && $value !== null) ? $value : '—' }}</dd>
