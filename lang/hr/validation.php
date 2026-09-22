<?php

/*
 * Only the rules this app uses; English comes with the framework.
 */
return [
    'required' => 'Polje :attribute je obavezno.',
    'email' => 'Polje :attribute mora biti ispravna email adresa.',
    'unique' => ':attribute je već zauzet.',
    'confirmed' => 'Potvrda za :attribute se ne podudara.',
    'min' => [
        'string' => ':attribute mora imati barem :min znakova.',
        'numeric' => ':attribute mora biti barem :min.',
    ],
    'max' => [
        'string' => ':attribute smije imati najviše :max znakova.',
        'numeric' => ':attribute smije biti najviše :max.',
    ],
    'numeric' => ':attribute mora biti broj.',
    'string' => ':attribute mora biti tekst.',
    'integer' => ':attribute mora biti cijeli broj.',
    'boolean' => ':attribute mora biti da ili ne.',
    'date' => ':attribute nije ispravan datum.',
    'after' => ':attribute mora biti nakon :date.',
    'exists' => 'Odabrani :attribute ne postoji.',
    'in' => 'Odabrani :attribute nije dopušten.',
    'regex' => ':attribute nije ispravnog oblika.',
    'timezone' => ':attribute mora biti ispravna vremenska zona.',
    'required_with' => 'Polje :attribute je obavezno kad je upisano :values.',
    'current_password' => 'Lozinka nije ispravna.',

    'attributes' => [
        'name' => 'ime',
        'email' => 'email',
        'password' => 'lozinka',
        'current_password' => 'trenutna lozinka',
        'hourly_rate' => 'satnica',
        'currency' => 'valuta',
        'locale' => 'jezik',
        'description' => 'opis',
        'started_at' => 'početak',
        'ended_at' => 'kraj',
        'project_id' => 'projekt',
        'client_id' => 'klijent',
        'address' => 'adresa',
        'tax_id' => 'OIB / VAT ID',
        'company_name' => 'naziv tvrtke',
        'company_address' => 'adresa tvrtke',
        'company_tax_id' => 'OIB / VAT ID',
        'company_iban' => 'IBAN',
        'color' => 'oznaka',
    ],
];
