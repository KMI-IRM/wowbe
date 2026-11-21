<?php

return [

    'header' => [
        'site' => 'Station ":site"',

        'register' => [
            'title' => 'Enregistrer une station',
        ],
        'update' => [
            'title' => 'Mettre à jour les informations de votre station',
            'description' => 'Gérez les informations de votre station.',
        ],
        'authentication' => [
            'title' => 'Mettre à jour les informations de votre station',
            'description' => 'Mettre à jour votre clé d\'authentification de la station.',
        ],
        'delete' => [
            'title' => 'Supprimer votre station',
            'description' => 'Supprimer votre station et toutes ses observations.',
        ],
    ],

    'menu' => [
        'edit' => 'Modifier',
        'authentication' => 'Clé d\'authentification',
        'delete' => 'Supprimer',
    ],

    'location' => [
        'title' => 'Emplacement de la station',
        'description' => 'Veuillez entrer soit la longitude et la latitude directement dans le formulaire, soit utiliser la carte pour sélectionner un emplacement. Vous pouvez rechercher un emplacement ou cliquer sur la carte pour définir l\'emplacement.',

        'longitude' => 'Longitude',
        'latitude' => 'Latitude',
        'altitude' => 'Altitude',
    ],

    'details' => [
        'title' => 'Détails de la station',
        'description' => 'Le nom de la station est la façon dont les autres verront votre station sur WOW-BE. Le fuseau horaire est également obligatoire - tous les autres champs sont facultatifs.',

        'name' => 'Nom',
        'timezone' => 'Fuseau horaire',
        'timezone_select' => 'Sélectionner un fuseau horaire',
        'timezone_select_notfound' => 'Aucun fuseau horaire trouvé.',
        'website' => 'Site web personnel de votre station',
        'brand' => 'Marque et modèle de la station',
        'software' => 'Logiciel utilisé pour envoyer les données à WOW-BE',
    ],

    'pictures' => [
        'title' => 'Image de la station',
        'description' => 'Vous pouvez télécharger une image pour votre station. L\'image doit être au format JPG ou PNG et ne doit pas dépasser 5 Mo.',

        'picture_add' => 'Ajouter une image',
        'picture_remove' => 'Supprimer l\'image',
    ],

    'authentication' => [
        'title' => 'Authentification de la station',
        'description' => 'Vous devez définir une clé d\'authentification pour votre station. Cette clé est utilisée pour authentifier votre station lors de l\'envoi d\'observations. Vous pouvez utiliser un code PIN (6 chiffres) ou un mot de passe. Si votre station le permet, utilisez un mot de passe et non un code PIN comme clé d\'authentification. Si vous souhaitez modifier votre clé d\'authentification plus tard, vous pouvez le faire.',

        'pincode' => 'Code PIN',
        'pincode_description' => 'Définir un code PIN à 6 chiffres comme clé d\'authentification.',
        'password' => 'Mot de passe',
        'password_description' => 'Définir un mot de passe comme clé d\'authentification.',
        'alphanumeric_hint' => 'Nous recommandons d\'utiliser uniquement des lettres et des chiffres (0-9, a-z, A-Z) pour la clé d\'authentification. Certaines consoles de station ne peuvent pas envoyer certains symboles correctement.',
        'mac_address' => 'Adresse MAC de la station',
        'mac_address_description' => 'Si vous voulez utiliser le **protocole Ecowitt** pour envoyer des observations depuis votre station, vous devez fournir l\'adresse MAC (y compris les deux-points) car elle est utilisée comme méthode d\'authentification.',
    ],

    'delete' => [
        'warning' => [
            'title' => 'Avertissement',
            'description' => 'Cette action ne peut pas être annulée.',
        ],
        'dialog' => [
            'title' => 'Êtes-vous sûr de vouloir supprimer votre station ?',
            'description' => 'Une fois que votre station est supprimée, toutes ses données seront également archivées. Veuillez taper la clé d\'authentification de votre station pour confirmer que vous souhaitez la supprimer.',
        ],
    ],

    'action' => [
        'submit' => 'Soumettre',
        'save' => 'Enregistrer',
        'delete' => 'Supprimer la station',
        'cancel' => 'Annuler',
    ],

    'success' => [
        'updated' => [
            'title' => 'Station mise à jour',
            'description' => 'La station ":site" a été mise à jour avec succès.',
            'description_auth' => 'La clé d\'authentification de la station ":site" a été mise à jour avec succès.',
        ],
        'created' => [
            'title' => 'Station créée',
            'description' => 'La station ":site" a été créée avec succès.',
        ],
    ],

    'public' => 'Cette information sera affichée publiquement.',
    'not_public' => 'Cette information ne sera pas affichée publiquement.',

];
