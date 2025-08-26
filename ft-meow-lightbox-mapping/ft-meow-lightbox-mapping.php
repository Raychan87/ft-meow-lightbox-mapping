<?php
/**
 * Plugin Name:   FotoTechnik - Meow Lightbox Mapping
 * Plugin URI:    https://github.com/Raychan87/ft-meow-lightbox-mapping
 * Description:   Ermöglicht das ändern der Kamera und Objektiv Namen in dem Meow Lightbox Plug-In (v5.3.3)
 * Version:       1.0.1
 * Author:        Raychan
 * Author URI:    https://Fototour-und-technik.de
 * License:       GPLv3
 * License URI:   https://github.com/Raychan87/ft-meow-lightbox-mapping/blob/main/LICENSE
 */

/* --------------------------------------------------------------
 * 0. Beim ersten Laden Standard‑Option anlegen
 * -------------------------------------------------------------- */
function ftmwl_maybe_create_option() {
    if ( false === get_option( 'ftmwl_maps' ) ) {
        // Leere Maps – spätere Render‑Funktionen können damit umgehen.
        add_option( 'ftmwl_maps', array(
            'camera_map' => array(),
            'lens_map'   => array(),
        ) );
    }
}
add_action( 'admin_init', 'ftmwl_maybe_create_option' );

/* --------------------------------------------------------------
 * 1. Admin‑Menü & Settings‑Seite
 * -------------------------------------------------------------- */
add_action( 'admin_menu', 'ftmwl_add_admin_menu' );
function ftmwl_add_admin_menu() {

    /* Top‑Level‑Eintrag „FotoTechnik“ */
    add_menu_page(
        __( 'FotoTechnik', 'fototechnik-mwl-maps' ), // Seitentitel (Browser‑Tab)
        __( 'FotoTechnik', 'fototechnik-mwl-maps' ), // Menü‑Eintrag
        'manage_options',                           // Capability
        'ftmwl-main',                               // Slug des Top‑Level‑Eintrags
        '__return_null',                            // Keine eigene Seite – wir leiten nur weiter
        'dashicons-camera',                         // Icon (optional)
        65                                          // Position im Menü
    );

    /* Den automatisch erzeugten Untermenü‑Eintrag entfernen */
    remove_submenu_page( 'ftmwl-main', 'ftmwl-main' );

    /* Untermenü‑Eintrag „MWL‑Maps“ */
    add_submenu_page(
        'ftmwl-main',                                   // Parent‑Slug
        __( 'Meow Lightbox Mapping', 'fototechnik-mwl-maps' ),       // Seitentitel (Browser‑Tab)
        __( 'Meow Lightbox Mapping', 'fototechnik-mwl-maps' ),       // Menü‑Eintrag im Untermenü
        'manage_options',                               // Capability
        'ftmwl-settings',                               // Slug des Untermenüs (muss mit Settings‑Page übereinstimmen)
        'ftmwl_settings_page'                          // Callback, die das Formular ausgibt
    );
}

/* --------------------------------------------------------------
 * 2. Settings‑API registrieren
 * -------------------------------------------------------------- */
add_action( 'admin_init', 'ftmwl_register_settings' );
function ftmwl_register_settings() {

    register_setting(
        'ftmwl_options_group',   // Settings‑Gruppe
        'ftmwl_maps',            // Options‑Name in DB
        'ftmwl_sanitize_maps'    // Sanitizer‑Callback
    );

    add_settings_section(
        'ftmwl_section_maps',
        __( 'Zum ändern der Objektiv und Kamera Namen in den Exif angaben.', 'fototechnik-mwl-maps' ),
        '__return_false',
        'ftmwl-settings'
    );

    add_settings_field(
        'ftmwl_camera_map',
        __( 'Kamera‑Map', 'fototechnik-mwl-maps' ),
        'ftmwl_render_camera_field',
        'ftmwl-settings',
        'ftmwl_section_maps'
    );

    add_settings_field(
        'ftmwl_lens_map',
        __( 'Objektiv‑Map', 'fototechnik-mwl-maps' ),
        'ftmwl_render_lens_field',
        'ftmwl-settings',
        'ftmwl_section_maps'
    );
}

/* --------------------------------------------------------------
 * 3. Sanitizer – Text → Array
 * -------------------------------------------------------------- */
function ftmwl_sanitize_maps( $input ) {

    $output = array(
        'camera_map' => array(),
        'lens_map'   => array(),
    );

    // Hilfsfunktion: Zeile‑zu‑Array (key => value)
    $parse = function( $string ) {
        $arr   = array();
        $lines = preg_split( "/\r\n|\n|\r/", trim( $string ) );
        foreach ( $lines as $line ) {
            $line = trim( $line );
            // Leere Zeilen oder Kommentare überspringen
            if ( '' === $line || 0 === strpos( $line, '#' ) ) {
                continue;
            }
            // Erlaubte Trennzeichen: =>  oder  :
            if ( preg_match( '/^(.+?)\s*(?:=>|:)\s*(.+)$/', $line, $m ) ) {
                $key   = trim( $m[1] );
                $value = trim( $m[2] );
                if ( $key !== '' && $value !== '' ) {
                    $arr[ $key ] = $value;
                }
            }
        }
        return $arr;
    };

    // Kamera‑Map
    if ( isset( $input['camera_map'] ) && is_string( $input['camera_map'] ) ) {
        $output['camera_map'] = $parse( $input['camera_map'] );
    }

    // Objektiv‑Map
    if ( isset( $input['lens_map'] ) && is_string( $input['lens_map'] ) ) {
        $output['lens_map'] = $parse( $input['lens_map'] );
    }

    return $output;
}

/* --------------------------------------------------------------
 * 4. Render‑Funktionen (HTML für die Textareas)
 * -------------------------------------------------------------- */
function ftmwl_render_camera_field() {
    $options = get_option( 'ftmwl_maps' );
    $map     = isset( $options['camera_map'] ) ? $options['camera_map'] : array();

    // Array → Zeilen‑String (key => value)
    $textarea = '';
    foreach ( $map as $k => $v ) {
        $textarea .= $k . ' => ' . $v . "\n";
    }
    ?>
    <textarea
        name="ftmwl_maps[camera_map]"
        rows="12"
        cols="70"
        class="large-text code"
        style="font-family:monospace;"><?php echo esc_textarea( $textarea ); ?></textarea>
    <p class="description">
        <?php esc_html_e( 'Ein Eintrag pro Zeile, z. B. "ILCE-7M3 => Sony α7 III". Kommentare mit # zulässig.', 'fototechnik-mwl-maps' ); ?>
    </p>
    <?php
}

function ftmwl_render_lens_field() {
    $options = get_option( 'ftmwl_maps' );
    $map     = isset( $options['lens_map'] ) ? $options['lens_map'] : array();

    $textarea = '';
    foreach ( $map as $k => $v ) {
        $textarea .= $k . ' => ' . $v . "\n";
    }
    ?>
    <textarea
        name="ftmwl_maps[lens_map]"
        rows="15"
        cols="70"
        class="large-text code"
        style="font-family:monospace;"><?php echo esc_textarea( $textarea ); ?></textarea>
    <p class="description">
        <?php esc_html_e( 'Ein Eintrag pro Zeile, z. B. "FE 24-240mm F3.5-6.3 OSS => Sony 24-240mm F3.5-6.3 OSS".', 'fototechnik-mwl-maps' ); ?>
    </p>
    <?php
}

/* --------------------------------------------------------------
 * 5. Settings‑Seite (Formular)
 * -------------------------------------------------------------- */
function ftmwl_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Zugriff verweigert.', 'fototechnik-mwl-maps' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Meow Lightbox - Kamera‑ & Objektiv‑Mappings', 'fototechnik-mwl-maps' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'ftmwl_options_group' );   // Nonce + hidden fields
            do_settings_sections( 'ftmwl-settings' );   // Unsere Felder
            submit_button();                           // „Änderungen übernehmen“
            ?>
        </form>
    </div>
    <?php
}

/* --------------------------------------------------------------
 * 6. Filter‑Callbacks (die eigentlichen Mapping‑Funktionen)
 * -------------------------------------------------------------- */
function ftmwl_mwl_img_lens( $value, $mediaId, $meta ) {
    if ( empty( $value ) ) {
        return 'N/A';
    }

    $maps     = get_option( 'ftmwl_maps' );
    $lens_map = isset( $maps['lens_map'] ) ? $maps['lens_map'] : array();

    return $lens_map[ $value ] ?? $value;
}
add_filter( 'mwl_img_lens', 'ftmwl_mwl_img_lens', 10, 3 );

function ftmwl_mwl_img_camera( $value, $mediaId, $meta ) {
    if ( empty( $value ) ) {
        return 'N/A';
    }

    $maps        = get_option( 'ftmwl_maps' );
    $camera_map  = isset( $maps['camera_map'] ) ? $maps['camera_map'] : array();

    return $camera_map[ $value ] ?? $value;
}

add_filter( 'mwl_img_camera', 'ftmwl_mwl_img_camera', 10, 3 );
