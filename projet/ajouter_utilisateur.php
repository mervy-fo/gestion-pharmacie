<?php

session_start();

require_once "connex.php";
require_once "protection.php";

protegerPage("ajouter_utilisateur");

$nom = trim($_POST['nom'] ?? '');
$nom_util = trim($_POST['nom_util'] ?? '');
$email = trim($_POST['email'] ?? '');
$mot_passe = $_POST['mot_passe'] ?? '';
$role = trim($_POST['role'] ?? '');
$statut = trim($_POST['statut'] ?? 'Actif');

if ($nom === '' || $nom_util === '' || $email === '' || $mot_passe === '' || $role === '') {
    die("Tous les champs sont obligatoires.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email invalide.");
}

$check = $conn->prepare("SELECT id_util FROM utilisateur WHERE nom_util = ? OR email = ?");
$check->bind_param("ss", $nom_util, $email);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    die("Nom d'utilisateur ou email déjà utilisé.");
}

$hash = password_hash($mot_passe, PASSWORD_DEFAULT);

$sql = "INSERT INTO utilisateur (nom, nom_util, email, statut, mot_passe, role)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nom, $nom_util, $email, $statut, $hash, $role);

if ($stmt->execute()) {
    header("Location: utilisateurs.php?success=1");
    exit();
} else {
    echo "Erreur lors de l'ajout.";
}

$stmt->close();
$check->close();
$conn->close();
?>