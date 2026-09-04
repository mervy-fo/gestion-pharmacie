<?php

session_start();

require_once 'connex.php';
require_once 'protection.php';

protegerPage("utilisateurs");


/* =====================================================
   VÉRIFICATION ADMINISTRATEUR
===================================================== */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrateur') {

    header("Location: acces_refuse.php");
    exit;
}


/* =====================================================
   ID DE L'ADMINISTRATEUR CONNECTÉ
===================================================== */

$id_admin = (int) $_SESSION['id_util'];


/* =====================================================
   MODIFIER UN UTILISATEUR
===================================================== */

if (isset($_POST['modifier_utilisateur'])) {

    $id_util = (int) ($_POST['id_util'] ?? 0);

    $nom = trim($_POST['nom'] ?? '');
    $nom_util = trim($_POST['nom_util'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $statut = trim($_POST['statut'] ?? '');
    $mot_passe = $_POST['mot_passe'] ?? '';


    /* -------------------------------------------------
       Vérification des champs
    ------------------------------------------------- */

    if (
        $id_util <= 0 ||
        empty($nom) ||
        empty($nom_util) ||
        empty($email) ||
        empty($role) ||
        empty($statut)
    ) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Veuillez remplir tous les champs obligatoires.")
        );

        exit;
    }


    /* -------------------------------------------------
       Vérification email
    ------------------------------------------------- */

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("L'adresse email est invalide.")
        );

        exit;
    }


    /* -------------------------------------------------
       Vérification rôle
    ------------------------------------------------- */

    $roles_autorises = [
        "Administrateur",
        "Pharmacien",
        "Gestionnaire",
        "Magasinier",
        "Assistant"
    ];

    if (!in_array($role, $roles_autorises, true)) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Le rôle sélectionné est invalide.")
        );

        exit;
    }


    /* -------------------------------------------------
       Vérification statut
    ------------------------------------------------- */

    $statuts_autorises = [
        "Actif",
        "Inactif"
    ];

    if (!in_array($statut, $statuts_autorises, true)) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Le statut sélectionné est invalide.")
        );

        exit;
    }


    /* -------------------------------------------------
       Empêcher l'admin de désactiver son propre compte
    ------------------------------------------------- */

    if (
        $id_util === $id_admin &&
        $statut !== "Actif"
    ) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Vous ne pouvez pas désactiver votre propre compte.")
        );

        exit;
    }


    /* -------------------------------------------------
       Vérifier si le nom_util existe déjà
    ------------------------------------------------- */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_util
         FROM utilisateur
         WHERE nom_util = ?
         AND id_util != ?"
    );

    if (!$stmt) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $nom_util,
        $id_util
    );

    mysqli_stmt_execute($stmt);

    $result_check = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result_check) > 0) {

        mysqli_stmt_close($stmt);

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Ce nom d'utilisateur est déjà utilisé.")
        );

        exit;
    }

    mysqli_stmt_close($stmt);


    /* -------------------------------------------------
       Vérifier si l'email existe déjà
    ------------------------------------------------- */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_util
         FROM utilisateur
         WHERE email = ?
         AND id_util != ?"
    );

    if (!$stmt) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $email,
        $id_util
    );

    mysqli_stmt_execute($stmt);

    $result_check = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result_check) > 0) {

        mysqli_stmt_close($stmt);

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Cette adresse email est déjà utilisée.")
        );

        exit;
    }

    mysqli_stmt_close($stmt);


    /* =================================================
       AVEC OU SANS MODIFICATION DU MOT DE PASSE
    ================================================= */

    if (!empty($mot_passe)) {

        if (strlen($mot_passe) < 6) {

            header(
                "Location: utilisateurs.php?erreur="
                . urlencode("Le mot de passe doit contenir au moins 6 caractères.")
            );

            exit;
        }

        $hash = password_hash(
            $mot_passe,
            PASSWORD_DEFAULT
        );


        $sql = "
            UPDATE utilisateur
            SET
                nom = ?,
                nom_util = ?,
                email = ?,
                role = ?,
                statut = ?,
                mot_passe = ?
            WHERE id_util = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("Erreur : " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssi",
            $nom,
            $nom_util,
            $email,
            $role,
            $statut,
            $hash,
            $id_util
        );

    } else {

        $sql = "
            UPDATE utilisateur
            SET
                nom = ?,
                nom_util = ?,
                email = ?,
                role = ?,
                statut = ?
            WHERE id_util = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("Erreur : " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssssi",
            $nom,
            $nom_util,
            $email,
            $role,
            $statut,
            $id_util
        );
    }


    /* -------------------------------------------------
       Exécuter modification
    ------------------------------------------------- */

    if (!mysqli_stmt_execute($stmt)) {

        $erreur = mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        die("Erreur lors de la modification : " . $erreur);
    }

    mysqli_stmt_close($stmt);


    /* -------------------------------------------------
       Mettre à jour la session si c'est l'admin lui-même
    ------------------------------------------------- */

    if ($id_util === $id_admin) {

        $_SESSION['nom'] = $nom;
        $_SESSION['nom_util'] = $nom_util;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['statut'] = $statut;
    }


    header(
        "Location: utilisateurs.php?success=modification"
    );

    exit;
}


/* =====================================================
   SUPPRIMER UN UTILISATEUR
===================================================== */

if (isset($_POST['supprimer'])) {

    $id_util = (int) ($_POST['id_util'] ?? 0);


    /* -------------------------------------------------
       Vérification ID
    ------------------------------------------------- */

    if ($id_util <= 0) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Utilisateur invalide.")
        );

        exit;
    }


    /* -------------------------------------------------
       Empêcher la suppression de son propre compte
    ------------------------------------------------- */

    if ($id_util === $id_admin) {

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Vous ne pouvez pas supprimer votre propre compte.")
        );

        exit;
    }


    /* -------------------------------------------------
       Vérifier que l'utilisateur existe
    ------------------------------------------------- */

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_util
         FROM utilisateur
         WHERE id_util = ?"
    );

    if (!$stmt) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_util
    );

    mysqli_stmt_execute($stmt);

    $result_check = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result_check) === 0) {

        mysqli_stmt_close($stmt);

        header(
            "Location: utilisateurs.php?erreur="
            . urlencode("Cet utilisateur n'existe pas.")
        );

        exit;
    }

    mysqli_stmt_close($stmt);


    /* -------------------------------------------------
       Suppression
    ------------------------------------------------- */

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM utilisateur
         WHERE id_util = ?"
    );

    if (!$stmt) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_util
    );

    if (!mysqli_stmt_execute($stmt)) {

        $erreur = mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        die("Erreur lors de la suppression : " . $erreur);
    }

    mysqli_stmt_close($stmt);


    header(
        "Location: utilisateurs.php?success=suppression"
    );

    exit;
}


/* =====================================================
   RÉCUPÉRER LES UTILISATEURS
===================================================== */

$sql = "
    SELECT
        id_util,
        nom,
        nom_util,
        email,
        role,
        statut
    FROM utilisateur
    ORDER BY id_util DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Erreur lors de la récupération des utilisateurs : " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Utilisateurs - PharmaStock</title>

    <link
        rel="stylesheet"
        href="../vendor/css/bootstrap.min.css">

    <link
        rel="stylesheet"
        href="../vendor/fontawesome/css/all.css">

    <link
        rel="stylesheet"
        href="./style.css">

</head>

<body>

<div class="dashboard-wrapper">

    <?php include 'sidebar.php'; ?>

    <main class="main-content">

        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">

            <div>

                <h5 class="mb-1">
                    Utilisateurs
                </h5>

                <small>
                    Gestion des comptes utilisateurs
                </small>

            </div>

        </header>


        <!-- =================================================
             CONTENU
        ================================================== -->

        <section class="content">


            <!-- Messages -->

            <?php if (isset($_GET['success'])): ?>

                <?php if ($_GET['success'] === 'modification'): ?>

                    <div class="alert alert-success alert-dismissible fade show">

                        <i class="fas fa-circle-check me-2"></i>

                        L'utilisateur a été modifié avec succès.

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                        </button>

                    </div>

                <?php elseif ($_GET['success'] === 'suppression'): ?>

                    <div class="alert alert-success alert-dismissible fade show">

                        <i class="fas fa-circle-check me-2"></i>

                        L'utilisateur a été supprimé avec succès.

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                        </button>

                    </div>

                <?php endif; ?>

            <?php endif; ?>


            <?php if (isset($_GET['erreur'])): ?>

                <div class="alert alert-danger alert-dismissible fade show">

                    <i class="fas fa-triangle-exclamation me-2"></i>

                    <?= htmlspecialchars($_GET['erreur']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="welcome-card">

                <div>

                    <h3>
                        Gestion des utilisateurs
                    </h3>

                    <p>
                        Créer, modifier et consulter les comptes de connexion.
                    </p>

                </div>

                <i class="fas fa-user-cog"></i>

            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <div class="dashboard-card mt-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <div>

                        <h5 class="mb-1">
                            Liste des utilisateurs
                        </h5>

                        <small>
                            Comptes enregistrés dans le système
                        </small>

                    </div>


                    <button
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#addUserModal">

                        <i class="fas fa-plus me-2"></i>

                        Ajouter un utilisateur

                    </button>

                </div>


                <div class="table-responsive mt-4">

                    <table class="table align-middle">

                        <thead>

                            <tr>

                                <th>Nom</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($row['nom']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['nom_util']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['email']) ?>
                                </td>

                                <td>

                                    <span class="badge bg-primary">

                                        <?= htmlspecialchars($row['role']) ?>

                                    </span>

                                </td>


                                <td>

                                    <?php if ($row['statut'] === 'Actif'): ?>

                                        <span class="badge bg-success">

                                            <i class="fas fa-circle-check me-1"></i>

                                            Actif

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">

                                            <i class="fas fa-circle-xmark me-1"></i>

                                            Inactif

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <!-- Modifier -->

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-action-edit me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalModifier<?= $row['id_util'] ?>">

                                        <i class="fas fa-pen"></i>

                                    </button>


                                    <!-- Supprimer -->

                                    <?php if ($row['id_util'] != $id_admin): ?>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-action-delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalSupprimer<?= $row['id_util'] ?>">

                                            <i class="fas fa-trash"></i>

                                        </button>

                                    <?php endif; ?>

                                </td>

                            </tr>


                            <!-- =================================================
                                 MODAL MODIFICATION
                            ================================================== -->

                            <div
                                class="modal fade"
                                id="modalModifier<?= $row['id_util'] ?>"
                                tabindex="-1"
                                aria-hidden="true">

                                <div class="modal-dialog modal-lg modal-dialog-centered">

                                    <div class="modal-content">

                                        <form
                                            method="POST"
                                            action="utilisateurs.php">

                                            <div class="modal-header">

                                                <h5 class="modal-title">

                                                    <i class="fas fa-user-pen me-2"></i>

                                                    Modifier l'utilisateur

                                                </h5>

                                                <button
                                                    type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal">
                                                </button>

                                            </div>


                                            <div class="modal-body">

                                                <input
                                                    type="hidden"
                                                    name="id_util"
                                                    value="<?= $row['id_util'] ?>">


                                                <div class="row g-3">


                                                    <!-- Nom -->

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Nom
                                                        </label>

                                                        <input
                                                            type="text"
                                                            name="nom"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($row['nom']) ?>"
                                                            required>

                                                    </div>


                                                    <!-- Nom utilisateur -->

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Nom d'utilisateur
                                                        </label>

                                                        <input
                                                            type="text"
                                                            name="nom_util"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($row['nom_util']) ?>"
                                                            required>

                                                    </div>


                                                    <!-- Email -->

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Email
                                                        </label>

                                                        <input
                                                            type="email"
                                                            name="email"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($row['email']) ?>"
                                                            required>

                                                    </div>


                                                    <!-- Mot de passe -->

                                                    <div class="col-md-6">

                                                        <label class="form-label">

                                                            Nouveau mot de passe

                                                        </label>

                                                        <input
                                                            type="password"
                                                            name="mot_passe"
                                                            class="form-control"
                                                            placeholder="Laisser vide pour conserver l'ancien">

                                                    </div>


                                                    <!-- Rôle -->

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Rôle
                                                        </label>

                                                        <select
                                                            name="role"
                                                            class="form-select"
                                                            required>

                                                            <option
                                                                value="Administrateur"
                                                                <?= $row['role'] === 'Administrateur' ? 'selected' : '' ?>>

                                                                Administrateur

                                                            </option>

                                                            <option
                                                                value="Pharmacien"
                                                                <?= $row['role'] === 'Pharmacien' ? 'selected' : '' ?>>

                                                                Pharmacien

                                                            </option>

                                                            <option
                                                                value="Gestionnaire"
                                                                <?= $row['role'] === 'Gestionnaire' ? 'selected' : '' ?>>

                                                                Gestionnaire

                                                            </option>

                                                            <option
                                                                value="Magasinier"
                                                                <?= $row['role'] === 'Magasinier' ? 'selected' : '' ?>>

                                                                Magasinier

                                                            </option>

                                                            <option
                                                                value="Assistant"
                                                                <?= $row['role'] === 'Assistant' ? 'selected' : '' ?>>

                                                                Assistant

                                                            </option>

                                                        </select>

                                                    </div>


                                                    <!-- Statut -->

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Statut
                                                        </label>

                                                        <select
                                                            name="statut"
                                                            class="form-select"
                                                            required>

                                                            <option
                                                                value="Actif"
                                                                <?= $row['statut'] === 'Actif' ? 'selected' : '' ?>>

                                                                Actif

                                                            </option>

                                                            <option
                                                                value="Inactif"
                                                                <?= $row['statut'] === 'Inactif' ? 'selected' : '' ?>>

                                                                Inactif

                                                            </option>

                                                        </select>

                                                    </div>

                                                </div>

                                            </div>


                                            <div class="modal-footer">

                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal">

                                                    Annuler

                                                </button>


                                                <button
                                                    type="submit"
                                                    name="modifier_utilisateur"
                                                    value="1"
                                                    class="btn btn-primary">

                                                    <i class="fas fa-save me-2"></i>

                                                    Enregistrer

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 MODAL SUPPRESSION
                            ================================================== -->

                            <?php if ($row['id_util'] != $id_admin): ?>

                                <div
                                    class="modal fade"
                                    id="modalSupprimer<?= $row['id_util'] ?>"
                                    tabindex="-1"
                                    aria-hidden="true">

                                    <div class="modal-dialog modal-dialog-centered">

                                        <div class="modal-content">

                                            <form
                                                method="POST"
                                                action="utilisateurs.php">

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
                                                        style="font-size:50px;">
                                                    </i>


                                                    <p class="mt-3 mb-1">

                                                        Voulez-vous vraiment supprimer :

                                                    </p>


                                                    <strong>

                                                        <?= htmlspecialchars($row['nom_util']) ?>

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
                                                        name="id_util"
                                                        value="<?= $row['id_util'] ?>">


                                                    <button
                                                        type="submit"
                                                        name="supprimer"
                                                        value="1"
                                                        class="btn btn-danger">

                                                        <i class="fas fa-trash me-2"></i>

                                                        Supprimer

                                                    </button>

                                                </div>

                                            </form>

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>


                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>


<!-- =================================================
     MODAL AJOUT
================================================== -->

<div
    class="modal fade"
    id="addUserModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    Ajouter un utilisateur

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>


            <div class="modal-body">

                <form
                    action="ajouter_utilisateur.php"
                    method="POST">

                    <div class="row g-3">


                        <div class="col-md-6">

                            <label class="form-label">
                                Nom
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="nom"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Nom d'utilisateur
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="nom_util"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                name="email"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Mot de passe
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                name="mot_passe"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Rôle
                            </label>

                            <select
                                class="form-select"
                                name="role"
                                required>

                                <option value="">
                                    Choisir un rôle
                                </option>

                                <option value="Administrateur">
                                    Administrateur
                                </option>

                                <option value="Pharmacien">
                                    Pharmacien
                                </option>

                                <option value="Gestionnaire">
                                    Gestionnaire
                                </option>

                                <option value="Magasinier">
                                    Magasinier
                                </option>

                                <option value="Assistant">
                                    Assistant
                                </option>

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Statut
                            </label>

                            <select
                                class="form-select"
                                name="statut"
                                required>

                                <option value="Actif">
                                    Actif
                                </option>

                                <option value="Inactif">
                                    Inactif
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="mt-4 d-flex justify-content-end gap-2">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                            Annuler

                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="fas fa-save me-2"></i>

                            Enregistrer

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<script src="../vendor/js/bootstrap.bundle.min.js"></script>

<script src="../vendor/js/fontAwesome.min.js"></script>

</body>
</html>