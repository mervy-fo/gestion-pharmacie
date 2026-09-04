<?php
require_once "auth.php";
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Accès refusé</title>

    <link rel="stylesheet"
          href="../vendor/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="../vendor/fontawesome/css/all.css">

</head>

<body>

<div class="container">

    <div class="row justify-content-center align-items-center"
         style="min-height: 100vh;">

        <div class="col-md-6">

            <div class="card shadow text-center">

                <div class="card-body p-5">

                    <i class="fas fa-ban text-danger fs-1 mb-3"></i>

                    <h3>
                        Accès refusé
                    </h3>

                    <p class="text-muted">

                        Vous n'avez pas les autorisations
                        nécessaires pour accéder à cette page.

                    </p>

                    <a href="tableau de bord.php"
                       class="btn btn-primary">

                        <i class="fas fa-home me-2"></i>

                        Retour au tableau de bord

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>