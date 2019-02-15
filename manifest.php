<?php

if ( !defined( 'FW' ) ) {
    die( 'Forbidden' );
}

$manifest = array();

$manifest[ 'name' ]         = esc_html__( 'Ajax portfolio', 'crumina' );
$manifest[ 'description' ]  = esc_html__( 'Ajax portfolio.', 'crumina' );
$manifest[ 'github_repo' ]  = 'https://github.com/crumina/FW_Extension_Ajax_Portfolio';
$manifest[ 'version' ]      = '1.0.10';
$manifest[ 'thumbnail' ]    = plugins_url( 'unyson/framework/extensions/ajax-portfolio/static/img/thumbnail.png' );
$manifest[ 'display' ]      = true;
$manifest[ 'standalone' ]   = false;
$manifest[ 'requirements' ] = array(
    'extensions' => array(
        'portfolio' => array(),
    )
);
