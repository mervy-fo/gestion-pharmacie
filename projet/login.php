<?php
session_start();
require 'connex.php';

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $mot_passe = $_POST["mot_passe"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez saisir un email valide.";
    } elseif ($mot_passe === '') {
        $erreur = "Veuillez saisir votre mot de passe.";
    } else {
        $sql = "SELECT id_util, nom, nom_util, email, mot_passe, role, statut
                FROM utilisateur
                WHERE email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $user["statut"] === "Actif" && password_verify($mot_passe, $user["mot_passe"])) {

            session_regenerate_id(true);

            $_SESSION["id_util"] = $user["id_util"];
            $_SESSION["nom"] = $user["nom"];
            $_SESSION["nom_util"] = $user["nom_util"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            header("Location: tableau de bord.php");
            exit();
            
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - PharmaStock</title>

    <link rel="stylesheet" href="../vendor/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.css" />
    <link rel="stylesheet" href="./style.css">
</head>
<body>

<div class="login-container">
    <div class="login-card">

        <div class="logo-container ">
            <img src="WhatsApp Image 2026-08-24 at 11.20.46 AM.jpeg" class="img-fluid rounded" style="width: 200px;" alt="">
          
            <p>Gestion intelligente des stocks</p>
        </div>

        <?php if (!empty($erreur)) : ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="Entrez votre email"
                        required
                    >
                </div>
            </div>

            <div class="mb-3">
                <label for="mot_passe" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input
                        type="password"
                        class="form-control"
                        id="mot_passe"
                        name="mot_passe"
                        placeholder="Entrez votre mot de passe"
                        required
                    >
                    <button type="button" class="btn btn-outline-secondary" id="showPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label" for="remember">
                        Se souvenir de moi
                    </label>
                </div>

                <a href="#" class="forgot-password">
                    Mot de passe oublié ?
                </a>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>
                Se connecter
            </button>

        </form>

        <div class="login-footer">
            <i class="fas fa-shield-alt"></i>
            Système sécurisé de gestion pharmaceutique
        </div>

    </div>
</div>

<script src="../vendor/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/js/fontAwesome.min.js"></script>

<script>
const showPassword = document.getElementById("showPassword");
const password = document.getElementById("mot_passe");

showPassword.addEventListener("click", function () {
    if (password.type === "password") {
        password.type = "text";
        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        password.type = "password";
        this.innerHTML = '<i class="fas fa-eye"></i>';
    }
});
</script>

</body>
</html>