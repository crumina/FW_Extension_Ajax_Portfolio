<?php

if ( !defined( 'FW' ) ) {
    die( 'Forbidden' );
}

$cfg = array();

$cfg[ 'shortcodeName' ] = 'ajax-portfolio';

$cfg[ 'ajaxActionName' ] = 'ajax-portfolio-get-items';

$cfg[ 'selectors' ] = array(
    //It will be ids
    'panelContainer' => 'ajax-portfolio-panel',
    'gridContainer'  => 'ajax-portfolio-grid',
    'navContainer'   => 'ajax-portfolio-nav',
    //It will be classes
    'masonryItem'       => 'ajax-portfolio-item',
    'loadmoreItem'      => 'ajax-portfolio-nav-loadmore',
    'navLinksWrapper'   => 'ajax-portfolio-nav-links',
    'panelLinksWrapper' => 'ajax-portfolio-panel-links',
);
