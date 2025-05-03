<?php

return [
    'custom' => [
        'title' => [
            'unique' => 'Vous avez déjà un certificat portant ce titre.',
            'required' => 'Le titre est obligatoire.',
        ],
        'file' => [
            'required' => 'Choisissez un fichier à envoyer.',
            'mimes' => 'Formats acceptés : :values.',
        ],
    ],

    /* noms d’attributs “propres” */
    'attributes' => [
        'title' => 'titre',
        'file' => 'fichier',
    ],
];
