<?php

/* =========================================================
   RAPPORTS.PHP
   ========================================================= */

require_once 'connex.php';

$page = basename($_SERVER['PHP_SELF']);

/* =========================================================
   FILTRES DE DATES
   ========================================================= */

$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : '';
$date_fin   = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : '';

$where = "";
$params = [];
$types = "";

if ($date_debut !== '' && $date_fin !== '') {

    $where = " WHERE v.date_vente BETWEEN ? AND ? ";

    $params[] = $date_debut;
    $params[] = $date_fin;
    $types = "ss";

} elseif ($date_debut !== '') {

    $where = " WHERE v.date_vente >= ? ";

    $params[] = $date_debut;
    $types = "s";

} elseif ($date_fin !== '') {

    $where = " WHERE v.date_vente <= ? ";

    $params[] = $date_fin;
    $types = "s";
}


/* =========================================================
   FONCTION POUR EXECUTER UNE REQUÊTE AVEC FILTRE
   ========================================================= */

function executerRequete($conn, $sql, $params = [], $types = "")
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Erreur préparation SQL : " . mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Erreur SQL : " . mysqli_stmt_error($stmt));
    }

    return $result;
}


/* =========================================================
   1. STATISTIQUES GÉNÉRALES
   ========================================================= */

/* Nombre de ventes */

$sqlTotalVentes = "
    SELECT COUNT(DISTINCT v.id_vente) AS total
    FROM vendre v
    $where
";

$result = executerRequete(
    $conn,
    $sqlTotalVentes,
    $params,
    $types
);

$totalVentes = (int) mysqli_fetch_assoc($result)['total'];


/* Chiffre d'affaires */

$sqlChiffreAffaires = "
    SELECT COALESCE(SUM(v.montant), 0) AS total
    FROM vendre v
    $where
";

$result = executerRequete(
    $conn,
    $sqlChiffreAffaires,
    $params,
    $types
);

$chiffreAffaires = (float) mysqli_fetch_assoc($result)['total'];


/* Quantité vendue */

$sqlQuantite = "
    SELECT COALESCE(SUM(v.quantite), 0) AS total
    FROM vendre v
    $where
";

$result = executerRequete(
    $conn,
    $sqlQuantite,
    $params,
    $types
);

$quantiteVendue = (int) mysqli_fetch_assoc($result)['total'];


/* Nombre de clients */

$sqlClients = "
    SELECT COUNT(DISTINCT v.id_client) AS total
    FROM vendre v
    $where
";

$result = executerRequete(
    $conn,
    $sqlClients,
    $params,
    $types
);

$totalClients = (int) mysqli_fetch_assoc($result)['total'];


/* =========================================================
   2. MÉDICAMENT LE PLUS VENDU
   ========================================================= */

$sqlMeilleurMedicament = "
    SELECT
        m.nom AS medicament,
        SUM(v.quantite) AS quantite
    FROM vendre v
    INNER JOIN medicament m
        ON m.id_medicament = v.id_medicament
    $where
    GROUP BY v.id_medicament, m.nom
    ORDER BY quantite DESC
    LIMIT 1
";

$result = executerRequete(
    $conn,
    $sqlMeilleurMedicament,
    $params,
    $types
);

$meilleurMedicament = mysqli_fetch_assoc($result);

$nomMeilleurMedicament = $meilleurMedicament
    ? $meilleurMedicament['medicament']
    : 'Aucun';

$quantiteMeilleurMedicament = $meilleurMedicament
    ? (int)$meilleurMedicament['quantite']
    : 0;


/* =========================================================
   3. RAPPORT DES MÉDICAMENTS VENDUS
   ========================================================= */

$sqlMedicaments = "
    SELECT
        m.id_medicament,
        m.nom AS medicament,
        SUM(v.quantite) AS quantite_vendue,
        SUM(v.montant) AS chiffre_affaires
    FROM vendre v
    INNER JOIN medicament m
        ON m.id_medicament = v.id_medicament
    $where
    GROUP BY
        m.id_medicament,
        m.nom
    ORDER BY
        quantite_vendue DESC
";

$resultMedicaments = executerRequete(
    $conn,
    $sqlMedicaments,
    $params,
    $types
);


/* =========================================================
   4. DÉTAIL DES VENTES
   ========================================================= */

$sqlDetails = "
    SELECT
        v.id_vente,
        v.date_vente,
        c.nom AS client,
        m.nom AS medicament,
        v.quantite,
        v.montant,

        (
            SELECT ve.prix_unitaire
            FROM vente ve
            WHERE ve.id_vente = v.id_vente
            LIMIT 1
        ) AS prix_unitaire

    FROM vendre v

    LEFT JOIN client c
        ON c.id_client = v.id_client

    INNER JOIN medicament m
        ON m.id_medicament = v.id_medicament

    $where

    ORDER BY
        v.date_vente DESC,
        v.id_vente DESC
";

$resultDetails = executerRequete(
    $conn,
    $sqlDetails,
    $params,
    $types
);


/* =========================================================
   5. CHIFFRE D'AFFAIRES PAR JOUR
   ========================================================= */

$sqlJournalier = "
    SELECT
        v.date_vente,
        SUM(v.quantite) AS quantite,
        SUM(v.montant) AS montant
    FROM vendre v
    $where
    GROUP BY v.date_vente
    ORDER BY v.date_vente DESC
";

$resultJournalier = executerRequete(
    $conn,
    $sqlJournalier,
    $params,
    $types
);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Rapports - PharmaStock</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
          href="../vendor/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="../vendor/fontawesome/css/all.css">
      <link rel="stylesheet"
          href="./style.css">
    <style>

        body {
            background: #f5f7fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 25px;
        }

        .page-title {
            font-weight: 700;
            color: #1f2937;
        }

        .stat-card {
            border: none;
            border-radius: 15px;
            padding: 20px;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            height: 100%;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .stat-title {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .report-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .report-card .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 18px 20px;
            font-weight: 700;
        }

        .table th {
            white-space: nowrap;
            font-size: 13px;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .badge-money {
            font-size: 13px;
            padding: 7px 10px;
        }

        @media (max-width: 991px) {

            .main-content {
                margin-left: 0;
            }

        }

    </style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<?php include 'sidebar.php'; ?>


<!-- =====================================================
     CONTENU PRINCIPAL
     ===================================================== -->

<div class="main-content">


    <!-- TITRE -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="page-title mb-1">

                <i class="fas fa-chart-bar me-2"></i>

                Rapports

            </h2>

            <p class="text-muted mb-0">

                Analyse des ventes et des performances de la pharmacie

            </p>

        </div>

    </div>


    <!-- =================================================
         FILTRE
         ================================================= -->

    <div class="filter-box mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <div class="col-md-4">

                    <label class="form-label fw-semibold">

                        Date début

                    </label>

                    <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="<?= htmlspecialchars($date_debut) ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label fw-semibold">

                        Date fin

                    </label>

                    <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="<?= htmlspecialchars($date_fin) ?>"
                    >

                </div>


                <div class="col-md-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >

                        <i class="fas fa-search me-1"></i>

                        Filtrer

                    </button>

                </div>


                <div class="col-md-2">

                    <a
                        href="rapports.php"
                        class="btn btn-outline-secondary w-100"
                    >

                        <i class="fas fa-rotate-left me-1"></i>

                        Réinitialiser

                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- =================================================
         STATISTIQUES
         ================================================= -->

    <div class="row g-4 mb-4">


        <!-- VENTES -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon bg-primary bg-opacity-10 text-primary">

                    <i class="fas fa-shopping-cart"></i>

                </div>

                <div class="stat-title">

                    Nombre de ventes

                </div>

                <div class="stat-value">

                    <?= number_format($totalVentes, 0, ',', ' ') ?>

                </div>

            </div>

        </div>


        <!-- CA -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon bg-success bg-opacity-10 text-success">

                    <i class="fas fa-money-bill-wave"></i>

                </div>

                <div class="stat-title">

                    Chiffre d'affaires

                </div>

                <div class="stat-value">

                    <?= number_format($chiffreAffaires, 0, ',', ' ') ?>

                    FCFA

                </div>

            </div>

        </div>


        <!-- QUANTITE -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon bg-warning bg-opacity-10 text-warning">

                    <i class="fas fa-pills"></i>

                </div>

                <div class="stat-title">

                    Médicaments vendus

                </div>

                <div class="stat-value">

                    <?= number_format($quantiteVendue, 0, ',', ' ') ?>

                </div>

            </div>

        </div>


        <!-- CLIENTS -->

        <div class="col-md-6 col-xl-3">

            <div class="stat-card">

                <div class="stat-icon bg-info bg-opacity-10 text-info">

                    <i class="fas fa-users"></i>

                </div>

                <div class="stat-title">

                    Clients concernés

                </div>

                <div class="stat-value">

                    <?= number_format($totalClients, 0, ',', ' ') ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         MEILLEUR MÉDICAMENT
         ================================================= -->

    <div class="alert alert-success d-flex align-items-center mb-4">

        <i class="fas fa-trophy fa-2x me-3"></i>

        <div>

            <strong>Médicament le plus vendu :</strong>

            <?= htmlspecialchars($nomMeilleurMedicament) ?>

            <?php if ($quantiteMeilleurMedicament > 0): ?>

                — <?= number_format(
                    $quantiteMeilleurMedicament,
                    0,
                    ',',
                    ' '
                ) ?> unité(s)

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         RAPPORT MÉDICAMENTS
         ================================================= -->

    <div class="card report-card mb-4">

        <div class="card-header">

            <i class="fas fa-pills me-2"></i>

            Performance des médicaments

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Médicament</th>

                            <th>Quantité vendue</th>

                            <th>Chiffre d'affaires</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    $rang = 1;

                    if (mysqli_num_rows($resultMedicaments) > 0):

                        while ($med = mysqli_fetch_assoc($resultMedicaments)):

                    ?>

                        <tr>

                            <td>

                                <strong>
                                    <?= $rang++ ?>
                                </strong>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $med['medicament']
                                ) ?>

                            </td>

                            <td>

                                <span class="badge bg-primary badge-money">

                                    <?= number_format(
                                        $med['quantite_vendue'],
                                        0,
                                        ',',
                                        ' '
                                    ) ?>

                                    unité(s)

                                </span>

                            </td>

                            <td>

                                <strong>

                                    <?= number_format(
                                        $med['chiffre_affaires'],
                                        0,
                                        ',',
                                        ' '
                                    ) ?>

                                    FCFA

                                </strong>

                            </td>

                        </tr>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td colspan="4"
                                class="text-center text-muted py-4">

                                Aucune vente trouvée.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- =================================================
         DÉTAIL DES VENTES
         ================================================= -->

    <div class="card report-card mb-4">

        <div class="card-header">

            <i class="fas fa-file-invoice me-2"></i>

            Détail des ventes

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>N° vente</th>

                            <th>Date</th>

                            <th>Client</th>

                            <th>Médicament</th>

                            <th>Quantité</th>

                            <th>Prix unitaire</th>

                            <th>Montant</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    if (mysqli_num_rows($resultDetails) > 0):

                        while ($vente = mysqli_fetch_assoc($resultDetails)):

                    ?>

                        <tr>

                            <td>

                                <span class="badge bg-secondary">

                                    #<?= htmlspecialchars(
                                        $vente['id_vente']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime($vente['date_vente'])
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $vente['client'] ?? 'Client inconnu'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $vente['medicament']
                                ) ?>

                            </td>


                            <td>

                                <?= number_format(
                                    $vente['quantite'],
                                    0,
                                    ',',
                                    ' '
                                ) ?>

                            </td>


                            <td>

                                <?php

                                $prixUnitaire = $vente['prix_unitaire'];

                                if (
                                    $prixUnitaire === null ||
                                    $prixUnitaire === ''
                                ) {

                                    echo '<span class="text-muted">-</span>';

                                } else {

                                    echo number_format(
                                        $prixUnitaire,
                                        0,
                                        ',',
                                        ' '
                                    ) . ' FCFA';

                                }

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?= number_format(
                                        $vente['montant'],
                                        0,
                                        ',',
                                        ' '
                                    ) ?>

                                    FCFA

                                </strong>

                            </td>

                        </tr>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td colspan="7"
                                class="text-center text-muted py-4">

                                Aucune vente trouvée pour cette période.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- =================================================
         RAPPORT JOURNALIER
         ================================================= -->

    <div class="card report-card mb-4">

        <div class="card-header">

            <i class="fas fa-calendar-day me-2"></i>

            Chiffre d'affaires par jour

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>Date</th>

                            <th>Quantité vendue</th>

                            <th>Chiffre d'affaires</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    if (mysqli_num_rows($resultJournalier) > 0):

                        while ($jour = mysqli_fetch_assoc($resultJournalier)):

                    ?>

                        <tr>

                            <td>

                                <strong>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($jour['date_vente'])
                                    ) ?>

                                </strong>

                            </td>

                            <td>

                                <?= number_format(
                                    $jour['quantite'],
                                    0,
                                    ',',
                                    ' '
                                ) ?>

                                unité(s)

                            </td>

                            <td>

                                <strong class="text-success">

                                    <?= number_format(
                                        $jour['montant'],
                                        0,
                                        ',',
                                        ' '
                                    ) ?>

                                    FCFA

                                </strong>

                            </td>

                        </tr>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td colspan="3"
                                class="text-center text-muted py-4">

                                Aucune donnée disponible.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>


<script src="../vendor/js/bootstrap.bundle.min.js"></script>

</body>

</html>