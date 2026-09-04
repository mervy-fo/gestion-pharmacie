<?php
session_start();

require_once __DIR__ . '/../connex.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /projet/login.php');
    exit;
}

$identifiant = trim($_POST['identifiant'] ?? '');
$motPasse = $_POST['mot_passe'] ?? '';

if (empty($identifiant) || empty($motPasse)) {
    header('Location: /pojet/login.php?type=danger&message=Veuillez renseigner tous les champs.');
    exit;
}

$sql = "SELECT 
            u.id_util,
            u.nom,
            u.nom_util,
            u.email,
            u.mot_passe,
            u.statut,
            r.id_role,
            r.nom AS role
        FROM utilisateur u
        INNER JOIN role r ON r.id_role = u.id_role
        WHERE u.nom_util = :nom_util = :identifiant
        OR u.email = :identifiant
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['identifiant' => $identifiant]);

$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    header('Location: index.php?type=danger&message=Nom utilisateur ou mot de passe incorrect.');
    exit;
}

if ($utilisateur['statut'] !== 'actif') {
    header('Location: index.php?type=warning&message=Votre compte est désactivé.');
    exit;
}

/*
 | Pour le moment : test simple.
 | À remplacer ensuite par password_verify() après avoir enregistré
 | de vrais mots de passe chiffrés avec password_hash().
 */
$motPasseValide = password_verify($motPasse, $utilisateur['mot_passe']);

if (!$motPasseValide) {
    header('Location: index.php?type=danger&message=Nom utilisateur ou mot de passe incorrect.');
    exit;
}

session_regenerate_id(true);

$_SESSION['utilisateur'] = [
    'id_util' => $utilisateur['id_util'],
    'nom' => $utilisateur['nom'],
    'nom_util' => $utilisateur['nom_util'],
    'email' => $utilisateur['email'],
    'id_role' => $utilisateur['id_role'],
    'role' => $utilisateur['role']
];

header('Location: /projet/login.php');
exit;