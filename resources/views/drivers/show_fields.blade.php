@include('partials.person_fields', [
    'person' => $driver,
    'g' => 'drivers',
    'password' => true,
    'extra' => [
        [__('drivers.debtor_types'), !empty($driver->debtor_types) ? implode(', ', (array) $driver->debtor_types) : null],
        [__('detail.invoice_code'), $driver->doc_code ?? null],
    ],
])
