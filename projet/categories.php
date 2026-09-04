<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catégories | PharmaStock</title>

    <link rel="stylesheet" href="../vendor/css/bootstrap.min.css">
    <link rel="stylesheet" href="../vendor/fontawesome/css/all.css">
    <link rel="stylesheet" href="./style.css">
</head>

<body class="dashboard-page">

    <!-- MENU LATERAL -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <img src="logopharm.png" alt="Logo PharmaStock" class="sidebar-logo">

            <div>
                <h1>PharmaStock</h1>
                <span>Gestion de pharmacie</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <p class="nav-title">MENU PRINCIPAL</p>

            <a href="tableau de bord.html" class="nav-link">
                <i class="fa-solid fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>

            <a href="medicaments.html" class="nav-link">
                <i class="fa-solid fa-pills"></i>
                <span>Médicaments</span>
            </a>

            <a href="categories.html" class="nav-link active">
                <i class="fa-solid fa-layer-group"></i>
                <span>Catégories</span>
            </a>

            <a href="fournisseurs.html" class="nav-link">
                <i class="fa-solid fa-truck-medical"></i>
                <span>Fournisseurs</span>
            </a>

            <p class="nav-title mt-4">GESTION DU STOCK</p>

            <a href="entrees.html" class="nav-link">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>Entrées de stock</span>
            </a>

            <a href="sorties.html" class="nav-link">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Sorties de stock</span>
            </a>

            <a href="stock.html" class="nav-link">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>État du stock</span>
            </a>

            <a href="mouvements.html" class="nav-link">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Historique</span>
            </a>

            <p class="nav-title mt-4">SUIVI</p>

            <a href="alertes.html" class="nav-link">
                <i class="fa-solid fa-bell"></i>
                <span>Alertes</span>
                <span class="badge badge-alert ms-auto">5</span>
            </a>

            <a href="rapports.html" class="nav-link">
                <i class="fa-solid fa-file-lines"></i>
                <span>Rapports</span>
            </a>

            <p class="nav-title mt-4">ADMINISTRATION</p>

            <a href="utilisateurs.html" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Utilisateurs</span>
            </a>

            <a href="profil.html" class="nav-link">
                <i class="fa-solid fa-user-gear"></i>
                <span>Mon profil</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <a href="login.html" class="nav-link logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Se déconnecter</span>
            </a>
        </div>

    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <!-- ENTETE -->
        <header class="topbar">

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light border d-lg-none" id="btnMenu" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <h2 class="page-title mb-0">Catégories</h2>
                    <p class="page-subtitle mb-0">
                        Organisez les médicaments par famille thérapeutique
                    </p>
                </div>
            </div>

            <div class="topbar-actions">

                <button class="btn btn-light border position-relative" type="button" title="Alertes">
                    <i class="fa-regular fa-bell"></i>
                    <span class="notification-dot"></span>
                </button>

                <div class="dropdown">
                    <button class="btn user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <span class="user-avatar">MF</span>

                        <span class="d-none d-md-inline text-start">
                            <strong class="d-block">Merveille Megne</strong>
                            <small>Administrateur</small>
                        </span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li>
                            <a class="dropdown-item" href="profil.html">
                                <i class="fa-solid fa-user me-2"></i>
                                Mon profil
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item text-danger" href="login.html">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>
                                Se déconnecter
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </header>

        <section class="content-wrapper">

            <!-- TITRE + BOUTON -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

                <div>
                    <h3 class="section-title mb-1">Gestion des catégories</h3>
                    <p class="text-muted mb-0">
                        Créez et organisez les familles de médicaments.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCategorie">
                    <i class="fa-solid fa-plus me-2"></i>
                    Ajouter une catégorie
                </button>

            </div>

            <!-- RESUME -->
            <div class="row g-3 mb-4">

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="mini-stat-card">
                        <span class="mini-stat-icon mini-blue">
                            <i class="fa-solid fa-layer-group"></i>
                        </span>

                        <div>
                            <small>Total catégories</small>
                            <strong>8</strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="mini-stat-card">
                        <span class="mini-stat-icon mini-green">
                            <i class="fa-solid fa-pills"></i>
                        </span>

                        <div>
                            <small>Médicaments classés</small>
                            <strong>50</strong>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="mini-stat-card">
                        <span class="mini-stat-icon mini-orange">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>

                        <div>
                            <small>Catégories à surveiller</small>
                            <strong>2</strong>
                        </div>
                    </div>
                </div>

            </div>

            <!-- TABLEAU DES CATEGORIES -->
            <section class="dashboard-card">

                <div class="card-header-custom flex-column align-items-stretch">

                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                        <div>
                            <h3>Liste des catégories</h3>
                            <p>Les catégories disponibles dans le système</p>
                        </div>

                        <div class="input-group categorie-search">
                            <span class="input-group-text">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>

                            <input
                                type="search"
                                class="form-control"
                                id="rechercheCategorie"
                                placeholder="Rechercher une catégorie...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0" id="tableCategories">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th>Nombre de médicaments</th>
                                <th>Date de création</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td><strong>01</strong></td>
                                <td>
                                    <span class="categorie-icon categorie-blue">
                                        <i class="fa-solid fa-tablets"></i>
                                    </span>
                                    <strong class="ms-2">Antalgique</strong>
                                </td>
                                <td>Médicaments contre la douleur et la fièvre.</td>
                                <td><span class="badge badge-stock-normal">12 médicaments</span></td>
                                <td>01/08/2026</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Modifier"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCategorie">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer"
                                        onclick="confirmerSuppression('Antalgique')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td><strong>02</strong></td>
                                <td>
                                    <span class="categorie-icon categorie-green">
                                        <i class="fa-solid fa-capsules"></i>
                                    </span>
                                    <strong class="ms-2">Antibiotique</strong>
                                </td>
                                <td>Médicaments utilisés contre les infections bactériennes.</td>
                                <td><span class="badge badge-stock-normal">10 médicaments</span></td>
                                <td>01/08/2026</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Modifier"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCategorie">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer"
                                        onclick="confirmerSuppression('Antibiotique')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td><strong>03</strong></td>
                                <td>
                                    <span class="categorie-icon categorie-purple">
                                        <i class="fa-solid fa-mosquito"></i>
                                    </span>
                                    <strong class="ms-2">Antipaludéen</strong>
                                </td>
                                <td>Médicaments destinés à la prévention et au traitement du paludisme.</td>
                                <td><span class="badge badge-stock-normal">7 médicaments</span></td>
                                <td>02/08/2026</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Modifier"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCategorie">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer"
                                        onclick="confirmerSuppression('Antipaludéen')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td><strong>04</strong></td>
                                <td>
                                    <span class="categorie-icon categorie-orange">
                                        <i class="fa-solid fa-heart-pulse"></i>
                                    </span>
                                    <strong class="ms-2">Cardiologie</strong>
                                </td>
                                <td>Médicaments utilisés dans le traitement des maladies cardiovasculaires.</td>
                                <td><span class="badge badge-stock-normal">6 médicaments</span></td>
                                <td>02/08/2026</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Modifier"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCategorie">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer"
                                        onclick="confirmerSuppression('Cardiologie')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td><strong>05</strong></td>
                                <td>
                                    <span class="categorie-icon categorie-yellow">
                                        <i class="fa-solid fa-sun"></i>
                                    </span>
                                    <strong class="ms-2">Vitamine</strong>
                                </td>
                                <td>Vitamines et compléments alimentaires.</td>
                                <td><span class="badge badge-stock-normal">8 médicaments</span></td>
                                <td>03/08/2026</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Modifier"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalCategorie">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Supprimer"
                                        onclick="confirmerSuppression('Vitamine')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white p-3">
                    <small class="text-muted" id="nombreCategories">
                        Affichage de 5 catégorie(s)
                    </small>
                </div>

            </section>
        </section>
    </main>

    <!-- MODAL AJOUT / MODIFICATION -->
    <div
        class="modal fade"
        id="modalCategorie"
        tabindex="-1"
        aria-labelledby="titreModalCategorie"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title" id="titreModalCategorie">
                        <i class="fa-solid fa-layer-group text-primary me-2"></i>
                        Ajouter une catégorie
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fermer">
                    </button>
                </div>

                <form id="formCategorie">

                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="nomCategorie" class="form-label fw-semibold">
                                Nom de la catégorie <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nomCategorie"
                                placeholder="Ex. Dermatologie"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="descriptionCategorie" class="form-label fw-semibold">
                                Description
                            </label>

                            <textarea
                                class="form-control"
                                id="descriptionCategorie"
                                rows="4"
                                placeholder="Décrivez brièvement cette catégorie..."></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            Annuler
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            Enregistrer
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="../vendor/js/bootstrap.bundle.min.js"></script>

    <script>
        const sidebar = document.getElementById("sidebar");
        const btnMenu = document.getElementById("btnMenu");
        const rechercheCategorie = document.getElementById("rechercheCategorie");
        const lignesCategories = document.querySelectorAll("#tableCategories tbody tr");
        const nombreCategories = document.getElementById("nombreCategories");
        const formCategorie = document.getElementById("formCategorie");

        btnMenu.addEventListener("click", function () {
            sidebar.classList.toggle("show-sidebar");
        });

        rechercheCategorie.addEventListener("input", function () {
            const recherche = this.value.toLowerCase().trim();
            let totalVisible = 0;

            lignesCategories.forEach(function (ligne) {
                const texte = ligne.textContent.toLowerCase();
                const visible = texte.includes(recherche);

                ligne.style.display = visible ? "" : "none";

                if (visible) {
                    totalVisible++;
                }
            });

            nombreCategories.textContent = "Affichage de " + totalVisible + " catégorie(s)";
        });

        function confirmerSuppression(nomCategorie) {
            const confirmation = confirm(
                "Voulez-vous vraiment supprimer la catégorie : " + nomCategorie + " ?"
            );

            if (confirmation) {
                alert("Suppression simulée de la catégorie : " + nomCategorie);
            }
        }

        formCategorie.addEventListener("submit", function (event) {
            event.preventDefault();

            alert("Catégorie enregistrée avec succès (simulation frontend).");

            const modalElement = document.getElementById("modalCategorie");
            const modal = bootstrap.Modal.getInstance(modalElement);

            modal.hide();
            formCategorie.reset();
        });
    </script>

</body>
</html>