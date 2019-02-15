<?php
/**
 * @var object $ext
 * @var array $atts
 */
$builder_type          = $exclude               = $items_per_page        = $columns               = $nav_type              = $order                 = $orderby               = $portfolio_categories  = $sort_panel_type       = $sort_panel_visibility = '';
extract( $atts );

global $wp_query;

if ( !$wp_query ) {
    return '';
}

if ( $sort_panel_type === 'ajax' ) {
    $spinner = $ext->get_view( 'spinner' );
} else {
    $spinner = '';
}

$page = $wp_query->get( 'paged' );
$max  = $wp_query->max_num_pages;
$prev = $page - 1;
$next = $page + 1;
?>
<nav class="<?php echo esc_attr( $ext->get_config( 'selectors/navLinksWrapper' ) ); ?> portfolio-nav-prevnext ajax-portfolio-nav-prevnext">
    <?php if ( 1 < $page ) { ?>
        <a class="prev" href="<?php echo esc_url( $sort_panel_type === 'ajax' ? "?page={$prev}" : get_pagenum_link($prev) ) ?>">
            <span><?php esc_html_e( 'Prev Page', 'ajax-portfolio' ); ?></span>
            <?php echo ($spinner); ?>
        </a>
    <?php } ?>
    <?php if ( $page < $max ) { ?>
        <a class="next" href="<?php echo esc_url( $sort_panel_type === 'ajax' ? "?page={$next}" : get_pagenum_link($next) ) ?>">
            <span><?php esc_html_e( 'Next Page', 'ajax-portfolio' ); ?></span>
            <?php echo ($spinner); ?>
        </a>
    <?php } ?>
</nav>