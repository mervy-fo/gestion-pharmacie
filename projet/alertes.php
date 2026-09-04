<?php
require_once 'auth.php';
require_once 'connex.php';


/* =========================
   RÉCUPÉRATION DES MÉDICAMENTS
   ========================= */

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

/* =========================
   DATE ACTUELLE
   ========================= */

$aujourd_hui = new DateTime();
/* Nombre total d'alertes */

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

    <title>Alertes - Pharmacie</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../vendor/css/bootstrap.min.css">
        <link rel="stylesheet" href="libre/DataTables/datatables.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.css">

    <!-- Ton CSS général -->
    <link rel="stylesheet" href="./style.css">

   
</head>

<body class="bg-light page-alertes">

<div class="container-fluid">

    <div class="row">

        <!-- =========================
             SIDEBAR
             ========================= -->
      <?php include 'sidebar.php'; ?>

        <!-- =========================
             CONTENU PRINCIPAL
             ========================= -->
        <main class="main-content">

            <!-- NAVBAR -->
            <nav class="navbar navbar-expand-lg bg-white border-bottom px-4 py-3">

                <div>
                    <h5 class="mb-0 fw-bold text-primary">
                        Alertes
                    </h5>

                    <small class="text-muted">
                        Surveillance des médicaments nécessitant une attention
                    </small>
                </div>

                <div class="ms-auto d-flex align-items-center gap-3">

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
                   

                </div>

            </nav>


            <!-- CONTENU -->
            <div class="p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <div>
                        <h2 class="fw-bold text-dark mb-1">
                            <i class="fas fa-triangle-exclamation me-2 text-primary"></i>
                            Alertes
                        </h2>

                        <p class="text-muted mb-0">
                            Médicaments nécessitant une intervention
                        </p>
                    </div>

                </div>


                <!-- =========================
                     CARTES DES ALERTES
                     ========================= -->

                <div class="row g-3 mb-4">

                    <?php
                    $rupture = 0;
                    $stock_faible = 0;
                    $perime = 0;
                    $proche = 0;
                    $surveiller = 0;

                    mysqli_data_seek($resultat, 0);

                    while ($med = mysqli_fetch_assoc($resultat)) {

                        $stock = (int)$med['quantite_restante'];
                        $seuil = (int)$med['seuil_minimum'];

                        if ($stock == 0) {
                            $rupture++;
                        } elseif ($stock <= $seuil) {
                            $stock_faible++;
                        }

                        if (!empty($med['date_peremption'])) {

                            $date_exp = new DateTime($med['date_peremption']);
                            $jours = (int)$aujourd_hui->diff($date_exp)->format('%r%a');

                            if ($jours < 0) {
                                $perime++;
                            } elseif ($jours <= 30) {
                                $proche++;
                            } elseif ($jours <= 90) {
                                $surveiller++;
                            }
                        }
                    }
                    ?>

                    <!-- Rupture -->
                    <div class="col-xl-3 col-md-6">
                        <div class="mini-stat">

                            <div class="stat-icon red">
                                    <i class="fas fa-circle-xmark"></i>
                            </div>
                            <div >
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        Rupture
                                    </span>

                                   
                                </div>

                                <h3 class="fw-bold text-danger mt-2">
                                    <?= $rupture ?>
                                </h3>

                                <small class="text-muted">
                                    médicaments
                                </small>

                            </div>
                        </div>
                    </div>


                    <!-- Stock faible -->
                    <div class="col-xl-3 col-md-6">
                        <div class="mini-stat">

                            <div class="stat-icon orange">
                                    <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div >
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        Stock faible
                                    </span>

                                   
                                </div>

                                <h3 class="fw-bold text-warning mt-2">
                                    <?= $stock_faible ?>
                                </h3>

                                <small class="text-muted">
                                    médicaments
                                </small>

                            </div>
                        </div>
                    </div>


                    <!-- Périmés -->
                    <div class="col-md-3">
                       
                        <div class="mini-stat">

                            <div class="mini-stat-icon red">
                                <i class="fas fa-skull-crossbones"></i>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        Périmés
                                    </span>

                                    
                                </div>

                                <h3 class="fw-bold text-danger mt-2">
                                    <?= $perime ?>
                                </h3>

                                <small class="text-muted">
                                    médicaments
                                </small>
                            </div>
                            
                        </div>
                    </div>


                    <!-- Péremption proche -->
                    <div class="col-md-3">
                        <div class="mini-stat">
                             <div class="mini-stat-icon purple">
                                <i class="fas fa-hourglass-half"></i>
                             </div>

                            <div >
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">
                                        Péremption proche
                                    </span>
                                </div>

                                <h3 class="fw-bold mt-2" style="color:#6f42c1;">
                                    <?= $proche ?>
                                </h3>

                                <small class="text-muted">
                                    dans les 30 jours
                                </small>

                            </div>
                        </div>
                    </div>

                </div>


                <!-- =========================
                     TABLEAU DES ALERTES
                     ========================= -->

                <div class="dashboard-card">

                        <div  class="table-toolbar">

                            <div >
                                <h5 class="mb-1 fw-bold text-dark">
                                    <i class="fas fa-bell me-2 "></i>
                                    Liste des alertes
                                </h5>

                                <small class="text-muted">
                                    Médicaments à surveiller
                                </small>
                            </div>

                            <span class="badge bg-danger rounded-pill">
                                <?= $rupture + $stock_faible + $perime + $proche + $surveiller ?>
                                alertes
                            </span>

                        </div>

                    <div class="table-responsive">

                        <table id="tableMedicaments" class="table medicine-table">

                            <thead >

                            <tr >
                                <th>N°</th>
                                <th class="text-center">Médicament</th>
                                <th class="text-center">Code</th>
                                <th class="text-center">Catégorie</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Seuil minimum</th>
                                <th class="text-center">Expiration</th>
                                <th class="text-center">État</th>
                                <th>Action</th>
                            </tr>

                            </thead>

                            <tbody>

                            <?php

                                mysqli_data_seek($resultat, 0);

                                $numero = 1;
                                $nombre_alertes = 0;

                                while ($med = mysqli_fetch_assoc($resultat)) {

                                $stock = (int) $med['quantite_restante'];
                                $seuil = (int) $med['seuil_minimum'];

                                $etat = "Normal";
                                $classe = "status-normal";
                                $icone = "fa-circle-check";

                                $alerte = false;


                                /* =========================================
                                CALCUL DE L'ÉTAT
                                ========================================= */

                                /* 1. RUPTURE DE STOCK */
                                if ($stock == 0) {

                                    $etat = "Rupture";
                                    $classe = "status-out";
                                    $icone = "fa-circle-xmark";
                                    $alerte = true;

                                }


                                /* 2. STOCK FAIBLE */
                                elseif ($stock <= $seuil) {

                                    $etat = "Stock faible";
                                    $classe = "status-low";
                                    $icone = "fa-triangle-exclamation";
                                    $alerte = true;

                                }


                                /* 3. VÉRIFICATION DE LA PÉREMPTION */
                                if (!empty($med['date_peremption'])) {

                                    $date_exp = new DateTime($med['date_peremption']);

                                    $jours_restants = (int) $aujourd_hui
                                        ->diff($date_exp)
                                        ->format('%r%a');


                                    /* MÉDICAMENT PÉRIMÉ */
                                    if ($jours_restants < 0) {

                                        $etat = "Périmé";
                                        $classe = "status-expired";
                                        $icone = "fas fa-skull-crossbones";
                                        $alerte = true;

                                    }


                                    /* PÉREMPTION DANS 30 JOURS */
                                    elseif ($jours_restants <= 30) {

                                        $etat = "Péremption proche";
                                        $classe = "status-expiring";
                                        $icone = "fa-hourglass-half";
                                        $alerte = true;

                                    }


                                    /* PÉREMPTION ENTRE 31 ET 90 JOURS */
                                    elseif ($jours_restants <= 90) {

                                        $etat = "À surveiller";
                                        $classe = "status-warning";
                                        $icone = "fas fa-eye";
                                        $alerte = true;

                                    }

                                }


                                /* =========================================
                                IGNORER LES MÉDICAMENTS NORMAUX
                                ========================================= */

                                if (!$alerte) {
                                    continue;
                                }


                                /* Compteur réel des alertes */
                                $nombre_alertes++;


                                ?>

                                <tr>

                                    <td class="text-center">
                                        <strong><?= $numero++ ?></strong>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($med['nom']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($med['code']) ?>
                                    </td>

                                    <td class="text-center">
                                        <?= htmlspecialchars($med['categorie_nom']) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= $stock ?>
                                        </strong>
                                    </td>

                                    <td class="text-center">
                                        <?= $seuil ?>
                                    </td>

                                    <td>
                                        <?= !empty($med['date_peremption'])
                                            ? htmlspecialchars($med['date_peremption'])
                                            : 'Non renseignée' ?>
                                    </td>

                                    <td class="text-center">

                                      <span class="medicine-status <?= $classe ?> ">

                                           <i class="fas <?= $icone ?> me-1"></i>

                                            <?= htmlspecialchars($etat) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a href="medicaments.php?id=<?= $med['id_medicament'] ?>"
                                           class="btn btn-sm btn-outline-success"
                                           title="Voir le médicament">

                                            <i class="fas fa-eye"></i>

                                        </a>

                                    </td>

                                </tr>

                            <?php } ?>


                            <?php if ($nombre_alertes == 0): ?>

                                <tr>

                                    <td colspan="9" class="text-center py-5">

                                        <i class="fas fa-circle-check fa-2x text-success mb-3"></i>

                                        <h6 class="fw-bold">
                                            Aucune alerte
                                        </h6>

                                        <p class="text-muted mb-0">
                                            Tous les médicaments sont dans un état normal.
                                        </p>

                                    </td>

                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>


<!-- Bootstrap JS -->
<script src="../vendor/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/js/fontAwesome.min.js"></script>
<script src="jquery-ui-1.14.2.custom/external/jquery/jquery.js"></script>
    <script src="jquery-ui-1.14.2.custom/jquery-ui.js"></script>
    <script src="libre/DataTables/datatables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#tableMedicaments').DataTable({
                responsive: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    lengthMenu: "Afficher _MENU_ médicaments",
                    search: "Rechercher",
                    info: "Affichage de _START_à _END_ sur _TOTAL_ médicaments",
                    infoEmpty: "Aucun médicament",
                    zeroRecords: "Aucun médicament trouvé",
                    emptyTable: "Aucun médicament disponible",
                            paginate: {
                        previous: "Précédent",
                        next: "Suivant"
                    }
                }

            });

        });
    </script>
</body>
</html>