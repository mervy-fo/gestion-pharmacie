<?php
$conn = new mysqli("localhost", "root", "", "pharmacie");

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>