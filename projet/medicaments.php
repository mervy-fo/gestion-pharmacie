<?php

session_start();

require_once 'connex.php';
require_once 'protection.php';

/* =====================================================
   PROTECTION DE LA PAGE
===================================================== */

protegerPage("medicaments");

/* =====================================================
   VÉRIFICATION DE CONNEXION
===================================================== */

if (!isset($_SESSION['id_util'])) {
    header("Location: login.php");
    exit;
}

/* =====================================================
   RÔLE DE L'UTILISATEUR
===================================================== */

$role = $_SESSION['role'] ?? '';

/* =====================================================
   DROITS
===================================================== */
$peutAjouter = aPermission("ajouter_medicament");

$peutModifier = aPermission("modifier_medicament");

$peutSupprimer = aPermission("supprimer_medicament");
/* =====================================================
   1. AJOUTER UN MEDICAMENT
===================================================== */

if (isset($_POST['ajouter'])) {

    if (!aPermission("ajouter_medicament")) {
        header("Location: acces_refuse.php");
        exit();
    }
    $nom = trim($_POST['nom'] ?? '');
    $forme = trim($_POST['forme'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $code = trim($_POST['code'] ?? '');

    $seuil_minimum = (int) ($_POST['seuil_minimum'] ?? 0);
    $quantite_restante = (int) ($_POST['quantite_restante'] ?? 0);

    $date_peremption = !empty($_POST['date_peremption'])
        ? $_POST['date_peremption']
        : null;
        $dateObjet = DateTime::createFromFormat('Y-m-d', $date_peremption);

    if (
        !$dateObjet ||
        $dateObjet->format('Y-m-d') !== $date_peremption
    ) {
        die("La date de péremption est invalide.");
    }

    // La BD contient l'ID de la catégorie
    $nom_categorie = trim($_POST['nom_categorie'] ?? '');

    $prix = (float) ($_POST['prix'] ?? 0);


    if (
        empty($nom) ||
        empty($code) ||
        empty($nom_categorie)
    ) {

        die("Veuillez remplir les champs obligatoires.");

    }


    if ($seuil_minimum < 0 || $quantite_restante < 0) {

        die("Le seuil et la quantité doivent être positifs.");

    }


    if ($prix < 0) {

        die("Le prix ne peut pas être négatif.");

    }


    // Vérifier le code
    $verification = mysqli_prepare(
        $conn,
        "SELECT id_medicament
         FROM medicament
         WHERE code = ?"
    );


    if (!$verification) {

        die("Erreur : " . mysqli_error($conn));

    }


    mysqli_stmt_bind_param(
        $verification,
        "s",
        $code
    );


    mysqli_stmt_execute($verification);

    $resultat_verification =
        mysqli_stmt_get_result($verification);


    if (mysqli_num_rows($resultat_verification) > 0) {

        die("Ce code médicament existe déjà.");

    }


    mysqli_stmt_close($verification);


    // INSERTION
    $sql_insert = "INSERT INTO medicament
    (
        nom,
        forme,
        dosage,
        code,
        seuil_minimum,
        quantite_restante,
        date_peremption,
        nom_categorie,
        prix
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";


    $stmt = mysqli_prepare(
        $conn,
        $sql_insert
    );


    if (!$stmt) {

        die("Erreur : " . mysqli_error($conn));

    }


    mysqli_stmt_bind_param(
        $stmt,
        "ssssiissd",
        $nom,
        $forme,
        $dosage,
        $code,
        $seuil_minimum,
        $quantite_restante,
        $date_peremption,
        $nom_categorie,
        $prix
    );


    if (!mysqli_stmt_execute($stmt)) {

        die(
            "Erreur lors de l'ajout : "
            . mysqli_stmt_error($stmt)
        );

    }


    mysqli_stmt_close($stmt);


    header(
        "Location: medicaments.php?success=added"
    );

    exit;
}



/* =====================================================
   2. SUPPRIMER UN MEDICAMENT
===================================================== */

if (isset($_POST['supprimer'])) {

     if (!aPermission("supprimer_medicament")) {
        header("Location: acces_refuse.php");
        exit();
    }
    $id_medicament =
        (int) ($_POST['id_medicament'] ?? 0);


    if ($id_medicament <= 0) {

        die("Identifiant du médicament invalide.");

    }


    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM medicament
         WHERE id_medicament = ?"
    );


    if (!$stmt) {

        die("Erreur : " . mysqli_error($conn));

    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_medicament
    );


    if (!mysqli_stmt_execute($stmt)) {

        die(
            "Erreur lors de la suppression : "
            . mysqli_stmt_error($stmt)
        );

    }


    mysqli_stmt_close($stmt);


    header(
        "Location: medicaments.php?success=deleted"
    );

    exit;
}



/* =====================================================
   3. MODIFIER UN MEDICAMENT
===================================================== */

if (isset($_POST['modifier'])) {
     if (!aPermission("modifier_medicament")) {
        header("Location: acces_refuse.php");
        exit();
    }

    $id_medicament =
        (int) ($_POST['id_medicament'] ?? 0);

    $nom =
        trim($_POST['nom'] ?? '');

    $forme =
        trim($_POST['forme'] ?? '');

    $dosage =
        trim($_POST['dosage'] ?? '');

    $code =
        trim($_POST['code'] ?? '');

    $seuil_minimum =
        (int) ($_POST['seuil_minimum'] ?? 0);

    $quantite_restante =
        (int) ($_POST['quantite_restante'] ?? 0);

    // IMPORTANT : récupérer directement la date
    $date_peremption =
        $_POST['date_peremption'] ?? '';
        $dateObjet = DateTime::createFromFormat('Y-m-d', $date_peremption);

if (
    !$dateObjet ||
    $dateObjet->format('Y-m-d') !== $date_peremption
) {
    die("La date de péremption est invalide.");
}

    $nom_categorie =
        trim($_POST['nom_categorie'] ?? '');

    $prix =
        (float) ($_POST['prix'] ?? 0);


    if ($id_medicament <= 0) {

        die("Identifiant du médicament invalide.");

    }


    if (
        empty($nom) ||
        empty($code) ||
        empty($nom_categorie)
    ) {

        die("Veuillez remplir les champs obligatoires.");

    }


    if ($seuil_minimum < 0) {

        die("Le seuil minimum ne peut pas être négatif.");

    }


    if ($quantite_restante < 0) {

        die("La quantité ne peut pas être négative.");

    }


    if ($prix < 0) {

        die("Le prix ne peut pas être négatif.");

    }


    if (empty($date_peremption)) {

        die("La date de péremption est obligatoire.");

    }


    // Vérifier le code
    $verification = mysqli_prepare(
        $conn,
        "SELECT id_medicament
         FROM medicament
         WHERE code = ?
         AND id_medicament != ?"
    );


    if (!$verification) {

        die("Erreur : " . mysqli_error($conn));

    }


    mysqli_stmt_bind_param(
        $verification,
        "si",
        $code,
        $id_medicament
    );


    mysqli_stmt_execute($verification);

    $resultat_verification =
        mysqli_stmt_get_result($verification);


    if (mysqli_num_rows($resultat_verification) > 0) {

        die("Ce code médicament est déjà utilisé.");

    }


    mysqli_stmt_close($verification);


    // UPDATE
    $sql_update = "UPDATE medicament SET

        nom = ?,
        forme = ?,
        dosage = ?,
        code = ?,
        seuil_minimum = ?,
        quantite_restante = ?,
        date_peremption = ?,
        nom_categorie = ?,
        prix = ?

        WHERE id_medicament = ?";


    $stmt = mysqli_prepare(
        $conn,
        $sql_update
    );


    if (!$stmt) {

        die("Erreur : " . mysqli_error($conn));

    }


 mysqli_stmt_bind_param(
    $stmt,
    "ssssiissdi",
    $nom,
    $forme,
    $dosage,
    $code,
    $seuil_minimum,
    $quantite_restante,
    $date_peremption,
    $nom_categorie,
    $prix,
    $id_medicament
);


    if (!mysqli_stmt_execute($stmt)) {

        die(
            "Erreur lors de la modification : "
            . mysqli_stmt_error($stmt)
        );

    }


    mysqli_stmt_close($stmt);


    header(
        "Location: medicaments.php?success=updated"
    );

    exit;
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



/* =====================================================
   4. RECUPERER LES MEDICAMENTS
===================================================== */

$sql = "SELECT
            m.*,
            c.nom_categorie AS categorie_nom

        FROM medicament m

        LEFT JOIN categorie c
            ON m.nom_categorie = c.id_categorie

        ORDER BY m.id_medicament ASC";


$resultat = mysqli_query(
    $conn,
    $sql
);


if (!$resultat) {

    die(
        "Erreur : "
        . mysqli_error($conn)
    );

}

?>

    

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Médicaments - PharmaStock</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../vendor/css/bootstrap.min.css">
    <!-- DataTables CSS -->
        <link rel="stylesheet" href="libre/DataTables/datatables.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.css">

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="./style.css">
</head>

<body>

<div class="dashboard-wrapper">

    <!-- =========================
         SIDEBAR
    ========================== -->

   <?php include 'sidebar.php'; ?>


    <!-- =========================
         CONTENU PRINCIPAL
    ========================== -->

    <main class="main-content">

        <!-- NAVBAR -->

        <header class="topbar">

            <div>

                <h5 class="mb-1">
                    Médicaments
                </h5>

                <small>
                    Gestion des médicaments de la pharmacie
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


        <!-- =========================
             CONTENU
        ========================== -->

        <section class="content">


            <!-- TITRE + BOUTON -->

            <div class="page-header">

                <div>

                    <h3>
                        Gestion des médicaments
                    </h3>

                    <p>
                        Consultez et gérez les médicaments disponibles.
                    </p>

                </div>

                <?php if ($peutAjouter): ?>
                    <button
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalAjouter">
                    

                        <i class="fas fa-plus me-2"></i>

                        Ajouter un médicament

                    </button>
                <?php endif; ?>

            </div>


              <!-- MESSAGES -->

                <?php if (isset($_GET['success'])): ?>

                    <?php if ($_GET['success'] == 'added'): ?>

                        <div class="alert alert-success">
                            Medicament ajouté avec succès.
                        </div>

                    <?php elseif ($_GET['success'] == 'updated'): ?>

                        <div class="alert alert-success">
                            Medicament modifié avec succès.
                        </div>

                    <?php elseif ($_GET['success'] == 'deleted'): ?>

                        <div class="alert alert-success">
                            Medicament supprimé avec succès.
                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            <!-- =========================
                 STATISTIQUES
            ========================== -->

            <div class="row g-3 mb-4">


                <div class="col-xl-3 col-md-6">

                    <div class="mini-stat">

                        <div class="mini-stat-icon green">
                            <i class="fas fa-circle-check"></i>
                        </div>

                        <div>

                            <span>Medicaments normaux</span>

                             <h4 class="mb-0">
                                       906
                                    </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="mini-stat">

                        <div class="mini-stat-icon purple">
                            <i class="fas fa-hourglass-half"></i>
                        </div>

                        <div>

                            <span>péremption proche</span>

                            <h4>32</h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="mini-stat">

                        <div class="mini-stat-icon yellow">
                           <i class="fas fa-eye"></i>
                        </div>

                        <div>

                            <span>A surveiller</span>

                            <h4>47</h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6">

                    <div class="mini-stat">

                        <div class="mini-stat-icon red">
                            <i class="fas fa-skull-crossbones"></i>
                        </div>

                        <div>

                            <span>périmé</span>

                            <h4>15</h4>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================
                 TABLEAU
            ========================== -->

            <div class="dashboard-card">


                <!-- Recherche -->

                <div class="table-toolbar">

                    <div>

                        <h5>
                            Liste des médicaments
                        </h5>

                        <small>
                            Médicaments enregistrés dans le stock
                        </small>

                    </div>
                 
                </div>


                <!-- Table -->

                <div class="table-responsive">

                    <table id="tableMedicaments" class="table medicine-table">

                        <thead>

                            <tr>

                                <th class="text-center">N°</th>

                                <th class="text-center">Médicament</th>

                                <th class="text-center">Code</th>

                                <th class="text-center">Forme</th>

                                <th class="text-center">Dosage</th>

                                <th class="text-center">Categories</th>

                                <th class="text-center">Prix</th>
                                
                                <th class="text-center">Stock</th>

                                <th class="text-center">Expiration</th>

                                <th class="text-center">État</th>

                                <th class="text-center">Actions</th>

                            </tr>

                        </thead>


                       <tbody id="medicineTable">

                            <?php

                            $numero = 1;

                            while ($medicament = mysqli_fetch_assoc($resultat)) {


                                // ============================
                                // STOCK
                                // ============================

                                $quantite = (int) $medicament['quantite_restante'];

                                // ============================
                                // DATE DE PEREMPTION
                                // ============================

                                $date_peremption = new DateTime(
                                    $medicament['date_peremption']
                                );

                                $aujourd_hui = new DateTime();

                                // Nombre de jours avant expiration
                                $difference = $aujourd_hui->diff($date_peremption);

                                $jours_restants = (int) $difference->days;
                                // ============================
                                // DETERMINATION DE L'ETAT
                                // ============================

                                if ($date_peremption < $aujourd_hui) {

                                    $etat = "Périmé";
                                    $classe = "status-expired";

                                }

                                elseif ($quantite == 0) {

                                    $etat = "Rupture de stock";
                                    $classe = "status-out";

                                }

                                elseif ($jours_restants <= 30) {

                                    $etat = "Péremption proche";
                                    $classe = "status-expiring";

                                }
                            elseif ($jours_restants <= 90) {

                                    $etat = "Péremption à surveiller";
                                    $classe = "status-warning";

                                }

                                elseif ($quantite <= 10) {

                                    $etat = "Stock faible";
                                    $classe = "status-low";

                                }

                                else {

                                    $etat = "Normal";
                                    $classe = "status-normal";

                                }

                                ?>

                                <tr>

                                    <!-- NUMERO D'AFFICHAGE -->
                                    <td class="text-center">
                                        <strong>
                                            <?= $numero ?>
                                        </strong>
                                    </td>


                                    <!-- MEDICAMENT -->
                                    <td class="text-center">

                                        <div class="medicine-name">

                                            <div class="medicine-icon">
                                                <i class="fas fa-pills"></i>
                                            </div>

                                            <strong>
                                                <?= htmlspecialchars($medicament['nom']) ?>
                                            </strong>

                                        </div>

                                    </td>


                                    <!-- CODE -->
                                    <td class="text-center">
                                        <?= htmlspecialchars($medicament['code']) ?>
                                    </td>


                                    <!-- FORME -->
                                    <td class="text-center">
                                        <?= htmlspecialchars($medicament['forme']) ?>
                                    </td>


                                    <!-- DOSAGE -->
                                    <td class="text-center">
                                        <?= htmlspecialchars($medicament['dosage']) ?>
                                    </td>

                                    <td class="text-center">
                                        <?= htmlspecialchars($medicament['categorie_nom']) ?>
                                    </td>

                                    <td class="text-center">
                                <strong>
                                    <?= number_format($medicament['prix'], 0, ',', ' ') ?> FCFA
                                </strong>
                            </td>

                                    <!-- STOCK -->
                                    <td class="text-center">
                                        <strong>
                                            <?= htmlspecialchars($medicament['quantite_restante']) ?>
                                        </strong>
                                    </td>


                                    <!-- DATE PEREMPTION -->
                                    <td class="text-center">
                                        <?= htmlspecialchars($medicament['date_peremption']) ?>
                                    </td>


                                    <!-- ETAT -->
                                    <td class="text-center">

                                        <span class="medicine-status <?= $classe ?> ">

                                            <?= $etat ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->
                                    <td class="text-center">
                                        <?php if ($peutModifier): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-action-edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalModifier<?=$medicament['id_medicament']?>">
                                    
                                                <i class="fas fa-edit"></i>
                                            
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($peutSupprimer): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-action-delete"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalSupprimer<?=$medicament['id_medicament']?>">
                                            
                                                <i class="fas fa-trash"></i>

                                            </button>
                                        <?php endif; ?>
                                    </td>

                                </tr>

    <!-- =========================================
     MODAL MODIFIER
========================================= -->

<div
    class="modal fade"
    id="modalModifier<?= $medicament['id_medicament'] ?>"
    tabindex="-1"
    aria-hidden="true"
    >

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form method="POST" action="medicaments.php">

                <!-- HEADER -->

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fas fa-edit me-2 text-warning"></i>

                        Modifier le médicament

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <!-- BODY -->

                <div class="modal-body">

                    <div class="row g-3">


                        <!-- NOM -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Nom du médicament
                            </label>

                            <input
                                type="text"
                                name="nom"
                                class="form-control"
                                value="<?= htmlspecialchars($medicament['nom']) ?>"
                                required>

                        </div>


                        <!-- CODE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Code médicament
                            </label>

                            <input
                                type="text"
                                name="code"
                                class="form-control"
                                value="<?= htmlspecialchars($medicament['code']) ?>"
                                required>

                        </div>


                        <!-- FORME -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Forme
                            </label>

                            <select name="forme" class="form-select" required>

                                    <option selected>
                                        Sélectionner
                                    </option>

                                    <option>
                                        Comprimé
                                    </option>

                                    <option>
                                        Gélule
                                    </option>

                                    <option>
                                        Sirop
                                    </option>

                                    <option>
                                        Injection
                                    </option>

                                    <option>
                                        Crème
                                    </option>

                                </select>

                        </div>


                        <!-- DOSAGE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Dosage
                            </label>

                            <input
                                type="text"
                                name="dosage"
                                class="form-control"
                                value="<?= htmlspecialchars($medicament['dosage']) ?>"
                                required>

                        </div>


                        <!-- CATEGORIE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Catégorie
                            </label>

                            <select
                                name="nom_categorie"
                                class="form-select"
                                required>

                                <?php

                                $sql_categories = "
                                    SELECT id_categorie, nom_categorie
                                    FROM categorie
                                    ORDER BY nom_categorie ASC
                                ";

                                $resultat_categories =
                                    mysqli_query(
                                        $conn,
                                        $sql_categories
                                    );


                                if (!$resultat_categories) {

                                    die(
                                        "Erreur catégories : "
                                        . mysqli_error($conn)
                                    );
                                }


                                while (
                                    $categorie =
                                    mysqli_fetch_assoc(
                                        $resultat_categories
                                    )
                                ) {

                                    /*
                                     * Ta colonne medicament.nom_categorie
                                     * contient actuellement l'ID.
                                     */

                                    $selected =
                                        ($medicament['nom_categorie']
                                        == $categorie['id_categorie'])
                                        ? 'selected'
                                        : '';

                                ?>

                                    <option
                                        value="<?= $categorie['id_categorie'] ?>"
                                        <?= $selected ?>
                                    >

                                        <?= htmlspecialchars(
                                            $categorie['nom_categorie']
                                        ) ?>

                                    </option>

                                <?php
                                }
                                ?>

                            </select>

                        </div>


                        <!-- PRIX -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Prix unitaire
                            </label>

                            <div class="input-group">

                                <input
                                    type="number"
                                    name="prix"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?= htmlspecialchars($medicament['prix']) ?>"
                                    required>

                                <span class="input-group-text">
                                    FCFA
                                </span>

                            </div>

                        </div>


                        <!-- QUANTITE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Quantité restante
                            </label>

                            <input
                                type="number"
                                name="quantite_restante"
                                class="form-control"
                                min="0"
                                value="<?= htmlspecialchars($medicament['quantite_restante']) ?>"
                                required>

                        </div>


                        <!-- SEUIL -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Seuil minimum
                            </label>

                            <input
                                type="number"
                                name="seuil_minimum"
                                class="form-control"
                                min="0"
                                value="<?= htmlspecialchars($medicament['seuil_minimum']) ?>"
                                required>

                        </div>


                        <!-- DATE PEREMPTION -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Date de péremption
                            </label>

                            <input
                                type="date"
                                name="date_peremption"
                                class="form-control"
                               value="<?= !empty($medicament['date_peremption'])
                        ? date('Y-m-d', strtotime($medicament['date_peremption']))
                        : '' ?>">
                               

                        </div>

                    </div>

                </div>


                <!-- FOOTER -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Annuler

                    </button>


                    <!-- ID DU MEDICAMENT -->

                    <input
                        type="hidden"
                        name="id_medicament"
                        value="<?= $medicament['id_medicament'] ?>">


                    <!-- BOUTON MODIFIER -->

                    <button
                        type="submit"
                        name="modifier"
                        value="1"
                        class="btn btn-action-edit">

                        <i class="fas fa-save me-2"></i>

                        Enregistrer les modifications

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
    <!-- =========================================
     MODAL SUPPRESSION
========================================= -->

<div
    class="modal fade"
    id="modalSupprimer<?= $medicament['id_medicament'] ?>"
    tabindex="-1"
    aria-hidden="true">


    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form method="POST" action="medicaments.php">

                <div class="modal-header">

                    <h5 class="modal-title text-danger">

                        <i class="fas fa-triangle-exclamation me-2"></i>

                        Confirmation de suppression

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body text-center">

                    <i
                        class="fas fa-trash-can text-danger"
                        style="font-size: 50px;">
                    </i>

                    <p class="mt-3 mb-1">

                        Voulez-vous vraiment supprimer :

                    </p>

                    <strong>

                        <?= htmlspecialchars($medicament['nom']) ?>

                    </strong>

                    <p class="text-muted mt-2">

                        Cette action est irréversible.

                    </p>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Annuler

                    </button>


                    <input
                        type="hidden"
                        name="id_medicament"
                        value="<?= $medicament['id_medicament'] ?>">


                    <button
                        type="submit"
                        name="supprimer"
                        value="1"
                        class="tn btn-sm btn-action-delete">

                        <i class="fas fa-trash me-2"></i>

                        Supprimer

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php

    $numero++;

}

?>

</tbody>

                    </table>

                </div>


                <!-- Pagination -->

              

            </div>

        </section>

    </main>

</div>


<!-- =========================================
     MODAL AJOUTER
========================================= -->

<div
    class="modal fade"
    id="modalAjouter"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form method="POST">
                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fas fa-pills me-2 text-success"></i>

                        Ajouter un médicament

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                   


                        <div class="row g-3">


                            <!-- Nom -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Nom du médicament
                                </label>

                                <input
                                    type="text"
                                    name="nom"
                                    class="form-control"
                                    placeholder="Ex : Paracétamol"
                                >

                            </div>


                            
                            <!-- Code -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Code médicament
                                </label>

                                <input
                                    type="text"
                                    name="code"
                                    class="form-control"
                                    placeholder="Ex : MED-00001"
                                >

                            </div>


                            <!-- Forme -->

                            <div class="col-md-6">

                                <label class="form-label"> Forme  </label>
                               

                                <select name="forme" class="form-select" required>

                                   <option value="">Sélectionner</option>
                                   <option value="Comprimé">Comprimé</option>
                                   <option value="Gélule">Gélule</option>
                                   <option value="Sirop">Sirop</option>
                                   <option value="Injection">Injection</option>
                                   <option value="Crème">Crème</option>

                                </select>

                            </div>


                            <!-- Dosage -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Dosage
                                </label>

                                <input
                                    type="text"
                                    name="dosage"
                                    class="form-control"
                                    placeholder="Ex : 500 mg"
                                >

                            </div>

                            <!-- Catégorie -->
                            <div class="col-md-6">

                                <label class="form-label">
                                    Catégorie
                                </label>

                                <select name="nom_categorie"
                                        class="form-select"
                                        required>

                                    <option value="">
                                        -- Sélectionner une catégorie --
                                    </option>

                                    <?php
                                    $sql_categories = "SELECT id_categorie, nom_categorie
                                                    FROM categorie
                                                    ORDER BY nom_categorie ASC";

                                    $resultat_categories = mysqli_query(
                                        $conn,
                                        $sql_categories
                                    );

                                    if (!$resultat_categories) {
                                        die("Erreur catégories : " . mysqli_error($conn));
                                    }

                                    while ($categorie = mysqli_fetch_assoc($resultat_categories)) {
                                    ?>

                                        <option value="<?= $categorie['nom_categorie'] ?>">
                                            <?= htmlspecialchars($categorie['nom_categorie']) ?>
                                        </option>

                                    <?php
                                    }
                                    ?>

                                </select>

                            </div>

                                                <!-- Prix -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Prix unitaire
                                    </label>

                                    <div class="input-group">

                                        <input type="number"
                                            name="prix"
                                            class="form-control"
                                            min="0"
                                            required>

                                        <span class="input-group-text">
                                            FCFA
                                        </span>

                                    </div>

                                </div>

                            <!-- Stock -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Quantité initiale
                                </label>

                                <input
                                    type="number"
                                    name="quantite_restante"
                                    class="form-control"
                                    min="0"
                                    placeholder="Ex : 100"
                                    required
                                >

                            </div>


                            <!-- Seuil -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Seuil minimum
                                </label>

                                <input
                                    type="number"
                                    name="seuil_minimum"
                                    class="form-control"
                                    min="0"
                                    placeholder="Ex : 10"
                                    required
                                >

                            </div>


                            <!-- Péremption -->

                            <div class="col-md-6">

                                <label class="form-label">
                                    Date de péremption
                                </label>

                                <input
                                    type="date"
                                    name="date_peremption"
                                    class="form-control"
                                    required
                                >

                            </div>


                        </div>

                   

                        </div>


                        <div class="modal-footer">

                            <button
                                type="button"
                                name="annuler"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            
                                Annuler
                            </button>

                            <button
                                type="submit"
                                name="ajouter"
                                value="1"
                                class="btn btn-pharma" >

                                <i class="fas fa-save me-2"></i>

                                Enregistrer

                            </button>
                        
                        </div>
                  
            </form>

        </div>

    </div>

</div>


<!-- Bootstrap -->
<script src="../vendor/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/js/fontAwesome.min.js"></script>
<script src="jquery-ui-1.14.2.custom/external/jquery/jquery.js"></script>

    <script src="jquery-ui-1.14.2.custom/jquery-ui.js"></script>
    <script src="libre/DataTables/datatables.min.js"></script>

<!-- Font Awesome -->



<!-- Recherche frontend -->
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
                },
                   // La colonne N° et Actions ne sont pas triables
                    columnDefs: [

                        {
                            orderable: false,
                            searchable: false,
                            targets: [0, 10]
                        }

                    ]
            });
                    // ==========================================
            // RECHERCHE NOM + CODE + CATEGORIE
            // ==========================================

            $('#searchMedicine').on('keyup', function () {

                const recherche = this.value;

                table
                    .columns([1, 2, 5])
                    .search(recherche)
                    .draw();

            });

        });
    </script>

</body>

</html>