<?php
/*
Plugin Name: Gestionnaire de Transferts Aéroport
Description: Gérer les véhicules de transfert aéroport et formulaire de réservation avec calcul de prix et notifications par email.
Version: 2.5
Author: Votre Nom
Text Domain: airport-transfer-manager
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper pour la devise (MAD)
 */
function atm_get_currency_label() {
    return 'MAD';
}
function atm_format_price( $price ) {
    return number_format( (float) $price, 2 ) . ' ' . atm_get_currency_label();
}

/**
 * Enregistrer le type de contenu personnalisé pour les Véhicules
 */
function atm_register_post_type() {
    $labels = array(
        'name'               => 'Véhicules',
        'singular_name'      => 'Véhicule',
        'add_new'            => 'Ajouter un Véhicule',
        'add_new_item'       => 'Ajouter un Nouveau Véhicule',
        'edit_item'          => 'Modifier le Véhicule',
        'new_item'           => 'Nouveau Véhicule',
        'view_item'          => 'Voir le Véhicule',
        'search_items'       => 'Rechercher des Véhicules',
        'not_found'          => 'Aucun véhicule trouvé',
        'not_found_in_trash' => 'Aucun véhicule trouvé dans la Corbeille',
        'menu_name'          => 'Véhicules Aéroport',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-car',
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
    );

    register_post_type( 'atm_vehicle', $args );
}
add_action( 'init', 'atm_register_post_type' );

/**
 * Ajouter les meta boxes pour les détails du véhicule
 */
function atm_register_meta_boxes() {
    add_meta_box(
        'atm_vehicle_details',
        'Détails du Véhicule & Itinéraires',
        'atm_vehicle_details_meta_box_callback',
        'atm_vehicle',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'atm_register_meta_boxes' );

/**
 * HTML de la meta box (itinéraires DYNAMIQUES + bouton "Ajouter un Itinéraire")
 */
function atm_vehicle_details_meta_box_callback( $post ) {
    wp_nonce_field( 'atm_save_vehicle_meta', 'atm_vehicle_nonce' );

    $capacity = get_post_meta( $post->ID, 'atm_capacity', true );
    $luggage  = get_post_meta( $post->ID, 'atm_luggage', true );
    $routes   = get_post_meta( $post->ID, 'atm_routes', true );

    if ( ! is_array( $routes ) ) {
        $routes = array();
    }

    ?>
    <div class="hbnb-meta-box">
        <p>
            <label for="atm_capacity"><strong>Capacité (nombre de passagers)</strong></label><br>
            <input type="number" name="atm_capacity" id="atm_capacity" value="<?php echo esc_attr( $capacity ); ?>" min="1" class="hbnb-input">
        </p>

        <p>
            <label for="atm_luggage"><strong>Capacité bagages (nombre de sacs)</strong></label><br>
            <input type="number" name="atm_luggage" id="atm_luggage" value="<?php echo esc_attr( $luggage ); ?>" min="0" class="hbnb-input">
        </p>

        <hr>
        <h3>Itinéraires & Prix</h3>
        <p>Ajoutez tous les itinéraires pour ce véhicule. Exemples :</p>
        <ul class="hbnb-examples">
            <li>Casablanca → Rabat | Aller simple: 140 | Aller-retour: 140</li>
            <li>Rabat → Casablanca | Aller simple: 140 | Aller-retour: 140</li>
        </ul>
        <p>
            Pour <strong>Aller &amp; Retour</strong>, le prix de réservation sera :
            <strong>prix aller simple + prix retour</strong>.<br>
            Si le prix retour est vide, il utilisera <strong>aller simple × 2</strong>.
        </p>

        <table class="widefat hbnb-routes-table">
            <thead>
                <tr>
                    <th>Lieu de Prise en Charge</th>
                    <th>Lieu de Dépose</th>
                    <th>Prix Aller Simple (<?php echo esc_html( atm_get_currency_label() ); ?>)</th>
                    <th>Prix Retour (second trajet)</th>
                </tr>
            </thead>
            <tbody id="hbnb-routes-body">
            <?php
            $num_rows = max( 1, count( $routes ) );

            for ( $i = 0; $i < $num_rows; $i++ ) {
                $pickup      = isset( $routes[ $i ]['pickup'] ) ? $routes[ $i ]['pickup'] : '';
                $dropoff     = isset( $routes[ $i ]['dropoff'] ) ? $routes[ $i ]['dropoff'] : '';
                $one_way     = isset( $routes[ $i ]['one_way'] ) ? $routes[ $i ]['one_way'] : '';
                $return_trip = isset( $routes[ $i ]['return'] ) ? $routes[ $i ]['return'] : '';
                ?>
                <tr>
                    <td>
                        <input type="text"
                               name="atm_route_pickup[]"
                               value="<?php echo esc_attr( $pickup ); ?>"
                               placeholder="ex: Casablanca"
                               class="hbnb-route-input">
                    </td>
                    <td>
                        <input type="text"
                               name="atm_route_dropoff[]"
                               value="<?php echo esc_attr( $dropoff ); ?>"
                               placeholder="ex: Rabat"
                               class="hbnb-route-input">
                    </td>
                    <td>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="atm_route_one_way[]"
                               value="<?php echo esc_attr( $one_way ); ?>"
                               placeholder="ex: 140"
                               class="hbnb-route-input">
                    </td>
                    <td>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="atm_route_return[]"
                               value="<?php echo esc_attr( $return_trip ); ?>"
                               placeholder="ex: 140"
                               class="hbnb-route-input">
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>

        <p class="hbnb-add-route-container">
            <button type="button" class="button hbnb-add-route-btn" id="hbnb-add-route">+ Ajouter un Itinéraire</button>
        </p>

        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                var btn  = document.getElementById('hbnb-add-route');
                var body = document.getElementById('hbnb-routes-body');
                if (!btn || !body) return;

                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var rows = body.querySelectorAll('tr');
                    if (!rows.length) {
                        return;
                    }

                    var templateRow = rows[rows.length - 1].cloneNode(true);
                    var inputs = templateRow.querySelectorAll('input');
                    inputs.forEach(function(input) {
                        input.value = '';
                    });
                    body.appendChild(templateRow);
                });
            });
        })();
        </script>
    </div>
    
    <style>
    .hbnb-input { width: 120px; }
    .hbnb-route-input { width: 100%; }
    .hbnb-meta-box { padding: 10px; }
    .hbnb-examples { margin-left: 18px; }
    .hbnb-add-route-container { margin-top: 10px; }
    .hbnb-routes-table { margin-top: 10px; }
    </style>
    <?php
}

/**
 * Sauvegarder les meta du véhicule
 */
function atm_save_vehicle_meta( $post_id ) {
    if ( ! isset( $_POST['atm_vehicle_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['atm_vehicle_nonce'], 'atm_save_vehicle_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $capacity = isset( $_POST['atm_capacity'] ) ? intval( $_POST['atm_capacity'] ) : '';
    $luggage  = isset( $_POST['atm_luggage'] ) ? intval( $_POST['atm_luggage'] ) : '';

    update_post_meta( $post_id, 'atm_capacity', $capacity );
    update_post_meta( $post_id, 'atm_luggage', $luggage );

    $pickups  = isset( $_POST['atm_route_pickup'] ) ? (array) $_POST['atm_route_pickup'] : array();
    $dropoffs = isset( $_POST['atm_route_dropoff'] ) ? (array) $_POST['atm_route_dropoff'] : array();
    $oneways  = isset( $_POST['atm_route_one_way'] ) ? (array) $_POST['atm_route_one_way'] : array();
    $returns  = isset( $_POST['atm_route_return'] ) ? (array) $_POST['atm_route_return'] : array();

    $routes = array();
    $count  = max( count( $pickups ), count( $dropoffs ), count( $oneways ), count( $returns ) );

    for ( $i = 0; $i < $count; $i++ ) {
        $pickup  = isset( $pickups[ $i ] ) ? sanitize_text_field( $pickups[ $i ] ) : '';
        $dropoff = isset( $dropoffs[ $i ] ) ? sanitize_text_field( $dropoffs[ $i ] ) : '';
        $one_way = isset( $oneways[ $i ] ) ? floatval( $oneways[ $i ] ) : 0;
        $return  = isset( $returns[ $i ] ) ? floatval( $returns[ $i ] ) : 0;

        if ( $pickup !== '' && $dropoff !== '' ) {
            $routes[] = array(
                'pickup'   => $pickup,
                'dropoff'  => $dropoff,
                'one_way'  => $one_way,
                'return'   => $return,
            );
        }
    }

    update_post_meta( $post_id, 'atm_routes', $routes );
}
add_action( 'save_post_atm_vehicle', 'atm_save_vehicle_meta' );

/**
 * Helper: obtenir tous les LIEUX DE PRISE EN CHARGE UNIQUES
 */
function atm_get_all_locations() {
    $locations = array();

    $vehicles = get_posts( array(
        'post_type'      => 'atm_vehicle',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    foreach ( $vehicles as $vehicle ) {
        $routes = get_post_meta( $vehicle->ID, 'atm_routes', true );
        if ( ! is_array( $routes ) ) {
            continue;
        }

        foreach ( $routes as $route ) {
            if ( ! empty( $route['pickup'] ) ) {
                $locations[] = $route['pickup'];
            }
        }
    }

    $locations = array_unique( $locations );
    sort( $locations );

    return $locations;
}

/**
 * Helper: mapping des lieux de dépose par lieu de prise en charge
 */
function atm_get_dropoff_locations_mapping() {
    $mapping = array();

    $vehicles = get_posts( array(
        'post_type'      => 'atm_vehicle',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    foreach ( $vehicles as $vehicle ) {
        $routes = get_post_meta( $vehicle->ID, 'atm_routes', true );
        if ( ! is_array( $routes ) ) {
            continue;
        }

        foreach ( $routes as $route ) {
            if ( ! empty( $route['pickup'] ) && ! empty( $route['dropoff'] ) ) {
                $pickup  = $route['pickup'];
                $dropoff = $route['dropoff'];
                
                if ( ! isset( $mapping[ $pickup ] ) ) {
                    $mapping[ $pickup ] = array();
                }
                
                if ( ! in_array( $dropoff, $mapping[ $pickup ], true ) ) {
                    $mapping[ $pickup ][] = $dropoff;
                }
            }
        }
    }

    foreach ( $mapping as $pickup => $dropoffs ) {
        sort( $mapping[ $pickup ] );
    }

    return $mapping;
}

/**
 * Helper: trouver le prix pour un véhicule, itinéraire et type de trajet donné
 */
function atm_get_price_for_vehicle_route( $vehicle_id, $pickup, $dropoff, $trip_type = 'one_way' ) {
    $routes = get_post_meta( $vehicle_id, 'atm_routes', true );
    if ( ! is_array( $routes ) ) {
        return false;
    }

    foreach ( $routes as $route ) {
        if (
            isset( $route['pickup'], $route['dropoff'] ) &&
            strtolower( trim( $route['pickup'] ) ) === strtolower( trim( $pickup ) ) &&
            strtolower( trim( $route['dropoff'] ) ) === strtolower( trim( $dropoff ) )
        ) {
            $one_way     = isset( $route['one_way'] ) ? floatval( $route['one_way'] ) : 0;
            $return_part = isset( $route['return'] ) ? floatval( $route['return'] ) : 0;

            if ( $trip_type === 'roundtrip' ) {
                if ( $return_part <= 0 ) {
                    return $one_way * 2;
                }
                return $one_way + $return_part;
            } else {
                return $one_way;
            }
        }
    }

    return false;
}

/**
 * Générer un email HTML beau avec ticket (lecture claire)
 */
function atm_generate_email_ticket($booking_data) {
    $trip_label = ($booking_data['trip_type'] === 'roundtrip') ? 'Aller & Retour' : 'Aller Simple';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            .hbnb-email-body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #111111; 
                background: #f2f2f2;
                margin: 0;
                padding: 20px;
            }
            .hbnb-email-container {
                max-width: 650px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 6px 24px rgba(0,0,0,0.08);
                border: 1px solid #e1e1e1;
            }
            .hbnb-email-header {
                background: #ffffff;
                color: #111111;
                padding: 24px 28px;
                text-align: left;
                border-bottom: 1px solid #e5e5e5;
            }
            .hbnb-email-header h1 {
                margin: 0;
                font-size: 22px;
                font-weight: 700;
                color: #111111;
            }
            .hbnb-booking-ref {
                background: #111111;
                padding: 6px 14px;
                border-radius: 999px;
                display: inline-block;
                margin-top: 10px;
                font-weight: 600;
                color: #ffffff;
                font-size: 13px;
            }
            .hbnb-email-content {
                padding: 24px 28px 28px;
            }
            .hbnb-email-content p {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #222222;
            }
            .hbnb-ticket-section {
                background: #fafafa;
                border: 1px solid #e0e0e0;
                border-radius: 10px;
                padding: 18px 16px;
                margin: 20px 0;
            }
            .hbnb-ticket-section h3 {
                margin: 0 0 12px 0;
                font-size: 16px;
                font-weight: 700;
                color: #111111;
            }
            .hbnb-info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                margin: 8px 0 4px;
            }
            .hbnb-info-item {
                background: #ffffff;
                padding: 10px 12px;
                border-radius: 8px;
                border: 1px solid #e5e5e5;
            }
            .hbnb-info-label {
                font-weight: 600;
                color: #555555;
                font-size: 11px;
                text-transform: uppercase;
                margin-bottom: 4px;
                letter-spacing: 0.4px;
            }
            .hbnb-info-value {
                font-size: 14px;
                color: #111111;
            }
            .hbnb-price-highlight {
                background: #111111;
                color: #ffffff;
                padding: 14px 16px;
                border-radius: 10px;
                text-align: center;
                font-size: 18px;
                font-weight: 700;
                margin: 18px 0 4px;
            }
            .hbnb-contact-info {
                background: #fafafa;
                padding: 16px 18px;
                border-radius: 10px;
                margin: 20px 0;
                border: 1px solid #e0e0e0;
                text-align: left;
            }
            .hbnb-contact-info h4 {
                margin: 0 0 10px 0;
                font-size: 15px;
                font-weight: 700;
                color: #111111;
            }
            .hbnb-contact-info p {
                margin: 0 0 5px 0;
                font-size: 13px;
            }
            .hbnb-info-box {
                background: #fafafa;
                padding: 14px 16px;
                border-radius: 10px;
                border-left: 4px solid #111111;
                margin-top: 10px;
            }
            .hbnb-info-box h4 {
                margin: 0 0 8px 0;
                font-size: 14px;
                font-weight: 700;
                color: #111111;
            }
            .hbnb-info-box ul {
                margin: 0;
                padding-left: 18px;
                font-size: 13px;
                color: #333333;
            }
            .hbnb-info-box li {
                margin-bottom: 4px;
            }
            .hbnb-email-footer {
                text-align: center;
                padding: 16px 20px 20px;
                background: #f7f7f7;
                color: #666666;
                font-size: 12px;
                border-top: 1px solid #e5e5e5;
            }
            @media (max-width: 600px) {
                .hbnb-info-grid {
                    grid-template-columns: 1fr;
                }
                .hbnb-email-content,
                .hbnb-email-header {
                    padding: 18px 16px;
                }
            }
        </style>
    </head>
    <body class="hbnb-email-body">
        <div class="hbnb-email-container">
            <div class="hbnb-email-header">
                <h1>Votre ticket de transfert aéroport</h1>
                <div class="hbnb-booking-ref"><?php echo $booking_data['booking_ref']; ?></div>
            </div>
            
            <div class="hbnb-email-content">
                <p>Cher(ère) <strong><?php echo esc_html($booking_data['first_name']); ?></strong>,</p>
                <p>Merci d'avoir réservé votre transfert aéroport avec nous. Vous trouverez ci-dessous le récapitulatif clair de votre réservation.</p>
                
                <div class="hbnb-ticket-section">
                    <h3>Résumé de la réservation</h3>
                    
                    <div class="hbnb-info-grid">
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Nom du passager</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['first_name']); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Téléphone</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['phone']); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Type de véhicule</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['vehicle']); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Type de trajet</div>
                            <div class="hbnb-info-value"><?php echo esc_html($trip_label); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Lieu de prise en charge</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['pickup']); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Lieu de dépose</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['dropoff']); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Date</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['date']); ?></div>
                        </div>
                        <div class="hbnb-info-item">
                            <div class="hbnb-info-label">Heure</div>
                            <div class="hbnb-info-value"><?php echo esc_html($booking_data['time']); ?></div>
                        </div>
                    </div>
                    
                    <div class="hbnb-price-highlight">
                        Montant total : <?php echo esc_html($booking_data['price']); ?>
                    </div>
                </div>
                
                <div class="hbnb-contact-info">
                    <h4>Coordonnées du service client</h4>
                    <p><strong>Téléphone :</strong> +212 661-955396</p>
                    <p><strong>Email :</strong> casteltrip1@gmail.com</p>
                    <p><strong>Urgence :</strong> +212 661-955396</p>
                </div>
                
                <div class="hbnb-info-box">
                    <h4>Instructions importantes</h4>
                    <ul>
                        <li>Veuillez être prêt 15 minutes avant l'heure de prise en charge.</li>
                        <li>Présentez ce ticket ou montrez cet email à votre chauffeur.</li>
                        <li>Gardez votre téléphone allumé pour que le chauffeur puisse vous joindre.</li>
                        <li>En cas de changement de vol ou de retard, merci de nous prévenir dès que possible.</li>
                    </ul>
                </div>
            </div>
            
            <div class="hbnb-email-footer">
                <p>Merci d'avoir choisi notre service de transfert.<br>Nous vous souhaitons un agréable voyage.</p>
                <p>&copy; <?php echo date('Y'); ?> CastelTrip. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * CSS front-end avec design mobile optimisé
 */
function atm_get_frontend_css() {
    return '
    :root {
        --primary: #ff5e14;
        --primary-dark: #e65112;
        --secondary: #0d1c2d;
        --light: #f8f9fa;
        --dark: #111111;
        --success: #28a745;
        --success-light: #2ecc71;
        --warning: #ffc107;
        --danger: #dc3545;
        --gray: #6c757d;
        --gray-light: #e9ecef;
        --border: #dee2e6;
        --shadow: 0 5px 15px rgba(0,0,0,0.08);
        --radius: 8px;
        --transition: all 0.3s ease;
        --green-title: #2ecc71;
    }

    /* Conteneurs principaux - Desktop */
    .hbnb-booking-form,
    .hbnb-step2-form,
    .hbnb-step3-form,
    .hbnb-booking-confirmation {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        overflow: hidden;
        font-family: "Segoe UI", -apple-system, system-ui, sans-serif;
        color: var(--dark);
        width: 100%;
    }

    /* Mobile: conteneurs prennent 100% avec espacement */
    @media (max-width: 768px) {
        .hbnb-booking-form,
        .hbnb-step2-form,
        .hbnb-step3-form,
        .hbnb-booking-confirmation {
            width: 100% !important;
            margin: 15px 0 !important;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 0 !important;
        }
    }

    .hbnb-form-header {
        background: linear-gradient(135deg, var(--secondary), #1a2c42);
        color: #ffffff;
        padding: 25px 30px;
        border-bottom: 3px solid var(--primary);
        width: 100%;
    }

    .hbnb-form-header h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Mobile: header */
    @media (max-width: 768px) {
        .hbnb-form-header {
            padding: 20px 15px !important;
            text-align: center;
        }
        
        .hbnb-form-header h3 {
            font-size: 18px;
            justify-content: center;
        }
    }

    .hbnb-form-content {
        padding: 30px;
        color: var(--dark);
        font-size: 14px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Mobile: contenu prend 98% */
    @media (max-width: 768px) {
        .hbnb-form-content {
            padding: 20px 15px !important;
            width: 98% !important;
            margin: 0 auto !important;
            font-size: 15px;
        }
    }

    /* Progress bar */
    .hbnb-progress-bar {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
        width: 100%;
    }

    .hbnb-progress-bar:before {
        content: "";
        position: absolute;
        top: 20px;
        left: 5%;
        right: 5%;
        height: 3px;
        background: var(--gray-light);
        z-index: 1;
    }

    .hbnb-progress-step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 2;
        padding: 0 5px;
    }

    .hbnb-step-number {
        width: 40px;
        height: 40px;
        background: #ffffff;
        border: 3px solid var(--gray-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: 700;
        color: var(--gray);
        transition: var(--transition);
    }

    .hbnb-progress-step.active .hbnb-step-number {
        background: var(--primary);
        border-color: var(--primary);
        color: #ffffff;
    }

    .hbnb-progress-step.completed .hbnb-step-number {
        background: var(--success);
        border-color: var(--success);
        color: #ffffff;
    }

    .hbnb-step-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray);
        white-space: nowrap;
    }

    .hbnb-progress-step.active .hbnb-step-label {
        color: var(--primary);
    }

    /* Mobile: progress bar */
    @media (max-width: 768px) {
        .hbnb-progress-bar {
            margin-bottom: 25px;
        }
        
        .hbnb-progress-bar:before {
            left: 10%;
            right: 10%;
            top: 17px;
        }
        
        .hbnb-step-number {
            width: 32px;
            height: 32px;
            font-size: 14px;
            border-width: 2px;
        }
        
        .hbnb-step-label {
            font-size: 11px;
            white-space: normal;
            line-height: 1.2;
        }
    }

    /* Form grid + champs */
    .hbnb-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
        margin-bottom: 25px;
        width: 100%;
    }

    .hbnb-form-group {
        margin-bottom: 8px;
        width: 100%;
    }

    .hbnb-form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--secondary);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .hbnb-form-control,
    .hbnb-form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--gray-light);
        border-radius: var(--radius);
        font-size: 15px;
        color: var(--dark);
        background: #ffffff;
        transition: var(--transition);
        box-sizing: border-box;
        display: block;
    }

    .hbnb-form-control:focus,
    .hbnb-form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255, 94, 20, 0.15);
    }

    select.hbnb-form-control {
        appearance: none;
        background-image: linear-gradient(45deg, transparent 50%, #888 50%), linear-gradient(135deg, #888 50%, transparent 50%);
        background-position: calc(100% - 18px) calc(50% - 3px), calc(100% - 13px) calc(50% - 3px);
        background-size: 7px 7px, 7px 7px;
        background-repeat: no-repeat;
    }

    /* Mobile: form grid - STEP 1 à 98% */
    @media (max-width: 768px) {
        .hbnb-form-grid {
            grid-template-columns: 1fr !important;
            gap: 18px !important;
            width: 98% !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        
        .hbnb-step1-form .hbnb-form-grid {
            width: 98% !important;
        }
        
        .hbnb-form-control,
        .hbnb-form-group textarea {
            padding: 14px 16px !important;
            font-size: 16px !important;
            width: 100% !important;
        }
        
        .hbnb-form-group label {
            font-size: 12px !important;
        }
        
        .hbnb-form-group {
            margin-bottom: 12px;
        }
    }

    /* Radio group (switch type de trajet) */
    .hbnb-radio-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        width: 100%;
    }

    .hbnb-radio-option {
        flex: 1;
        min-width: 150px;
    }

    .hbnb-radio-option input[type="radio"] {
        display: none;
    }

    .hbnb-radio-label {
        display: block;
        padding: 14px;
        border: 2px solid var(--gray-light);
        border-radius: var(--radius);
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        font-weight: 500;
        background: #ffffff;
        font-size: 14px;
        color: var(--dark);
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-radio-label:hover {
        border-color: var(--primary);
        background: rgba(255, 94, 20, 0.05);
    }

    .hbnb-radio-option input[type="radio"]:checked + .hbnb-radio-label {
        border-color: var(--primary);
        background: rgba(255, 94, 20, 0.08);
        color: var(--primary);
        font-weight: 600;
    }

    /* Mobile: radio group */
    @media (max-width: 768px) {
        .hbnb-radio-group {
            flex-direction: column !important;
            gap: 8px !important;
            width: 100% !important;
        }
        
        .hbnb-radio-option {
            min-width: 100% !important;
            width: 100% !important;
        }
        
        .hbnb-radio-label {
            padding: 16px !important;
            font-size: 15px !important;
            width: 100% !important;
        }
    }

    /* Boutons */
    .hbnb-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 28px;
        border: none;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        box-sizing: border-box;
        width: auto;
    }

    .hbnb-btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #ffffff;
    }

    .hbnb-btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark), #cc4a10);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 94, 20, 0.25);
    }

    .hbnb-btn-secondary {
        background: #ffffff;
        color: var(--secondary);
        border: 2px solid var(--border);
    }

    .hbnb-btn-secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: rgba(255, 94, 20, 0.05);
    }

    /* Mobile: boutons pleine largeur */
    @media (max-width: 768px) {
        .hbnb-btn {
            padding: 16px 20px !important;
            font-size: 16px !important;
            width: 100% !important;
            justify-content: center;
        }
    }

    /* Summary box */
    .hbnb-summary-box {
        background: #f8f9fa;
        border-radius: var(--radius);
        padding: 20px;
        border-left: 4px solid var(--primary);
        margin-bottom: 30px;
        color: var(--dark);
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-summary-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--secondary);
        margin-bottom: 10px;
    }

    .hbnb-summary-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
        margin-top: 10px;
    }

    .hbnb-summary-item {
        background: #ffffff;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid var(--border);
        box-sizing: border-box;
    }

    .hbnb-summary-label {
        font-size: 11px;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }

    .hbnb-summary-value {
        font-weight: 600;
        color: var(--dark);
        font-size: 14px;
    }

    /* Mobile: summary box */
    @media (max-width: 768px) {
        .hbnb-summary-box {
            padding: 16px !important;
            margin-bottom: 25px !important;
            width: 96% !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        
        .hbnb-summary-details {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }
        
        .hbnb-summary-title {
            font-size: 16px !important;
        }
        
        .hbnb-summary-item {
            width: 100% !important;
        }
    }

    /* Grille des véhicules - IMPORTANT: organisation fixe pour mobile */
    .hbnb-vehicles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin: 26px 0;
        width: 100%;
    }

    .hbnb-vehicle-card {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border);
        transition: var(--transition);
        cursor: pointer;
        position: relative;
        color: var(--dark);
        width: 100%;
        box-sizing: border-box;
        display: block;
    }

    .hbnb-vehicle-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        border-color: var(--primary);
    }

    .hbnb-vehicle-card input[type="radio"] {
        position: absolute;
        top: 15px;
        left: 15px;
        width: 20px;
        height: 20px;
        z-index: 2;
        accent-color: var(--primary);
    }

    .hbnb-vehicle-image {
        height: 170px;
        overflow: hidden;
        background: #f3f3f3;
        width: 100%;
    }

    .hbnb-vehicle-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
        display: block;
    }

    .hbnb-vehicle-card:hover .hbnb-vehicle-image img {
        transform: scale(1.05);
    }

    .hbnb-vehicle-content {
        padding: 16px 18px 18px;
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-vehicle-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .hbnb-vehicle-features {
        display: flex;
        gap: 16px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }

    .hbnb-vehicle-feature {
        font-size: 13px;
        color: var(--gray);
    }

    .hbnb-vehicle-price {
        font-size: 22px;
        font-weight: 700;
        color: var(--primary);
        margin-top: 6px;
    }

    .hbnb-vehicle-card.selected {
        border: 2px solid var(--primary);
        box-shadow: 0 0 0 3px rgba(255, 94, 20, 0.18);
    }

    /* Mobile: véhicules grid - FIX pour organisation */
    @media (max-width: 768px) {
        .hbnb-vehicles-grid {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
            width: 96% !important;
            margin: 20px auto !important;
            display: block !important;
        }
        
        .hbnb-vehicle-card {
            width: 96% !important;
            margin: 0 auto 20px auto !important;
            display: block !important;
            float: none !important;
        }
        
        .hbnb-vehicle-image {
            height: 160px !important;
        }
        
        .hbnb-vehicle-content {
            padding: 14px 16px 16px !important;
        }
        
        .hbnb-vehicle-title {
            font-size: 16px !important;
        }
        
        .hbnb-vehicle-price {
            font-size: 20px !important;
        }
        
        .hbnb-vehicle-card input[type="radio"] {
            width: 24px !important;
            height: 24px !important;
        }
    }

    /* Billing layout (étape 2) */
    .hbnb-billing-layout {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 28px;
        margin-top: 10px;
        width: 100%;
    }

    .hbnb-order-summary {
        background: #f8f9fa;
        border-radius: var(--radius);
        padding: 22px;
        border: 1px solid var(--border);
        position: sticky;
        top: 20px;
        color: var(--dark);
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-order-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--secondary);
        margin-bottom: 18px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--primary);
    }

    .hbnb-order-details {
        margin-bottom: 18px;
        width: 100%;
    }

    .hbnb-order-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--border);
        font-size: 14px;
        color: var(--dark);
        width: 100%;
    }

    .hbnb-order-total {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid var(--border);
        width: 100%;
    }

    /* Mobile: billing layout */
    @media (max-width: 992px) {
        .hbnb-billing-layout {
            grid-template-columns: 1fr !important;
            gap: 25px !important;
            width: 98% !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        
        .hbnb-order-summary {
            position: static !important;
            width: 96% !important;
            margin: 0 auto !important;
        }
    }

    /* Paiement */
    .hbnb-payment-methods {
        margin-top: 20px;
        width: 100%;
    }

    .hbnb-payment-option {
        margin-bottom: 12px;
        width: 100%;
    }

    .hbnb-payment-option input[type="radio"] {
        display: none;
    }

    .hbnb-payment-label {
        display: block;
        padding: 14px;
        border: 2px solid var(--border);
        border-radius: var(--radius);
        cursor: pointer;
        transition: var(--transition);
        font-size: 14px;
        color: var(--dark);
        background: #ffffff;
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-payment-label:hover {
        border-color: var(--primary);
        background: rgba(255,94,20,0.05);
    }

    .hbnb-payment-option input[type="radio"]:checked + .hbnb-payment-label {
        border-color: var(--primary);
        background: rgba(255,94,20,0.10);
    }

    .hbnb-payment-icon {
        font-size: 16px;
        margin-right: 6px;
        vertical-align: middle;
    }

    /* Mobile: payment methods */
    @media (max-width: 768px) {
        .hbnb-payment-methods {
            width: 100% !important;
        }
        
        .hbnb-payment-label {
            padding: 16px !important;
            font-size: 15px !important;
        }
    }

    /* Ticket de confirmation (front) */
    .hbnb-ticket {
        background: #ffffff;
        border-radius: 12px;
        padding: 26px;
        border: 1px solid var(--border);
        margin: 28px 0;
        position: relative;
        overflow: hidden;
        color: var(--dark);
        width: 96%;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }

    .hbnb-ticket:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .hbnb-ticket-header {
        text-align: center;
        margin-bottom: 24px;
        width: 100%;
    }

    .hbnb-ticket-title {
        font-size: 22px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 6px;
    }

    .hbnb-ticket-ref {
        display: inline-block;
        background: var(--dark);
        color: #ffffff;
        padding: 7px 18px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .hbnb-ticket-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 18px;
        margin-bottom: 26px;
        width: 100%;
    }

    .hbnb-ticket-section {
        background: #fafafa;
        padding: 16px 18px;
        border-radius: 8px;
        border: 1px solid var(--border);
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-ticket-section h4 {
        color: var(--green-title);
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .hbnb-ticket-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        color: var(--dark);
        width: 100%;
    }

    .hbnb-ticket-row:last-child {
        border-bottom: none;
    }

    .hbnb-ticket-actions {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 20px;
        justify-content: center;
        width: 100%;
    }

    .hbnb-ticket-btn {
        flex: 1;
        min-width: 150px;
        max-width: 220px;
    }

    .hbnb-ticket-btn.print {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: #ffffff;
    }

    .hbnb-ticket-btn.print:hover {
        background: linear-gradient(135deg, #495057, #343a40);
    }

    .hbnb-ticket-btn.whatsapp {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: #ffffff;
    }

    .hbnb-ticket-btn.whatsapp:hover {
        background: linear-gradient(135deg, #128C7E, #0c6b5e);
    }

    /* Mobile: ticket */
    @media (max-width: 768px) {
        .hbnb-ticket {
            padding: 20px 15px !important;
            width: 96% !important;
            margin: 20px auto !important;
        }
        
        .hbnb-ticket-grid {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }
        
        .hbnb-ticket-section {
            padding: 14px 16px !important;
            width: 100% !important;
        }
        
        .hbnb-ticket-title {
            font-size: 19px !important;
        }
        
        .hbnb-ticket-actions {
            flex-direction: column !important;
            gap: 12px !important;
            width: 100% !important;
        }
        
        .hbnb-ticket-btn {
            max-width: 100% !important;
            min-width: 100% !important;
            width: 100% !important;
        }
        
        .hbnb-ticket-row {
            flex-direction: column !important;
            gap: 4px !important;
        }
        
        .hbnb-ticket-section h4 {
            font-size: 13px !important;
        }
    }

    /* Alerts */
    .hbnb-alert {
        padding: 14px 18px;
        border-radius: var(--radius);
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-alert-success {
        background: rgba(40, 167, 69, 0.08);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: #155724;
    }

    .hbnb-alert-error {
        background: rgba(220, 53, 69, 0.08);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: #721c24;
    }

    /* Mobile: alerts */
    @media (max-width: 768px) {
        .hbnb-alert {
            padding: 12px 16px !important;
            margin: 15px auto !important;
            width: 96% !important;
            font-size: 15px !important;
        }
    }

    /* Hero search (étape 0) - IMPORTANT: STEP 1 à 98% */
    .hbnb-search-hero {
        background: #ffffff;
        padding: 26px 24px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-top: 20px;
        color: var(--dark);
        width: 96%;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }

    .hbnb-search-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px 24px;
        align-items: flex-end;
        width: 100%;
    }

    .hbnb-search-grid .hbnb-field-trip,
    .hbnb-search-grid .hbnb-field-date,
    .hbnb-search-grid .hbnb-field-time {
        grid-column: auto;
    }

    .hbnb-search-grid .hbnb-field-pickup {
        grid-column: 1 / 2;
    }
    .hbnb-search-grid .hbnb-field-dropoff {
        grid-column: 2 / 3;
    }

    .hbnb-search-grid .hbnb-search-btn {
        grid-column: 3 / 4;
    }

    .hbnb-step1-form .hbnb-btn-primary {
        width: 100%;
        padding: 14px;
        font-size: 16px;
        font-weight: 700;
    }

    /* Mobile: search hero - STEP 1 à 98% */
    @media (max-width: 992px) {
        .hbnb-search-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .hbnb-search-grid .hbnb-field-trip {
            grid-column: 1 / 3 !important;
        }
        .hbnb-search-grid .hbnb-field-date {
            grid-column: 1 / 2 !important;
        }
        .hbnb-search-grid .hbnb-field-time {
            grid-column: 2 / 3 !important;
        }
        .hbnb-search-grid .hbnb-field-pickup {
            grid-column: 1 / 2 !important;
        }
        .hbnb-search-grid .hbnb-field-dropoff {
            grid-column: 2 / 3 !important;
        }
        .hbnb-search-grid .hbnb-search-btn {
            grid-column: 1 / 3 !important;
        }
        
        .hbnb-search-hero {
            padding: 20px !important;
            width: 98% !important;
        }
    }

    @media (max-width: 576px) {
        .hbnb-search-grid {
            grid-template-columns: 1fr !important;
        }
        
        .hbnb-search-grid > div {
            grid-column: 1 / -1 !important;
        }
        
        .hbnb-search-hero {
            padding: 16px !important;
            width: 98% !important;
        }
    }

    /* Titres verts pour les informations */
    .hbnb-info-title,
    .hbnb-section-title,
    .hbnb-subtitle {
        color: var(--green-title) !important;
        font-weight: 600 !important;
    }

    /* Mobile: titres */
    @media (max-width: 768px) {
        .hbnb-info-title,
        .hbnb-section-title,
        .hbnb-subtitle {
            font-size: 16px !important;
            line-height: 1.4 !important;
        }
    }

    /* Améliorations pour l\'affichage des données */
    .hbnb-data-display {
        background: #f8f9fa;
        border-radius: var(--radius);
        padding: 15px;
        margin: 15px 0;
        border-left: 4px solid var(--green-title);
        width: 100%;
        box-sizing: border-box;
    }

    .hbnb-data-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
        width: 100%;
    }

    .hbnb-data-item:last-child {
        border-bottom: none;
    }

    .hbnb-data-label {
        font-weight: 600;
        color: var(--secondary);
        font-size: 13px;
    }

    .hbnb-data-value {
        font-weight: 500;
        color: var(--dark);
        font-size: 14px;
        text-align: right;
    }

    /* Mobile: data display */
    @media (max-width: 768px) {
        .hbnb-data-display {
            padding: 12px !important;
            width: 96% !important;
            margin: 12px auto !important;
        }
        
        .hbnb-data-item {
            flex-direction: column !important;
            gap: 4px !important;
        }
        
        .hbnb-data-value {
            text-align: left !important;
            font-size: 15px !important;
        }
        
        .hbnb-data-label {
            font-size: 12px !important;
        }
    }

    /* Animation */
    @keyframes hbnbFadeIn {
        from {
            opacity: 0;
            transform: translateY(18px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hbnb-booking-form,
    .hbnb-step2-form,
    .hbnb-step3-form,
    .hbnb-booking-confirmation {
        animation: hbnbFadeIn 0.45s ease-out;
    }

    /* Reset pour éviter les débordements */
    * {
        max-width: 100%;
    }

    /* Améliorations spécifiques pour mobile */
    @media (max-width: 480px) {
        body {
            -webkit-text-size-adjust: 100%;
        }
        
        .hbnb-form-content {
            padding: 15px 12px !important;
            width: 98% !important;
        }
        
        .hbnb-summary-box,
        .hbnb-vehicle-card,
        .hbnb-ticket,
        .hbnb-search-hero {
            width: 98% !important;
        }
        
        input, select, textarea, button {
            font-size: 16px !important;
            max-width: 100% !important;
        }
        
        .hbnb-btn {
            padding: 18px 20px !important;
            font-size: 17px !important;
        }
        
        /* Force les cartes à rester dans le conteneur */
        .hbnb-vehicles-grid > * {
            float: none !important;
            clear: both !important;
        }
    }

    /* Correction spécifique pour éviter le débordement des cartes */
    .hbnb-step2-form .hbnb-vehicles-grid {
        display: block;
        width: 100%;
    }
    
    .hbnb-step2-form .hbnb-vehicle-card {
        float: left;
        margin: 0 2% 20px 0;
        width: 48%;
    }
    
    .hbnb-step2-form .hbnb-vehicle-card:nth-child(2n) {
        margin-right: 0;
    }

    /* Mobile: correction pour les cartes */
    @media (max-width: 768px) {
        .hbnb-step2-form .hbnb-vehicles-grid {
            display: block !important;
            width: 100% !important;
        }
        
        .hbnb-step2-form .hbnb-vehicle-card {
            float: none !important;
            margin: 0 auto 20px auto !important;
            width: 96% !important;
            display: block !important;
        }
        
        .hbnb-step2-form .hbnb-vehicle-card:nth-child(2n) {
            margin-right: auto !important;
        }
    }

    /* Pour éviter tout débordement horizontal */
    html, body {
        overflow-x: hidden;
        width: 100%;
    }
    
    .hbnb-booking-form,
    .hbnb-step2-form,
    .hbnb-step3-form,
    .hbnb-booking-confirmation {
        overflow: hidden;
    }
    ';
}

/**
 * Générer le téléchargement du ticket (version imprimable claire)
 */
function atm_generate_ticket_download() {
    if ( ! isset( $_POST['atm_generate_ticket'] ) || ! wp_verify_nonce( $_POST['atm_ticket_nonce'], 'atm_generate_ticket' ) ) {
        return;
    }

    $booking_data = array(
        'first_name' => sanitize_text_field( $_POST['first_name'] ),
        'email'      => sanitize_email( $_POST['email'] ),
        'phone'      => sanitize_text_field( $_POST['phone'] ),
        'date'       => sanitize_text_field( $_POST['date'] ),
        'time'       => sanitize_text_field( $_POST['time'] ),
        'pickup'     => sanitize_text_field( $_POST['pickup'] ),
        'dropoff'    => sanitize_text_field( $_POST['dropoff'] ),
        'vehicle'    => sanitize_text_field( $_POST['vehicle'] ),
        'trip_type'  => sanitize_text_field( $_POST['trip_type'] ),
        'price'      => sanitize_text_field( $_POST['price'] ),
        'booking_ref'=> 'ATM-' . date('Ymd') . '-' . substr(md5(uniqid()), 0, 8)
    );

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Ticket de Transfert Aéroport - <?php echo $booking_data['booking_ref']; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            .hbnb-ticket-body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
                background: #f2f2f2; 
                color: #111111;
            }
            .hbnb-ticket-container { 
                max-width: 650px; 
                margin: 0 auto; 
            }
            .hbnb-ticket {
                background: #ffffff;
                border-radius: 12px;
                border: 1px solid #dddddd;
                padding: 20px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            }
            .hbnb-ticket-header { 
                text-align: center; 
                margin-bottom: 20px; 
                padding-bottom: 15px; 
                border-bottom: 1px dashed #cccccc; 
            }
            .hbnb-ticket-header h2 { 
                color: #111111; 
                margin: 0 0 12px 0; 
                font-size: 22px; 
                font-weight: 700;
            }
            .hbnb-booking-ref { 
                background: #111111; 
                color: #ffffff; 
                padding: 8px 18px; 
                border-radius: 999px; 
                font-weight: 700; 
                display: inline-block; 
                margin-top: 4px; 
                font-size: 13px;
            }
            .hbnb-ticket-content { 
                display: grid; 
                grid-template-columns: 1fr 1fr; 
                gap: 15px; 
                margin-bottom: 20px; 
            }
            .hbnb-ticket-section { 
                background: #fafafa; 
                padding: 15px; 
                border-radius: 8px; 
                border: 1px solid #e1e1e1; 
            }
            .hbnb-ticket-section h3 { 
                color: #2ecc71; 
                margin: 0 0 10px 0; 
                font-size: 15px; 
                font-weight: 600;
                border-bottom: 1px solid #e1e1e1;
                padding-bottom: 6px;
                text-transform: uppercase;
                letter-spacing: 0.4px;
            }
            .hbnb-ticket-info-row { 
                display: flex; 
                justify-content: space-between; 
                padding: 5px 0;
                border-bottom: 1px solid #f0f0f0;
                font-size: 13px;
            }
            .hbnb-ticket-info-row:last-child {
                border-bottom: none;
            }
            .hbnb-ticket-info-row strong { 
                color: #111111; 
                font-weight: 600;
                margin-right: 8px;
            }
            .hbnb-ticket-contact {
                background: #fafafa;
                padding: 15px;
                border-radius: 8px;
                border: 1px solid #e1e1e1;
                margin-top: 10px;
                text-align: center;
                grid-column: 1 / -1;
            }
            .hbnb-ticket-contact h4 {
                color: #2ecc71;
                margin: 0 0 10px 0;
                font-size: 15px;
                font-weight: 600;
            }
            .hbnb-ticket-contact p {
                margin: 3px 0;
                font-size: 13px;
            }
            .hbnb-ticket-footer { 
                text-align: center; 
                padding-top: 18px; 
                border-top: 1px dashed #cccccc; 
                color: #555555; 
                font-size: 12px; 
                line-height: 1.6;
            }
            @media (max-width: 600px) {
                .hbnb-ticket-body {
                    padding: 15px;
                }
                .hbnb-ticket-content {
                    grid-template-columns: 1fr;
                }
                .hbnb-ticket {
                    padding: 15px;
                }
                .hbnb-ticket-header h2 {
                    font-size: 19px;
                }
            }
            @media print { 
                .hbnb-ticket-body { 
                    background: #ffffff; 
                    padding: 0; 
                }
                .hbnb-ticket-container { 
                    max-width: none; 
                    margin: 0; 
                }
                .hbnb-ticket { 
                    box-shadow: none; 
                    border: 1px solid #000000; 
                    page-break-after: always; 
                }
            }
        </style>
    </head>
    <body class="hbnb-ticket-body">
        <div class="hbnb-ticket-container">
            <div class="hbnb-ticket">
                <div class="hbnb-ticket-header">
                    <h2>Ticket de transfert aéroport</h2>
                    <div class="hbnb-booking-ref"><?php echo esc_html($booking_data['booking_ref']); ?></div>
                </div>
                
                <div class="hbnb-ticket-content">
                    <div class="hbnb-ticket-section">
                        <h3>Détails du passager</h3>
                        <div class="hbnb-ticket-info">
                            <div class="hbnb-ticket-info-row">
                                <strong>Nom</strong>
                                <span><?php echo esc_html($booking_data['first_name']); ?></span>
                            </div>
                            <div class="hbnb-ticket-info-row">
                                <strong>Email</strong>
                                <span><?php echo esc_html($booking_data['email']); ?></span>
                            </div>
                            <div class="hbnb-ticket-info-row">
                                <strong>Téléphone</strong>
                                <span><?php echo esc_html($booking_data['phone']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hbnb-ticket-section">
                        <h3>Détails du transfert</h3>
                        <div class="hbnb-ticket-info">
                            <div class="hbnb-ticket-info-row">
                                <strong>Véhicule</strong>
                                <span><?php echo esc_html($booking_data['vehicle']); ?></span>
                            </div>
                            <div class="hbnb-ticket-info-row">
                                <strong>Type de trajet</strong>
                                <span><?php echo esc_html($booking_data['trip_type'] === 'roundtrip' ? 'Aller & Retour' : 'Aller Simple'); ?></span>
                            </div>
                            <div class="hbnb-ticket-info-row">
                                <strong>Prix</strong>
                                <span style="font-weight:700;"><?php echo esc_html($booking_data['price']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hbnb-ticket-section">
                        <h3>Itinéraire</h3>
                        <div class="hbnb-ticket-info">
                            <div class="hbnb-ticket-info-row">
                                <strong>De</strong>
                                <span><?php echo esc_html($booking_data['pickup']); ?></span>
                            </div>
                            <div class="hbnb-ticket-info-row">
                                <strong>À</strong>
                                <span><?php echo esc_html($booking_data['dropoff']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hbnb-ticket-section">
                        <h3>Date & heure</h3>
                        <div class="hbnb-ticket-info">
                            <div class="hbnb-ticket-info-row">
                                <strong>Date</strong>
                                <span><?php echo esc_html($booking_data['date']); ?></span>
                            </div>
                            <div class="hbnb-ticket-info-row">
                                <strong>Heure</strong>
                                <span><?php echo esc_html($booking_data['time']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hbnb-ticket-contact">
                        <h4>Contact</h4>
                        <p><strong>Téléphone :</strong> +212 661-955396</p>
                        <p><strong>Email :</strong> casteltrip1@gmail.com</p>
                    </div>
                </div>
                
                <div class="hbnb-ticket-footer">
                    <p><strong>Merci d'avoir choisi notre service.</strong></p>
                    <p>Veuillez présenter ce ticket à votre chauffeur. Pour toute question, contactez-nous au +212 661-955396.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    $ticket_html = ob_get_clean();

    echo $ticket_html;
    exit;
}
add_action( 'init', 'atm_generate_ticket_download' );

/**
 * Shortcode: [airport_transfer_manager]
 */
function atm_booking_form_shortcode( $atts ) {
    static $css_printed   = false;
    static $form_instance = 0;
    $form_instance++;

    ob_start();

    if ( ! $css_printed ) {
        echo '<style>' . atm_get_frontend_css() . '</style>';
        $css_printed = true;
    }

    $step = isset( $_POST['atm_step'] ) ? sanitize_text_field( $_POST['atm_step'] ) : '0';

    if ( $step === '2' && isset( $_POST['atm_back_to_1'] ) ) {
        $step = '1';
    }
    if ( $step === '3' && isset( $_POST['atm_back_to_1'] ) ) {
        $step = '1';
    }

    $prefill_date      = isset( $_POST['atm_date'] ) ? sanitize_text_field( $_POST['atm_date'] ) : '';
    $prefill_time      = isset( $_POST['atm_time'] ) ? sanitize_text_field( $_POST['atm_time'] ) : '';
    $prefill_trip_type = isset( $_POST['atm_trip_type'] ) ? sanitize_text_field( $_POST['atm_trip_type'] ) : 'one_way';
    $prefill_pickup    = isset( $_POST['atm_pickup'] ) ? sanitize_text_field( $_POST['atm_pickup'] ) : '';
    $prefill_dropoff   = isset( $_POST['atm_dropoff'] ) ? sanitize_text_field( $_POST['atm_dropoff'] ) : '';

    $locations_mapping = atm_get_dropoff_locations_mapping();
    $all_locations     = atm_get_all_locations();

    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        function scrollToForm() {
            const form = document.getElementById("atm-booking-form-' . $form_instance . '");
            if (form) {
                setTimeout(function() {
                    form.scrollIntoView({ 
                        behavior: "smooth",
                        block: "start",
                        inline: "nearest"
                    });
                    form.style.transition = "all 0.3s ease";
                    form.style.boxShadow = "0 0 0 3px rgba(255, 94, 20, 0.3)";
                    setTimeout(function() {
                        form.style.boxShadow = "";
                    }, 1000);
                }, 100);
            }
        }

        if (' . intval( $step ) . ' > 0) {
            scrollToForm();
        }

        const forms = document.querySelectorAll("#atm-booking-form-' . $form_instance . ' form");
        forms.forEach(form => {
            form.addEventListener("submit", function() {
                sessionStorage.setItem("atm_scroll_to_form_' . $form_instance . '", "true");
            });
        });

        if (sessionStorage.getItem("atm_scroll_to_form_' . $form_instance . '") === "true") {
            scrollToForm();
            sessionStorage.removeItem("atm_scroll_to_form_' . $form_instance . '");
        }

        const hbnb_radioLabels = document.querySelectorAll(".hbnb-radio-label");
        hbnb_radioLabels.forEach(label => {
            const radio = label.previousElementSibling;
            if (radio && radio.checked) {
                label.parentElement.classList.add("selected");
            }
            
            label.addEventListener("click", function() {
                const parent = this.parentElement;
                const allOptions = parent.parentElement.querySelectorAll(".hbnb-radio-option");
                allOptions.forEach(opt => opt.classList.remove("selected"));
                parent.classList.add("selected");
            });
        });

        const hbnb_vehicleCards = document.querySelectorAll(".hbnb-vehicle-card");
        hbnb_vehicleCards.forEach(card => {
            const radio = card.querySelector("input[type=\'radio\']");
            
            card.addEventListener("click", function(e) {
                if (e.target.tagName.toLowerCase() === "input") return;
                hbnb_vehicleCards.forEach(c => c.classList.remove("selected"));
                this.classList.add("selected");
                if (radio) radio.checked = true;
            });
        });

        const pickupSelect = document.getElementById("atm_pickup");
        const dropoffSelect = document.getElementById("atm_dropoff");
        const locationsMapping = ' . json_encode( $locations_mapping ) . ';

        function updateDropoffLocations() {
            if (!dropoffSelect) return;
            const selectedPickup = pickupSelect ? pickupSelect.value : "";
            dropoffSelect.innerHTML = \'<option value="">Sélectionnez le lieu de dépose</option>\';
            
            if (selectedPickup && locationsMapping[selectedPickup]) {
                locationsMapping[selectedPickup].forEach(function(dropoff) {
                    const option = document.createElement("option");
                    option.value = dropoff;
                    option.textContent = dropoff;
                    dropoffSelect.appendChild(option);
                });
            }
            
            if ("' . $prefill_dropoff . '" && selectedPickup === "' . $prefill_pickup . '") {
                dropoffSelect.value = "' . $prefill_dropoff . '";
            }
        }

        if (pickupSelect) {
            pickupSelect.addEventListener("change", updateDropoffLocations);
            if (pickupSelect.value) {
                updateDropoffLocations();
            }
        }
        
        const dateInput = document.getElementById("atm_date");
        if (dateInput) {
            const today = new Date().toISOString().split("T")[0];
            dateInput.min = today;
            if (!dateInput.value) {
                dateInput.value = today;
            }
        }
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                }
            });
        }, observerOptions);
        
        hbnb_vehicleCards.forEach(card => {
            card.style.opacity = "0";
            card.style.transform = "translateY(20px)";
            card.style.transition = "opacity 0.5s ease, transform 0.5s ease";
            observer.observe(card);
        });
        
        // Correction spécifique pour organiser les cartes sur mobile
        function organizeMobileCards() {
            const isMobile = window.innerWidth <= 768;
            const vehicleGrid = document.querySelector(".hbnb-vehicles-grid");
            const vehicleCards = document.querySelectorAll(".hbnb-vehicle-card");
            
            if (isMobile && vehicleGrid && vehicleCards.length > 0) {
                // Forcer l\'affichage en bloc pour chaque carte
                vehicleCards.forEach(card => {
                    card.style.display = "block";
                    card.style.float = "none";
                    card.style.margin = "0 auto 20px auto";
                    card.style.width = "96%";
                });
                
                // Forcer le conteneur à afficher en bloc
                vehicleGrid.style.display = "block";
            }
        }
        
        organizeMobileCards();
        window.addEventListener("resize", organizeMobileCards);
        window.addEventListener("load", organizeMobileCards);
        
        // Prévenir le débordement horizontal
        document.body.style.overflowX = "hidden";
        document.body.style.width = "100%";
    });
    </script>
    ';

    /**
     * Étape 3 : traiter la réservation
     */
    if ( $step === '3' && isset( $_POST['atm_step3_nonce'] ) && wp_verify_nonce( $_POST['atm_step3_nonce'], 'atm_step3_action' ) ) {

        $date       = isset( $_POST['atm_date'] ) ? sanitize_text_field( $_POST['atm_date'] ) : '';
        $time       = isset( $_POST['atm_time'] ) ? sanitize_text_field( $_POST['atm_time'] ) : '';
        $trip_type  = isset( $_POST['atm_trip_type'] ) ? sanitize_text_field( $_POST['atm_trip_type'] ) : 'one_way';
        $pickup     = isset( $_POST['atm_pickup'] ) ? sanitize_text_field( $_POST['atm_pickup'] ) : '';
        $dropoff    = isset( $_POST['atm_dropoff'] ) ? sanitize_text_field( $_POST['atm_dropoff'] ) : '';
        $vehicle_id = isset( $_POST['atm_vehicle_id'] ) ? intval( $_POST['atm_vehicle_id'] ) : 0;

        $billing_first_name = isset( $_POST['atm_billing_first_name'] ) ? sanitize_text_field( $_POST['atm_billing_first_name'] ) : '';
        $billing_company    = isset( $_POST['atm_billing_company'] ) ? sanitize_text_field( $_POST['atm_billing_company'] ) : '';
        $billing_country    = isset( $_POST['atm_billing_country'] ) ? sanitize_text_field( $_POST['atm_billing_country'] ) : 'Maroc';
        $billing_address_1  = isset( $_POST['atm_billing_address_1'] ) ? sanitize_text_field( $_POST['atm_billing_address_1'] ) : '';
        $billing_address_2  = isset( $_POST['atm_billing_address_2'] ) ? sanitize_text_field( $_POST['atm_billing_address_2'] ) : '';
        $billing_city       = isset( $_POST['atm_billing_city'] ) ? sanitize_text_field( $_POST['atm_billing_city'] ) : '';
        $billing_postcode   = isset( $_POST['atm_billing_postcode'] ) ? sanitize_text_field( $_POST['atm_billing_postcode'] ) : '';
        $billing_phone      = isset( $_POST['atm_billing_phone'] ) ? sanitize_text_field( $_POST['atm_billing_phone'] ) : '';
        $billing_email      = isset( $_POST['atm_billing_email'] ) ? sanitize_email( $_POST['atm_billing_email'] ) : '';
        $order_notes        = isset( $_POST['atm_order_notes'] ) ? sanitize_textarea_field( $_POST['atm_order_notes'] ) : '';
        $payment_method     = isset( $_POST['atm_payment_method'] ) ? sanitize_text_field( $_POST['atm_payment_method'] ) : '';

        $price         = atm_get_price_for_vehicle_route( $vehicle_id, $pickup, $dropoff, $trip_type );
        $vehicle_title = get_the_title( $vehicle_id );

        if ( ! $price || ! $vehicle_title || ! is_email( $billing_email ) || empty( $billing_first_name ) ) {
            echo '<div id="atm-booking-form-' . $form_instance . '" class="hbnb-booking-confirmation">
                    <div class="hbnb-form-header"><h3>Erreur de Réservation</h3></div>
                    <div class="hbnb-form-content">
                        <div class="hbnb-alert hbnb-alert-error">
                            <div>Un problème est survenu lors de votre réservation. Veuillez revenir en arrière et réessayer.</div>
                        </div>
                    </div>
                  </div>';
            return ob_get_clean();
        }

        $trip_label      = ( $trip_type === 'roundtrip' ) ? 'Aller & Retour' : 'Aller Simple';
        $formatted_price = atm_format_price( $price );
        $booking_ref     = 'ATM-' . date('Ymd') . '-' . substr(md5(uniqid()), 0, 8);

        $payment_label = '';
        if ( $payment_method === 'bank' ) {
            $payment_label = 'Virement bancaire';
        } elseif ( $payment_method === 'cash' ) {
            $payment_label = 'Paiement sur place (Cash ou TPE)';
        } else {
            $payment_label = 'Non spécifié';
        }

        $blogname    = get_bloginfo('name');
        $admin_email = 'casteltrip1@gmail.com';
        
        $admin_email_content  = "NOUVELLE RÉSERVATION DE TRANSFERT AÉROPORT\n";
        $admin_email_content .= "=====================================\n\n";
        
        $admin_email_content .= "RÉFÉRENCE DE RÉSERVATION: " . $booking_ref . "\n\n";
        
        $admin_email_content .= "INFORMATIONS CLIENT:\n";
        $admin_email_content .= "--------------------\n";
        $admin_email_content .= "Nom: " . $billing_first_name . "\n";
        $admin_email_content .= "Email: " . $billing_email . "\n";
        $admin_email_content .= "Téléphone: " . $billing_phone . "\n";
        $admin_email_content .= "Société: " . $billing_company . "\n\n";
        
        $admin_email_content .= "ADRESSE:\n";
        $admin_email_content .= $billing_address_1 . " " . $billing_address_2 . "\n";
        $admin_email_content .= $billing_city . ", " . $billing_postcode . "\n";
        $admin_email_content .= $billing_country . "\n\n";
        
        $admin_email_content .= "DÉTAILS DU TRAJET:\n";
        $admin_email_content .= "-------------------\n";
        $admin_email_content .= "De: " . $pickup . "\n";
        $admin_email_content .= "À: " . $dropoff . "\n";
        $admin_email_content .= "Date: " . $date . "\n";
        $admin_email_content .= "Heure: " . $time . "\n";
        $admin_email_content .= "Type de trajet: " . $trip_label . "\n";
        $admin_email_content .= "Véhicule: " . $vehicle_title . "\n";
        $admin_email_content .= "Prix total: " . $formatted_price . "\n";
        $admin_email_content .= "Méthode de paiement: " . $payment_label . "\n\n";
        
        if ( ! empty( $order_notes ) ) {
            $admin_email_content .= "NOTES DU CLIENT:\n";
            $admin_email_content .= "-----------------\n";
            $admin_email_content .= $order_notes . "\n\n";
        }
        
        $admin_email_content .= "================\n";
        $admin_email_content .= "Réservation reçue: " . date('Y-m-d H:i:s') . "\n";
        $admin_email_content .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

        $admin_headers = array(
            'From: ' . $blogname . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $billing_email
        );

        $admin_email_sent = wp_mail(
            $admin_email,
            'Nouvelle Réservation de Transfert Aéroport - ' . $billing_first_name,
            $admin_email_content,
            $admin_headers
        );

        $booking_data = array(
            'first_name'  => $billing_first_name,
            'email'       => $billing_email,
            'phone'       => $billing_phone,
            'date'        => $date,
            'time'        => $time,
            'pickup'      => $pickup,
            'dropoff'     => $dropoff,
            'vehicle'     => $vehicle_title,
            'trip_type'   => $trip_type,
            'price'       => $formatted_price,
            'booking_ref' => $booking_ref
        );

        $client_email_html = atm_generate_email_ticket( $booking_data );
        
        $client_headers = array(
            'From: ' . $blogname . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $admin_email
        );

        $client_email_sent = wp_mail(
            $billing_email,
            'Votre Ticket de Transfert Aéroport - ' . $booking_ref,
            $client_email_html,
            $client_headers
        );

        $whatsapp_message = rawurlencode(
            "RÉSERVATION DE TRANSFERT AÉROPORT CONFIRMÉE\n" .
            "------------------------------------------\n" .
            "Référence: " . $booking_ref . "\n" .
            "Passager: " . $billing_first_name . "\n" .
            "Contact: " . $billing_phone . "\n\n" .
            "DÉTAILS DE L\'ITINÉRAIRE\n" .
            "De: " . $pickup . "\n" .
            "À: " . $dropoff . "\n" .
            "Date: " . $date . "\n" .
            "Heure: " . $time . "\n\n" .
            "INFORMATIONS VÉHICULE\n" .
            "Type: " . $vehicle_title . "\n" .
            "Trajet: " . $trip_label . "\n" .
            "Paiement: " . $payment_label . "\n\n" .
            "PRIX\n" .
            "Total: " . $formatted_price . "\n\n" .
            "TICKET ENVOYÉ\n" .
            "Votre e-ticket a été envoyé à: " . $billing_email . "\n\n" .
            "CONTACTEZ-NOUS\n" .
            "Pour tout changement, appelez: +212 661-955396\n\n" .
            "Merci pour votre réservation."
        );

        echo '<div id="atm-booking-form-' . $form_instance . '" class="hbnb-booking-confirmation">';
        echo '<div class="hbnb-form-header"><h3>Réservation confirmée</h3></div>';
        echo '<div class="hbnb-form-content">';
        
        echo '<div class="hbnb-progress-bar">';
        echo '<div class="hbnb-progress-step completed"><div class="hbnb-step-number">1</div><div class="hbnb-step-label">Recherche</div></div>';
        echo '<div class="hbnb-progress-step completed"><div class="hbnb-step-number">2</div><div class="hbnb-step-label">Véhicule</div></div>';
        echo '<div class="hbnb-progress-step completed"><div class="hbnb-step-number">3</div><div class="hbnb-step-label">Facturation</div></div>';
        echo '<div class="hbnb-progress-step active"><div class="hbnb-step-number">4</div><div class="hbnb-step-label">Confirmation</div></div>';
        echo '</div>';
        
        echo '<div class="hbnb-alert hbnb-alert-success">';
        echo '<div><strong>Merci, ' . esc_html( $billing_first_name ) . '.</strong><br>Votre transfert aéroport a été réservé et votre e-ticket a été envoyé à votre adresse email.</div>';
        echo '</div>';
        
        echo '<div class="hbnb-ticket">';
        echo '<div class="hbnb-ticket-header">';
        echo '<div class="hbnb-ticket-title">Votre ticket de transfert</div>';
        echo '<div class="hbnb-ticket-ref">' . $booking_ref . '</div>';
        echo '</div>';
        
        echo '<div class="hbnb-ticket-grid">';
        echo '<div class="hbnb-ticket-section">';
        echo '<h4 class="hbnb-section-title">Informations passager</h4>';
        echo '<div class="hbnb-ticket-row"><strong>Nom :</strong><span>' . esc_html( $billing_first_name ) . '</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Email :</strong><span>' . esc_html( $billing_email ) . '</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Téléphone :</strong><span>' . esc_html( $billing_phone ) . '</span></div>';
        echo '</div>';
        
        echo '<div class="hbnb-ticket-section">';
        echo '<h4 class="hbnb-section-title">Détails du transfert</h4>';
        echo '<div class="hbnb-ticket-row"><strong>Véhicule :</strong><span>' . esc_html( $vehicle_title ) . '</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Trajet :</strong><span>' . esc_html( $trip_label ) . '</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Prix :</strong><span style="color:var(--primary);font-weight:bold;">' . esc_html( $formatted_price ) . '</span></div>';
        echo '</div>';
        
        echo '<div class="hbnb-ticket-section">';
        echo '<h4 class="hbnb-section-title">Itinéraire</h4>';
        echo '<div class="hbnb-ticket-row"><strong>Départ :</strong><span>' . esc_html( $pickup ) . '</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Arrivée :</strong><span>' . esc_html( $dropoff ) . '</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Date et heure :</strong><span>' . esc_html( $date ) . ' à ' . esc_html( $time ) . '</span></div>';
        echo '</div>';
        
        echo '<div class="hbnb-ticket-section">';
        echo '<h4 class="hbnb-section-title">Contact et support</h4>';
        echo '<div class="hbnb-ticket-row"><strong>Téléphone :</strong><span>+212 661-955396</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Email :</strong><span>casteltrip1@gmail.com</span></div>';
        echo '<div class="hbnb-ticket-row"><strong>Référence :</strong><span>' . $booking_ref . '</span></div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="hbnb-data-display">';
        echo '<h4 class="hbnb-info-title" style="color:#2ecc71;font-weight:600;margin-top:0;margin-bottom:15px;">Récapitulatif de votre réservation</h4>';
        echo '<div class="hbnb-data-item">';
        echo '<div class="hbnb-data-label">Nom du passager</div>';
        echo '<div class="hbnb-data-value">' . esc_html( $billing_first_name ) . '</div>';
        echo '</div>';
        echo '<div class="hbnb-data-item">';
        echo '<div class="hbnb-data-label">Téléphone</div>';
        echo '<div class="hbnb-data-value">' . esc_html( $billing_phone ) . '</div>';
        echo '</div>';
        echo '<div class="hbnb-data-item">';
        echo '<div class="hbnb-data-label">Véhicule</div>';
        echo '<div class="hbnb-data-value">' . esc_html( $vehicle_title ) . '</div>';
        echo '</div>';
        echo '<div class="hbnb-data-item">';
        echo '<div class="hbnb-data-label">Itinéraire</div>';
        echo '<div class="hbnb-data-value">' . esc_html( $pickup . ' → ' . $dropoff ) . '</div>';
        echo '</div>';
        echo '<div class="hbnb-data-item">';
        echo '<div class="hbnb-data-label">Date et heure</div>';
        echo '<div class="hbnb-data-value">' . esc_html( $date . ' à ' . $time ) . '</div>';
        echo '</div>';
        echo '<div class="hbnb-data-item">';
        echo '<div class="hbnb-data-label">Prix total</div>';
        echo '<div class="hbnb-data-value" style="color:var(--primary);font-weight:bold;">' . esc_html( $formatted_price ) . '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="hbnb-ticket-actions">';
        
        echo '<form method="post" target="_blank" style="display: inline;">';
        wp_nonce_field( 'atm_generate_ticket', 'atm_ticket_nonce' );
        echo '<input type="hidden" name="atm_generate_ticket" value="1">';
        echo '<input type="hidden" name="first_name" value="' . esc_attr( $billing_first_name ) . '">';
        echo '<input type="hidden" name="email" value="' . esc_attr( $billing_email ) . '">';
        echo '<input type="hidden" name="phone" value="' . esc_attr( $billing_phone ) . '">';
        echo '<input type="hidden" name="date" value="' . esc_attr( $date ) . '">';
        echo '<input type="hidden" name="time" value="' . esc_attr( $time ) . '">';
        echo '<input type="hidden" name="pickup" value="' . esc_attr( $pickup ) . '">';
        echo '<input type="hidden" name="dropoff" value="' . esc_attr( $dropoff ) . '">';
        echo '<input type="hidden" name="vehicle" value="' . esc_attr( $vehicle_title ) . '">';
        echo '<input type="hidden" name="trip_type" value="' . esc_attr( $trip_type ) . '">';
        echo '<input type="hidden" name="price" value="' . esc_attr( $formatted_price ) . '">';
        echo '<button type="submit" class="hbnb-btn hbnb-ticket-btn">Télécharger le ticket</button>';
        echo '</form>';
        
        echo '<button onclick="window.print()" class="hbnb-btn hbnb-ticket-btn print">Imprimer le ticket</button>';
        
        echo '<a href="https://wa.me/212661955396?text=' . $whatsapp_message . '" class="hbnb-btn hbnb-ticket-btn whatsapp" target="_blank">Envoyer sur WhatsApp</a>';
        
        echo '</div>';
        echo '</div>';
        
        echo '<div class="hbnb-summary-box">';
        echo '<div class="hbnb-summary-title">Instructions importantes</div>';
        echo '<p><strong>Notre équipe vous contactera au ' . esc_html( $billing_phone ) . ' dans les 24 heures pour confirmer votre transfert.</strong></p>';
        echo '<ul style="margin:10px 0 0 20px;color:#666;font-size:14px;">';
        echo '<li>Veuillez être prêt 15 minutes avant l\'heure de prise en charge.</li>';
        echo '<li>Ayez votre pièce d\'identité et ce ticket à portée de main.</li>';
        echo '<li>Gardez votre téléphone accessible pour la communication avec le chauffeur.</li>';
        echo '<li>Contactez-nous immédiatement en cas de changement de plans.</li>';
        echo '</ul>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Étape 2 : formulaire de facturation
     */
    if ( $step === '2' && isset( $_POST['atm_step2_nonce'] ) && wp_verify_nonce( $_POST['atm_step2_nonce'], 'atm_step2_action' ) ) {

        $date       = isset( $_POST['atm_date'] ) ? sanitize_text_field( $_POST['atm_date'] ) : '';
        $time       = isset( $_POST['atm_time'] ) ? sanitize_text_field( $_POST['atm_time'] ) : '';
        $trip_type  = isset( $_POST['atm_trip_type'] ) ? sanitize_text_field( $_POST['atm_trip_type'] ) : 'one_way';
        $pickup     = isset( $_POST['atm_pickup'] ) ? sanitize_text_field( $_POST['atm_pickup'] ) : '';
        $dropoff    = isset( $_POST['atm_dropoff'] ) ? sanitize_text_field( $_POST['atm_dropoff'] ) : '';
        $vehicle_id = isset( $_POST['atm_vehicle_id'] ) ? intval( $_POST['atm_vehicle_id'] ) : 0;

        $vehicle_title = get_the_title( $vehicle_id );
        $price         = atm_get_price_for_vehicle_route( $vehicle_id, $pickup, $dropoff, $trip_type );

        if ( ! $vehicle_title || ! $price ) {
            echo '<div id="atm-booking-form-' . $form_instance . '" class="hbnb-step3-form">
                    <div class="hbnb-form-header"><h3>Erreur de sélection</h3></div>
                    <div class="hbnb-form-content">
                        <div class="hbnb-alert hbnb-alert-error">
                            <div>Un problème est survenu. Veuillez revenir en arrière et réessayer.</div>
                        </div>
                    </div>
                  </div>';
            return ob_get_clean();
        }

        $trip_label      = ( $trip_type === 'roundtrip' ) ? 'Aller & Retour' : 'Aller Simple';
        $formatted_price = atm_format_price( $price );

        ?>
        <div id="atm-booking-form-<?php echo $form_instance; ?>" class="hbnb-step3-form">
            <div class="hbnb-form-header"><h3>Détails de facturation</h3></div>
            <div class="hbnb-form-content">
                <div class="hbnb-progress-bar">
                    <div class="hbnb-progress-step completed"><div class="hbnb-step-number">1</div><div class="hbnb-step-label">Recherche</div></div>
                    <div class="hbnb-progress-step completed"><div class="hbnb-step-number">2</div><div class="hbnb-step-label">Véhicule</div></div>
                    <div class="hbnb-progress-step active"><div class="hbnb-step-number">3</div><div class="hbnb-step-label">Facturation</div></div>
                    <div class="hbnb-progress-step"><div class="hbnb-step-number">4</div><div class="hbnb-step-label">Confirmation</div></div>
                </div>
                
                <div class="hbnb-summary-box">
                    <div class="hbnb-summary-title" style="color:#2ecc71;font-weight:600;">Résumé de votre réservation</div>
                    <div class="hbnb-summary-details">
                        <div class="hbnb-summary-item">
                            <div class="hbnb-summary-label">Véhicule</div>
                            <div class="hbnb-summary-value"><?php echo esc_html( $vehicle_title ); ?></div>
                        </div>
                        <div class="hbnb-summary-item">
                            <div class="hbnb-summary-label">Type de trajet</div>
                            <div class="hbnb-summary-value"><?php echo esc_html( $trip_label ); ?></div>
                        </div>
                        <div class="hbnb-summary-item">
                            <div class="hbnb-summary-label">Itinéraire</div>
                            <div class="hbnb-summary-value"><?php echo esc_html( $pickup . ' → ' . $dropoff ); ?></div>
                        </div>
                        <div class="hbnb-summary-item">
                            <div class="hbnb-summary-label">Date et heure</div>
                            <div class="hbnb-summary-value"><?php echo esc_html( $date . ' à ' . $time ); ?></div>
                        </div>
                        <div class="hbnb-summary-item">
                            <div class="hbnb-summary-label">Montant total</div>
                            <div class="hbnb-summary-value" style="color:var(--primary);font-weight:bold;"><?php echo esc_html( $formatted_price ); ?></div>
                        </div>
                    </div>
                </div>
                
                <form method="post">
                    <?php wp_nonce_field( 'atm_step3_action', 'atm_step3_nonce' ); ?>
                    <input type="hidden" name="atm_step" value="3">
                    <input type="hidden" name="atm_date" value="<?php echo esc_attr( $date ); ?>">
                    <input type="hidden" name="atm_time" value="<?php echo esc_attr( $time ); ?>">
                    <input type="hidden" name="atm_trip_type" value="<?php echo esc_attr( $trip_type ); ?>">
                    <input type="hidden" name="atm_pickup" value="<?php echo esc_attr( $pickup ); ?>">
                    <input type="hidden" name="atm_dropoff" value="<?php echo esc_attr( $dropoff ); ?>">
                    <input type="hidden" name="atm_vehicle_id" value="<?php echo esc_attr( $vehicle_id ); ?>">

                    <div class="hbnb-billing-layout">
                        <div>
                            <h4 class="hbnb-info-title" style="color:#2ecc71;font-weight:600;margin-top:0;margin-bottom:18px;">Informations personnelles</h4>
                            
                            <div class="hbnb-form-grid">
                                <div class="hbnb-form-group">
                                    <label for="atm_billing_first_name">Prénom *</label>
                                    <input type="text" name="atm_billing_first_name" id="atm_billing_first_name" class="hbnb-form-control" required>
                                </div>
                                
                                <div class="hbnb-form-group">
                                    <label for="atm_billing_company">Nom de la société</label>
                                    <input type="text" name="atm_billing_company" id="atm_billing_company" class="hbnb-form-control">
                                </div>
                                
                                <div class="hbnb-form-group">
                                    <label for="atm_billing_country">Pays *</label>
                                    <input type="text" name="atm_billing_country" id="atm_billing_country" class="hbnb-form-control" value="Maroc" required>
                                </div>
                                
                                <div class="hbnb-form-group">
                                    <label for="atm_billing_city">Ville *</label>
                                    <input type="text" name="atm_billing_city" id="atm_billing_city" class="hbnb-form-control" required>
                                </div>
                                
                                <div class="hbnb-form-group" style="grid-column:span 2;">
                                    <label for="atm_billing_address_1">Adresse *</label>
                                    <input type="text" name="atm_billing_address_1" id="atm_billing_address_1" class="hbnb-form-control" placeholder="Numéro et nom de rue" required>
                                </div>
                                
                                <div class="hbnb-form-group" style="grid-column:span 2;">
                                    <label for="atm_billing_address_2">Complément d\'adresse</label>
                                    <input type="text" name="atm_billing_address_2" id="atm_billing_address_2" class="hbnb-form-control" placeholder="Appartement, étage, etc.">
                                </div>
                                
                                <div class="hbnb-form-group">
                                    <label for="atm_billing_postcode">Code postal *</label>
                                    <input type="text" name="atm_billing_postcode" id="atm_billing_postcode" class="hbnb-form-control" required>
                                </div>
                                
                                <div class="hbnb-form-group">
                                    <label for="atm_billing_phone">Téléphone *</label>
                                    <input type="tel" name="atm_billing_phone" id="atm_billing_phone" class="hbnb-form-control" required>
                                </div>
                                
                                <div class="hbnb-form-group" style="grid-column:span 2;">
                                    <label for="atm_billing_email">Adresse email *</label>
                                    <input type="email" name="atm_billing_email" id="atm_billing_email" class="hbnb-form-control" required>
                                </div>
                                
                                <div class="hbnb-form-group" style="grid-column:span 2;">
                                    <label for="atm_order_notes">Notes supplémentaires</label>
                                    <textarea name="atm_order_notes" id="atm_order_notes" class="hbnb-form-control" rows="4" placeholder="Informations complémentaires pour votre transfert..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="hbnb-order-summary">
                                <div class="hbnb-order-title">Votre commande</div>
                                
                                <div class="hbnb-order-details">
                                    <div class="hbnb-order-row">
                                        <div>
                                            <strong><?php echo esc_html( $vehicle_title ); ?></strong><br>
                                            <small style="color:var(--gray);">
                                                <?php echo esc_html( $pickup . ' → ' . $dropoff ); ?><br>
                                                <?php echo esc_html( $trip_label ); ?> • <?php echo esc_html( $date ); ?> à <?php echo esc_html( $time ); ?>
                                            </small>
                                        </div>
                                        <div style="text-align:right;"><?php echo esc_html( $formatted_price ); ?></div>
                                    </div>
                                    
                                    <div class="hbnb-order-row" style="padding-top:15px;border-top:1px solid var(--border);">
                                        <div><strong>Sous-total</strong></div>
                                        <div><strong><?php echo esc_html( $formatted_price ); ?></strong></div>
                                    </div>
                                    
                                    <div class="hbnb-order-total">
                                        <div style="display:flex;justify-content:space-between;">
                                            <span>Total</span>
                                            <span><?php echo esc_html( $formatted_price ); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="hbnb-payment-methods">
                                    <h4 class="hbnb-info-title" style="color:#2ecc71;font-weight:600;margin-top:0;margin-bottom:15px;">Méthode de paiement</h4>
                                    
                                    <div class="hbnb-payment-option">
                                        <input type="radio" name="atm_payment_method" id="payment_bank" value="bank" checked>
                                        <label for="payment_bank" class="hbnb-payment-label">
                                            <span class="hbnb-payment-icon">■</span>
                                            <strong>Virement bancaire</strong><br>
                                            <small style="color:var(--gray);display:block;margin-top:5px;">
                                                Effectuez le paiement directement depuis votre compte bancaire. Veuillez utiliser la référence de votre commande.
                                            </small>
                                        </label>
                                    </div>
                                    
                                    <div class="hbnb-payment-option">
                                        <input type="radio" name="atm_payment_method" id="payment_cash" value="cash">
                                        <label for="payment_cash" class="hbnb-payment-label">
                                            <span class="hbnb-payment-icon">■</span>
                                            <strong>Paiement sur place</strong><br>
                                            <small style="color:var(--gray);display:block;margin-top:5px;">
                                                Réglez le montant de votre transfert directement à votre chauffeur (espèces ou TPE).
                                            </small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div style="margin-top:22px;padding:14px;background:rgba(255,94,20,0.05);border-radius:var(--radius);border:1px solid rgba(255,94,20,0.2);font-size:13px;color:var(--secondary);">
                                    En cliquant sur « Confirmer la réservation », vous acceptez nos conditions générales de vente.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:14px;margin-top:26px;padding-top:22px;border-top:1px solid var(--border);flex-wrap:wrap;">
                        <button type="submit" name="atm_back_to_1" value="1" class="hbnb-btn hbnb-btn-secondary">Retour</button>
                        <button type="submit" class="hbnb-btn hbnb-btn-primary" style="flex:1;min-width:200px;">Confirmer la réservation</button>
                    </div>
                </form>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Étape 1 : liste des véhicules
     */
    if ( $step === '1' && isset( $_POST['atm_step1_nonce'] ) && wp_verify_nonce( $_POST['atm_step1_nonce'], 'atm_step1_action' ) ) {

        $date      = isset( $_POST['atm_date'] ) ? sanitize_text_field( $_POST['atm_date'] ) : '';
        $time      = isset( $_POST['atm_time'] ) ? sanitize_text_field( $_POST['atm_time'] ) : '';
        $trip_type = isset( $_POST['atm_trip_type'] ) ? sanitize_text_field( $_POST['atm_trip_type'] ) : 'one_way';
        $pickup    = isset( $_POST['atm_pickup'] ) ? sanitize_text_field( $_POST['atm_pickup'] ) : '';
        $dropoff   = isset( $_POST['atm_dropoff'] ) ? sanitize_text_field( $_POST['atm_dropoff'] ) : '';

        echo '<div id="atm-booking-form-' . $form_instance . '" class="hbnb-step2-form">';
        echo '<div class="hbnb-form-header"><h3>Sélectionnez votre véhicule</h3></div>';
        echo '<div class="hbnb-form-content">';
        
        echo '<div class="hbnb-progress-bar">';
        echo '<div class="hbnb-progress-step completed"><div class="hbnb-step-number">1</div><div class="hbnb-step-label">Recherche</div></div>';
        echo '<div class="hbnb-progress-step active"><div class="hbnb-step-number">2</div><div class="hbnb-step-label">Véhicule</div></div>';
        echo '<div class="hbnb-progress-step"><div class="hbnb-step-number">3</div><div class="hbnb-step-label">Facturation</div></div>';
        echo '<div class="hbnb-progress-step"><div class="hbnb-step-number">4</div><div class="hbnb-step-label">Confirmation</div></div>';
        echo '</div>';
        
        echo '<div class="hbnb-summary-box">';
        echo '<div class="hbnb-summary-title" style="color:#2ecc71;font-weight:600;">Critères de votre recherche</div>';
        echo '<div class="hbnb-summary-details">';
        echo '<div class="hbnb-summary-item"><div class="hbnb-summary-label">Date</div><div class="hbnb-summary-value">' . esc_html( $date ) . '</div></div>';
        echo '<div class="hbnb-summary-item"><div class="hbnb-summary-label">Heure</div><div class="hbnb-summary-value">' . esc_html( $time ) . '</div></div>';
        echo '<div class="hbnb-summary-item"><div class="hbnb-summary-label">Type de trajet</div><div class="hbnb-summary-value">' . esc_html( $trip_type === 'roundtrip' ? 'Aller & Retour' : 'Aller Simple' ) . '</div></div>';
        echo '<div class="hbnb-summary-item"><div class="hbnb-summary-label">Itinéraire</div><div class="hbnb-summary-value">' . esc_html( $pickup . ' → ' . $dropoff ) . '</div></div>';
        echo '</div>';
        echo '</div>';
        
        echo '<form method="post">';
        wp_nonce_field( 'atm_step2_action', 'atm_step2_nonce' );
        ?>
        <input type="hidden" name="atm_step" value="2">
        <input type="hidden" name="atm_date" value="<?php echo esc_attr( $date ); ?>">
        <input type="hidden" name="atm_time" value="<?php echo esc_attr( $time ); ?>">
        <input type="hidden" name="atm_trip_type" value="<?php echo esc_attr( $trip_type ); ?>">
        <input type="hidden" name="atm_pickup" value="<?php echo esc_attr( $pickup ); ?>">
        <input type="hidden" name="atm_dropoff" value="<?php echo esc_attr( $dropoff ); ?>">

        <h4 class="hbnb-info-title" style="color:#2ecc71;font-weight:600;margin-bottom:18px;">Véhicules disponibles pour votre trajet</h4>

        <?php
        $vehicles = get_posts( array(
            'post_type'      => 'atm_vehicle',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        $matching_vehicles = array();

        foreach ( $vehicles as $vehicle ) {
            $price = atm_get_price_for_vehicle_route( $vehicle->ID, $pickup, $dropoff, $trip_type );
            if ( $price !== false && $price > 0 ) {
                $matching_vehicles[] = array(
                    'id'       => $vehicle->ID,
                    'title'    => $vehicle->post_title,
                    'price'    => $price,
                    'capacity' => get_post_meta( $vehicle->ID, 'atm_capacity', true ),
                    'luggage'  => get_post_meta( $vehicle->ID, 'atm_luggage', true ),
                    'image'    => get_the_post_thumbnail_url( $vehicle->ID, 'medium' ),
                );
            }
        }

        if ( empty( $matching_vehicles ) ) {
            echo '<div class="hbnb-alert hbnb-alert-error">';
            echo '<div><strong>Aucun véhicule disponible.</strong><br>Aucun véhicule n\'est disponible pour cet itinéraire avec les critères sélectionnés. Veuillez essayer avec d\'autres lieux ou une autre date.</div>';
            echo '</div>';
            echo '<div style="margin-top:24px;">';
            echo '<button type="submit" name="atm_back_to_0" value="1" class="hbnb-btn hbnb-btn-primary">Retour à la recherche</button>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
            return ob_get_clean();
        }

        echo '<div class="hbnb-vehicles-grid">';
        foreach ( $matching_vehicles as $v ) :
            $img = $v['image'] ? $v['image'] : 'https://via.placeholder.com/400x250?text=' . urlencode($v['title']);
            ?>
            <div class="hbnb-vehicle-card">
                <input type="radio" name="atm_vehicle_id" value="<?php echo esc_attr( $v['id'] ); ?>" required>
                <div class="hbnb-vehicle-image">
                    <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $v['title'] ); ?>">
                </div>
                <div class="hbnb-vehicle-content">
                    <div class="hbnb-vehicle-title"><?php echo esc_html( $v['title'] ); ?></div>
                    <div class="hbnb-vehicle-features">
                        <?php if ( $v['capacity'] ) : ?>
                            <div class="hbnb-vehicle-feature"><?php echo intval( $v['capacity'] ); ?> passagers</div>
                        <?php endif; ?>
                        <?php if ( $v['luggage'] ) : ?>
                            <div class="hbnb-vehicle-feature"><?php echo intval( $v['luggage'] ); ?> bagages</div>
                        <?php endif; ?>
                    </div>
                    <div class="hbnb-vehicle-price">
                        <?php echo esc_html( atm_format_price( $v['price'] ) ); ?>
                    </div>
                </div>
            </div>
            <?php
        endforeach;
        echo '</div>';
        ?>

        <div style="display:flex;gap:14px;margin-top:32px;padding-top:22px;border-top:1px solid var(--border);flex-wrap:wrap;">
            <button type="submit" name="atm_back_to_0" value="1" class="hbnb-btn hbnb-btn-secondary">Retour</button>
            <button type="submit" class="hbnb-btn hbnb-btn-primary" style="flex:1;min-width:200px;">Continuer vers la facturation</button>
        </div>
        </form>
        </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Étape 0 : formulaire de recherche initial - STEP 1 à 98%
     */
    $locations = atm_get_all_locations();

    if ( empty( $locations ) ) {
        echo '<div id="atm-booking-form-' . $form_instance . '" class="hbnb-booking-form">
                <div class="hbnb-form-header"><h3>Configuration requise</h3></div>
                <div class="hbnb-form-content">
                    <div class="hbnb-alert hbnb-alert-error">
                        <div>Aucun lieu de prise en charge n\'est configuré pour le moment. Veuillez ajouter des itinéraires à vos véhicules dans l\'interface d\'administration WordPress.</div>
                    </div>
                </div>
              </div>';
        return ob_get_clean();
    }

    ?>
    <div id="atm-booking-form-<?php echo $form_instance; ?>" class="hbnb-booking-form hbnb-step1-form">
        <div class="hbnb-form-header">
            <h3>Réservez votre transfert aéroport</h3>
        </div>
        <div class="hbnb-form-content">
            <div class="hbnb-progress-bar">
                <div class="hbnb-progress-step active"><div class="hbnb-step-number">1</div><div class="hbnb-step-label">Recherche</div></div>
                <div class="hbnb-progress-step"><div class="hbnb-step-number">2</div><div class="hbnb-step-label">Véhicule</div></div>
                <div class="hbnb-progress-step"><div class="hbnb-step-number">3</div><div class="hbnb-step-label">Facturation</div></div>
                <div class="hbnb-progress-step"><div class="hbnb-step-number">4</div><div class="hbnb-step-label">Confirmation</div></div>
            </div>
            
            <div class="hbnb-search-hero">
                <form method="post">
                    <?php wp_nonce_field( 'atm_step1_action', 'atm_step1_nonce' ); ?>
                    <input type="hidden" name="atm_step" value="1">
                    
                    <div class="hbnb-search-grid">
                        <!-- Ligne 1 : type + date + heure -->
                        <div class="hbnb-form-group hbnb-field-trip">
                            <label>Type de trajet *</label>
                            <div class="hbnb-radio-group">
                                <div class="hbnb-radio-option">
                                    <input type="radio" name="atm_trip_type" id="trip_one_way" value="one_way"
                                        <?php checked( $prefill_trip_type, 'one_way' ); ?>>
                                    <label for="trip_one_way" class="hbnb-radio-label">Aller simple</label>
                                </div>
                                <div class="hbnb-radio-option">
                                    <input type="radio" name="atm_trip_type" id="trip_roundtrip" value="roundtrip"
                                        <?php checked( $prefill_trip_type, 'roundtrip' ); ?>>
                                    <label for="trip_roundtrip" class="hbnb-radio-label">Aller &amp; retour</label>
                                </div>
                            </div>
                        </div>

                        <div class="hbnb-form-group hbnb-field-date">
                            <label for="atm_date">Date de transfert *</label>
                            <input type="date"
                                name="atm_date"
                                id="atm_date"
                                class="hbnb-form-control"
                                required
                                value="<?php echo esc_attr( $prefill_date ?: date('Y-m-d') ); ?>"
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="hbnb-form-group hbnb-field-time">
                            <label for="atm_time">Heure de prise en charge *</label>
                            <input type="time"
                                name="atm_time"
                                id="atm_time"
                                class="hbnb-form-control"
                                required
                                value="<?php echo esc_attr( $prefill_time ?: '08:00' ); ?>">
                        </div>

                        <!-- Ligne 2 : pickup + dropoff -->
                        <div class="hbnb-form-group hbnb-field-pickup">
                            <label for="atm_pickup">Lieu de prise en charge *</label>
                            <select name="atm_pickup" id="atm_pickup" class="hbnb-form-control" required>
                                <option value="">Sélectionnez un lieu…</option>
                                <?php foreach ( $locations as $loc ) : ?>
                                    <option value="<?php echo esc_attr( $loc ); ?>"
                                        <?php selected( $prefill_pickup, $loc ); ?>>
                                        <?php echo esc_html( $loc ); ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                        </div>

                        <div class="hbnb-form-group hbnb-field-dropoff">
                            <label for="atm_dropoff">Lieu de dépose *</label>
                            <select name="atm_dropoff" id="atm_dropoff" class="hbnb-form-control" required>
                                <option value="">Sélectionnez un lieu…</option>
                                <?php 
                                if ( $prefill_pickup && isset( $locations_mapping[ $prefill_pickup ] ) ) {
                                    foreach ( $locations_mapping[ $prefill_pickup ] as $dropoff_loc ) : ?>
                                        <option value="<?php echo esc_attr( $dropoff_loc ); ?>"
                                            <?php selected( $prefill_dropoff, $dropoff_loc ); ?>>
                                            <?php echo esc_html( $dropoff_loc ); ?>
                                        </option>
                                    <?php endforeach;
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Ligne 3 : bouton -->
                        <div class="hbnb-search-btn">
                            <button type="submit" class="hbnb-btn hbnb-btn-primary">
                                Rechercher les véhicules
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div style="margin-top:30px;padding:20px;background:#f8f9fa;border-radius:var(--radius);border:1px solid var(--border);width:96%;margin-left:auto;margin-right:auto;">
                <h4 class="hbnb-info-title" style="color:#2ecc71;font-weight:600;margin-top:0;">Pourquoi réserver avec nous ?</h4>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:20px;margin-top:15px;">
                    <div style="text-align:center;">
                        <div style="font-weight:600;color:var(--secondary);">Véhicules confortables</div>
                        <div style="font-size:13px;color:var(--gray);">Des véhicules modernes et bien entretenus.</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-weight:600;color:var(--secondary);">Chauffeurs professionnels</div>
                        <div style="font-size:13px;color:var(--gray);">Des chauffeurs expérimentés et courtois.</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-weight:600;color:var(--secondary);">Prix transparents</div>
                        <div style="font-size:13px;color:var(--gray);">Un tarif clair avant la réservation.</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-weight:600;color:var(--secondary);">Support 24/7</div>
                        <div style="font-size:13px;color:var(--gray);">Assistance disponible à tout moment.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'airport_transfer_manager', 'atm_booking_form_shortcode' );
