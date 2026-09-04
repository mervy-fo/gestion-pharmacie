<?php
require_once 'auth.php';
require_once 'connex.php';

/* =====================================================
   FILTRES
===================================================== */

$recherche = trim($_GET['recherche'] ?? '');
$nature = trim($_GET['nature'] ?? '');
$dateDebut = trim($_GET['date_debut'] ?? '');
$dateFin = trim($_GET['date_fin'] ?? '');


/* =====================================================
   REQUÊTE DES MOUVEMENTS
===================================================== */

$sqlMouvements = "
    SELECT
        ms.id_mouv,
        ms.nature,
        ms.quantite,
        ms.date_mouv,
        ms.reference,
        ms.motif,
        m.nom AS nom_medicament,
        m.code AS code_medicament
    FROM mouvement_stock ms
    INNER JOIN medicament m
        ON ms.id_medicament = m.id_medicament
    WHERE 1=1
";

$params = [];
$types = "";


/* =====================================================
   RECHERCHE TEXTE
===================================================== */

if ($recherche !== '') {

    $sqlMouvements .= "
        AND (
            m.nom LIKE ?
            OR m.code LIKE ?
            OR ms.reference LIKE ?
            OR ms.motif LIKE ?
        )
    ";

    $rechercheSQL = "%" . $recherche . "%";

    $params[] = $rechercheSQL;
    $params[] = $rechercheSQL;
    $params[] = $rechercheSQL;
    $params[] = $rechercheSQL;

    $types .= "ssss";
}


/* =====================================================
   FILTRE NATURE
===================================================== */

if ($nature !== '') {

    $sqlMouvements .= "
        AND ms.nature = ?
    ";

    $params[] = $nature;
    $types .= "s";
}


/* =====================================================
   FILTRE DATE DEBUT
===================================================== */

if ($dateDebut !== '') {

    $sqlMouvements .= "
        AND ms.date_mouv >= ?
    ";

    $params[] = $dateDebut;
    $types .= "s";
}


/* =====================================================
   FILTRE DATE FIN
===================================================== */

if ($dateFin !== '') {

    $sqlMouvements .= "
        AND ms.date_mouv <= ?
    ";

    $params[] = $dateFin;
    $types .= "s";
}


/* =====================================================
   TRI
===================================================== */

$sqlMouvements .= "
    ORDER BY ms.date_mouv DESC, ms.id_mouv DESC
";


/* =====================================================
   PRÉPARATION
===================================================== */

$stmtMouvements = mysqli_prepare(
    $conn,
    $sqlMouvements
);

if (!$stmtMouvements) {

    die(
        "Erreur préparation mouvements : " .
        mysqli_error($conn)
    );

}


/* =====================================================
   PARAMÈTRES
===================================================== */

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmtMouvements,
        $types,
        ...$params
    );

}


/* =====================================================
   EXÉCUTION
===================================================== */

if (!mysqli_stmt_execute($stmtMouvements)) {

    die(
        "Erreur exécution mouvements : " .
        mysqli_stmt_error($stmtMouvements)
    );

}


$resultMouvements = mysqli_stmt_get_result(
    $stmtMouvements
);


/* =====================================================
   STATISTIQUES
===================================================== */

$totalMouvements = 0;
$totalEntrees = 0;
$totalSorties = 0;

$listeMouvements = [];


while ($mouvement = mysqli_fetch_assoc($resultMouvements)) {

    $listeMouvements[] = $mouvement;

    $totalMouvements++;

    /* Normalisation de la nature */
    $natureNormalisee = strtoupper(
        trim($mouvement['nature'])
    );

    $natureNormalisee = strtr(
        $natureNormalisee,
        [
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'À' => 'A',
            'Â' => 'A',
            'Ä' => 'A',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Î' => 'I',
            'Ï' => 'I',
            'Ô' => 'O',
            'Ö' => 'O',
            'Ç' => 'C'
        ]
    );

    if ($natureNormalisee === 'ENTREE') {

        $totalEntrees += (int) $mouvement['quantite'];

    } elseif ($natureNormalisee === 'SORTIE') {

        $totalSorties += (int) $mouvement['quantite'];

    }
}


?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Mouvements de stock - Pharmacie</title>


    <!-- Bootstrap -->

    <link rel="stylesheet"
          href="../vendor/css/bootstrap.min.css">


    <!-- Font Awesome -->

    <link rel="stylesheet"
          href="../vendor/fontawesome/css/all.css">


    <!-- CSS du projet -->

    <link rel="stylesheet"
          href="./style.css">

</head>


<body>


<div class="container-fluid">


    <!-- =====================================================
         SIDEBAR
    ===================================================== -->

    <?php include 'sidebar.php'; ?>


    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================= -->

        <header class="topbar">

            <div>

                <h5 class="mb-1">
                    Mouvements de stock
                </h5>

                <small>
                    Suivi des entrées et sorties de médicaments
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

        <div class="content">


            <!-- =================================================
                 TITRE
            ================================================= -->

            <div class="page-header">

                <div>

                    <h3>

                        <i class="fas fa-arrow-right-arrow-left me-2 text-primary"></i>

                        Mouvements de stock

                    </h3>

                    <p class="text-muted mb-0">

                        Consultez l'historique des mouvements de stock.

                    </p>

                </div>


                <div>

                    <a href="mouvement_stock.php"
                       class="btn btn-primary">

                        <i class="fas fa-rotate me-1"></i>

                        Actualiser

                    </a>

                </div>

            </div>



            <!-- =================================================
                 STATISTIQUES
            ================================================= -->

            <div class="row g-3 mb-4">


                <!-- TOTAL -->

                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <small class="text-muted">
                                        Mouvements
                                    </small>

                                    <h3 class="fw-bold mb-0">
                                        <?= $totalMouvements ?>
                                    </h3>

                                </div>

                                <div class="fs-2 text-primary">

                                    <i class="fas fa-right-left"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ENTREES -->

                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <small class="text-muted">
                                        Quantité entrée
                                    </small>

                                    <h3 class="fw-bold mb-0 text-success">
                                        +<?= $totalEntrees ?>
                                    </h3>

                                </div>

                                <div class="fs-2 text-success">

                                    <i class="fas fa-arrow-down"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SORTIES -->

                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <small class="text-muted">
                                        Quantité sortie
                                    </small>

                                    <h3 class="fw-bold mb-0 text-danger">
                                        -<?= $totalSorties ?>
                                    </h3>

                                </div>

                                <div class="fs-2 text-danger">

                                    <i class="fas fa-arrow-up"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 FILTRES
            ================================================= -->

            <div class="card border-0 shadow-sm mb-4">


                <div class="card-header bg-white py-3">

                    <h5 class="mb-0 fw-bold">

                        <i class="fas fa-filter me-2 text-primary"></i>

                        Rechercher et filtrer

                    </h5>

                </div>


                <div class="card-body">


                    <form method="GET">


                        <div class="row g-3 align-items-end">


                            <!-- RECHERCHE -->

                            <div class="col-md-4">

                                <label class="form-label">
                                    Recherche
                                </label>

                                <div class="input-group">

                                    <button
                                        type=" button" 
                                        id="btnRechercher"
                                        class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </button>

                                    <input
                                        type="text"
                                        name="recherche"
                                        id="rechercheMouvement"
                                        class="form-control"
                                        value="<?= htmlspecialchars($recherche) ?>"
                                        placeholder="Médicament, code, référence..."
                                        autocomplete="off">

                                </div>

                            </div>


                            <!-- NATURE -->

                            <div class="col-md-2">

                                <label class="form-label">
                                    Nature
                                </label>

                                <select
                                    name="nature"
                                    id="natureMouvement"
                                    class="form-select">

                                    <option value="">
                                        Toutes
                                    </option>

                                    <option value="ENTREE"
                                        <?= $nature === 'ENTREE' ? 'selected' : '' ?>>

                                        Entrée

                                    </option>

                                    <option value="SORTIE"
                                        <?= $nature === 'SORTIE' ? 'selected' : '' ?>>

                                        Sortie

                                    </option>

                                </select>

                            </div>


                            <!-- DATE DEBUT -->

                            <div class="col-md-2">

                                <label class="form-label">
                                    Du
                                </label>

                                <input
                                    type="date"
                                    name="date_debut"
                                    id="dateDebut"
                                    class="form-control"
                                    value="<?= htmlspecialchars($dateDebut) ?>">

                            </div>


                            <!-- DATE FIN -->

                            <div class="col-md-2">

                                <label class="form-label">
                                    Au
                                </label>

                                <input
                                    type="date"
                                    name="date_fin"
                                    id="dateFin"
                                    class="form-control"
                                    value="<?= htmlspecialchars($dateFin) ?>">

                            </div>


                            <!-- BOUTONS -->

                            <div class="col-md-2">
                                <button
                                    type="button"
                                    id="btnReset"
                                    class="btn btn-outline-secondary w-100">

                                    <i class="fas fa-rotate-left me-1"></i>
                                    Réinitialiser

                                </button>
                            </div>


                        </div>


                    </form>

                </div>

            </div>



            <!-- =================================================
                 TABLEAU
            ================================================= -->

            <div class="card border-0 shadow-sm">


                <div class="card-header bg-white py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <h5 class="mb-0 fw-bold">

                            <i class="fas fa-clock-rotate-left me-2 text-primary"></i>

                            Historique des mouvements

                        </h5>


                        <span class="badge bg-primary">

                            <?= count($listeMouvements) ?>
                            mouvement(s)

                        </span>

                    </div>

                </div>


                <div class="card-body p-0">


                    <div class="table-responsive">


                        <table class="table table-hover table-bordered align-middle mb-0">


                            <thead class="table-light">

                                <tr>

                                    <th class="text-center">
                                        #
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Médicament
                                    </th>

                                    <th>
                                        Code
                                    </th>

                                    <th class="text-center">
                                        Nature
                                    </th>

                                    <th class="text-center">
                                        Quantité
                                    </th>

                                    <th>
                                        Référence
                                    </th>

                                    <th>
                                        Motif
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="tableauMouvements">


                            <?php if (empty($listeMouvements)): ?>

                                <tr>

                                    <td colspan="8"
                                        class="text-center py-5 text-muted">

                                        <i class="fas fa-box-open fa-2x mb-3"></i>

                                        <br>

                                        Aucun mouvement de stock trouvé.

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($listeMouvements as $index => $mouvement): ?>


                                    <?php

                                        $natureMouvement = strtoupper(
                                            trim($mouvement['nature'])
                                        );

                                        /* Supprimer les accents */
                                        $natureMouvement = strtr(
                                            $natureMouvement,
                                            [
                                                'É' => 'E',
                                                'È' => 'E',
                                                'Ê' => 'E',
                                                'Ë' => 'E',
                                                'À' => 'A',
                                                'Â' => 'A',
                                                'Ä' => 'A',
                                                'Ù' => 'U',
                                                'Û' => 'U',
                                                'Ü' => 'U',
                                                'Î' => 'I',
                                                'Ï' => 'I',
                                                'Ô' => 'O',
                                                'Ö' => 'O',
                                                'Ç' => 'C'
                                            ]
                                        );

                                    ?>


                                    <tr>


                                        <!-- NUMERO -->

                                        <td class="text-center text-muted">

                                            <?= $index + 1 ?>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $mouvement['date_mouv']
                                                )
                                            ) ?>

                                        </td>


                                        <!-- MEDICAMENT -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $mouvement['nom_medicament']
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- CODE -->

                                        <td>

                                            <span class="text-muted">

                                                <?= htmlspecialchars(
                                                    $mouvement['code_medicament']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- NATURE -->

                                        <td class="text-center">


                                            <?php if ($natureMouvement === 'ENTREE'): ?>

                                                <span class="badge bg-success">

                                                    <i class="fas fa-arrow-down me-1"></i>

                                                    ENTRÉE

                                                </span>


                                            <?php elseif ($natureMouvement === 'SORTIE'): ?>

                                                <span class="badge bg-danger">

                                                    <i class="fas fa-arrow-up me-1"></i>

                                                    SORTIE

                                                </span>


                                            <?php else: ?>

                                                <span class="badge bg-secondary">

                                                    <?= htmlspecialchars(
                                                        $mouvement['nature']
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>


                                        </td>


                                        <!-- QUANTITE -->

                                        <td class="text-center fw-bold">


                                            <?php if ($natureMouvement === 'ENTREE'): ?>

                                                <span class="text-success">

                                                    +<?= (int) $mouvement['quantite'] ?>

                                                </span>


                                            <?php elseif ($natureMouvement === 'SORTIE'): ?>

                                                <span class="text-danger">

                                                    -<?= (int) $mouvement['quantite'] ?>

                                                </span>


                                            <?php else: ?>

                                                <?= (int) $mouvement['quantite'] ?>

                                            <?php endif; ?>


                                        </td>


                                        <!-- REFERENCE -->

                                        <td>

                                            <span class="badge bg-light text-dark border">

                                                <?= htmlspecialchars(
                                                    $mouvement['reference']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- MOTIF -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $mouvement['motif']
                                            ) ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>


                        </table>

                    </div>

                </div>

            </div>


        </div>

    </main>

</div>


<script src="../vendor/js/bootstrap.bundle.min.js"></script>
<script>

/* =====================================================
   RÉCUPÉRATION DES ÉLÉMENTS
===================================================== */

const rechercheMouvement =
    document.getElementById('rechercheMouvement');

const natureMouvement =
    document.getElementById('natureMouvement');

const dateDebut =
    document.getElementById('dateDebut');

const dateFin =
    document.getElementById('dateFin');

const btnRechercher =
    document.getElementById('btnRechercher');

const btnReset =
    document.getElementById('btnReset');

const tableauMouvements =
    document.getElementById('tableauMouvements');


/* =====================================================
   FONCTION DE RECHERCHE
===================================================== */

function rechercherMouvements() {

    const recherche =
        rechercheMouvement.value.trim();

    const nature =
        natureMouvement.value;

    const debut =
        dateDebut.value;

    const fin =
        dateFin.value;


    /* =================================================
       VÉRIFICATION DES DATES
    ================================================= */

    if (debut !== '' && fin !== '' && debut > fin) {

        alert(
            'La date de début doit être antérieure ou égale à la date de fin.'
        );

        return;
    }


    /* =================================================
       CONSTRUCTION DES PARAMÈTRES
    ================================================= */

    const params =
        new URLSearchParams();

    if (recherche !== '') {

        params.set(
            'recherche',
            recherche
        );

    }

    if (nature !== '') {

        params.set(
            'nature',
            nature
        );

    }

    if (debut !== '') {

        params.set(
            'date_debut',
            debut
        );

    }

    if (fin !== '') {

        params.set(
            'date_fin',
            fin
        );

    }


    /* =================================================
       REQUÊTE AJAX
    ================================================= */

    fetch(
        'mouvement_stock.php?' +
        params.toString()
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Erreur HTTP : ' +
                response.status
            );

        }

        return response.text();

    })

    .then(html => {

        const parser =
            new DOMParser();

        const documentHTML =
            parser.parseFromString(
                html,
                'text/html'
            );


        const nouveauTableau =
            documentHTML.getElementById(
                'tableauMouvements'
            );


        if (nouveauTableau) {

            tableauMouvements.innerHTML =
                nouveauTableau.innerHTML;

        }

    })

    .catch(error => {

        console.error(
            'Erreur recherche :',
            error
        );

    });

}


/* =====================================================
   RECHERCHE AUTOMATIQUE
   LORSQUE L'UTILISATEUR TAPE
===================================================== */

let timerRecherche = null;

rechercheMouvement.addEventListener(
    'input',
    function () {

        clearTimeout(
            timerRecherche
        );

        timerRecherche =
            setTimeout(
                function () {

                    rechercherMouvements();

                },
                300
            );

    }
);


/* =====================================================
   BOUTON RECHERCHER
===================================================== */

btnRechercher.addEventListener(
    'click',
    function () {

        rechercherMouvements();

    }
);


/* =====================================================
   CHANGEMENT DE NATURE
   → ACTUALISATION AUTOMATIQUE
===================================================== */

natureMouvement.addEventListener(
    'change',
    function () {

        rechercherMouvements();

    }
);


/* =====================================================
   CHANGEMENT DATE DEBUT
   → ACTUALISATION AUTOMATIQUE
===================================================== */

dateDebut.addEventListener(
    'change',
    function () {

        rechercherMouvements();

    }
);


/* =====================================================
   CHANGEMENT DATE FIN
   → ACTUALISATION AUTOMATIQUE
===================================================== */

dateFin.addEventListener(
    'change',
    function () {

        rechercherMouvements();

    }
);


/* =====================================================
   RÉINITIALISATION
===================================================== */

btnReset.addEventListener(
    'click',
    function () {

        rechercheMouvement.value = '';

        natureMouvement.value = '';

        dateDebut.value = '';

        dateFin.value = '';

        rechercherMouvements();

    }
);

</script>



</body>

</html>