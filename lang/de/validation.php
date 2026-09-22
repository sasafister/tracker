<?php

/*
 * Only the rules this app uses; English comes with the framework.
 */
return [
    'required' => 'Das Feld :attribute ist erforderlich.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'unique' => ':attribute ist bereits vergeben.',
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'min' => [
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
        'numeric' => ':attribute muss mindestens :min sein.',
    ],
    'max' => [
        'string' => ':attribute darf höchstens :max Zeichen lang sein.',
        'numeric' => ':attribute darf höchstens :max sein.',
    ],
    'numeric' => ':attribute muss eine Zahl sein.',
    'string' => ':attribute muss ein Text sein.',
    'integer' => ':attribute muss eine ganze Zahl sein.',
    'boolean' => ':attribute muss ja oder nein sein.',
    'date' => ':attribute ist kein gültiges Datum.',
    'after' => ':attribute muss nach :date liegen.',
    'exists' => 'Das gewählte Feld :attribute existiert nicht.',
    'in' => 'Der gewählte Wert für :attribute ist nicht erlaubt.',
    'regex' => ':attribute hat ein ungültiges Format.',
    'timezone' => ':attribute muss eine gültige Zeitzone sein.',
    'required_with' => 'Das Feld :attribute ist erforderlich, wenn :values angegeben ist.',
    'current_password' => 'Das Passwort ist falsch.',

    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'current_password' => 'aktuelles Passwort',
        'hourly_rate' => 'Stundensatz',
        'currency' => 'Währung',
        'locale' => 'Sprache',
        'description' => 'Beschreibung',
        'started_at' => 'Beginn',
        'ended_at' => 'Ende',
        'project_id' => 'Projekt',
        'client_id' => 'Kunde',
        'address' => 'Adresse',
        'tax_id' => 'OIB / USt-IdNr.',
        'company_name' => 'Firmenname',
        'company_address' => 'Firmenadresse',
        'company_tax_id' => 'OIB / USt-IdNr.',
        'company_iban' => 'IBAN',
        'color' => 'Markierung',
    ],
];
