<?php
/**
 * Plugin Name:   FotoTechnik – Meow Lightbox Mapping
 * Plugin URI:    https://github.com/Raychan87/ft-meow-lightbox-mapping
 * Description:   Ermöglicht das Ändern der Kamera‑ und Objektiv‑Namen im Meow Lightbox‑Plugin (v5.3.3).
 * Version:       1.0.6
 * Author:        Raychan
 * Author URI:    https://Fototour-und-technik.de
 * License:       GPLv3
 * License URI:   https://github.com/Raychan87/ft-meow-lightbox-mapping/blob/main/LICENSE
 * Text Domain:   ft-meow-lightbox-maps
 * Domain Path:   /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direktzugriff verhindern.
}

/* --------------------------------------------------------------
 * Globale Konstante – Hauptmenü für meine Plugins
 * -------------------------------------------------------------- */
if ( ! defined( 'FT_MENU_SLUG' ) ) {
    define( 'FT_MENU_SLUG', 'fototechnik' );
}

/* --------------------------------------------------------------
 * Beim ersten Laden Standard‑Option anlegen
 * -------------------------------------------------------------- */
function ftmlm_maybe_create_option() {
    if ( false === get_option( 'ftmlm_maps' ) ) {
        // Leere Maps – spätere Render‑Funktionen können damit umgehen.
        add_option( 'ftmlm_maps', array(
            'camera_map' => array(),
            'lens_map'   => array(),
        ) );
    }
}
add_action( 'admin_init', 'ftmlm_maybe_create_option' );

/* --------------------------------------------------------------
 * Admin‑Menü & Settings‑Seite
 * -------------------------------------------------------------- */
add_action( 'admin_menu', 'ftmlm_add_admin_menu' );
function ftmlm_add_admin_menu() {

    // Prüfen, ob das Hauptmenü bereits existiert
    $menu_exists = false;
    global $menu; // $menu ist das Kern‑Array von WP‑Admin‑Menüs

    foreach ( $menu as $item ) {
        // $item[2] enthält den Slug
        if ( isset( $item[2] ) && $item[2] === FT_MENU_SLUG ) {
            $menu_exists = true;
            break;
        }
    }

    // Wenn nicht vorhanden → Hauptmenü anlegen
    // Top‑Level‑Eintrag „FotoTechnik“
    if ( ! $menu_exists ) {

        // ----  Pfad / URL zum eigenen Icon  ----
        $icon_url = plugin_dir_url( __FILE__ ) . 'inc/ft-icon.png';
        if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'inc/ft-icon.png' ) ) {
            // Fallback zu Dashicon, falls die PNG fehlt
            $icon_url = 'dashicons-camera';
        }

        add_menu_page(
            __( 'FotoTechnik', 'ft-meow-lightbox-maps' ), // Page title (wird im Browser‑Tab angezeigt)
            __( 'FotoTechnik', 'ft-meow-lightbox-maps' ), // Menu title (sichtbar im Admin‑Menu)
            'manage_options',                             // Capability – wer das Menü sehen darf
            FT_MENU_SLUG,                                 // Slug des Top‑Level‑Eintrags
            '__return_null',                              // Callback (hier nicht nötig)
            $icon_url,                                    // **eigenes PNG‑Icon**
            81                                            // Position im Menü
        );

        add_submenu_page(
            FT_MENU_SLUG,                               // Parent slug (unser Top‑Level‑Menü)
            __( 'Overview', 'ft-meow-lightbox-maps' ),  // Seitentitel (Browser‑Tab)
            __( 'Overview', 'ft-meow-lightbox-maps' ),  // Menü‑Eintrag im Untermenü 
            'manage_options',                           // Capability
            FT_MENU_SLUG,                               // **gleicher Slug wie das Top‑Level‑Menü**
            'ftmlm_main_page_callback'                  // Callback, der die Haupt‑Seite rendert
        );
    }

    // Untermenü‑Eintrag „Meow Lightbox Mapping“
    add_submenu_page(
        FT_MENU_SLUG,                                           // Parent‑Slug
        __( 'Meow Lightbox Mapping', 'ft-meow-lightbox-maps' ), // Seitentitel (Browser‑Tab)
        __( 'Meow Lightbox Mapping', 'ft-meow-lightbox-maps' ), // Menü‑Eintrag im Untermenü
        'manage_options',                                       // Capability
        'ftmlm-settings',                                       // Slug des Untermenüs (muss mit Settings‑Page übereinstimmen)
        'ftmlm_settings_page'                                   // Callback, die das Formular ausgibt
    );
}

/* -------------------------------------------------
 * CSS‑Feinjustierung für das Icon
 * ------------------------------------------------- */
add_action( 'admin_enqueue_scripts', 'ftmlm_admin_menu_icon_css' );
function ftmlm_admin_menu_icon_css() {
    // Nur im Admin‑Dashboard ausgeben
    wp_add_inline_style(
        'wp-admin',
        '
        #toplevel_page_' . FT_MENU_SLUG . ' .wp-menu-image img {
            width: 20px;
            height: 20px;
            padding-top: 6px;
        }
        '
    );
}

/* --------------------------------------------------------------
 * Callback für die Haupt‑Seite (FotoTechnik)
 * -------------------------------------------------------------- */
function ftmlm_main_page_callback() {
    $file = plugin_dir_path( __FILE__ ) . 'inc/ft-main-page.php';

    if ( file_exists( $file ) ) {
        /**
         * Optional: Ausgabe‑Puffer, damit wir im Fehlerfall
         * kontrolliert reagieren können.
         */
        ob_start();
        include $file;
        echo ob_get_clean();
    } else {
         // Fallback‑Nachricht, falls die Datei fehlt
        echo '<div class="notice notice-error"><p>';
        _e( 'Die Haupt‑Seite konnte nicht geladen werden – die Datei ft‑main‑page.php fehlt.', 'ft-meow-lightbox-maps' );
        echo '</p></div>';
    }
}

/* --------------------------------------------------------------
 * Settings‑API registrieren
 * -------------------------------------------------------------- */
add_action( 'admin_init', 'ftmlm_register_settings' );
function ftmlm_register_settings() {

    register_setting(
        'ftmlm_options_group',   // Settings‑Gruppe
        'ftmlm_maps',            // Options‑Name in DB
        'ftmlm_sanitize_maps'    // Sanitizer‑Callback
    );

    add_settings_section(
        'ftmlm_section_maps',
        __( 'Zum Ändern der Objektiv‑ und Kamera‑Namen in den Exif‑Angaben.', 'ft-meow-lightbox-maps' ),
        '__return_false',
        'ftmlm-settings'
    );

    add_settings_field(
        'ftmlm_camera_map',
        __( 'Kamera‑Map', 'ft-meow-lightbox-maps' ),
        'ftmlm_render_camera_field',
        'ftmlm-settings',
        'ftmlm_section_maps'
    );

    add_settings_field(
        'ftmlm_lens_map',
        __( 'Objektiv‑Map', 'ft-meow-lightbox-maps' ),
        'ftmlm_render_lens_field',
        'ftmlm-settings',
        'ftmlm_section_maps'
    );
}

/* --------------------------------------------------------------
 * Sanitizer – Text → Array
 * -------------------------------------------------------------- */
function ftmlm_sanitize_maps( $input ) {

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
 * Render‑Funktionen (HTML für die Textareas)
 * -------------------------------------------------------------- */
function ftmlm_render_camera_field() {
    $options = get_option( 'ftmlm_maps' );
    $map     = isset( $options['camera_map'] ) ? $options['camera_map'] : array();

    // Array → Zeilen‑String (key => value)
    $textarea = '';
    foreach ( $map as $k => $v ) {
        $textarea .= $k . ' => ' . $v . "\n";
    }
    ?>
    <textarea
        name="ftmlm_maps[camera_map]"
        rows="12"
        cols="70"
        class="large-text code"
        style="font-family:monospace;"><?php echo esc_textarea( $textarea ); ?></textarea>
    <p class="description">
        <?php esc_html_e( 'Ein Eintrag pro Zeile, z. B. "ILCE-7M3 => Sony α7 III". Kommentare mit # zulässig.', 'ft-meow-lightbox-maps' ); ?>
    </p>
    <?php
}

function ftmlm_render_lens_field() {
    $options = get_option( 'ftmlm_maps' );
    $map     = isset( $options['lens_map'] ) ? $options['lens_map'] : array();

    $textarea = '';
    foreach ( $map as $k => $v ) {
        $textarea .= $k . ' => ' . $v . "\n";
    }
    ?>
    <textarea
        name="ftmlm_maps[lens_map]"
        rows="15"
        cols="70"
        class="large-text code"
        style="font-family:monospace;"><?php echo esc_textarea( $textarea ); ?></textarea>
    <p class="description">
        <?php esc_html_e( 'Ein Eintrag pro Zeile, z. B. "FE 24-240mm F3.5-6.3 OSS => Sony 24-240mm F3.5-6.3 OSS".', 'ft-meow-lightbox-maps' ); ?>
    </p>
    <?php
}

/* --------------------------------------------------------------
 * Settings‑Seite (Formular)
 * -------------------------------------------------------------- */
function ftmlm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Zugriff verweigert.', 'ft-meow-lightbox-maps' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Foto Technik - Meow Lightbox - Kamera‑ & Objektiv‑Mappings', 'ft-meow-lightbox-maps' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'ftmlm_options_group' );   // Nonce + hidden fields
            do_settings_sections( 'ftmlm-settings' );   // Sections + fields
            submit_button();                            // „Änderungen übernehmen“
            ?>
        </form>
    </div>
    <?php
}

/* --------------------------------------------------------------
 * Hilfsfunktion: Mapping‑Array aus der DB holen
 * -------------------------------------------------------------- */
function ftmlm_get_map( $type ) {
    $options = get_option( 'ftmlm_maps', array() );

    $key = ( $type === 'camera' ) ? 'camera_map' : 'lens_map';

    $raw = ( isset( $options[ $key ] ) && is_array( $options[ $key ] ) )
        ? $options[ $key ]
        : array();

    // Trim‑Whitespace um mögliche Eingabefehler zu kompensieren
    $clean = array();
    foreach ( $raw as $k => $v ) {
        $clean[ trim( $k ) ] = trim( $v );
    }

    return $clean;
}

/* --------------------------------------------------------------
 * Filter‑Callbacks für Meow Lightbox
 * -------------------------------------------------------------- */
function ftmlm_filter_lens( $value, $mediaId, $meta ) {
    if ( empty( $value ) ) {
        return 'N/A';
    }

    $map = ftmlm_get_map( 'lens' );

    return isset( $map[ $value ] ) ? $map[ $value ] : $value;
}

function ftmlm_filter_camera( $value, $mediaId, $meta ) {
    if ( empty( $value ) ) {
        return 'N/A';
    }

    $map = ftmlm_get_map( 'camera' );

    return isset( $map[ $value ] ) ? $map[ $value ] : $value;
}

/* --------------------------------------------------------------
 * Registrierung der Filter (nach allen Plugins geladen)
 * -------------------------------------------------------------- */
add_action( 'plugins_loaded', function () {
    // Priority 20 stellt sicher, dass unser Mapping nach evtl. anderen Plugins ausgeführt wird
    add_filter( 'mwl_img_lens',   'ftmlm_filter_lens',   20, 3 );
    add_filter( 'mwl_img_camera','ftmlm_filter_camera', 20, 3 );
} );

/* --------------------------------------------------------------
 * Textdomain laden (für Übersetzungen)
 * -------------------------------------------------------------- */
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'ft-meow-lightbox-maps', false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

