<?php

require_once 'connex.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: stocks.php");
    exit;
}

$id_medicament = (int) $_POST['id_medicament'];
$quantite = (int) $_POST['quantite'];
$date_mouv = $_POST['date_mouv'];
$reference = trim($_POST['reference'] ?? '');
$motif = trim($_POST['motif'] ?? '');

if ($id_medicament <= 0) {
    die("Médicament invalide.");
}

if ($quantite <= 0) {
    die("La quantité doit être supérieure à zéro.");
}


/* Récupérer le stock actuel */

$sql = "SELECT quantite_restante
        FROM medicament
        WHERE id_medicament = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_medicament
);

mysqli_stmt_execute($stmt);

$resultat = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultat) == 0) {
    die("Médicament introuvable.");
}

$medicament = mysqli_fetch_assoc($resultat);

$ancien_stock = (int) $medicament['quantite_restante'];

mysqli_stmt_close($stmt);


/* Nouveau stock */

$nouveau_stock = $ancien_stock + $quantite;


/* Enregistrer le mouvement */

$nature = "ENTREE";

$sql = "INSERT INTO mouvement_stock
        (nature, quantite, date_mouv, reference, motif, id_medicament)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Erreur SQL : " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "sisssi",
    $nature,
    $quantite,
    $date_mouv,
    $reference,
    $motif,
    $id_medicament
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erreur lors de l'enregistrement : "
        . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);


/* Mettre à jour le stock */

$sql = "UPDATE medicament
        SET quantite_restante = ?
        WHERE id_medicament = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $nouveau_stock,
    $id_medicament
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erreur mise à jour stock : "
        . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);


/* Retour */

header("Location: stocks.php?success=entree");
exit;

?>