<?php

require_once "auth.php";
require_once "permissions.php";

/* =====================================================
   PROTECTION D'UNE PAGE
===================================================== */

function protegerPage($permission)
{
    /* Vérification de connexion */
    if (!isset($_SESSION['id_util'])) {
        header("Location: login.php");
        exit();
    }

    /* Vérification du rôle */
    if (!isset($_SESSION['role'])) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    /* Vérification de la permission */
    if (!aPermission($permission)) {
        header("Location: acces_refuse.php");
        exit();
    }
}