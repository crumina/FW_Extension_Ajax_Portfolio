<?php

if ( !defined( 'FW' ) ) {
    die( 'Forbidden' );
}

class FW_Extension_Ajax_Portfolio extends FW_Extension {

    public static $available_navs     = array( 'numbers', 'loadmore', 'prev_next' );
    public static $available_orders   = array( 'ASC', 'DESC' );
    public static $available_ordersby = array( 'title', 'date', 'comments', 'author' );

    protected function _init() {
        add_image_size( 'ajax-portfolio-thumb', 500, 500 );

        add_shortcode( $this->get_config( 'shortcodeName' ), array( $this, 'shortcode' ) );
    }

    public function shortcode( $atts ) {
        static $is_rendered = false;

        if ( $is_rendered ) {
            return '<h4 class="clearfix text-danger">' . esc_html__( 'You can use this shortcode just once!', 'ajax-portfolio' ) . '</h4>';
        }

        $builder_type = isset( $atts[ 'builder_type' ] ) ? $atts[ 'builder_type' ] : '';

        if ( $builder_type !== 'kc' && function_exists( 'vc_map_get_attributes' ) ) {
            $atts = vc_map_get_attributes( $this->get_config( 'shortcodeName' ), $atts );
        }

        $atts = shortcode_atts( array(
            'sort_panel_visibility' => 'yes',
            'sort_panel_type'       => 'ajax',
            'nav_type'              => 'numbers',
            'order'                 => 'ASC',
            'orderby'               => 'title',
            'items_per_page'        => 10,
            'portfolio_categories'  => '',
            'builder_type'          => '',
            'exclude'               => '',
            'columns'               => 3
        ), $atts );

        wp_localize_script( 'ajax-portfolio', 'ajaxPortfolioParams', array(
            'nonce' => wp_create_nonce( 'ajax-portfolio-nonce' ),
            'ext'   => $this,
            'atts'  => $atts,
        ) );

        $is_rendered = true;

        return $this->render_view( 'main', array(
            'ext'  => $this,
            'atts' => $atts,
        ) );
    }

    final public static function the_query( $atts = array(), $cats = array() ) {
        $builder_type          = $exclude               = $items_per_page        = $columns               = $nav_type              = $order                 = $orderby               = $portfolio_categories  = $sort_panel_type       = $sort_panel_visibility = '';
        extract( $atts );

        $page_var = is_front_page() ? 'page' : 'paged';
        $paged    = ( get_query_var( $page_var ) ) ? get_query_var( $page_var ) : 1;

        $items_per_page = (int) $items_per_page ? (int) $items_per_page : 10;

        $args = array(
            'post_type'      => fw_ext( 'portfolio' )->get_post_type_name(),
            'posts_per_page' => $items_per_page,
            'paged'          => $paged,
        );

        if ( in_array( $order, self::$available_orders ) ) {
            $args[ 'order' ] = $order;
        }

        if ( in_array( $orderby, self::$available_ordersby ) ) {
            $args[ 'orderby' ] = $orderby;
        }

        if ( !empty( $cats ) ) {
            //Forse for ajax query
            $exclude = 'no';
        } else {
            $cats = self::parse_cats( $atts );
        }

        if ( !empty( $cats ) ) {
            if ( $exclude === 'yes' ) {
                $operator = 'NOT IN';
            } else {
                $operator = 'IN';
            }
            $args[ 'tax_query' ] = array(
                array(
                    'taxonomy' => fw_ext( 'portfolio' )->get_taxonomy_name(),
                    'field'    => 'term_id',
                    'terms'    => $cats,
                    'operator' => $operator,
                ),
            );
        }

        return new WP_Query( $args );
    }

    /**
     * @param string $name View file name (without .php) from <extension>/views directory
     * @param  array $view_variables Keys will be variables names within view
     * @param   bool $return In some cases, for memory saving reasons, you can disable the use of output buffering
     * @return string HTML
     */
    final public function get_view( $name, $view_variables = array(), $return = true ) {
        $full_path = $this->locate_path( '/views/' . $name . '.php' );

        if ( !$full_path ) {
            trigger_error( 'Extension view not found: ' . $name, E_USER_WARNING );
            return;
        }

        return fw_render_view( $full_path, $view_variables, $return );
    }

    public static function get_items() {
        check_ajax_referer( 'ajax-portfolio-nonce', 'nonce' );

        $ext      = fw_ext( 'ajax-portfolio' );
        $category = filter_input( INPUT_POST, 'category', FILTER_VALIDATE_INT );
        $params   = filter_input( INPUT_POST, 'params', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
        $page     = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );
        $page     = $page ? $page : 1;

        if ( !isset( $params[ 'atts' ] ) ) {
            wp_send_json_error( array(
                'message' => esc_html__( 'Wrong request data!', 'ajax-portfolio' ),
            ) );
        } else {
            $atts = $params[ 'atts' ];
        }

        $cats = $category ? array( $category ) : 0;

        $nav_type = isset( $params[ 'atts' ][ 'nav_type' ] ) ? $params[ 'atts' ][ 'nav_type' ] : '';
        $nav_type = in_array( $nav_type, $ext::$available_navs ) ? $nav_type : 'numbers';

        global $wp_query;
        set_query_var( 'paged', $page );
        $the_query = $ext::the_query( $atts, $cats );
        $wp_query  = $the_query;

        wp_send_json_success( array(
            'grid' => $ext->get_view( "loop", array(
                'ext'  => $ext,
                'atts' => $atts,
            ) ),
            'nav'  => $ext->get_view( "navs/{$nav_type}", array(
                'ext'  => $ext,
                'atts' => $atts,
            ) ),
        ) );
    }

    public static function parse_cats( $atts ) {
        $portfolio_categories = $exclude              = $builder_type         = '';
        $cats                 = array();
        extract( $atts );

        switch ( $builder_type ) {
            case 'kc':
                $regex = '/\:.*?(\,|$)+/';
                break;
            case 'vc':
                $regex = '/\D+/';
                break;
            default:
                return $cats;
                break;
        }

        $split = preg_split( $regex, $portfolio_categories );

        foreach ( $split as $id ) {
            $id = (int) $id;

            if ( $id ) {
                $cats[] = $id;
            }
        }

        return $cats;
    }

    public static function get_cats( $atts ) {
        $exclude = '';
        extract( $atts );

        $cats = self::parse_cats( $atts );

        $args = array(
            'taxonomy'   => fw_ext( 'portfolio' )->get_taxonomy_name(),
            'hide_empty' => false,
        );

        if ( !empty( $cats ) && $exclude === 'yes' ) {
            $args[ 'exclude' ] = $cats;
        } else if ( !empty( $cats ) ) {
            $args[ 'include' ] = $cats;
        }

        $cats = get_terms( $args );

        return !empty( $cats ) && !is_wp_error( $cats ) ? $cats : array();
    }

    public static function kc_mapping() {
        $shortcodeName = fw_ext( 'ajax-portfolio' )->get_config( 'shortcodeName' );

        if ( function_exists( 'kc_add_map' ) ) {
            kc_add_map( array(
                $shortcodeName => array(
                    'name'     => esc_html__( 'Ajax Portfolio', 'ajax-portfolio' ),
                    'category' => esc_html__( 'Crumina', 'ajax-portfolio' ),
                    'icon'     => 'kc-ajax-portfolio-icon',
                    'params'   => array(
                        array(
                            'label' => esc_html__( 'Show sort panel?', 'ajax-portfolio' ),
                            'name'  => 'sort_panel_visibility',
                            'type'  => 'toggle',
                            'value' => 'yes'
                        ),
                        array(
                            'label'    => esc_html__( 'Sort panel type', 'ajax-portfolio' ),
                            'name'     => 'sort_panel_type',
                            'type'     => 'select',
                            'value'    => 'ajax',
                            'options'  => array(
                                'ajax'     => esc_html__( 'Ajax', 'ajax-portfolio' ),
                                'standard' => esc_html__( 'Standard', 'ajax-portfolio' ),
                            ),
                            'relation' => array(
                                'parent'    => 'sort_panel_visibility',
                                'show_when' => 'yes'
                            )
                        ),
                        array(
                            'name'        => 'nav_type',
                            'label'       => esc_html__( 'Pagination type', 'ajax-portfolio' ),
                            'type'        => 'select',
                            'value'       => 'numbers',
                            'options'     => array(
                                'numbers'   => esc_html__( 'Numbers links', 'ajax-portfolio' ),
                                'prev_next' => esc_html__( 'Previous, next links', 'ajax-portfolio' ),
                                'loadmore'  => esc_html__( 'Load more', 'ajax-portfolio' ),
                            ),
                            'description' => esc_html__( 'Loadmore works with ajax panel only', 'ajax-portfolio' ),
                        ),
                        array(
                            'label'   => esc_html__( 'Order', 'ajax-portfolio' ),
                            'name'    => 'order',
                            'type'    => 'select',
                            'value'   => 'ASC',
                            'options' => array(
                                'ASC'  => esc_html__( 'Ascending', 'ajax-portfolio' ),
                                'DESC' => esc_html__( 'Descending', 'ajax-portfolio' ),
                            ),
                        ),
                        array(
                            'label'   => esc_html__( 'Order by', 'ajax-portfolio' ),
                            'name'    => 'orderby',
                            'type'    => 'select',
                            'value'   => 'title',
                            'options' => array(
                                'title'    => esc_html__( 'Title', 'ajax-portfolio' ),
                                'date'     => esc_html__( 'Date', 'ajax-portfolio' ),
                                'comments' => esc_html__( 'Comments', 'ajax-portfolio' ),
                                'author'   => esc_html__( 'Author', 'ajax-portfolio' ),
                            ),
                        ),
                        array(
                            'type'    => 'autocomplete',
                            'label'   => esc_html__( 'Portfolio categories', 'ajax-portfolio' ),
                            'name'    => 'portfolio_categories',
                            'options' => array(
                                'multiple' => true,
                                'taxonomy' => fw_ext( 'portfolio' )->get_taxonomy_name(),
                            ),
                        ),
                        array(
                            'label'       => esc_html__( 'Exclude selected?', 'ajax-portfolio' ),
                            'name'        => 'exclude',
                            'type'        => 'toggle',
                            'value'       => 'no',
                            'description' => esc_html__( 'Show all categories except that selected in "Portfolio categories" option', 'ajax-portfolio' ),
                        ),
                        array(
                            'name'  => 'items_per_page',
                            'label' => esc_html__( 'Items per page', 'ajax-portfolio' ),
                            'type'  => 'text',
                            'value' => 10
                        ),
                        array(
                            'label'   => esc_html__( 'Columns', 'ajax-portfolio' ),
                            'name'    => 'columns',
                            'type'    => 'select',
                            'value'   => 3,
                            'options' => array(
                                2 => 2,
                                3 => 3,
                                4 => 4,
                            ),
                        ),
                        array(
                            'type'  => 'hidden',
                            'name'  => 'builder_type',
                            'value' => 'kc'
                        )
                    )
                )
            ) );
        }
    }

    public static function vc_mapping() {
        $ext           = fw_ext( 'ajax-portfolio' );
        $shortcodeName = $ext->get_config( 'shortcodeName' );

        if ( function_exists( 'vc_map' ) ) {
            vc_map( array(
                'base'     => $shortcodeName,
                'name'     => esc_html__( 'Ajax Portfolio', 'ajax-portfolio' ),
                'category' => esc_html__( 'Crumina', 'ajax-portfolio' ),
                'icon'     => $ext->locate_URI( '/static/img/builder-ico.svg' ),
                'params'   => array(
                    array(
                        'type'       => 'checkbox',
                        'heading'    => esc_html__( 'Show sort panel?', 'ajax-portfolio' ),
                        'param_name' => 'sort_panel_visibility',
                        'std'        => 'yes',
                        'value'      => array(
                            esc_html__( 'Yes', 'ajax-portfolio' ) => 'yes',
                        ),
                    ),
                    array(
                        'type'       => 'dropdown',
                        'heading'    => esc_html__( 'Sort panel type', 'ajax-portfolio' ),
                        'param_name' => 'sort_panel_type',
                        'std'        => 'ajax',
                        'value'      => array(
                            esc_html__( 'Ajax', 'ajax-portfolio' )     => 'ajax',
                            esc_html__( 'Standard', 'ajax-portfolio' ) => 'standard',
                        ),
                        'dependency' => array(
                            'element' => 'sort_panel_visibility',
                            'value'   => 'yes',
                        )
                    ),
                    array(
                        'type'        => 'dropdown',
                        'heading'     => esc_html__( 'Pagination type', 'ajax-portfolio' ),
                        'param_name'  => 'nav_type',
                        'std'         => 'numbers',
                        'value'       => array(
                            esc_html__( 'Numbers links', 'ajax-portfolio' )        => 'numbers',
                            esc_html__( 'Previous, next links', 'ajax-portfolio' ) => 'prev_next',
                            esc_html__( 'Load more', 'ajax-portfolio' )            => 'loadmore',
                        ),
                        'description' => esc_html__( 'Loadmore works with ajax panel only', 'ajax-portfolio' ),
                    ),
                    array(
                        'type'       => 'dropdown',
                        'heading'    => esc_html__( 'Order', 'ajax-portfolio' ),
                        'param_name' => 'order',
                        'std'        => 'ASC',
                        'value'      => array(
                            esc_html__( 'Ascending', 'ajax-portfolio' )  => 'ASC',
                            esc_html__( 'Descending', 'ajax-portfolio' ) => 'DESC'
                        ),
                    ),
                    array(
                        'type'       => 'dropdown',
                        'heading'    => esc_html__( 'Order by', 'ajax-portfolio' ),
                        'param_name' => 'orderby',
                        'std'        => 'title',
                        'value'      => array(
                            esc_html__( 'Title', 'ajax-portfolio' )    => 'title',
                            esc_html__( 'Date', 'ajax-portfolio' )     => 'date',
                            esc_html__( 'Comments', 'ajax-portfolio' ) => 'comments',
                            esc_html__( 'Author', 'ajax-portfolio' )   => 'author',
                        ),
                    ),
                    array(
                        'type'               => 'autocomplete',
                        'heading'            => esc_html__( 'Portfolio categories', 'ajax-portfolio' ),
                        'param_name'         => 'portfolio_categories',
                        'settings'           => array(
                            'multiple'       => true,
                            'min_length'     => 1,
                            'groups'         => true,
                            'unique_values'  => true,
                            'display_inline' => true,
                            'delay'          => 500,
                            'auto_focus'     => true,
                        ),
                        'param_holder_class' => 'vc_not-for-custom',
                    ),
                    array(
                        'type'        => 'checkbox',
                        'heading'     => esc_html__( 'Exclude selected?', 'ajax-portfolio' ),
                        'param_name'  => 'exclude',
                        'value'       => array(
                            esc_html__( 'Yes', 'ajax-portfolio' ) => 'yes',
                        ),
                        'description' => esc_html__( 'Show all categories except that selected in "Portfolio categories" option', 'ajax-portfolio' ),
                    ),
                    array(
                        "type"       => "textfield",
                        'heading'    => esc_html__( 'Items per page', 'ajax-portfolio' ),
                        "param_name" => "items_per_page",
                        "value"      => 10,
                    ),
                    array(
                        'heading'    => esc_html__( 'Columns', 'ajax-portfolio' ),
                        'param_name' => 'columns',
                        'type'       => 'dropdown',
                        'std'        => 3,
                        'value'      => array(
                            2 => 2,
                            3 => 3,
                            4 => 4,
                        ),
                    ),
                    array(
                        'type'       => 'hidden',
                        'param_name' => 'builder_type',
                        'value'      => 'vc'
                    )
                )
            ) );
        }
    }

    final static function kc_autocomplete_categories_field_search() {
        $search_string = filter_input( INPUT_POST, 's' );

        $categories = array( esc_html__( 'Portfolio categories', 'ajax-portfolio' ) => array() );

        $kc_categories = get_terms( array(
            'taxonomy'   => fw_ext( 'portfolio' )->get_taxonomy_name(),
            'hide_empty' => false,
            'search'     => $search_string,
        ) );

        if ( is_array( $kc_categories ) && !empty( $kc_categories ) ) {
            foreach ( $kc_categories as $c ) {
                if ( is_object( $c ) ) {
                    $categories[ esc_html__( 'Portfolio categories', 'ajax-portfolio' ) ][] = "{$c->term_id}:{$c->name}";
                }
            }
        }

        return $categories;
    }

    final static function vc_autocomplete_categories_field_search( $search_string ) {
        $data = array();

        $vc_categories = get_terms( array(
            'taxonomy'   => fw_ext( 'portfolio' )->get_taxonomy_name(),
            'hide_empty' => false,
            'search'     => $search_string,
        ) );

        if ( is_array( $vc_categories ) && !empty( $vc_categories ) ) {
            foreach ( $vc_categories as $c ) {
                if ( is_object( $c ) ) {
                    $data[] = vc_get_term_object( $c );
                }
            }
        }

        return $data;
    }

    final static function vc_autocomplete_categories_field_render( $term ) {
        $terms = get_terms( array(
            'taxonomy'   => fw_ext( 'portfolio' )->get_taxonomy_name(),
            'include'    => array( $term[ 'value' ] ),
            'hide_empty' => false,
        ) );
        $data  = false;
        if ( is_array( $terms ) && 1 === count( $terms ) ) {
            $term = $terms[ 0 ];
            $data = vc_get_term_object( $term );
        }

        return $data;
    }

}
