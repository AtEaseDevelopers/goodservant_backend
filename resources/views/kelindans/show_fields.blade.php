@include('partials.person_fields', [
    'person' => $kelindan,
    'g' => 'kelindans',
    'extra' => [[__('kelindans.permit_date'), $kelindan->permitdate ?? null]],
])
