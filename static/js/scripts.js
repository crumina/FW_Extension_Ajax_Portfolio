"use strict";
var ajaxPortfolio = {
    cat: 0,
    page: 1,
    append: false,
    masonryItem: null,
    panelType: null,
    $btns: null,
    $cats: null,
    $posts: null,
    $panel: null,
    $grid: null,
    $nav: null,
    init: function () {
        if ( typeof ajaxPortfolioParams === 'undefined' ) {
            return;
        }
        this.panelType = ajaxPortfolioParams.atts.sort_panel_type;
        this.masonryItem = '.' + ajaxPortfolioConfig.selectors.masonryItem;
        this.$panel = jQuery( '#' + ajaxPortfolioConfig.selectors.panelContainer );
        this.$grid = jQuery( '#' + ajaxPortfolioConfig.selectors.gridContainer );
        this.$nav = jQuery( '#' + ajaxPortfolioConfig.selectors.navContainer );

        if ( !this.$grid.length ) {
            return false;
        }

        this.$posts = jQuery( '> ' + this.masonryItem, this.$grid );

        this.$cats = jQuery( 'a', this.$panel );

        var _this = this;
        this.$posts.imagesLoaded( function () {
            _this.$grid.isotope( {
                itemSelector: this.masonryItem,
                layoutMode: 'packery'
            } );
        } );

        if ( this.panelType === 'ajax' ) {
            this.addAjaxEventListeners();
        } else {
            this.addStandardEventListeners();
        }
    },
    addStandardEventListeners: function () {
        var _this = this;
        this.$nav.on( 'click', 'a.' + ajaxPortfolioConfig.selectors.loadmoreItem, function ( event ) {
            _this.navHandler( jQuery( this ), event );
        } );
    },
    addAjaxEventListeners: function () {
        var _this = this;
        this.$cats.on( 'click', function ( event ) {
            event.preventDefault();

            _this.$cats.each( function () {
                jQuery( this ).parent().removeClass( 'active' ).removeClass( 'loading' );
            } );

            jQuery( this ).parent().addClass( 'active' ).addClass( 'loading' );

            _this.page = 1;
            _this.cat = jQuery( this ).attr( 'href' ).replace( /\D/g, '' );
            _this.getPosts();
        } );

        this.$nav.on( 'click', 'a', function ( event ) {
            _this.navHandler( jQuery( this ), event );
        } );
    },
    navHandler: function ( $self, event ) {
        var _this = this;
        event.preventDefault();
        _this.page = parseInt( $self.attr( 'href' ).replace( /\D/g, '' ) );

        if ( !_this.page ) {
            return;
        }

        if ( $self.hasClass( ajaxPortfolioConfig.selectors.loadmoreItem ) ) {
            _this.append = true;
        } else if ( _this.$panel.length ) {
            var top = _this.$panel.offset().top;
            var calc = Hooks.apply_filters( 'ajax_portfolio_scroll_to', top, {} );
            
            jQuery( 'html, body' ).animate( {
                scrollTop: calc > 0 ? calc : top
            }, 400 );
        }

        $self.addClass( 'loading' );
        _this.getPosts();
    },
    getPosts: function () {
        var _this = this;

        jQuery.ajax( {
            url: ajaxPortfolioConfig.ajaxUrl,
            dataType: 'json',
            type: 'POST',
            data: {
                action: ajaxPortfolioConfig.ajaxActionName,
                nonce: ajaxPortfolioParams.nonce,
                params: ajaxPortfolioParams,
                category: _this.cat,
                page: _this.page,
            },
            beforeSend: function () {
                if ( !_this.append ) {
                    _this.$grid.isotope( 'remove', _this.$posts );
                }
            },
            success: function ( response ) {
                if ( !response.success ) {
                    alert( response.data.message );
                    return;
                }

                // Update pagination
                _this.$nav.html( response.data.nav );

                if ( _this.append ) {
                    // Append new portfolio posts
                    _this.$newPosts = jQuery( response.data.grid ).filter( _this.masonryItem );
                    _this.$posts = _this.$posts.add( _this.$newPosts );

                    _this.$newPosts.imagesLoaded( function () {
                        _this.$grid.isotope( 'insert', _this.$newPosts );
                    } );
                } else {
                    // Replace new portfolio posts
                    _this.$posts = Hooks.apply_filters( 'ajax_portfolio_replaced_posts', jQuery( response.data.grid ).filter( _this.masonryItem ), {} );
                    
                    _this.$posts.imagesLoaded( function () {
                        _this.$grid.isotope( 'insert', _this.$posts );
                    } );
                }

                // Remove loading class
                _this.$cats.each( function () {
                    jQuery( this ).parent().removeClass( 'loading' );
                } );
            },
            error: function ( jqXHR, textStatus ) {
                alert( textStatus );
            },
            complete: function () {
                _this.append = false;
            }
        } );
    }
};

jQuery( document ).ready( function () {
    ajaxPortfolio.init();
} );

