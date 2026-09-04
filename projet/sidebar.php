<?php
require_once 'protection.php';


/* ==========================================
   PAGE ACTUELLE
   ========================================== */

$page = basename($_SERVER['PHP_SELF']);


/* ==========================================
   CONNEXION À LA BASE DE DONNÉES
   ========================================== */

require_once 'connex.php';


/* ==========================================
   NOMBRE DE NOTIFICATIONS
   ========================================== */

$sqlNotifications = "
    SELECT COUNT(*) AS total
    FROM medicament
    WHERE

        /* Rupture de stock */
        quantite_restante = 0

        /* Stock faible */
        OR (
            quantite_restante > 0
            AND quantite_restante <= seuil_minimum
        )

        /* Médicament périmé */
        OR (
            date_peremption IS NOT NULL
            AND date_peremption < CURDATE()
        )

        /* Péremption dans les 30 jours */
        OR (
            date_peremption IS NOT NULL
            AND date_peremption >= CURDATE()
            AND date_peremption <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        )

        /* Péremption entre 31 et 90 jours */
        OR (
            date_peremption IS NOT NULL
            AND date_peremption > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND date_peremption <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        )
";

$resultNotifications = mysqli_query($conn, $sqlNotifications);

if (!$resultNotifications) {
    die("Erreur notifications : " . mysqli_error($conn));
}

$nombreNotifications = mysqli_fetch_assoc(
    $resultNotifications
)['total'];

?>

<aside class="sidebar">

    <div class="sidebar-logo">

        <div class="logo-icon">

            <img
                src="logopharm.png"
                class="img-fluid rounded"
                style="width: 100px;"
                alt=""
            >

        </div>

        <div>

            <h4>PharmaStock</h4>

            <small>
                Gestion pharmacie
            </small>

        </div>

    </div>


    <nav class="sidebar-menu">


        <!-- TABLEAU DE BORD -->

        <a href="tableau de bord.php"
           class="menu-item <?= $page === 'tableau de bord.php' ? 'active' : '' ?>">

            <i class="fas fa-home"></i>

            <span>
                Tableau de bord
            </span>

        </a>


        <!-- MÉDICAMENTS -->

        <a href="medicaments.php"
           class="menu-item <?= $page === 'medicaments.php' ? 'active' : '' ?>">

            <i class="fas fa-pills"></i>

            <span>
                Médicaments
            </span>

        </a>


        <!-- STOCKS -->

        <a href="stocks.php"
           class="menu-item <?= $page === 'stocks.php' ? 'active' : '' ?>">

            <i class="fas fa-boxes-stacked"></i>

            <span>
                Stocks
            </span>

        </a>


        <!-- FOURNISSEURS -->

        <a href="fournisseurs.php"
           class="menu-item <?= $page === 'fournisseurs.php' ? 'active' : '' ?>">

            <i class="fas fa-truck"></i>

            <span>
                Fournisseurs
            </span>

        </a>


        <!-- MOUVEMENTS -->

        <a href="mouvement_stock.php"
           class="menu-item <?= $page === 'mouvements.php' ? 'active' : '' ?>">

            <i class="fas fa-exchange-alt"></i>

            <span>
                Mouvements
            </span>

        </a>


        <!-- VENTES -->

        <a href="vente.php"
           class="menu-item <?= $page === 'ventes.php' ? 'active' : '' ?>">

            <i class="fas fa-shopping-cart"></i>

            <span>
                Ventes
            </span>

        </a>


        <!-- CLIENTS -->

        <a href="clients.php"
           class="menu-item <?= $page === 'clients.php' ? 'active' : '' ?>">

            <i class="fas fa-users"></i>

            <span>
                Clients
            </span>

        </a>


        <!-- ALERTES -->

        <a href="alertes.php"
           class="menu-item <?= $page === 'alertes.php' ? 'active' : '' ?>">

            <i class="fas fa-bell"></i>

            <span>
                Alertes
            </span>

            <?php if ($nombreNotifications > 0): ?>

                <span class="notification-badge">
                    <?= $nombreNotifications ?>
                </span>

            <?php endif; ?>

        </a>


        <!-- RAPPORTS -->

        <a href="rapports.php"
           class="menu-item <?= $page === 'rapports.php' ? 'active' : '' ?>">

            <i class="fas fa-chart-bar"></i>

            <span>
                Rapports
            </span>

        </a>


        <!-- UTILISATEURS -->
        <?php if (aPermission("utilisateurs")): ?>
            <a href="utilisateurs.php"
            class="menu-item <?= $page === 'utilisateurs.php' ? 'active' : '' ?>">

                <i class="fas fa-user-cog"></i>

                <span>
                    Utilisateurs
                </span>

            </a>
        <?php endif; ?>
    </nav>


    <!-- BAS DE LA SIDEBAR -->

    <div class="sidebar-bottom">


        <!-- PROFIL -->

        <a href="profil.php"
           class="menu-item <?= $page === 'profil.php' ? 'active' : '' ?>">

            <i class="fas fa-user"></i>

            <span>
                Mon profil
            </span>

        </a>


        <!-- DÉCONNEXION -->

        <a href="loyout.php"
           class="menu-item logout">

            <i class="fas fa-sign-out-alt"></i>

            <span>
                Déconnexion
            </span>

        </a>

    </div>

</aside>