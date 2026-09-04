<?php

require_once "protection.php";
require_once "connex.php";

/* =====================================================
   PROTECTION DE LA PAGE
===================================================== */

protegerPage("profil");

/* =====================================================
   UTILISATEUR CONNECTÉ
===================================================== */

$id_util = (int) $_SESSION['id_util'];
/* =====================================================
   RÉCUPÉRER LES INFORMATIONS
===================================================== */

$sql = "
    SELECT
        id_util,
        nom,
        nom_util,
        email,
        statut,
        mot_passe,
        role
    FROM utilisateur
    WHERE id_util = ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Erreur : " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_util);
mysqli_stmt_execute($stmt);

$resultat = mysqli_stmt_get_result($stmt);

$utilisateur = mysqli_fetch_assoc($resultat);

mysqli_stmt_close($stmt);


if (!$utilisateur) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =====================================================
   VARIABLES
===================================================== */

$nom = $utilisateur['nom'];
$nom_util = $utilisateur['nom_util'];
$email = $utilisateur['email'];
$statut = $utilisateur['statut'];
$role = $utilisateur['role'];
$mot_passe_actuel = $utilisateur['mot_passe'];


/* =====================================================
   MODIFIER LES INFORMATIONS PERSONNELLES
===================================================== */

if (isset($_POST['modifier_profil'])) {

    $nouveau_nom = trim($_POST['nom'] ?? '');
    $nouveau_nom_util = trim($_POST['nom_util'] ?? '');
    $nouvel_email = trim($_POST['email'] ?? '');


    /* Vérification */

    if (
        empty($nouveau_nom) ||
        empty($nouveau_nom_util) ||
        empty($nouvel_email)
    ) {

        header(
            "Location: profil.php?erreur="
            . urlencode("Veuillez remplir tous les champs.")
        );

        exit;
    }


    /* Vérification email */

    if (!filter_var($nouvel_email, FILTER_VALIDATE_EMAIL)) {

        header(
            "Location: profil.php?erreur="
            . urlencode("L'adresse email est invalide.")
        );

        exit;
    }


    /* =================================================
       VÉRIFIER LE NOM UTILISATEUR
    ================================================= */

    $verification = mysqli_prepare(
        $conn,
        "SELECT id_util
         FROM utilisateur
         WHERE nom_util = ?
         AND id_util != ?"
    );

    if (!$verification) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $verification,
        "si",
        $nouveau_nom_util,
        $id_util
    );

    mysqli_stmt_execute($verification);

    $resultat_verification =
        mysqli_stmt_get_result($verification);

    if (mysqli_num_rows($resultat_verification) > 0) {

        mysqli_stmt_close($verification);

        header(
            "Location: profil.php?erreur="
            . urlencode("Ce nom d'utilisateur est déjà utilisé.")
        );

        exit;
    }

    mysqli_stmt_close($verification);


    /* =================================================
       VÉRIFIER L'EMAIL
    ================================================= */

    $verification = mysqli_prepare(
        $conn,
        "SELECT id_util
         FROM utilisateur
         WHERE email = ?
         AND id_util != ?"
    );

    if (!$verification) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $verification,
        "si",
        $nouvel_email,
        $id_util
    );

    mysqli_stmt_execute($verification);

    $resultat_verification =
        mysqli_stmt_get_result($verification);

    if (mysqli_num_rows($resultat_verification) > 0) {

        mysqli_stmt_close($verification);

        header(
            "Location: profil.php?erreur="
            . urlencode("Cette adresse email est déjà utilisée.")
        );

        exit;
    }

    mysqli_stmt_close($verification);


    /* =================================================
       MODIFICATION
    ================================================= */

    $sql_update = "
        UPDATE utilisateur
        SET
            nom = ?,
            nom_util = ?,
            email = ?
        WHERE id_util = ?
    ";

    $stmt = mysqli_prepare($conn, $sql_update);

    if (!$stmt) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssi",
        $nouveau_nom,
        $nouveau_nom_util,
        $nouvel_email,
        $id_util
    );

    if (!mysqli_stmt_execute($stmt)) {

        die(
            "Erreur lors de la modification : "
            . mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    /* =================================================
       METTRE À JOUR LA SESSION
    ================================================= */

    $_SESSION['nom'] = $nouveau_nom;
    $_SESSION['nom_util'] = $nouveau_nom_util;
    $_SESSION['email'] = $nouvel_email;


    header(
        "Location: profil.php?success=profil"
    );

    exit;
}


/* =====================================================
   MODIFIER LE MOT DE PASSE
===================================================== */

if (isset($_POST['modifier_mot_passe'])) {

    $ancien_mot_passe =
        $_POST['ancien_mot_passe'] ?? '';

    $nouveau_mot_passe =
        $_POST['nouveau_mot_passe'] ?? '';

    $confirmation_mot_passe =
        $_POST['confirmation_mot_passe'] ?? '';


    /* Vérifier les champs */

    if (
        empty($ancien_mot_passe) ||
        empty($nouveau_mot_passe) ||
        empty($confirmation_mot_passe)
    ) {

        header(
            "Location: profil.php?erreur="
            . urlencode("Veuillez remplir tous les champs du mot de passe.")
        );

        exit;
    }


    /* Vérifier ancien mot de passe */

    if (
        !password_verify(
            $ancien_mot_passe,
            $mot_passe_actuel
        )
    ) {

        header(
            "Location: profil.php?erreur="
            . urlencode("L'ancien mot de passe est incorrect.")
        );

        exit;
    }


    /* Vérifier longueur */

    if (strlen($nouveau_mot_passe) < 6) {

        header(
            "Location: profil.php?erreur="
            . urlencode("Le nouveau mot de passe doit contenir au moins 6 caractères.")
        );

        exit;
    }


    /* Vérifier confirmation */

    if (
        $nouveau_mot_passe !==
        $confirmation_mot_passe
    ) {

        header(
            "Location: profil.php?erreur="
            . urlencode("Les mots de passe ne correspondent pas.")
        );

        exit;
    }


    /* Hash */

    $nouveau_hash = password_hash(
        $nouveau_mot_passe,
        PASSWORD_DEFAULT
    );


    /* UPDATE */

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE utilisateur
         SET mot_passe = ?
         WHERE id_util = ?"
    );

    if (!$stmt) {
        die("Erreur : " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $nouveau_hash,
        $id_util
    );

    if (!mysqli_stmt_execute($stmt)) {

        die(
            "Erreur lors de la modification du mot de passe : "
            . mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    header(
        "Location: profil.php?success=password"
    );

    exit;
}


/* =====================================================
   NOMBRE D'ALERTES
===================================================== */

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

$resultNotifications = mysqli_query(
    $conn,
    $sqlNotifications
);

$nombreNotifications = 0;

if ($resultNotifications) {

    $nombreNotifications =
        mysqli_fetch_assoc(
            $resultNotifications
        )['total'];
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Profil - PharmaStock</title>

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


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <?php include 'sidebar.php'; ?>


    <!-- =================================================
         CONTENU PRINCIPAL
    ================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">


            <div>

                <h5 class="mb-1">
                    Profil
                </h5>

                <small>
                    Gérez vos informations personnelles
                </small>

            </div>


            <div class="topbar-right">


                <!-- Notifications -->

                <a
                    href="alertes.php"
                    class="text-decoration-none">

                    <button class="icon-button">

                        <i class="fas fa-bell"></i>

                        <?php if ($nombreNotifications > 0): ?>

                            <span>
                                <?= $nombreNotifications ?>
                            </span>

                        <?php endif; ?>

                    </button>

                </a>


                <!-- Utilisateur -->

                <div class="user-profile">


                    <div class="user-avatar">

                        <i class="fas fa-user"></i>

                    </div>


                    <div>

                        <strong>
                            <?= htmlspecialchars($nom_util) ?>
                        </strong>

                        <small>
                            <?= htmlspecialchars($role) ?>
                        </small>

                    </div>

                </div>


            </div>


        </header>


        <!-- =================================================
             CONTENU
        ================================================== -->

        <section class="content">


            <!-- TITRE -->

            <div class="page-header">

                <div>

                    <h3>
                        Mon profil
                    </h3>

                    <p>
                        Consultez et modifiez vos informations personnelles.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 MESSAGES
            ================================================== -->

            <?php if (isset($_GET['success'])): ?>


                <?php if ($_GET['success'] === 'profil'): ?>

                    <div class="alert alert-success alert-dismissible fade show">

                        <i class="fas fa-circle-check me-2"></i>

                        Vos informations ont été modifiées avec succès.

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                        </button>

                    </div>

                <?php endif; ?>


                <?php if ($_GET['success'] === 'password'): ?>

                    <div class="alert alert-success alert-dismissible fade show">

                        <i class="fas fa-circle-check me-2"></i>

                        Votre mot de passe a été modifié avec succès.

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
                 INFORMATIONS PROFIL
            ================================================== -->

            <div class="row g-4">


                <!-- =================================================
                     CARTE PROFIL
                ================================================== -->

                <div class="col-lg-4">


                    <div class="dashboard-card text-center h-100">


                        <!-- Avatar -->

                        <div
                            class="profile-avatar mx-auto mb-3">

                            <i class="fas fa-user"></i>

                        </div>


                        <!-- Nom -->

                        <h4 class="mb-1">

                            <?= htmlspecialchars($nom) ?>

                        </h4>


                        <!-- Nom utilisateur -->

                        <p class="text-muted mb-3">

                            @<?= htmlspecialchars($nom_util) ?>

                        </p>


                        <!-- Rôle -->

                        <div class="mb-3">

                            <span class="badge bg-primary px-3 py-2">

                                <i class="fas fa-user-shield me-1"></i>

                                <?= htmlspecialchars($role) ?>

                            </span>

                        </div>


                        <!-- Statut -->

                        <?php if (strtolower($statut) === 'actif'): ?>

                            <span class="badge bg-success px-3 py-2">

                                <i class="fas fa-circle-check me-1"></i>

                                Actif

                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger px-3 py-2">

                                <i class="fas fa-circle-xmark me-1"></i>

                                <?= htmlspecialchars($statut) ?>

                            </span>

                        <?php endif; ?>


                        <hr class="my-4">


                        <!-- Informations -->

                        <div class="text-start">


                            <div class="d-flex align-items-center mb-3">

                                <div class="profile-info-icon">

                                    <i class="fas fa-envelope"></i>

                                </div>

                                <div>

                                    <small class="text-muted d-block">
                                        Email
                                    </small>

                                    <strong>
                                        <?= htmlspecialchars($email) ?>
                                    </strong>

                                </div>

                            </div>


                            <div class="d-flex align-items-center">

                                <div class="profile-info-icon">

                                    <i class="fas fa-id-card"></i>

                                </div>

                                <div>

                                    <small class="text-muted d-block">
                                        Identifiant
                                    </small>

                                    <strong>
                                        <?= htmlspecialchars($id_util) ?>
                                    </strong>

                                </div>

                            </div>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                     INFORMATIONS PERSONNELLES
                ================================================== -->

                <div class="col-lg-8">


                    <div class="dashboard-card">


                        <div class="table-toolbar mb-4">


                            <div>

                                <h5>

                                    <i class="fas fa-user-pen me-2"></i>

                                    Informations personnelles

                                </h5>

                                <small>

                                    Modifiez vos informations de compte.

                                </small>

                            </div>


                        </div>


                        <form method="POST">


                            <div class="row g-3">


                                <!-- Nom -->

                                <div class="col-md-6">

                                    <label class="form-label">

                                        Nom

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-user"></i>

                                        </span>

                                        <input
                                            type="text"
                                            name="nom"
                                            class="form-control"
                                            value="<?= htmlspecialchars($nom) ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- Nom utilisateur -->

                                <div class="col-md-6">

                                    <label class="form-label">

                                        Nom d'utilisateur

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-at"></i>

                                        </span>

                                        <input
                                            type="text"
                                            name="nom_util"
                                            class="form-control"
                                            value="<?= htmlspecialchars($nom_util) ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- Email -->

                                <div class="col-12">

                                    <label class="form-label">

                                        Adresse email

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-envelope"></i>

                                        </span>

                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control"
                                            value="<?= htmlspecialchars($email) ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- Rôle -->

                                <div class="col-md-6">

                                    <label class="form-label">

                                        Rôle

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-user-shield"></i>

                                        </span>

                                        <input
                                            type="text"
                                            class="form-control"
                                            value="<?= htmlspecialchars($role) ?>"
                                            disabled>

                                    </div>

                                    <small class="text-muted">

                                        Le rôle ne peut pas être modifié ici.

                                    </small>

                                </div>


                                <!-- Statut -->

                                <div class="col-md-6">

                                    <label class="form-label">

                                        Statut

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-circle-check"></i>

                                        </span>

                                        <input
                                            type="text"
                                            class="form-control"
                                            value="<?= htmlspecialchars($statut) ?>"
                                            disabled>

                                    </div>

                                    <small class="text-muted">

                                        Le statut est géré par l'administration.

                                    </small>

                                </div>


                            </div>


                            <!-- Bouton -->

                            <div class="text-end mt-4">

                                <button
                                    type="submit"
                                    name="modifier_profil"
                                    value="1"
                                    class="btn btn-pharma">

                                    <i class="fas fa-save me-2"></i>

                                    Enregistrer les modifications

                                </button>

                            </div>


                        </form>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 MOT DE PASSE
            ================================================== -->

            <div class="row mt-4">


                <div class="col-12">


                    <div class="dashboard-card">


                        <div class="table-toolbar mb-4">


                            <div>

                                <h5>

                                    <i class="fas fa-lock me-2"></i>

                                    Sécurité du compte

                                </h5>

                                <small>

                                    Modifiez votre mot de passe pour sécuriser votre compte.

                                </small>

                            </div>


                        </div>


                        <form method="POST">


                            <div class="row g-3">


                                <!-- Ancien mot de passe -->

                                <div class="col-md-4">

                                    <label class="form-label">

                                        Ancien mot de passe

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-lock"></i>

                                        </span>

                                        <input
                                            type="password"
                                            name="ancien_mot_passe"
                                            id="ancienMotPasse"
                                            class="form-control"
                                            required>

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('ancienMotPasse', this)">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </div>

                                </div>


                                <!-- Nouveau mot de passe -->

                                <div class="col-md-4">

                                    <label class="form-label">

                                        Nouveau mot de passe

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-key"></i>

                                        </span>

                                        <input
                                            type="password"
                                            name="nouveau_mot_passe"
                                            id="nouveauMotPasse"
                                            class="form-control"
                                            minlength="6"
                                            required>

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('nouveauMotPasse', this)">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </div>

                                    <small class="text-muted">

                                        Minimum 6 caractères.

                                    </small>

                                </div>


                                <!-- Confirmation -->

                                <div class="col-md-4">

                                    <label class="form-label">

                                        Confirmer le mot de passe

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="fas fa-key"></i>

                                        </span>

                                        <input
                                            type="password"
                                            name="confirmation_mot_passe"
                                            id="confirmationMotPasse"
                                            class="form-control"
                                            minlength="6"
                                            required>

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('confirmationMotPasse', this)">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </div>

                                </div>


                            </div>


                            <!-- Bouton -->

                            <div class="text-end mt-4">

                                <button
                                    type="submit"
                                    name="modifier_mot_passe"
                                    value="1"
                                    class="btn btn-pharma">

                                    <i class="fas fa-shield-halved me-2"></i>

                                    Modifier le mot de passe

                                </button>

                            </div>


                        </form>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>


<!-- =================================================
     JAVASCRIPT
================================================== -->

<script src="../vendor/js/bootstrap.bundle.min.js"></script>

<script src="../vendor/js/fontAwesome.min.js"></script>


<script>

function togglePassword(id, button) {

    const input = document.getElementById(id);

    const icon = button.querySelector('i');


    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("fa-eye");

        icon.classList.add("fa-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("fa-eye-slash");

        icon.classList.add("fa-eye");

    }

}

</script>


</body>

</html>