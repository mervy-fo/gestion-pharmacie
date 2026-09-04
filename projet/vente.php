<?php
session_start();

require_once 'connex.php';
require_once 'protection.php';

protegerPage("ventes");

/* =====================================================
   AUTOCOMPLÉTION DES MÉDICAMENTS
===================================================== */

if (isset($_GET['recherche_medicament'])) {

    header('Content-Type: application/json; charset=utf-8');

    $recherche = trim($_GET['recherche_medicament']);

    if ($recherche === '' || strlen($recherche) < 2) {
        echo json_encode([]);
        exit;
    }

    $rechercheSQL = "%" . $recherche . "%";

    $sql = "
        SELECT
            id_medicament,
            nom,
            code,
            prix,
            quantite_restante
        FROM medicament
        WHERE quantite_restante > 0
        AND (
            nom LIKE ?
            OR code LIKE ?
        )
        ORDER BY nom ASC
        LIMIT 10
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        echo json_encode([
            'erreur' => mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $rechercheSQL,
        $rechercheSQL
    );

    mysqli_stmt_execute($stmt);

    $resultat = mysqli_stmt_get_result($stmt);

    $medicaments = [];

    while ($medicament = mysqli_fetch_assoc($resultat)) {

        $medicaments[] = [
            'id' => (int) $medicament['id_medicament'],
            'nom' => $medicament['nom'],
            'code' => $medicament['code'],
            'prix' => (float) $medicament['prix'],
            'stock' => (int) $medicament['quantite_restante']
        ];
    }

    echo json_encode(
        $medicaments,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   ENREGISTREMENT DE LA VENTE
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date_vente = $_POST['date_vente'] ?? date('Y-m-d');

    $idsMedicaments = $_POST['id_medicament'] ?? [];
    $quantites = $_POST['quantite'] ?? [];

    if (
        !is_array($idsMedicaments) ||
        !is_array($quantites) ||
        count($idsMedicaments) === 0 ||
        count($idsMedicaments) !== count($quantites)
    ) {
        header(
            'Location: vente.php?erreur=' .
            urlencode('Ajoutez au moins un médicament.')
        );
        exit;
    }

    mysqli_begin_transaction($conn);

    try {

        /* =================================================
           CRÉATION AUTOMATIQUE DU CLIENT
        ================================================= */

        $sqlClient = "
            INSERT INTO client (nom, prenom)
            VALUES ('Client', '')
        ";

        if (!mysqli_query($conn, $sqlClient)) {
            throw new Exception(
                "Erreur création client : " .
                mysqli_error($conn)
            );
        }

        $id_client = mysqli_insert_id($conn);

        $nomClient = "Client" . $id_client;

        $sqlNomClient = "
            UPDATE client
            SET nom = ?
            WHERE id_client = ?
        ";

        $stmtNomClient = mysqli_prepare(
            $conn,
            $sqlNomClient
        );

        if (!$stmtNomClient) {
            throw new Exception(
                "Erreur préparation client : " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmtNomClient,
            "si",
            $nomClient,
            $id_client
        );

        if (!mysqli_stmt_execute($stmtNomClient)) {
            throw new Exception(
                "Erreur modification client : " .
                mysqli_stmt_error($stmtNomClient)
            );
        }


        /* =================================================
           CRÉATION DE LA VENTE
        ================================================= */

        $sqlVente = "
            INSERT INTO vente (
                id_client,
                date_vente,
                montant_total
            )
            VALUES (?, ?, 0)
        ";

        $stmtVente = mysqli_prepare(
            $conn,
            $sqlVente
        );

        if (!$stmtVente) {
            throw new Exception(
                "Erreur préparation vente : " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmtVente,
            "is",
            $id_client,
            $date_vente
        );

        if (!mysqli_stmt_execute($stmtVente)) {
            throw new Exception(
                "Erreur création vente : " .
                mysqli_stmt_error($stmtVente)
            );
        }

        $id_vente = mysqli_insert_id($conn);

        $montantTotal = 0;


        /* =================================================
           TRAITEMENT DES MÉDICAMENTS
        ================================================= */

        foreach ($idsMedicaments as $index => $id) {

            $id_medicament = (int) $id;
            $quantite = (int) ($quantites[$index] ?? 0);

            if ($id_medicament <= 0 || $quantite <= 0) {
                throw new Exception(
                    "Une ligne de vente est invalide."
                );
            }


            /* =============================================
               RÉCUPÉRATION DU MÉDICAMENT
            ============================================= */

            $sqlMedicament = "
                SELECT
                    nom,
                    prix,
                    quantite_restante
                FROM medicament
                WHERE id_medicament = ?
                FOR UPDATE
            ";

            $stmtMedicament = mysqli_prepare(
                $conn,
                $sqlMedicament
            );

            if (!$stmtMedicament) {
                throw new Exception(
                    "Erreur préparation médicament : " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $stmtMedicament,
                "i",
                $id_medicament
            );

            if (!mysqli_stmt_execute($stmtMedicament)) {
                throw new Exception(
                    "Erreur médicament : " .
                    mysqli_stmt_error($stmtMedicament)
                );
            }

            $resultMedicament =
                mysqli_stmt_get_result($stmtMedicament);

            $medicament =
                mysqli_fetch_assoc($resultMedicament);

            if (!$medicament) {
                throw new Exception(
                    "Médicament introuvable."
                );
            }


            /* =============================================
               VÉRIFICATION DU STOCK
            ============================================= */

            $stockDisponible =
                (int) $medicament['quantite_restante'];

            if ($quantite > $stockDisponible) {

                throw new Exception(
                    "Stock insuffisant pour « " .
                    $medicament['nom'] .
                    " ». Stock disponible : " .
                    $stockDisponible
                );
            }


            /* =============================================
               CALCUL
            ============================================= */

            $prixUnitaire =
                (float) $medicament['prix'];

            $montantLigne =
                $prixUnitaire * $quantite;

            $montantTotal += $montantLigne;


            /* =============================================
               DIMINUTION DU STOCK
            ============================================= */

            $sqlStock = "
                UPDATE medicament
                SET quantite_restante =
                    quantite_restante - ?
                WHERE id_medicament = ?
            ";

            $stmtStock = mysqli_prepare(
                $conn,
                $sqlStock
            );

            if (!$stmtStock) {
                throw new Exception(
                    "Erreur préparation stock : " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $stmtStock,
                "ii",
                $quantite,
                $id_medicament
            );

            if (!mysqli_stmt_execute($stmtStock)) {
                throw new Exception(
                    "Erreur mise à jour stock : " .
                    mysqli_stmt_error($stmtStock)
                );
            }


            /* =============================================
               AJOUT DANS VENDRE
            ============================================= */

            $sqlLigneVente = "
                INSERT INTO vendre (
                    id_medicament,
                    id_client,
                    id_vente,
                    date_vente,
                    montant,
                    quantite
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmtLigneVente = mysqli_prepare(
                $conn,
                $sqlLigneVente
            );

            if (!$stmtLigneVente) {
                throw new Exception(
                    "Erreur préparation vendre : " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $stmtLigneVente,
                "iiisdi",
                $id_medicament,
                $id_client,
                $id_vente,
                $date_vente,
                $montantLigne,
                $quantite
            );

            if (!mysqli_stmt_execute($stmtLigneVente)) {
                throw new Exception(
                    "Erreur ajout dans vendre : " .
                    mysqli_stmt_error($stmtLigneVente)
                );
            }


            /* =============================================
               MOUVEMENT DE STOCK
            ============================================= */

            $nature = "SORTIE";
            $reference = "VENTE-" . $id_vente;
            $motif = "Vente au " . $nomClient;

            $sqlMouvement = "
                INSERT INTO mouvement_stock (
                    nature,
                    quantite,
                    date_mouv,
                    reference,
                    motif,
                    id_medicament
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmtMouvement = mysqli_prepare(
                $conn,
                $sqlMouvement
            );

            if (!$stmtMouvement) {
                throw new Exception(
                    "Erreur préparation mouvement : " .
                    mysqli_error($conn)
                );
            }

            mysqli_stmt_bind_param(
                $stmtMouvement,
                "sisssi",
                $nature,
                $quantite,
                $date_vente,
                $reference,
                $motif,
                $id_medicament
            );

            if (!mysqli_stmt_execute($stmtMouvement)) {
                throw new Exception(
                    "Erreur ajout mouvement : " .
                    mysqli_stmt_error($stmtMouvement)
                );
            }
        }


        /* =================================================
           MISE À JOUR DU TOTAL
        ================================================= */

        $sqlTotal = "
            UPDATE vente
            SET montant_total = ?
            WHERE id_vente = ?
        ";

        $stmtTotal = mysqli_prepare(
            $conn,
            $sqlTotal
        );

        if (!$stmtTotal) {
            throw new Exception(
                "Erreur préparation total : " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmtTotal,
            "di",
            $montantTotal,
            $id_vente
        );

        if (!mysqli_stmt_execute($stmtTotal)) {
            throw new Exception(
                "Erreur total vente : " .
                mysqli_stmt_error($stmtTotal)
            );
        }


        /* =================================================
           VALIDATION
        ================================================= */

        mysqli_commit($conn);

        header(
            'Location: facture.php?id_vente=' .
            $id_vente
        );

        exit;

    } catch (Exception $e) {

        mysqli_rollback($conn);

        header(
            'Location: vente.php?erreur=' .
            urlencode($e->getMessage())
        );

        exit;
    }
}


/* =====================================================
   LISTE DES MÉDICAMENTS DISPONIBLES
===================================================== */

$sqlMedicaments = "
    SELECT
        id_medicament,
        nom,
        code,
        prix,
        quantite_restante
    FROM medicament
    WHERE quantite_restante > 0
    ORDER BY nom ASC
";

$resultMedicaments = mysqli_query(
    $conn,
    $sqlMedicaments
);

if (!$resultMedicaments) {
    die(
        "Erreur médicaments : " .
        mysqli_error($conn)
    );
}

?>
<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Nouvelle vente - Pharmacie</title>

    <!-- BOOTSTRAP -->
    <link rel="stylesheet"
          href="../vendor/css/bootstrap.min.css">

    <!-- FONT AWESOME -->
    <link rel="stylesheet"
          href="../vendor/fontawesome/css/all.css">
      <link rel="stylesheet"
          href="./style.css">
    <style>

        /* =================================================
           SEULEMENT LE MINIMUM DE CSS
        ================================================= */

        body {
            background-color: #f8f9fa;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #dee2e6;
        }

        @media (max-width: 991px) {

            .main-content {
                margin-left: 0;
            }

        }

        #resultatsMedicaments {
            z-index: 1055;
            max-height: 300px;
            overflow-y: auto;
        }

    </style>

</head>


<body>

<div class="container-fluid p-0">

    <!-- =================================================
         SIDEBAR
    ================================================= -->

    <?php include 'sidebar.php'; ?>


    <!-- =================================================
         CONTENU PRINCIPAL
    ================================================= -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

       <header class="topbar">

            <div>

                <h5 class="mb-1">
                       <i class="fas fa-cart-shopping
                                  text-primary me-2"></i>
                    Vente
                </h5>

                <small>
                    Enregistrer une nouvelle vente
                </small>

            </div>
            <div class="ms-auto d-flex align-items-center gap-3">

                    <div class="topbar-right">
                        <a href="alertes.php" class="text-decoration-none">

                            <button class="icon-button">

                                <i class="fas fa-bell"></i>

                                <?php if ($nombreNotifications > 0): ?>

                                    <span>
                                        <?= $nombreNotifications ?>
                                    </span>

                                <?php endif; ?>

                            </button>

                        </a>
                    

                        <div class="user-profile">

                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>

                            <div>

                                <strong>
                                    <?= htmlspecialchars($_SESSION['nom_util'] ?? 'Utilisateur') ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                                </small>

                            </div>

                        </div>

                    </div>
                   

                </div>

        </header>


        <!-- =================================================
             CONTENU
        ================================================= -->

        <div class="container-fluid p-4">


            <!-- TITRE -->

            <div class="d-flex
                        justify-content-between
                        align-items-center
                        mb-4">

                <div>

                    <h3 class="fw-bold mb-1">

                        <i class="fas fa-cart-plus
                                  text-primary me-2"></i>

                        Nouvelle vente

                    </h3>

                    <p class="text-muted mb-0">

                        Ajoutez les médicaments vendus
                        et indiquez les quantités.

                    </p>

                </div>

            </div>


            <!-- =================================================
                 ALERTES
            ================================================= -->

            <?php if (isset($_GET['succes'])): ?>

                <div class="alert alert-success
                            alert-dismissible
                            fade show shadow-sm">

                    <i class="fas fa-circle-check me-2"></i>

                    Vente enregistrée avec succès.

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                    </button>

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['erreur'])): ?>

                <div class="alert alert-danger
                            alert-dismissible
                            fade show shadow-sm">

                    <i class="fas fa-circle-exclamation me-2"></i>

                    <?= htmlspecialchars(
                        $_GET['erreur']
                    ) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                    </button>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORMULAIRE
            ================================================= -->

            <form method="POST"
                  id="formVente">


                <!-- =================================================
                     INFORMATIONS DE LA VENTE
                ================================================= -->

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header
                                bg-white
                                border-bottom
                                py-3">

                        <h5 class="mb-0 fw-bold">

                            <i class="fas fa-receipt
                                      text-success me-2"></i>

                            Informations de la vente

                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row g-4">


                            <!-- CLIENT -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Client

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text
                                                 bg-light">

                                        <i class="fas fa-user
                                                  text-primary"></i>

                                    </span>

                                    <input type="text"
                                           class="form-control
                                                  bg-light"
                                           value="Client automatique"
                                           readonly>

                                </div>

                                <div class="form-text">

                                    Un numéro client sera
                                    automatiquement créé
                                    pour cette vente.

                                </div>

                            </div>


                            <!-- DATE -->

                            <div class="col-md-6">

                                <label for="date_vente"
                                       class="form-label fw-semibold">

                                    Date de vente

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input type="date"
                                       name="date_vente"
                                       id="date_vente"
                                       class="form-control"
                                       value="<?= date('Y-m-d') ?>"
                                       required>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     AJOUT MÉDICAMENT
                ================================================= -->

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header
                                bg-white
                                border-bottom
                                py-3">

                        <h5 class="mb-0 fw-bold">

                            <i class="fas fa-pills
                                      text-primary me-2"></i>

                            Ajouter un médicament

                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row g-3 align-items-end">


                            <!-- RECHERCHE -->

                            <div class="col-lg-6
                                        position-relative">

                                <label for="medicamentAjout"
                                       class="form-label fw-semibold">

                                    Médicament

                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">

                                        <i class="fas fa-search"></i>

                                    </span>

                                    <input type="text"
                                           id="medicamentAjout"
                                           class="form-control"
                                           autocomplete="off"
                                           placeholder="Nom ou code du médicament">

                                </div>


                                <!-- RÉSULTATS -->

                                <div id="resultatsMedicaments"
                                     class="list-group
                                            position-absolute
                                            w-100
                                            shadow
                                            d-none">

                                </div>

                            </div>


                            <!-- QUANTITÉ -->

                            <div class="col-lg-3">

                                <label for="quantiteAjout"
                                       class="form-label fw-semibold">

                                    Quantité

                                </label>

                                <input type="number"
                                       id="quantiteAjout"
                                       class="form-control"
                                       min="1"
                                       value="1">

                            </div>


                            <!-- BOUTON -->

                            <div class="col-lg-3">

                                <button type="button"
                                        class="btn btn-primary
                                               w-100"
                                        id="btnAjouterMedicament">

                                    <i class="fas fa-plus me-1"></i>

                                    Ajouter

                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     PANIER
                ================================================= -->

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header
                                bg-white
                                border-bottom
                                py-3">

                        <div class="d-flex
                                    justify-content-between
                                    align-items-center">

                            <h5 class="mb-0 fw-bold">

                                <i class="fas fa-shopping-cart
                                          text-success me-2"></i>

                                Médicaments de la vente

                            </h5>

                            <span class="badge bg-primary"
                                  id="nombreLignes">

                                0 médicament

                            </span>

                        </div>

                    </div>


                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table
                                          table-hover
                                          align-middle
                                          mb-0">

                                <thead class="table-light">

                                    <tr>

                                        <th class="px-3">
                                            Médicament
                                        </th>

                                        <th class="text-center">
                                            Prix unitaire
                                        </th>

                                        <th class="text-center">
                                            Stock
                                        </th>

                                        <th class="text-center">
                                            Quantité
                                        </th>

                                        <th class="text-end">
                                            Sous-total
                                        </th>

                                        <th class="text-center">
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody id="lignesVente">

                                    <tr id="ligneVide">

                                        <td colspan="6"
                                            class="text-center
                                                   text-muted
                                                   py-5">

                                            <i class="fas fa-cart-shopping
                                                      fa-2x
                                                      mb-3
                                                      d-block
                                                      opacity-50"></i>

                                            Aucun médicament
                                            ajouté à la vente.

                                        </td>

                                    </tr>

                                </tbody>


                                <tfoot class="table-light">

                                    <tr>

                                        <th colspan="4"
                                            class="text-end">

                                            TOTAL

                                        </th>

                                        <th id="totalVente"
                                            class="text-end
                                                   text-success
                                                   fs-5">

                                            0 FCFA

                                        </th>

                                        <th></th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     BOUTONS
                ================================================= -->

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex
                                    justify-content-end
                                    gap-2">

                            <a href="facture.php"
                               class="btn btn-outline-secondary">

                                <i class="fas fa-xmark me-1"></i>

                                Annuler

                            </a>


                            <button type="submit"
                                    class="btn btn-success">

                                <i class="fas fa-check me-1"></i>

                                Enregistrer la vente

                            </button>

                        </div>

                    </div>

                </div>


            </form>

        </div>

    </main>

</div>


<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script src="../vendor/js/bootstrap.bundle.min.js"></script>


<script>

/* =====================================================
   ÉLÉMENTS
===================================================== */

const medicamentAjout =
    document.getElementById('medicamentAjout');

const resultatsMedicaments =
    document.getElementById('resultatsMedicaments');

const quantiteAjout =
    document.getElementById('quantiteAjout');

const btnAjouterMedicament =
    document.getElementById('btnAjouterMedicament');

const lignesVente =
    document.getElementById('lignesVente');

const totalVente =
    document.getElementById('totalVente');

const formVente =
    document.getElementById('formVente');

const nombreLignes =
    document.getElementById('nombreLignes');


/* =====================================================
   PANIER
===================================================== */

let panier = [];


/* =====================================================
   MÉDICAMENT SÉLECTIONNÉ
===================================================== */

let medicamentSelectionne = null;


/* =====================================================
   TIMER RECHERCHE
===================================================== */

let timerRecherche = null;


/* =====================================================
   FORMAT MONTANT
===================================================== */

function formatMontant(montant) {

    return Number(montant)
        .toLocaleString('fr-FR') + ' FCFA';

}


/* =====================================================
   AUTOCOMPLÉTION
===================================================== */

medicamentAjout.addEventListener(
    'input',
    function () {

        const recherche =
            this.value.trim();

        medicamentSelectionne = null;

        clearTimeout(timerRecherche);


        if (recherche.length < 2) {

            resultatsMedicaments.innerHTML = '';

            resultatsMedicaments.classList.add('d-none');

            return;

        }


        timerRecherche = setTimeout(
            function () {

                fetch(
                    'vente.php?recherche_medicament=' +
                    encodeURIComponent(recherche)
                )

                .then(response => {

                    if (!response.ok) {

                        throw new Error(
                            'Erreur HTTP : ' +
                            response.status
                        );

                    }

                    return response.json();

                })

                .then(medicaments => {

                    resultatsMedicaments.innerHTML = '';


                    if (
                        !medicaments ||
                        medicaments.length === 0
                    ) {

                        resultatsMedicaments.innerHTML = `

                            <div class="list-group-item
                                        text-muted">

                                <i class="fas fa-search me-2"></i>

                                Aucun médicament trouvé

                            </div>

                        `;

                        resultatsMedicaments.classList.remove(
                            'd-none'
                        );

                        return;

                    }


                    resultatsMedicaments.classList.remove(
                        'd-none'
                    );


                    medicaments.forEach(
                        function (medicament) {

                            const element =
                                document.createElement(
                                    'button'
                                );

                            element.type = 'button';

                            element.className =
                                'list-group-item ' +
                                'list-group-item-action';


                            element.innerHTML = `

                                <div class="d-flex
                                            justify-content-between
                                            align-items-center">

                                    <div>

                                        <strong>
                                            ${medicament.nom}
                                        </strong>

                                        <div>
                                            <small class="text-muted">

                                                Code :
                                                ${medicament.code}

                                            </small>
                                        </div>

                                    </div>

                                    <div class="text-end">

                                        <span class="badge bg-success">

                                            ${Number(
                                                medicament.prix
                                            ).toLocaleString('fr-FR')}

                                            FCFA

                                        </span>

                                        <div>

                                            <small class="text-muted">

                                                Stock :
                                                ${medicament.stock}

                                            </small>

                                        </div>

                                    </div>

                                </div>

                            `;


                            element.addEventListener(
                                'click',
                                function () {

                                    medicamentSelectionne = {

                                        id:
                                            medicament.id,

                                        nom:
                                            medicament.nom,

                                        code:
                                            medicament.code,

                                        prix:
                                            parseFloat(
                                                medicament.prix
                                            ),

                                        stock:
                                            parseInt(
                                                medicament.stock
                                            )

                                    };


                                    medicamentAjout.value =
                                        medicament.nom;


                                    resultatsMedicaments.innerHTML =
                                        '';

                                    resultatsMedicaments.classList.add(
                                        'd-none'
                                    );


                                    quantiteAjout.focus();

                                }
                            );


                            resultatsMedicaments.appendChild(
                                element
                            );

                        }
                    );

                })

                .catch(error => {

                    console.error(
                        'Erreur recherche médicament :',
                        error
                    );


                    resultatsMedicaments.innerHTML = `

                        <div class="list-group-item
                                    text-danger">

                            <i class="fas fa-exclamation-circle
                                      me-2"></i>

                            Erreur lors de la recherche

                        </div>

                    `;


                    resultatsMedicaments.classList.remove(
                        'd-none'
                    );

                });

            },
            300
        );

    }
);


/* =====================================================
   AFFICHER PANIER
===================================================== */

function afficherPanier() {

    lignesVente.innerHTML = '';


    if (panier.length === 0) {

        lignesVente.innerHTML = `

            <tr>

                <td colspan="6"
                    class="text-center
                           text-muted
                           py-5">

                    <i class="fas fa-cart-shopping
                              fa-2x
                              mb-3
                              d-block
                              opacity-50"></i>

                    Aucun médicament
                    ajouté à la vente.

                </td>

            </tr>

        `;


        totalVente.textContent =
            '0 FCFA';


        nombreLignes.textContent =
            '0 médicament';


        return;

    }


    let total = 0;


    panier.forEach(
        function (ligne, index) {

            const sousTotal =
                ligne.prix *
                ligne.quantite;


            total += sousTotal;


            lignesVente.innerHTML += `

                <tr>

                    <td class="px-3">

                        <div class="fw-bold">
                            ${ligne.nom}
                        </div>

                        <small class="text-muted">
                            Code : ${ligne.code}
                        </small>


                        <input type="hidden"
                               name="id_medicament[]"
                               value="${ligne.id}">

                        <input type="hidden"
                               name="quantite[]"
                               value="${ligne.quantite}">

                    </td>


                    <td class="text-center">

                        ${formatMontant(
                            ligne.prix
                        )}

                    </td>


                    <td class="text-center">

                        <span class="badge bg-info text-dark">

                            ${ligne.stock}

                        </span>

                    </td>


                    <td class="text-center">

                        <span class="badge bg-primary">

                            ${ligne.quantite}

                        </span>

                    </td>


                    <td class="text-end fw-semibold">

                        ${formatMontant(
                            sousTotal
                        )}

                    </td>


                    <td class="text-center">

                        <button type="button"
                                class="btn btn-sm
                                       btn-outline-danger"
                                onclick="supprimerLigne(${index})">

                            <i class="fas fa-trash"></i>

                        </button>

                    </td>

                </tr>

            `;

        }
    );


    totalVente.textContent =
        formatMontant(total);


    nombreLignes.textContent =
        panier.length +
        (
            panier.length > 1
            ? ' médicaments'
            : ' médicament'
        );

}


/* =====================================================
   SUPPRIMER LIGNE
===================================================== */

function supprimerLigne(index) {

    panier.splice(index, 1);

    afficherPanier();

}


/* =====================================================
   AJOUT MÉDICAMENT
===================================================== */

btnAjouterMedicament.addEventListener(
    'click',
    function () {

        const medicament =
            medicamentSelectionne;

        const quantite =
            parseInt(
                quantiteAjout.value || 0
            );


        if (!medicament) {

            alert(
                'Veuillez sélectionner un médicament dans les suggestions.'
            );

            medicamentAjout.focus();

            return;

        }


        if (quantite <= 0) {

            alert(
                'La quantité doit être supérieure à zéro.'
            );

            quantiteAjout.focus();

            return;

        }


        if (quantite > medicament.stock) {

            alert(
                'Stock insuffisant. Stock disponible : ' +
                medicament.stock
            );

            return;

        }


        const indexExistant =
            panier.findIndex(
                function (ligne) {

                    return ligne.id ==
                           medicament.id;

                }
            );


        if (indexExistant !== -1) {

            const nouvelleQuantite =
                panier[indexExistant].quantite +
                quantite;


            if (
                nouvelleQuantite >
                medicament.stock
            ) {

                alert(
                    'La quantité totale demandée dépasse le stock disponible : ' +
                    medicament.stock
                );

                return;

            }


            panier[indexExistant].quantite =
                nouvelleQuantite;

        } else {

            panier.push({

                id:
                    medicament.id,

                nom:
                    medicament.nom,

                code:
                    medicament.code,

                prix:
                    medicament.prix,

                stock:
                    medicament.stock,

                quantite:
                    quantite

            });

        }


        medicamentAjout.value = '';

        medicamentSelectionne = null;

        quantiteAjout.value = 1;

        resultatsMedicaments.innerHTML = '';

        resultatsMedicaments.classList.add(
            'd-none'
        );


        afficherPanier();

        medicamentAjout.focus();

    }
);


/* =====================================================
   ENTRÉE CLAVIER
===================================================== */

medicamentAjout.addEventListener(
    'keydown',
    function (event) {

        if (event.key === 'Enter') {

            event.preventDefault();

            btnAjouterMedicament.click();

        }

    }
);


/* =====================================================
   VALIDATION FORMULAIRE
===================================================== */

formVente.addEventListener(
    'submit',
    function (event) {

        if (panier.length === 0) {

            event.preventDefault();

            alert(
                'Ajoutez au moins un médicament à la vente.'
            );

            return;

        }

    }
);


/* =====================================================
   FERMER LES SUGGESTIONS
===================================================== */

document.addEventListener(
    'click',
    function (event) {

        if (
            !medicamentAjout.contains(event.target) &&
            !resultatsMedicaments.contains(event.target)
        ) {

            resultatsMedicaments.classList.add(
                'd-none'
            );

        }

    }
);

</script>

</body>

</html>