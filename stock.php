<?php
/*
Plugin Name: Gestion des Stocks
Description: Un plugin pour gérer les stocks.
Version: 1.0
Author: Votre Nom
*/

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Fonction d'activation du plugin
function gestion_stocks_activation() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name_in = $wpdb->prefix . 'stocks_in';
    $sql_in = "CREATE TABLE $table_name_in (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        produit varchar(255) NOT NULL,
        quantite int NOT NULL,
        date_heure datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $table_name_out = $wpdb->prefix . 'stocks_out';
    $sql_out = "CREATE TABLE $table_name_out (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        produit varchar(255) NOT NULL,
        quantite int NOT NULL,
        date_heure datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_in);
    dbDelta($sql_out);
}
register_activation_hook(__FILE__, 'gestion_stocks_activation');

// Fonction de désactivation du plugin
function gestion_stocks_deactivation() {
    global $wpdb;
    $table_name_in = $wpdb->prefix . 'stocks_in';
    $table_name_out = $wpdb->prefix . 'stocks_out';
    $sql_in = "DROP TABLE IF EXISTS $table_name_in;";
    $sql_out = "DROP TABLE IF EXISTS $table_name_out;";
    $wpdb->query($sql_in);
    $wpdb->query($sql_out);
}
register_deactivation_hook(__FILE__, 'gestion_stocks_deactivation');

// Ajouter une page d'administration
function gestion_stocks_menu() {
    add_menu_page(
        'Gestion des Stocks',
        'Gestion des Stocks',
        'manage_options',
        'gestion-stocks',
        'gestion_stocks_page',
        'dashicons-archive',
        6
    );
}
add_action('admin_menu', 'gestion_stocks_menu');

// Ajouter une entrée de stock
function ajouter_stock_in($produit, $quantite) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stocks_in';
    $wpdb->insert(
        $table_name,
        array(
            'produit' => $produit,
            'quantite' => $quantite
        )
    );
}

// Ajouter une sortie de stock
function ajouter_stock_out($produit, $quantite) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stocks_out';
    $wpdb->insert(
        $table_name,
        array(
            'produit' => $produit,
            'quantite' => $quantite
        )
    );
}

// Récupérer toutes les entrées de stock
function recuperer_stocks_in() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stocks_in';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

// Récupérer toutes les sorties de stock
function recuperer_stocks_out() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stocks_out';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

// Calculer le stock restant
function calculer_stock_restant($produit) {
    global $wpdb;
    $table_name_in = $wpdb->prefix . 'stocks_in';
    $table_name_out = $wpdb->prefix . 'stocks_out';

    $quantite_in = $wpdb->get_var("SELECT SUM(quantite) FROM $table_name_in WHERE produit = '$produit'");
    $quantite_out = $wpdb->get_var("SELECT SUM(quantite) FROM $table_name_out WHERE produit = '$produit'");

    return $quantite_in - $quantite_out;
}

// Afficher la page d'administration
function gestion_stocks_page() {
    $message = '';

    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'ajouter_in') {
            ajouter_stock_in($_POST['produit'], $_POST['quantite']);
            $message = 'Entrée de stock ajoutée avec succès.';
        } elseif ($_POST['action'] == 'ajouter_out') {
            ajouter_stock_out($_POST['produit'], $_POST['quantite']);
            $message = 'Sortie de stock ajoutée avec succès.';
        }
    }

    $stocks_in = recuperer_stocks_in();
    $stocks_out = recuperer_stocks_out();
    ?>
    <div class="wrap">
        <h1>Gestion des Stocks</h1>
        <?php if ($message): ?>
            <div class="updated notice is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
        <h2>Ajouter une Entrée de Stock</h2>
        <form method="post">
            <input type="hidden" name="action" value="ajouter_in">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="produit">Produit</label></th>
                    <td><input name="produit" type="text" id="produit" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="quantite">Quantité</label></th>
                    <td><input name="quantite" type="number" id="quantite" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button-primary" value="Ajouter"></p>
        </form>

        <h2>Ajouter une Sortie de Stock</h2>
        <form method="post">
            <input type="hidden" name="action" value="ajouter_out">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="produit">Produit</label></th>
                    <td><input name="produit" type="text" id="produit" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="quantite">Quantité</label></th>
                    <td><input name="quantite" type="number" id="quantite" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button-primary" value="Ajouter"></p>
        </form>

        <h2>Liste des Entrées de Stock</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Date et Heure</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stocks_in as $stock) : ?>
                    <tr>
                        <td><?php echo $stock->id; ?></td>
                        <td><?php echo $stock->produit; ?></td>
                        <td><?php echo $stock->quantite; ?></td>
                        <td><?php echo $stock->date_heure; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Liste des Sorties de Stock</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Date et Heure</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stocks_out as $stock) : ?>
                    <tr>
                        <td><?php echo $stock->id; ?></td>
                        <td><?php echo $stock->produit; ?></td>
                        <td><?php echo $stock->quantite; ?></td>
                        <td><?php echo $stock->date_heure; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Stock Restant</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Stock Restant</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $produits = array_unique(array_merge(
                    array_map(function($stock) { return $stock->produit; }, $stocks_in),
                    array_map(function($stock) { return $stock->produit; }, $stocks_out)
                ));
                foreach ($produits as $produit) : ?>
                    <tr>
                        <td><?php echo $produit; ?></td>
                        <td><?php echo calculer_stock_restant($produit); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>