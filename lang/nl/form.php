<?php

return [

    'header' => [
        'site' => 'Station ":site"',

        'register' => [
            'title' => 'Registreer een station',
        ],
        'update' => [
            'title' => 'Update je stationinformatie',
            'description' => 'Beheer de informatie van je station.',
        ],
        'authentication' => [
            'title' => 'Update je stationinformatie',
            'description' => 'Werk de authenticatiesleutel van je station bij.',
        ],
        'delete' => [
            'title' => 'Verwijder je station',
            'description' => 'Verwijder je station en alle bijbehorende observaties.',
        ],
    ],

    'menu' => [
        'edit' => 'Bewerken',
        'authentication' => 'Authenticatiesleutel',
        'delete' => 'Verwijderen',
    ],

    'location' => [
        'title' => 'Locatie van het station',
        'description' => 'Voer de lengte- en breedtegraad direct in het formulier in of gebruik de kaart om een locatie te selecteren. Je kunt een locatie zoeken of op de kaart klikken om de locatie te definiëren.',
        'longitude' => 'Lengtegraad',
        'latitude' => 'Breedtegraad',
        'altitude' => 'Hoogte boven zeeniveau',
    ],

    'details' => [
        'title' => 'Stationgegevens',
        'description' => 'De naam van het station is hoe anderen het zullen zien op WOW-BE. Tijdzone is verplicht, alle andere velden zijn optioneel.',
        'name' => 'Naam',
        'timezone' => 'Tijdzone',
        'timezone_select' => 'Selecteer een tijdzone',
        'timezone_select_notfound' => 'Geen tijdzone gevonden.',
        'website' => 'Persoonlijke website van je station',
        'brand' => 'Merk en model van het station',
        'software' => 'Software waarmee je de gegevens uploadt naar WOW-BE',
    ],

    'pictures' => [
        'title' => 'Foto van het station',
        'description' => 'Je kunt een foto voor je station uploaden. De foto moet in JPG- of PNG-formaat zijn en mag niet groter zijn dan 5MB.',
        'picture_add' => 'Foto toevoegen',
        'picture_remove' => 'Foto verwijderen',
    ],

    'authentication' => [
        'title' => 'Authenticatie van het station',
        'description' => 'Je moet een authenticatiesleutel voor je station instellen. Deze sleutel wordt gebruikt om je station te verifiëren bij het verzenden van observaties. Je kunt een zescijferige pincode of een wachtwoord gebruiken. We raden aan om een wachtwoord te gebruiken als sleutel. Als je later de sleutel wilt wijzigen, kan dat.',

        'pincode' => 'Pincode',
        'pincode_description' => 'Stel een pincode van 6 cijfers in als authenticatiesleutel.',
        'password' => 'Wachtwoord',
        'password_description' => 'Stel een wachtwoord in als authenticatiesleutel.',
        'alphanumeric_hint' => 'We raden aan om enkel letters en cijfers (0-9, a-z, A-Z) te gebruiken voor de authenticatiesleutel. Sommige stationsconsoles kunnen bepaalde symbolen niet correct verzenden.',
        'mac_address' => 'MAC-adres van het station',
        'mac_address_description' => 'Als je het **Ecowitt-protocol** wilt gebruiken om observaties van je station te uploaden, moet je het MAC-adres opgeven (inclusief dubbele punten) omdat dit wordt gebruikt als authenticatiemethode.',
    ],

    'delete' => [
        'warning' => [
            'title' => 'Waarschuwing',
            'description' => 'Deze actie kan niet ongedaan worden gemaakt.',
        ],
        'dialog' => [
            'title' => 'Weet je zeker dat je je station wilt verwijderen?',
            'description' => 'Zodra je station is verwijderd, worden alle bijbehorende gegevens gearchiveerd. Voer de authenticatiesleutel van je station in om te bevestigen dat je deze wilt verwijderen.',
        ],
    ],

    'action' => [
        'submit' => 'Verzenden',
        'save' => 'Opslaan',
        'delete' => 'Station verwijderen',
        'cancel' => 'Annuleren',
    ],

    'success' => [
        'updated' => [
            'title' => 'Station bijgewerkt',
            'description' => 'Het station ":site" is succesvol bijgewerkt.',
            'description_auth' => 'De authenticatiesleutel van de station ":site" is succesvol bijgewerkt.',
        ],
        'created' => [
            'title' => 'Station aangemaakt',
            'description' => 'Het station ":site" is succesvol aangemaakt.',
        ],
    ],

    'public' => 'Deze informatie zal openbaar worden weergegeven.',
    'not_public' => 'Deze informatie zal niet openbaar worden weergegeven.',

];
