<?php

require_once 'connex.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stocks.php');
    exit;
}

$id_medicament = isset($_POST['id_medicament'])
    ? (int) $_POST['id_medicament']
    : 0;

$quantite = isset($_POST['quantite'])
    ? (int) $_POST['quantite']
    : 0;

$date_mouv = $_POST['date_mouv'] ?? date('Y-m-d');

$reference = trim($_POST['reference'] ?? '');
$motif = trim($_POST['motif'] ?? '');

if ($id_medicament <= 0 || $quantite <= 0 || empty($motif)) {
    header('Location: stocks.php?erreur=donnees_invalides');
    exit;
}

mysqli_begin_transaction($conn);

try {

    /* Bloque la ligne du médicament pendant la sortie */
    $sqlStock = "
        SELECT quantite_restante
        FROM medicament
        WHERE id_medicament = ?
        FOR UPDATE
    ";

    $stmtStock = mysqli_prepare($conn, $sqlStock);

    mysqli_stmt_bind_param($stmtStock, "i", $id_medicament);

    mysqli_stmt_execute($stmtStock);

    $resultatStock = mysqli_stmt_get_result($stmtStock);

    $medicament = mysqli_fetch_assoc($resultatStock);

    if (!$medicament) {
        throw new Exception("Médicament introuvable.");
    }

    $stockDisponible = (int) $medicament['quantite_restante'];

    if ($quantite > $stockDisponible) {
        throw new Exception(
            "Stock insuffisant. Stock disponible : " . $stockDisponible
        );
    }

    /* Diminue le stock */
    $sqlUpdate = "
        UPDATE medicament
        SET quantite_restante = quantite_restante - ?
        WHERE id_medicament = ?
    ";

    $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);

    mysqli_stmt_bind_param(
        $stmtUpdate,
        "ii",
        $quantite,
        $id_medicament
    );

    if (!mysqli_stmt_execute($stmtUpdate)) {
        throw new Exception("Impossible de mettre à jour le stock.");
    }

    /* Enregistre l'historique de la sortie */
    $nature = "SORTIE";

    $sqlMouvement = "
        INSERT INTO mouvement_stock
            (
                id_medicament,
                nature,
                quantite,
                date_mouv,
                reference,
                motif
            )
        VALUES (?, ?, ?, ?, ?, ?)
    ";

    $stmtMouvement = mysqli_prepare($conn, $sqlMouvement);
    if (!$stmtMouvement) {
    throw new Exception(
        "Erreur préparation mouvement : " . mysqli_error($conn)
    );
}

    mysqli_stmt_bind_param(
        $stmtMouvement,
        "isisss",
        $id_medicament,
        $nature,
        $quantite,
        $date_mouv,
        $reference,
        $motif
    );

    if (!mysqli_stmt_execute($stmtMouvement)) {
        throw new Exception("Impossible d'enregistrer la sortie.");
    }

    mysqli_commit($conn);

    header('Location: stocks.php?succes=sortie_ajoutee');
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    $message = urlencode($e->getMessage());

    header("Location: stocks.php?erreur=$message");
    exit;
}