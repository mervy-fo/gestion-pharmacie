<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Vérifier si l'utilisateur est connecté */
if (!isset($_SESSION['id_util'])) {
    header("Location: login.php");
    exit();
}