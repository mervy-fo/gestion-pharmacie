<?php

function aPermission($permission)
{
    if (!isset($_SESSION['role'])) {
        return false;
    }

    $role = $_SESSION['role'];

    $permissions = [

        "Pharmacien" => [

            "dashboard",

            "medicaments",
            "ajouter_medicament",
            "modifier_medicament",
            "supprimer_medicament",

            "stocks",
            "entree_stock",
            "sortie_stock",

            "ventes",
            "factures",

            "mouvements",
            "clients",
            "fournisseurs",

            "alertes",
            "rapports",

            "profil"
        ],

        "Gestionnaire" => [

            "dashboard",

            "medicaments",
            "ajouter_medicament",
            "modifier_medicament",
            "supprimer_medicament",

            "stocks",
            "entree_stock",
            "sortie_stock",

            "ventes",
            "factures",
            "mouvements",
            "fournisseurs",

            "alertes",
            "rapports",
            
            "profil"
        ],

        "Administrateur" => [

            "dashboard",

            "medicaments",
            "ajouter_medicament",
            "modifier_medicament",
            "supprimer_medicament",

            "stocks",
            "entree_stock",
            "sortie_stock",
            "ventes",
            "mouvements",

            "alertes",
            "rapports",

            "utilisateurs",
            "ajouter_utilisateur",
            "modifier_utilisateur",
            "supprimer_utilisateur",
            "modifier_statut_utilisateur",

            "profil"
        ]
    ];

    return isset($permissions[$role])
        && in_array($permission, $permissions[$role], true);
}