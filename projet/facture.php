<?php
require_once 'auth.php';
require_once 'connex.php';

/* =====================================================
   VÉRIFICATION DE L'ID DE LA VENTE
===================================================== */

$id_vente = isset($_GET['id_vente'])
    ? (int) $_GET['id_vente']
    : 0;

if ($id_vente <= 0) {
    die("Vente invalide.");
}


/* =====================================================
   TVA
===================================================== */

$tauxTVA = 19.25;


/* =====================================================
   INFORMATIONS DE LA VENTE
===================================================== */

$sqlVente = "
    SELECT
        v.id_vente,
        v.date_vente,
        v.montant_total,
        c.id_client,
        c.nom,
        c.prenom
    FROM vente v
    INNER JOIN client c
        ON v.id_client = c.id_client
    WHERE v.id_vente = ?
";

$stmtVente = mysqli_prepare($conn, $sqlVente);

if (!$stmtVente) {
    die("Erreur préparation vente : " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtVente,
    "i",
    $id_vente
);

mysqli_stmt_execute($stmtVente);

$resultVente = mysqli_stmt_get_result($stmtVente);

$vente = mysqli_fetch_assoc($resultVente);

if (!$vente) {
    die("Vente introuvable.");
}


/* =====================================================
   DÉTAILS DE LA VENTE
===================================================== */

$sqlDetails = "
    SELECT
        m.nom,
        m.code,
        ve.quantite,
        ve.montant
    FROM vendre ve
    INNER JOIN medicament m
        ON ve.id_medicament = m.id_medicament
    WHERE ve.id_vente = ?
    ORDER BY m.nom ASC
";

$stmtDetails = mysqli_prepare($conn, $sqlDetails);

if (!$stmtDetails) {
    die("Erreur préparation détails : " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtDetails,
    "i",
    $id_vente
);

mysqli_stmt_execute($stmtDetails);

$resultDetails = mysqli_stmt_get_result($stmtDetails);


/* =====================================================
   CALCULS
===================================================== */

$totalTTC = (float) $vente['montant_total'];

$totalHT =
    $totalTTC / (1 + ($tauxTVA / 100));

$montantTVA =
    $totalTTC - $totalHT;


/* =====================================================
   DATE ET HEURE
===================================================== */

$dateFacture = date(
    'd/m/Y',
    strtotime($vente['date_vente'])
);

$heureFacture = date('H:i');


/* =====================================================
   FORMATAGE
===================================================== */

function montant($valeur)
{
    return number_format(
        $valeur,
        0,
        ',',
        ' '
    ) . ' FCFA';
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Ticket VENTE-<?= $id_vente ?>
    </title>

    <!-- Bootstrap -->
    <link
        rel="stylesheet"
        href="../vendor/css/bootstrap.min.css"
    >

    <!-- FontAwesome -->
    <link
        rel="stylesheet"
        href="../vendor/fontawesome/css/all.css"
    >

    <style>

        /* =================================================
           AFFICHAGE ÉCRAN
        ================================================= */

        body {
            background: #f1f1f1;
        }

        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 20px auto;
            background: #fff;
        }

        /* Logo noir */
        .logo {
            width: 55px;
            height: 55px;
            object-fit: contain;

            /*
             * Transforme automatiquement
             * le logo en noir.
             */
            filter:
                grayscale(200%)
                brightness(0.9)
                contrast(200%);
        }

        .ticket-title {
            font-size: 18px;
            font-weight: 800;
            line-height: 1.1;
        }

        .ticket-subtitle {
            font-size: 11px;
        }

        .ticket-small {
            font-size: 11px;
        }

        .ticket-table {
            width: 100%;
            font-size: 10px;
        }

        .ticket-table th,
        .ticket-table td {
            padding: 4px 2px;
            vertical-align: top;
        }

        .ligne-produit {
            border-bottom: 1px dashed #000;
        }

        .separation {
            border-top: 1px dashed #000;
        }

        .separation-forte {
            border-top: 2px solid #000;
        }

        .montant {
            white-space: nowrap;
        }


        /* =================================================
           IMPRESSION THERMIQUE 80 MM
        ================================================= */

        @media print {

            @page {
                size: 80mm auto;
                margin: 0;
            }

            html,
            body {
                width: 80mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            body {
                font-family: Arial, Helvetica, sans-serif;
                color: #000 !important;
                font-size: 10px;
            }

            .no-print {
                display: none !important;
            }

            .ticket {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 4mm !important;

                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }

            /*
             * Force tous les éléments en noir et blanc
             */
            .ticket,
            .ticket * {
                color: #000 !important;
                background: transparent !important;
                border-color: #000 !important;
                box-shadow: none !important;
            }

            /*
             * Logo entièrement noir
             */
            .logo {
                filter:
                    grayscale(100%)
                    brightness(0)
                    contrast(200%) !important;
            }

            .ticket-title {
                font-size: 17px !important;
            }

            .ticket-subtitle {
                font-size: 10px !important;
            }

            .ticket-small {
                font-size: 10px !important;
            }

            .ticket-table {
                font-size: 9.5px !important;
            }

            .ticket-table th,
            .ticket-table td {
                padding: 3px 1px !important;
            }

            .footer-ticket {
                font-size: 10px !important;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     BOUTONS ÉCRAN
===================================================== -->

<div class="container my-3 no-print">

    <div class="d-flex justify-content-between align-items-center">

        <a
            href="vente.php"
            class="btn btn-outline-secondary"
        >
            <i class="fas fa-arrow-left me-1"></i>
            Retour aux ventes
        </a>


        <button
            type="button"
            class="btn btn-dark"
            onclick="imprimerTicket()"
        >
            <i class="fas fa-print me-1"></i>
            Imprimer ticket 80 mm
        </button>

    </div>

</div>



<!-- =====================================================
     TICKET THERMIQUE
===================================================== -->

<div
    id="ticket"
    class="ticket bg-white border shadow-sm rounded p-3"
>


    <!-- =================================================
         LOGO + NOM PHARMACIE
    ================================================== -->

    <div class="text-center">

        <div class="d-flex justify-content-center align-items-center">

            <img
                src="logopharm.png"
                class="logo me-2"
                alt="Logo Pharmacie Sainte Monique"
            >

            <div class="text-start">

                <div class="ticket-title">
                    PHARMACIE
                </div>

                <div class="ticket-title">
                    SAINTE MONIQUE
                </div>

                <div class="ticket-subtitle">
                    Gestion des médicaments
                </div>

            </div>

        </div>


        <div class="mt-2 ticket-small">

            Bafoussam - Cameroun

            <br>

            Tél : 697 12 22 76

        </div>

    </div>


    <!-- Séparation -->

    <div class="separation my-2"></div>


    <!-- =================================================
         FACTURE
    ================================================== -->

    <div class="text-center">

        <div class="fw-bold fs-5">
            FACTURE
        </div>

        <div class="fw-bold">
            N° VENTE-<?= $vente['id_vente'] ?>
        </div>

        <div class="ticket-small mt-1">

            Date :
            <?= $dateFacture ?>

            &nbsp;&nbsp;

            Heure :
            <?= $heureFacture ?>

        </div>

    </div>


    <div class="separation my-2"></div>


    <!-- =================================================
         CLIENT
    ================================================== -->

    <div class="text-center ticket-small">

        <div class="fw-bold">
            CLIENT
        </div>

        <div class="fw-semibold">

            <?= htmlspecialchars($vente['nom']) ?>

            <?= htmlspecialchars($vente['prenom']) ?>

        </div>

        <div>

            Client N°
            <?= $vente['id_client'] ?>

        </div>

    </div>


    <div class="separation my-2"></div>


    <!-- =================================================
         PRODUITS
    ================================================== -->

    <table class="ticket-table mb-0">

        <thead>

            <tr class="separation-forte">

                <th class="text-start">
                    PRODUIT
                </th>

                <th class="text-center">
                    CODE
                </th>

                <th class="text-center">
                    QTÉ
                </th>

                <th class="text-end">
                    P.U.
                </th>

                <th class="text-end">
                    TOTAL
                </th>

            </tr>

            <tr>
                <td
                    colspan="5"
                    class="p-0"
                >
                    <div class="separation-forte"></div>
                </td>
            </tr>

        </thead>


        <tbody>

        <?php while ($ligne = mysqli_fetch_assoc($resultDetails)): ?>

            <?php

            $prixTotal =
                (float) $ligne['montant'];

            $quantite =
                (int) $ligne['quantite'];

            $prixUnitaire =
                $quantite > 0
                    ? $prixTotal / $quantite
                    : 0;

            ?>

            <tr class="ligne-produit">

                <td>

                    <div class="fw-semibold">

                        <?= htmlspecialchars(
                            $ligne['nom']
                        ) ?>

                    </div>

                </td>


                <td class="text-center">

                    <?= htmlspecialchars(
                        $ligne['code']
                    ) ?>

                </td>


                <td class="text-center">

                    <?= $quantite ?>

                </td>


                <td class="text-end montant">

                    <?= number_format(
                        $prixUnitaire,
                        0,
                        ',',
                        ' '
                    ) ?>

                </td>


                <td class="text-end montant fw-bold">

                    <?= number_format(
                        $prixTotal,
                        0,
                        ',',
                        ' '
                    ) ?>

                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>


    <!-- =================================================
         TOTAUX
    ================================================== -->

    <div class="mt-2">

        <div class="d-flex justify-content-between ticket-small">

            <span>
                SOUS-TOTAL HT
            </span>

            <strong>
                <?= number_format(
                    $totalHT,
                    0,
                    ',',
                    ' '
                ) ?>
            </strong>

        </div>


        <div class="d-flex justify-content-between ticket-small">

            <span>
                TVA (<?= $tauxTVA ?> %)
            </span>

            <strong>
                <?= number_format(
                    $montantTVA,
                    0,
                    ',',
                    ' '
                ) ?>
            </strong>

        </div>


        <div class="separation-forte my-1"></div>


        <div class="d-flex justify-content-between fs-6 fw-bold">

            <span>
                TOTAL TTC
            </span>

            <span>
                <?= number_format(
                    $totalTTC,
                    0,
                    ',',
                    ' '
                ) ?>
            </span>

        </div>


        <div class="separation-forte mt-2"></div>

    </div>


    <!-- =================================================
         PAIEMENT
    ================================================== -->

    <div class="mt-2 ticket-small">

        <div class="d-flex justify-content-between">

            <span>
                Montant payé
            </span>

            <strong id="montantPayeAffiche">
                <?= number_format(
                    $totalTTC,
                    0,
                    ',',
                    ' '
                ) ?>
            </strong>

        </div>


        <div class="d-flex justify-content-between">

            <span>
                Monnaie à rendre
            </span>

            <strong id="monnaie">
                0
            </strong>

        </div>

    </div>


    <!-- =================================================
         PIED DE TICKET
    ================================================== -->

    <div class="separation my-2"></div>


    <div class="text-center footer-ticket">

        <div class="fw-bold mb-1">
            Merci pour votre confiance !
        </div>

        <div>
            Conservez ce ticket comme
        </div>

        <div>
            justificatif de votre achat.
        </div>

        <div class="fw-bold mt-2">
            VENTE-<?= $vente['id_vente'] ?>
        </div>

    </div>

</div>



<script src="../vendor/js/bootstrap.bundle.min.js"></script>


<script>

/* =====================================================
   IMPRESSION DU TICKET 80 MM
===================================================== */

function imprimerTicket()
{
    window.print();
}


/* =====================================================
   CALCUL MONNAIE
===================================================== */

function calculerMonnaie()
{
    const total =
        <?= $totalTTC ?>;

    const montantPaye =
        parseFloat(
            document.getElementById('montantPaye')?.value
        ) || total;

    let monnaie =
        montantPaye - total;

    if (monnaie < 0) {
        monnaie = 0;
    }

    document.getElementById(
        'monnaie'
    ).textContent =
        monnaie.toLocaleString('fr-FR');
}

</script>

</body>

</html>