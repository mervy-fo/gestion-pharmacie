<?php
session_start();

require_once 'connex.php';
require_once 'protection.php';

protegerPage("stocks");

/* =====================================================
   RÉCUPÉRATION DES MÉDICAMENTS
===================================================== */

$sql = "SELECT
            m.*,
            c.nom_categorie AS categorie_nom

        FROM medicament m

        LEFT JOIN categorie c
            ON m.nom_categorie = c.id_categorie

        ORDER BY m.id_medicament ASC";


$resultat = mysqli_query($conn, $sql);

if (!$resultat) {
    die("Erreur : " . mysqli_error($conn));
}
/* =====================================================
   DATE ACTUELLE
===================================================== */

$aujourd_hui = new DateTime();

/* =====================================================
   STATISTIQUES
===================================================== */

$stock_total = 0;
$nombre_normal = 0;
$nombre_faible = 0;
$nombre_rupture = 0;


/* On parcourt les médicaments une première fois */

$medicaments = [];

while ($medicament = mysqli_fetch_assoc($resultat)) {

    $quantite = (int) $medicament['quantite_restante'];
    $seuil = (int) $medicament['seuil_minimum'];

    $stock_total += $quantite;

    $datePeremption = $medicament['date_peremption'];
/* =====================================================
   DÉTERMINATION DE L'ÉTAT DU STOCK
===================================================== */

if (
    !empty($datePeremption)
    && $datePeremption < date('Y-m-d')
) {

    $etat = "Périmé";

} elseif ($quantite <= 0) {

    $etat = "Rupture";

    $nombre_rupture++;

} elseif (
    !empty($datePeremption)
    && $datePeremption <= date('Y-m-d', strtotime('+30 days'))
) {

    $etat = "Péremption proche";

} elseif (
    !empty($datePeremption)
    && $datePeremption <= date('Y-m-d', strtotime('+90 days'))
) {

    $etat = "À surveiller";

} elseif ($quantite <= $seuil) {

    $etat = "Stock faible";

    $nombre_faible++;

} else {

    $etat = "Normal";

    $nombre_normal++;
}

$medicament['etat_stock'] = $etat;
    $medicaments[] = $medicament;
}

$sqlNotifications = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE
        quantite_restante = 0

        OR (
            quantite_restante > 0
            AND quantite_restante <= seuil_minimum
        )

        OR (
            date_peremption IS NOT NULL
            AND date_peremption < CURDATE()
        )

        OR (
            date_peremption IS NOT NULL
            AND date_peremption >= CURDATE()
            AND date_peremption <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        )

        OR (
            date_peremption IS NOT NULL
            AND date_peremption > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND date_peremption <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        )
";

$resultNotifications = mysqli_query($conn, $sqlNotifications);

if (!$resultNotifications) {
    die("Erreur notifications : " . mysqli_error($conn));
}

$nombreNotifications = mysqli_fetch_assoc($resultNotifications)['total'];
 


?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des stocks - Pharmacie</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../vendor/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.css">

    <!-- CSS de la page -->
    <link rel="stylesheet" href="./style.css">
<style>
        /* =====================================================
        ÉTATS DES STOCKS
        ===================================================== */

        .badge-alerte {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;

            padding: 6px 18px !important;

            border-radius: 50px !important;

            font-size: 10px !important;
            font-weight: 600 !important;

            white-space: nowrap !important;
        }

        /* =========================
        PÉRIMÉ
        ========================= */

        .etat-perime {
              background-color: #f8d7da !important;
    color: #dc3545 !important;
        }

        /* =========================
        RUPTURE
        ========================= */

        .etat-rupture {
           background-color: #f8d7da !important;
    color: #dc3545 !important;
        }

        /* =========================
        PÉREMPTION PROCHE
        ========================= */

        .etat-proche {
            background-color: #e0d5f6 !important;
            color: #5206f6  !important;
        }

        /* =========================
        STOCK FAIBLE
        ========================= */

        .etat-faible {
             background-color: #ffe5c2 !important;
    color: #fd7e14 !important;
        }

        /* =========================
        À SURVEILLER
        ========================= */

        .etat-surveiller {
            background-color: #f4f8ce !important;
            color: #f58506cd !important;
        }

        /* =========================
        NORMAL
        ========================= */

        .etat-normal {
            background-color: #d1e7dd !important;
            color: #198754 !important;
        }
</style>
</head>


<body>

<div class="container-fluid">

 <!-- =========================
         SIDEBAR
    ========================== -->

      <?php include 'sidebar.php'; ?>
  <main class="main-content">

        <!-- NAVBAR -->

        <header class="topbar">

            <div>

                <h5 class="mb-1">
                    Stocks
                </h5>

                <small>
                    Gestion des stocks de la pharmacie
                </small>

            </div>


            <div class="topbar-right">
                <a href="alertes.php" class="text-decoration-none">

                    <button class="icon-button">

                        <i class="fas fa-bell"></i>

                        <?php if ($nombreNotifications > 0): ?>

                                        <span>
                                            <?= $nombreNotifications ?>
                                        </span>

                                    <?php endif; ?>
                    </button>

                </a>

                <div class="user-profile">

                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>

                    <div>

                        <strong>
                            <?= htmlspecialchars($_SESSION['nom_util'] ?? 'Utilisateur') ?>
                        </strong>

                        <small>
                            <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                        </small>

                    </div>

                </div>

            </div>

        </header>

    <!-- =====================================================
         EN-TÊTE
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center py-4">

        <div>

            <h2 class="fw-bold mb-1" >
                
                Gestion des stocks
            </h2>

            <p class="text-muted mb-0">
                Suivi et contrôle des stocks de médicaments
            </p>
            
        </div>


        <div>

            <button
               type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalNouvelleEntree">
                <i class="fas fa-plus me-2"></i>
                Nouvelle entrée

            </button>
            <button
                type="button"
                class="btn btn-danger ms-2"
                data-bs-toggle="modal"
                data-bs-target="#modalNouvelleSortie">

                <i class="fas fa-minus me-2"></i>
                Nouvelle sortie
            </button>

        </div>

    </div>


    <!-- =====================================================
         CARTES STATISTIQUES
    ====================================================== -->

    <div class="row g-4 mb-4">


        <!-- STOCK TOTAL -->

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Stock total
                            </p>

                            <h3 class="fw-bold mb-0">
                                <?= number_format($stock_total, 0, ',', ' ') ?>
                            </h3>

                            <small class="text-muted">
                                unités disponibles
                            </small>

                        </div>


                        <div class="stock-icon stock-icon-green">

                            <i class="fas fa-boxes-stacked"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- STOCK NORMAL -->

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Stock normal
                            </p>

                            <h3 class="fw-bold text-success mb-0">
                                <?= $nombre_normal ?>
                            </h3>

                            <small class="text-muted">
                                médicaments
                            </small>

                        </div>


                        <div class="stock-icon stock-icon-normal">

                            <i class="fas fa-circle-check"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- STOCK FAIBLE -->

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Stock faible
                            </p>

                            <h3 class="fw-bold text-warning mb-0">
                                 <?= $nombre_faible ?>
                            </h3>

                            <small class="text-muted">
                                médicaments
                            </small>

                        </div>


                        <div class="stock-icon stock-icon-low">

                            <i class="fas fa-triangle-exclamation"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- RUPTURE -->

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Rupture de stock
                            </p>

                            <h3 class="fw-bold text-danger mb-0">
                                  <?= $nombre_rupture ?>
                            </h3>

                            <small class="text-muted">
                                médicaments
                            </small>

                        </div>


                        <div class="stock-icon stock-icon-danger">

                            <i class="fas fa-circle-xmark"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         RECHERCHE ET FILTRE
    ====================================================== -->

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="row g-3 align-items-end">


                <!-- RECHERCHE -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">

                        <i class="fas fa-search me-1"></i>

                        Rechercher un médicament

                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-white">

                            <i class="fas fa-search text-muted"></i>

                        </span>

                        <input
                            type="text"
                            id="rechercheStock"
                            class="form-control"
                            placeholder="Nom ou code du médicament...">

                        </input>
                        
                    </div>

                </div>



                <!-- FILTRE ETAT -->

                <div class="col-md-3">

                    <label class="form-label fw-semibold">

                        État du stock

                    </label>

                    <select
                        id="filtreEtat"
                        class="form-select">

                        <option value="tous">
                            Tous les états
                        </option>

                        <option value="normal">
                            🟢 Normal
                        </option>

                        <option value="faible">
                            🟠 Stock faible
                        </option>

                        <option value="rupture">
                            🔴 Rupture
                        </option>
                        <option value="périmé">🔴 Périmé</option>
                        <option value="proche">🟣 Péremption proche</option>
                        <option value="surveiller">🟡 À surveiller</option>

                    </select>

                </div>



                <!-- BOUTON -->

                <div class="col-md-3">

                    <button
                        type="button"
                        id="btnReinitialiser"
                        class="btn btn-outline-secondary w-100">

                        <i class="fas fa-rotate-left me-2"></i>

                        Réinitialiser

                    </button>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         TABLEAU DES STOCKS
    ====================================================== -->

    <div class="dashboard-card">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="fas fa-warehouse me-2 text-primary"></i>

                        État des stocks

                    </h5>

                    <small class="text-muted">

                        Liste des médicaments disponibles en stock

                    </small>

                </div>


                <span class="badge bg-light text-dark">

                    <i class="fas fa-database me-1"></i>
                    <?= count($medicaments) ?>
                    médicament<?= count($medicaments) > 1 ? 's' : '' ?>

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table medicine-table">

                    <thead >

                        <tr>

                            <th class="text-center">
                                N°
                            </th>

                            <th>
                                Médicament
                            </th>

                            <th>
                                Code
                            </th>

                            <th>
                                Catégorie
                            </th>

                            <th class="text-center">
                                Stock actuel
                            </th>

                            <th class="text-center">
                                Seuil minimum
                            </th>

                            <th class="text-center">
                                État
                            </th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tableauStocks">

                        <?php if (count($medicaments) > 0): ?>

                            <?php $numero = 1; ?>

                            <?php foreach ($medicaments as $medicament): ?>

                            <?php

                                /* =====================================================
                                CALCUL DE L'ÉTAT DU MÉDICAMENT
                                ===================================================== */

                                $stock = (int) $medicament['quantite_restante'];
                                $seuil = (int) $medicament['seuil_minimum'];

                                $etat = "Normal";
                                $classe = "etat-normal";
                                $icone = "fa-circle-check";


                                /* =====================================================
                                1. VÉRIFICATION DE LA DATE DE PÉREMPTION
                                ===================================================== */

                                if (!empty($medicament['date_peremption'])) {

                                    $date_exp = new DateTime($medicament['date_peremption']);

                                    $jours_restants = (int) $aujourd_hui
                                        ->diff($date_exp)
                                        ->format('%r%a');


                                    /* PÉRIMÉ */

                                    if ($jours_restants < 0) {

                                        $etat = "Périmé";
                                        $classe = "etat-perime";
                                        $icone = "fas fa-skull-crossbones";

                                    }


                                    /* PÉREMPTION PROCHE */

                                    elseif ($jours_restants <= 30) {

                                        $etat = "Péremption proche";
                                        $classe = "etat-proche";
                                        $icone = "fa-hourglass-half";

                                    }


                                    /* À SURVEILLER */

                                    elseif ($jours_restants <= 90) {

                                        $etat = "À surveiller";
                                        $classe = "etat-surveiller";
                                        $icone = "fas fa-eye";

                                    }


                                    /* STOCK */

                                    elseif ($stock == 0) {

                                        $etat = "Rupture";
                                        $classe = "etat-rupture";
                                        $icone = "fa-circle-xmark";

                                    }


                                    elseif ($stock <= $seuil) {

                                        $etat = "Stock faible";
                                        $classe = "etat-faible";
                                        $icone = "fa-triangle-exclamation";

                                    }

                                }


                                /* =====================================================
                                2. SI AUCUNE DATE DE PÉREMPTION
                                ===================================================== */

                                else {

                                    if ($stock == 0) {

                                        $etat = "Rupture";
                                        $classe = "etat-rupture";
                                        $icone = "fa-circle-xmark";

                                    }

                                    elseif ($stock <= $seuil) {

                                        $etat = "Stock faible";
                                        $classe = "etat-faible";
                                        $icone = "fa-triangle-exclamation";

                                    }

                                }

                            ?>
                               

                                <tr>

                                    <!-- N° -->
                                    <td>
                                        <strong>
                                            <?= $numero ?>
                                        </strong>
                                    </td>


                                    <!-- MÉDICAMENT -->
                                    <td>

                                        <strong>
                                            <?= htmlspecialchars($medicament['nom']) ?>
                                        </strong>

                                    </td>


                                    <!-- CODE -->
                                    <td>

                                        <?= htmlspecialchars($medicament['code']) ?>

                                    </td>


                                    <!-- CATÉGORIE -->
                                    <td>

                                        <?= htmlspecialchars($medicament['categorie_nom']) ?>

                                    </td>


                                    <!-- STOCK ACTUEL -->
                                    <td class="text-center">

                                        <strong>
                                            <?= (int) $medicament['quantite_restante'] ?>
                                        </strong>

                                    </td>


                                    <!-- SEUIL MINIMUM -->
                                    <td class="text-center">

                                        <?= (int) $medicament['seuil_minimum'] ?>

                                    </td>

                                   <!-- ÉTAT -->

                                    <td class="text-center">

                                        <span class="badge-alerte <?= $classe ?>">

                                            <i class="fas <?= $icone ?> me-1"></i>

                                            <?= htmlspecialchars($etat) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->
                                    <td class="text-center">

                                        <a
                                            href="medicaments.php?id=<?= (int) $medicament['id_medicament'] ?>"
                                            class="btn btn-sm btn-outline-success"
                                            title="Voir le médicament">

                                            <i class="fas fa-eye"></i>

                                        </a>

                                    </td>

                                </tr>


                                <?php $numero++; ?>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center py-5 text-muted">

                                    <i class="fas fa-box-open fa-2x mb-3"></i>

                                    <p class="mb-0">
                                        Aucun médicament à afficher
                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>

                            <!-- =====================================================
                                MODAL : NOUVELLE ENTRÉE DE STOCK
                            ===================================================== -->

                           

                    </tbody>

                </table>

                    <div class="modal fade"
                                id="modalNouvelleEntree"
                                tabindex="-1"
                                aria-labelledby="modalNouvelleEntreeLabel"
                                aria-hidden="true">

                            <div class="modal-dialog modal-lg modal-dialog-centered">

                                <div class="modal-content">

                                    <!-- En-tête -->
                                    <div class="modal-header">

                                        <h5 class="modal-title" id="modalNouvelleEntreeLabel">

                                            <i class="fas fa-box-open me-2"></i>
                                            Nouvelle entrée de stock

                                        </h5>

                                        <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                                aria-label="Fermer">
                                        </button>

                                    </div>


                                    <!-- Formulaire -->
                                    <form method="POST" action="ajouter_entree.php">

                                        <div class="modal-body">

                                            <div class="row g-3">

                                                <!-- Médicament -->
                                                <div class="col-md-12">

                                                    <label for="id_medicament" class="form-label">
                                                        Médicament <span class="text-danger">*</span>
                                                    </label>

                                                    <select
                                                        name="id_medicament"
                                                        id="id_medicament"
                                                        class="form-select"
                                                        required>

                                                        <option value="">
                                                            Sélectionner un médicament
                                                        </option>

                                                        <?php

                                                        $sqlMedicaments = "
                                                            SELECT
                                                                id_medicament,
                                                                nom,
                                                                code,
                                                                forme,
                                                                dosage
                                                            FROM medicament
                                                            ORDER BY nom ASC
                                                        ";

                                                        $resultMedicaments =
                                                            mysqli_query($conn, $sqlMedicaments);

                                                        if ($resultMedicaments) {

                                                            while ($med = mysqli_fetch_assoc($resultMedicaments)) {

                                                        ?>

                                                            <option value="<?= (int)$med['id_medicament'] ?>">

                                                                <?= htmlspecialchars($med['nom']) ?>
                                                                -
                                                                <?= htmlspecialchars($med['code']) ?>
                                                                -
                                                                <?= htmlspecialchars($med['forme']) ?>
                                                                <?= htmlspecialchars($med['dosage']) ?>

                                                            </option>

                                                        <?php

                                                            }

                                                        }

                                                        ?>

                                                    </select>

                                                </div>


                                                <!-- Quantité -->
                                                <div class="col-md-6">

                                                    <label for="quantite" class="form-label">

                                                        Quantité entrée
                                                        <span class="text-danger">*</span>

                                                    </label>

                                                    <input
                                                        type="number"
                                                        name="quantite"
                                                        id="quantite"
                                                        class="form-control"
                                                        min="1"
                                                        required
                                                        placeholder="Ex : 50">

                                                </div>


                                                <!-- Date -->
                                                <div class="col-md-6">

                                                    <label for="date_mouv" class="form-label">

                                                        Date d'entrée
                                                        <span class="text-danger">*</span>

                                                    </label>

                                                    <input
                                                        type="date"
                                                        name="date_mouv"
                                                        id="date_mouv"
                                                        class="form-control"
                                                        value="<?= date('Y-m-d') ?>"
                                                        required>

                                                </div>


                                                <!-- Référence -->
                                                <div class="col-md-6">

                                                    <label for="reference" class="form-label">

                                                        Référence

                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="reference"
                                                        id="reference"
                                                        class="form-control"
                                                        placeholder="Ex : BL-2026-001">

                                                </div>


                                                <!-- Motif -->
                                                <div class="col-md-6">

                                                    <label for="motif" class="form-label">

                                                        Motif

                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="motif"
                                                        id="motif"
                                                        class="form-control"
                                                        placeholder="Ex : Réapprovisionnement">

                                                </div>

                                            </div>

                                        </div>


                                        <!-- Pied du modal -->
                                        <div class="modal-footer">

                                            <button type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal">

                                                <i class="fas fa-times me-1"></i>
                                                Annuler

                                            </button>

                                            <button type="submit"
                                                    class="btn btn-primary">

                                                <i class="fas fa-save me-1"></i>
                                                Enregistrer l'entrée

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                    </div>

                    <!-- MODAL SORTIE STOCKS  -->
                <div class="modal fade"
                    id="modalNouvelleSortie"
                    tabindex="-1"
                    aria-labelledby="modalNouvelleSortieLabel"
                    aria-hidden="true">

                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="modalNouvelleSortieLabel">
                                    <i class="fas fa-minus-circle me-2"></i>
                                    Nouvelle sortie de stock
                                </h5>

                                <button type="button"
                                        class="btn-close btn-close-white"
                                        data-bs-dismiss="modal"
                                        aria-label="Fermer">
                                </button>
                            </div>

                            <form method="POST" action="ajouter_sortie.php">

                                <div class="modal-body">
                                    <div class="row g-3">

                                        <div class="col-md-12">
                                            <label for="id_medicament_sortie" class="form-label">
                                                Médicament <span class="text-danger">*</span>
                                            </label>

                                            <select name="id_medicament"
                                                    id="id_medicament_sortie"
                                                    class="form-select"
                                                    required>

                                                <option value="">Sélectionner un médicament</option>

                                                <?php foreach ($medicaments as $medicament): ?>
                                                    <option value="<?= (int) $medicament['id_medicament'] ?>">
                                                        <?= htmlspecialchars($medicament['nom']) ?>
                                                        — Code : <?= htmlspecialchars($medicament['code']) ?>
                                                        — Stock disponible :
                                                        <?= (int) $medicament['quantite_restante'] ?>
                                                    </option>
                                                <?php endforeach; ?>

                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="quantite_sortie" class="form-label">
                                                Quantité sortie <span class="text-danger">*</span>
                                            </label>

                                            <input type="number"
                                                name="quantite"
                                                id="quantite_sortie"
                                                class="form-control"
                                                min="1"
                                                required
                                                placeholder="Ex : 5">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="date_sortie" class="form-label">
                                                Date de sortie <span class="text-danger">*</span>
                                            </label>

                                            <input type="date"
                                                name="date_mouv"
                                                id="date_sortie"
                                                class="form-control"
                                                value="<?= date('Y-m-d') ?>"
                                                required>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="reference_sortie" class="form-label">
                                                Référence
                                            </label>

                                            <input type="text"
                                                name="reference"
                                                id="reference_sortie"
                                                class="form-control"
                                                placeholder="Ex : ORD-2026-001">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="motif_sortie" class="form-label">
                                                Motif <span class="text-danger">*</span>
                                            </label>

                                            <input type="text"
                                                name="motif"
                                                id="motif_sortie"
                                                class="form-control"
                                                required
                                                placeholder="Ex : Vente, ordonnance, destruction">
                                        </div>

                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button"
                                            class="btn btn-secondary"
                                            data-bs-dismiss="modal">
                                        Annuler
                                    </button>

                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-minus-circle me-1"></i>
                                        Enregistrer la sortie
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>

     </div>

    </div>
</div>


<!-- =====================================================
     BOOTSTRAP JS
====================================================== -->

<script src="../vendor/js/bootstrap.bundle.min.js"></script>

<script src="../vendor/js/fontAwesome.min.js"></script>


<!-- =====================================================
     RECHERCHE / FILTRE FRONTEND
====================================================== -->

<script>

const rechercheStock =
    document.getElementById('rechercheStock');

const filtreEtat =
    document.getElementById('filtreEtat');

const btnReinitialiser =
    document.getElementById('btnReinitialiser');


function filtrerStocks() {

    const recherche =
        rechercheStock.value.toLowerCase().trim();

    const etat =
        filtreEtat.value;

    const lignes =
        document.querySelectorAll(
            '#tableauStocks tr'
        );


    lignes.forEach(function(ligne) {

        const texte =
            ligne.textContent.toLowerCase();

        let afficherRecherche =
            texte.includes(recherche);

        let afficherEtat = true;


        if (etat !== 'tous') {

            afficherEtat =
                texte.includes(etat);

        }


        if (
            afficherRecherche &&
            afficherEtat
        ) {

            ligne.style.display = '';

        } else {

            ligne.style.display = 'none';

        }

    });

}


rechercheStock.addEventListener(
    'input',
    filtrerStocks
);


filtreEtat.addEventListener(
    'change',
    filtrerStocks
);


btnReinitialiser.addEventListener(
    'click',
    function() {

        rechercheStock.value = '';

        filtreEtat.value = 'tous';

        filtrerStocks();

    }
);

</script>

</body>

</html>