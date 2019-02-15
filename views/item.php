<?php
/**
 * @var object $ext
 * @var array $atts
 */
$builder_type          = $exclude               = $columns               = $items_per_page        = $nav_type              = $order                 = $orderby               = $portfolio_categories  = $sort_panel_type       = $sort_panel_visibility = '';

extract( $atts );

global $wp_query, $post;
?>
<div>
    <a href="<?php echo the_permalink(); ?>" class="ajax-portfolio-item-thumb">
        <?php
        if ( has_post_thumbnail() ) {
            the_post_thumbnail( 'ajax-portfolio-thumb' );
        } else {
            ?>
            <?php $image_url = $ext->locate_URI( '/static/img/no-photo.jpg' ); ?>
            <img src="<?php echo esc_url( $image_url ) ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
        <?php } ?>
    </a>
    <a href="<?php the_permalink(); ?>" class="ajax-portfolio-item-title"><?php the_title(); ?></a>
    <div class="ajax-portfolio-item-terms">
        <?php the_terms( get_the_ID(), fw_ext( 'portfolio' )->get_taxonomy_name() ); ?>
    </div>
</div>