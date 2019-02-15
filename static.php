<?php

$ext = fw_ext( 'ajax-portfolio' );

if ( !is_admin() ) {
    wp_enqueue_style( 'ajax-portfolio', $ext->locate_URI( '/static/css/styles.css' ), array(), $ext->manifest->get_version() );
    wp_enqueue_script( 'isotope', $ext->locate_URI( '/static/libs/isotope/isotope.pkgd.js' ), array( 'jquery', 'imagesloaded' ), '3.0.6', true );
    wp_enqueue_script( 'isotope-packery-mode', $ext->locate_URI( '/static/libs/isotope/packery-mode.pkgd.js' ), array( 'isotope' ), '2.0.1', true );
    wp_enqueue_script( 'jquery-extend', $ext->locate_URI( '/static/js/jquery-extend.js' ), array( 'jquery' ), $ext->manifest->get_version(), true );
    wp_enqueue_script( 'hooks', $ext->locate_URI( '/static/libs/hooks.js' ), array( 'jquery' ), $ext->manifest->get_version(), true );
    wp_enqueue_script( 'ajax-portfolio', $ext->locate_URI( '/static/js/scripts.js' ), array( 'isotope-packery-mode', 'jquery-extend', 'hooks' ), $ext->manifest->get_version(), true );

    $config              = $ext->get_config();
    $config[ 'ajaxUrl' ] = admin_url( 'admin-ajax.php' );

    wp_localize_script( 'ajax-portfolio', 'ajaxPortfolioConfig', $config );
} else {
    wp_enqueue_style( 'ajax-portfolio-admin', $ext->locate_URI( '/static/css/admin.css' ), array(), $ext->manifest->get_version() );
}