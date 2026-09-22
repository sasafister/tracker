<?php

/*
 * Only the rules this app uses; English comes with the framework.
 */
return [
    'required' => 'Polje :attribute je obvezno.',
    'email' => ':attribute mora biti veljaven e-poštni naslov.',
    'unique' => ':attribute je že zaseden.',
    'confirmed' => 'Potrditev za :attribute se ne ujema.',
    'min' => [
        'string' => ':attribute mora imeti vsaj :min znakov.',
        'numeric' => ':attribute mora biti vsaj :min.',
    ],
    'max' => [
        'string' => ':attribute ima lahko največ :max znakov.',
        'numeric' => ':attribute je lahko največ :max.',
    ],
    'numeric' => ':attribute mora biti število.',
    'string' => ':attribute mora biti besedilo.',
    'integer' => ':attribute mora biti celo število.',
    'boolean' => ':attribute mora biti da ali ne.',
    'date' => ':attribute ni veljaven datum.',
    'after' => ':attribute mora biti za :date.',
    'exists' => 'Izbrani :attribute ne obstaja.',
    'in' => 'Izbrani :attribute ni dovoljen.',
    'regex' => ':attribute ni v pravilni obliki.',
    'timezone' => ':attribute mora biti veljaven časovni pas.',
    'required_with' => 'Polje :attribute je obvezno, ko je vpisano :values.',
    'current_password' => 'Geslo ni pravilno.',

    'attributes' => [
        'name' => 'ime',
        'email' => 'e-pošta',
        'password' => 'geslo',
        'current_password' => 'trenutno geslo',
        'hourly_rate' => 'urna postavka',
        'currency' => 'valuta',
        'locale' => 'jezik',
        'description' => 'opis',
        'started_at' => 'začetek',
        'ended_at' => 'konec',
        'project_id' => 'projekt',
        'client_id' => 'stranka',
        'address' => 'naslov',
        'tax_id' => 'OIB / ID za DDV',
        'company_name' => 'ime podjetja',
        'company_address' => 'naslov podjetja',
        'company_tax_id' => 'OIB / ID za DDV',
        'company_iban' => 'IBAN',
        'color' => 'oznaka',
    ],
];
