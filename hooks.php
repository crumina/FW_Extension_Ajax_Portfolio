<?php

$ext           = fw_ext( 'ajax-portfolio' );
$shortcodeName = $ext->get_config( 'shortcodeName' );
$actionName    = $ext->get_config( 'ajaxActionName' );

add_filter( "vc_before_init", 'FW_Extension_Ajax_Portfolio::vc_mapping' );
add_filter( "vc_autocomplete_{$shortcodeName}_portfolio_categories_callback", 'FW_Extension_Ajax_Portfolio::vc_autocomplete_categories_field_search', 10, 1 );
add_filter( "vc_autocomplete_{$shortcodeName}_portfolio_categories_render", 'FW_Extension_Ajax_Portfolio::vc_autocomplete_categories_field_render', 10, 1 );

add_filter( 'init', 'FW_Extension_Ajax_Portfolio::kc_mapping' );
add_filter( 'kc_autocomplete_portfolio_categories', 'FW_Extension_Ajax_Portfolio::kc_autocomplete_categories_field_search' );

add_action( "wp_ajax_{$actionName}", 'FW_Extension_Ajax_Portfolio::get_items' );
add_action( "wp_ajax_nopriv_{$actionName}", 'FW_Extension_Ajax_Portfolio::get_items' );
