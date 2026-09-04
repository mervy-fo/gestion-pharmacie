<?php
require_once 'auth.php';
require_once 'connex.php';

/* =====================================================
   STATISTIQUES DU TABLEAU DE BORD
   ===================================================== */


/* -----------------------------------------------------
   1. TOTAL DES MÉDICAMENTS
   ----------------------------------------------------- */

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM medicament
";

$resultTotal = mysqli_query($conn, $sqlTotal);

if (!$resultTotal) {
    die("Erreur total médicaments : " . mysqli_error($conn));
}

$totalMedicaments = mysqli_fetch_assoc($resultTotal)['total'];


/* -----------------------------------------------------
   2. STOCK NORMAL
   Quantité supérieure au seuil minimum
   ----------------------------------------------------- */

$sqlStockNormal = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE quantite_restante > seuil_minimum
";

$resultStockNormal = mysqli_query($conn, $sqlStockNormal);

if (!$resultStockNormal) {
    die("Erreur stock normal : " . mysqli_error($conn));
}

$stockNormal = mysqli_fetch_assoc($resultStockNormal)['total'];


/* -----------------------------------------------------
   3. STOCK FAIBLE
   Quantité > 0 et <= seuil minimum
   ----------------------------------------------------- */

$sqlStockFaible = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE quantite_restante > 0
    AND quantite_restante <= seuil_minimum
";

$resultStockFaible = mysqli_query($conn, $sqlStockFaible);

if (!$resultStockFaible) {
    die("Erreur stock faible : " . mysqli_error($conn));
}

$stockFaible = mysqli_fetch_assoc($resultStockFaible)['total'];


/* -----------------------------------------------------
   4. RUPTURE DE STOCK
   ----------------------------------------------------- */

$sqlRupture = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE quantite_restante = 0
";

$resultRupture = mysqli_query($conn, $sqlRupture);

if (!$resultRupture) {
    die("Erreur rupture : " . mysqli_error($conn));
}

$ruptures = mysqli_fetch_assoc($resultRupture)['total'];


/* =====================================================
   STATISTIQUES DES PÉREMPTIONS
   ===================================================== */


/* -----------------------------------------------------
   5. MÉDICAMENTS PÉRIMÉS
   ----------------------------------------------------- */

$sqlPerime = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE date_peremption IS NOT NULL
    AND date_peremption < CURDATE()
";

$resultPerime = mysqli_query($conn, $sqlPerime);

if (!$resultPerime) {
    die("Erreur médicaments périmés : " . mysqli_error($conn));
}

$medicamentsPerimes = mysqli_fetch_assoc($resultPerime)['total'];


/* -----------------------------------------------------
   6. PÉREMPTION PROCHE
   Entre aujourd'hui et 30 jours
   ----------------------------------------------------- */

$sqlProche = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE date_peremption IS NOT NULL
    AND date_peremption >= CURDATE()
    AND date_peremption <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
";

$resultProche = mysqli_query($conn, $sqlProche);

if (!$resultProche) {
    die("Erreur péremption proche : " . mysqli_error($conn));
}

$peremptionsProches = mysqli_fetch_assoc($resultProche)['total'];


/* -----------------------------------------------------
   7. À SURVEILLER
   Entre 31 et 90 jours
   ----------------------------------------------------- */

$sqlSurveiller = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE date_peremption IS NOT NULL
    AND date_peremption > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND date_peremption <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
";

$resultSurveiller = mysqli_query($conn, $sqlSurveiller);

if (!$resultSurveiller) {
    die("Erreur surveillance : " . mysqli_error($conn));
}

$aSurveiller = mysqli_fetch_assoc($resultSurveiller)['total'];


/* -----------------------------------------------------
   8. PÉREMPTION NORMALE
   Plus de 90 jours
   ----------------------------------------------------- */

$sqlNormalPeremption = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE date_peremption IS NOT NULL
    AND date_peremption > DATE_ADD(CURDATE(), INTERVAL 90 DAY)
";

$resultNormalPeremption = mysqli_query($conn, $sqlNormalPeremption);

if (!$resultNormalPeremption) {
    die("Erreur péremption normale : " . mysqli_error($conn));
}

$medicamentsNormaux = mysqli_fetch_assoc($resultNormalPeremption)['total'];

/* ==========================================
   ALERTES RÉCENTES
   ========================================== */

$sqlAlertes = "
    SELECT
        id_medicament,
        nom,
        quantite_restante,
        seuil_minimum,
        date_peremption
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

    ORDER BY
        CASE
            WHEN quantite_restante = 0 THEN 1
            WHEN date_peremption < CURDATE() THEN 2
            WHEN quantite_restante <= seuil_minimum THEN 3
            WHEN date_peremption <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 4
            ELSE 5
        END,

        date_peremption ASC

    LIMIT 5
";

$resultAlertes = mysqli_query($conn, $sqlAlertes);

if (!$resultAlertes) {
    die("Erreur alertes : " . mysqli_error($conn));
}
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

    <title>Tableau de bord - PharmaStock</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../vendor/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.css">

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="dashboard-wrapper">

    <!-- =========================
         SIDEBAR
    ========================== -->

   <?php include 'sidebar.php'; ?>

    <!-- =========================
         CONTENU
    ========================== -->

    <main class="main-content">

        <!-- NAVBAR -->

        <header class="topbar">

            <div>

                <h5 class="mb-1">
                    Tableau de bord
                </h5>

                <small>
                    Vue générale de votre pharmacie
                </small>

            </div>


            <div class="topbar-right">

                <!-- Notification -->

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


                <!-- Profil -->

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


        <!-- CONTENU -->

        <section class="content">

            <!-- Bienvenue -->

            <div class="welcome-card">

                <div>

                    <h3>
                        Bonjour, Pharmacien 
                    </h3>

                    <p>
                        Voici un aperçu de l'état actuel de votre stock.
                    </p>

                </div>

                <i class="fas fa-prescription-bottle-medical"></i>

            </div>


            <!-- =====================
                 STATISTIQUES
            ====================== -->

            <div class="row g-4 mt-1">


                <!-- Total médicaments -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon blue">
                            <i class="fas fa-pills"></i>
                        </div>

                        <div>

                            <span>
                                Médicaments
                            </span>

                            <h3 class="mb-0">
                                        <?= $totalMedicaments ?>
                                    </h3>


                            <small class="text-muted">
                                Total enregistré
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Stock normal -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon green">
                            <i class="fas fa-circle-check"></i>
                        </div>

                        <div>

                            <span>
                                Stock normal
                            </span>

                            <h3 class="mb-0">
                                <?= $stockNormal ?>
                            </h3>

                            <small class="normal-text">
                                Situation normale
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Stock faible -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon orange">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>

                        <div>

                            <span>
                                Stock faible
                            </span>

                            <h3> <?= $stockFaible ?></h3>

                            <small class="orange-text">
                                À réapprovisionner
                            </small>

                        </div>

                    </div>

                </div>


                <!-- Rupture -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon red">
                            <i class="fas fa-circle-xmark"></i>
                        </div>

                        <div>

                            <span>
                                Ruptures
                            </span>

                            <h3>    <?= $ruptures ?> </h3>

                            <small class="red-text">
                                Action nécessaire
                            </small>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================
                 ALERTES PEREMPTION
            ====================== -->

            <h5 class="section-title">
                État des péremptions
            </h5>


            <div class="row g-4 ">


                <!-- Périmés -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon red">
                                    
                            <i class="fas fa-skull-crossbones"></i>
                        
                        </div>
                            

                        <div>
                           <span>
                                Médicaments périmés
                            </span>

                              <h2><?= $medicamentsPerimes ?></h2>
                            

                        </div>
                        <div class="alert-card-header">

                            <span class="status red-status red-text">
                                Périmé
                            </span>

                        </div>
                        
                    </div>
                </div>


                <!-- Péremption proche -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon purple">

                            <i class="fas fa-hourglass-half"></i>
                        </div>

                        <div>
                            <span>
                                Péremptions proches
                            </span>
                            <h2>  <?= $peremptionsProches ?></h2>
                        </div>
                      
                        <div class="alert-card-header">
                            <span class="status purple-status purple-text">
                                Proche
                            </span> 
                        </div>
                    </div>

                </div>


                <!-- À surveiller -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon yellow">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <span>
                                À surveiller
                            </span>

                            <h2><?= $aSurveiller ?></h2>
                        </div>

                        <div class="alert-card-header">
                            <span class="status yellow-status yellow-text">
                                Surveiller
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Normal -->

                <div class="col-xl-3 col-md-6">

                    <div class="stat-card">

                        <div class="stat-icon green">
                            <i class="fas fa-circle-check"></i>
                        </div>

                        <div>
                            <span>
                                Médicaments normaux
                            </span>
                        <h2> <?= $medicamentsNormaux ?> </h2>

                        </div>


                         <div class="alert-card-header">

                            <span class="status green-status green-text me-0">
                                Normal
                            </span>

                        </div>
                    </div>

                </div>

            </div>


            <!-- =====================
                 ALERTES RECENTES
            ====================== -->

            <div class="row g-4 mt-1">


                <!-- Alertes -->

                <div class="col-lg-7">

                    <div class="dashboard-card">

                        <div class="card-title">

                            <div>
                                <h5>Alertes récentes</h5>
                                <small>
                                    Médicaments nécessitant votre attention
                                </small>
                            </div>

                            <a href="alertes.php">
                                Voir tout
                            </a>

                        </div>


                        <div class="alert-list">

                            <?php if (mysqli_num_rows($resultAlertes) > 0): ?>

                                <?php while ($alerte = mysqli_fetch_assoc($resultAlertes)): ?>

                                    <?php

                                    $nom = $alerte['nom'];
                                    $stock = (int)$alerte['quantite_restante'];
                                    $seuil = (int)$alerte['seuil_minimum'];
                                    $dateExpiration = $alerte['date_peremption'];

                                    /* Valeurs par défaut */
                                    $etat = "";
                                    $description = "";
                                    $icone = "";
                                    $classe = "";

                                    /* ==================================
                                    RUPTURE DE STOCK
                                    ================================== */

                                    if ($stock == 0) {

                                        $etat = "Rupture";
                                        $description = "Stock épuisé";
                                        $icone = "fa-circle-xmark";
                                        $classe = "red";

                                    }

                                    /* ==================================
                                    MÉDICAMENT PÉRIMÉ
                                    ================================== */

                                    elseif (
                                        !empty($dateExpiration)
                                        && $dateExpiration < date('Y-m-d')
                                    ) {

                                        $etat = "Périmé";
                                        $description = "Date dépassée";
                                        $icone = "fa-skull-crossbones";
                                        $classe = "red";

                                    }

                                    /* ==================================
                                    STOCK FAIBLE
                                    ================================== */

                                    elseif (
                                        $stock > 0
                                        && $stock <= $seuil
                                    ) {

                                        $etat = "Stock faible";
                                        $description = "Stock : " . $stock . " unités";
                                        $icone = "fa-triangle-exclamation";
                                        $classe = "orange";

                                    }

                                    /* ==================================
                                    PÉREMPTION
                                    ================================== */

                                    else {

                                        if (!empty($dateExpiration)) {

                                            $dateExp = new DateTime($dateExpiration);
                                            $dateActuelle = new DateTime();

                                            $jours = $dateActuelle->diff($dateExp)->days;

                                            /* Péremption proche : 0 à 30 jours */

                                            if ($dateExpiration >= date('Y-m-d')
                                                && $dateExpiration <= date('Y-m-d', strtotime('+30 days'))) {

                                                $etat = "Proche";
                                                $description = "Expire dans " . $jours . " jours";
                                                $icone = "fa-clock";
                                                $classe = "purple";

                                            }

                                            /* À surveiller : 31 à 90 jours */

                                            elseif ($dateExpiration <= date('Y-m-d', strtotime('+90 days'))) {

                                                $etat = "À surveiller";
                                                $description = "Expire dans " . $jours . " jours";
                                                $icone = "fa-calendar-days";
                                                $classe = "yellow";

                                            }
                                        }
                                    }

                                    ?>

                                    <?php if ($etat != ""): ?>

                                        <div class="alert-row">

                                            <!-- Icône -->

                                            <div class="row-icon <?= $classe ?>">

                                                <i class="fas <?= $icone ?>"></i>

                                            </div>


                                            <!-- Informations -->

                                            <div class="alert-info">

                                                <strong>
                                                    <?= htmlspecialchars($nom) ?>
                                                </strong>

                                                <span>
                                                    <?= htmlspecialchars($description) ?>
                                                </span>

                                            </div>


                                            <!-- État -->

                                            <span class="badge-alert <?= $classe ?>-status">

                                                <?= htmlspecialchars($etat) ?>

                                            </span>

                                        </div>

                                    <?php endif; ?>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <div class="text-center py-4">

                                    <i class="fas fa-circle-check text-success fa-2x mb-2"></i>

                                    <p class="text-muted mb-0">
                                        Aucune alerte récente
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- Mouvements -->

                <div class="col-lg-5">

                    <div class="dashboard-card">

                        <div class="card-title">

                            <div>

                                <h5>
                                    Mouvements récents
                                </h5>

                                <small>
                                    Dernières opérations
                                </small>

                            </div>

                            <a href="#">
                                Voir tout
                            </a>

                        </div>


                        <div class="movement-list">


                            <div class="movement-row">

                                <div class="movement-icon green">
                                    <i class="fas fa-arrow-down"></i>
                                </div>

                                <div>

                                    <strong>
                                        Entrée de stock
                                    </strong>

                                    <small>
                                        Paracetamol
                                    </small>

                                </div>

                                <span class="movement-quantity green-text">
                                    +100
                                </span>

                            </div>


                            <div class="movement-row">

                                <div class="movement-icon red">
                                    <i class="fas fa-arrow-up"></i>
                                </div>

                                <div>

                                    <strong>
                                        Sortie de stock
                                    </strong>

                                    <small>
                                        Amoxicilline
                                    </small>

                                </div>

                                <span class="movement-quantity red-text">
                                    -25
                                </span>

                            </div>


                            <div class="movement-row">

                                <div class="movement-icon green">
                                    <i class="fas fa-arrow-down"></i>
                                </div>

                                <div>

                                    <strong>
                                        Entrée de stock
                                    </strong>

                                    <small>
                                        Vitamine C
                                    </small>

                                </div>

                                <span class="movement-quantity green-text">
                                    +50
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- =====================
     ACTIONS RAPIDES
====================== -->

<h5 class="section-title">
    Actions rapides
</h5>

<div class="row g-3">

    <!-- Ajouter médicament -->
    <div class="col-xl-3 col-md-6">

        <a href="medicaments.php" class="quick-action">

            <div class="quick-action-icon blue">
                <i class="fas fa-pills"></i>
            </div>

            <div class="quick-action-content">

                <strong>Ajouter un médicament</strong>

                <span>
                    Enregistrer un nouveau médicament
                </span>

            </div>

            <i class="fas fa-chevron-right quick-arrow"></i>

        </a>

    </div>


    <!-- Entrée de stock -->
    <div class="col-xl-3 col-md-6">

        <a href="stocks.php" class="quick-action">

            <div class="quick-action-icon green">
                <i class="fas fa-arrow-down"></i>
            </div>

            <div class="quick-action-content">

                <strong>Entrée de stock</strong>

                <span>
                    Ajouter une quantité au stock
                </span>

            </div>

            <i class="fas fa-chevron-right quick-arrow"></i>

        </a>

    </div>


    <!-- Sortie de stock -->
    <div class="col-xl-3 col-md-6">

        <a href="stocks.php" class="quick-action">

            <div class="quick-action-icon red">
                <i class="fas fa-arrow-up"></i>
            </div>

            <div class="quick-action-content">

                <strong>Sortie de stock</strong>

                <span>
                    Enregistrer une sortie
                </span>

            </div>

            <i class="fas fa-chevron-right quick-arrow"></i>

        </a>

    </div>


    <!-- Nouvelle vente -->
    <div class="col-xl-3 col-md-6">

        <a href="vente.php" class="quick-action">

            <div class="quick-action-icon purple">
                <i class="fas fa-cart-plus"></i>
            </div>

            <div class="quick-action-content">

                <strong>Nouvelle vente</strong>

                <span>
                    Enregistrer une vente
                </span>

            </div>

            <i class="fas fa-chevron-right quick-arrow"></i>

        </a>

    </div>

</div>
        </section>

    </main>

</div>


<script src="../vendor/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/js/fontAwesome.min.js"></script>

</body>
</html>