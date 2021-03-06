/**
 * @typedef  {object}              FLEX
 * @property {object}              FLEX.dom
 * @property {Boolean}             FLEX.dom.use
 * @property {Array.BS4domsize}    FLEX.dom.items
 * @property {object}              FLEX.env
 * @property {object}              FLEX.env.class
 * @property {string}              FLEX.env.class.body
 * @property {string}              FLEX.toggle
 * @property {string}              FLEX.env.class.hide
 * @property {string}              FLEX.env.class.hidedown
 * @property {string}              FLEX.env.class.hideup
 * @property {number}              FLEX.debounce
 * @property {object}              FLEX.env.breakpoints
 * @property {object}              FLEX.toowide
 * @property {Boolean}             FLEX.toowide.use
 * @property {string}              FLEX.toowide.J_measure
 * @property {string}              FLEX.toowide.J_against
 * @property {string}              FLEX.toowide.J_against_subtract
 * @property {string}              FLEX.toowide.J_addto
 * @property {object}              FLEX.toowide.maxwidths
 * @property {object}              FLEX.forceclick
 * @property {Boolean}             FLEX.forceclick.use
 * @property {string}              FLEX.forceclick.J_selector
 * @property {string}              FLEX.forceclick.J_find
 * @property {string}              FLEX.forceclick.attr
 * @property {object}              FLEX.menuscreen
 * @property {Boolean}             FLEX.menuscreen.use
 * @property {string}              FLEX.menuscreen.screen
 * @property {string}              FLEX.menuscreen.J_toggle
 * @property {string}              FLEX.menuscreen.attr
 * @property {string}              FLEX.menuscreen.value
 * @property {object}              FLEX.togglerstateclass
 * @property {Boolean}             FLEX.togglerstateclass.use
 * @property {string}              FLEX.togglerstateclass.J_toggle
 * @property {Array.BS4state}      FLEX.togglerstateclass.items
 * @property {object}              FLEX.resizehide
 * @property {Boolean}             FLEX.resizehide.use
 * @property {Array.BS4resize}     FLEX.resizehide.items
 * @property {Array.BS4mod}        FLEX.modifymenu
 *
 * @typedef  {object}              BS4domsize
 * @property {string}              BS4domsize.id
 * @property {string}              BS4domsize.J_selector
 * @property {string}              BS4domsize.J_parent
 * @property {Array.BS4domtrigger} BS4domsize.triggers
 *
 * @typedef  {object}              BS4domtrigger
 * @property {object}              BS4domtrigger.checker
 * @property {string}              BS4domtrigger.checker.J_selector
 * @property {Boolean}             BS4domtrigger.checker.has
 * @property {string}              BS4domtrigger.checker.class
 * @property {string}              BS4domtrigger.J_parent
 * @property {object}              BS4domtrigger.class
 * @property {string}              BS4domtrigger.class.add
 * @property {string}              BS4domtrigger.class.remove
 * @property {Boolean}             BS4domtrigger.show
 * @property {Boolean}             BS4domtrigger.append
 *
 * @typedef  {object}              BS4mod
 * @property {Boolean}             BS4mod.use
 * @property {Array.BS4moditems}   BS4mod.items
 *
 * @typedef  {object}              BS4moditems
 * @property {string}              BS4moditems.J_selector
 * @property {Array.BS4moditem}    BS4moditems.new
 *
 * @typedef  {object}              BS4moditem
 * @property {string}              BS4moditem.id
 * @property {string}              BS4moditem.J_selector
 * @property {Boolean}             BS4moditem.append
 * @property {string}              BS4moditem.tag
 * @property {object}              BS4moditem.attr
 * @property {string}              BS4moditem.attr.class
 *
 * @typedef  {object}              BS4resize
 * @property {string}              BS4resize.id
 * @property {string}              BS4resize.J_selector
 * @property {string}              BS4resize.J_parent
 *
 * @typedef  {object}              BS4state
 * @property {string}              BS4state.id
 * @property {string}              BS4state.J_selector
 * @property {string}              BS4state.state
 * @property {string}              BS4state.class
 *
 * @typedef  {jQuery}              jQuery
 */

jQuery( document ).ready( function ( $ ) {

    var $document = $( document ),
        initcls   = {},
        hider     = resize_start,
        sizer     = _debounce( resized, FLEX.debounce ),
        resizing  = false,
        fitted    = {},
        BS        = 'XX';

    /**
     * Fired on ready.
     *
     * @since 1.1 Removed extra listener and call
     */
    window.addEventListener( 'resize', hider );
    window.addEventListener( 'resize', sizer );

    modify_menu();
    dropdown_screen();
    force_clicks();
    resized();

    /**
     * Modifies the configured menu
     *
     * @since 1.3 Checks 'use' argument, var is now 'modifymenu'
     */
    function modify_menu() {

        if ( FLEX.modifymenu.use ) {

            $.each( FLEX.modifymenu.items, function ( i, v ) {

                $.each( v.new, function ( ii, vv ) {

                    var $new = $( '<' + vv.tag + '>', vv.attr ),
                        $sel = vv.J_selector !== '' ? $( v.J_selector ).find( vv.J_selector ) : $( v.J_selector );

                    vv.append ? $sel.append( $new ) : $sel.prepend( $new );
                } );
            } );
        }
    }

    /**
     * Fires without delay at start of  resizing event.
     *
     * @since 1.2
     */
    function resize_start() {

        if ( ! resizing ) {
            _resize_hide();
        }

        resizing = true;
    }

    /**
     * Adjust DOM on resize
     *
     * @since 1.2 Only fires _resize_hide at end
     * @since 1.1 Changed to a promise model to ensure methods are checked sequentially
     */
    function resized() {

        _size()
            .then( _too_wide )
            .then( _add_class_on_toggle )
            .then( _resize_dom )
            .then( _resize_hide( false ) );

        resizing = false;
    }

    /**
     * Allows top-level clicks on navbars, recommended for split buttons
     */
    function force_clicks() {

        if ( FLEX.forceclick.use ) {

            $document.on( 'click', FLEX.forceclick.J_selector, function () {

                var $this = $( this ), $link, attr;

                $link = FLEX.forceclick.J_find !== '' ? $this.find( FLEX.forceclick.J_find ) : $this;
                attr  = FLEX.forceclick.attr !== '' ? $link.attr( FLEX.forceclick.attr ) : 'href';

                if ( typeof( attr ) !== 'undefined' ) window.location.href = attr;
            } );
        }
    }

    /**
     * Adds screen behind dropdown menu, specifically checks for
     */
    function dropdown_screen() {

        if ( FLEX.menuscreen.use ) {

            var $el = $( '<div>' );

            $el.appendTo( $( 'body' ) ).addClass( FLEX.menuscreen.screen );

            // add a click action on the menu dropdown toggler
            $document.on( 'click', FLEX.menuscreen.J_toggle, function () {

                $( FLEX.menuscreen.J_toggle ).attr( FLEX.menuscreen.attr ) === FLEX.menuscreen.value ?
                    $el.addClass( 'active' ) : $el.removeClass( 'active' );
            } );
        }
    }

    /**
     * Adds class to parent whether top level menu is hiden behind toggle or not
     *
     * @since 1.1 returns promise
     */
    function _add_class_on_toggle() {

        var d = new $.Deferred;

        if ( FLEX.togglerstateclass.use ) {

            $.each( FLEX.togglerstateclass.items, function ( i, v ) {

                var togvis = $( FLEX.togglerstateclass.J_toggle ).is( ':visible' ) ? 'visible' : 'hidden';

                // add class to parent with state of toggler/menu
                if ( v.state === togvis ) {
                    $( v.J_selector ).addClass( v.class );
                } else {
                    $( v.J_selector ).removeClass( v.class );
                }
            } );
        }

        d.resolve();
        return d.promise();
    }

    /**
     * Adjusts configured elements in dom
     */
    function _dom( item ) {

        var $items = $( item.J_parent + '>' + item.J_selector );

        $.each( item.triggers, function ( i, v ) {
            if ( ! $items.length ) {
                $items = $( v.J_parent + '>' + item.J_selector )
            }
        } );

        $items.each( function ( itemindex ) {

            var $item   = $( this ),
                attrid  = $item.attr( 'id' ),
                $parent,
                $current,
                current = {};

            itemindex = attrid ? attrid : itemindex;

            $.each( item.triggers, function ( i, v ) {
                if ( v.checker.has === $( v.checker.J_selector ).hasClass( v.checker.class ) ) {
                    current = typeof( current.J_parent ) === 'undefined' ? v : current;
                }
            } );

            if ( typeof( current.J_parent ) === 'undefined' || ! $item.length ) return false;

            // set a data attribute in the dom
            $item.attr( 'data-instance', itemindex );

            $parent  = $item.parent();
            $current = $( current.J_parent );

            // if this is the first time through, save initial classes, per instance
            if ( ! _initial_class( item.id, itemindex ) ) {
                _initial_class( item.id, itemindex, $item.attr( 'class' ) );
            }

            // remove all classes, add original classes back in
            $item.attr( 'class', '' ).addClass( _initial_class( item.id, itemindex ) );

            // add/remove classes as configured
            $item.addClass( current.class.add ).removeClass( current.class.remove );

            // set the display
            if ( current.show && $item.not( ':visible' ) ) $item.show();
            if ( ! current.show && $item.is( ':visible' ) ) $item.hide();

            // if the parent is not correct, move item in dom
            if ( ! $parent.is( $current ) ) {
                $item.detach();
                current.append ? $item.appendTo( $current ) : $item.prependTo( $current );
            }
        } );
    }

    /**
     * Saves initial class values.
     *
     * @param {number|string} id       Type identifier
     * @param {number|string} ins      Instance of type
     * @param {string}        [value]  New value to set it to
     * @returns {*}
     * @private
     */
    function _initial_class( id, ins, value ) {

        initcls[ id ]        = typeof( initcls[ id ] ) === 'undefined' ? {} : initcls[ id ];
        initcls[ id ][ ins ] = typeof( initcls[ id ][ ins ] ) === 'undefined' ? false : initcls[ id ][ ins ];
        initcls[ id ][ ins ] = typeof( value ) !== 'undefined' ? value : initcls[ id ][ ins ];

        return initcls[ id ][ ins ];
    }

    /**
     * Gets the current breakpoint and sets it as a tag on the body
     *
     * @since 1.1 Returns promise
     */
    function _size() {

        var bp = _environment(),
            d  = new $.Deferred();

        if ( bp !== BS ) {
            $( 'body' ).removeClass( FLEX.env.class.body + BS ).addClass( FLEX.env.class.body + bp );
            BS = bp;
        }

        d.resolve();
        return d.promise();
    }

    /**
     * Finds the environment, adds tag to body
     *
     * @returns {string}
     */
    function _environment() {

        var $el = $( '<div>' ),
            bp  = '';

        $el.appendTo( $( 'body' ) );

        $.each( FLEX.env.breakpoints, function ( i ) {

            if ( i !== 'xs' ) {
                $el.addClass( 'd-none' );
            }

            $el.addClass( FLEX.env.class.hide + i + FLEX.env.class.hideup );

            if ( $el.is( ':visible' ) ) {
                bp = i;
                $el.attr( 'class', '' );
            } else {
                $el.remove();
                //return;
            }
        } );

        $el.remove();

        return bp;
    }

    /**
     * Times events to prevent performance issues
     *
     * @param func
     * @param wait
     * @param immediate
     * @returns {Function}
     */
    function _debounce( func, wait, immediate ) {

        var timeout;

        return function () {

            var context = this,
                args    = arguments,
                later,
                callNow;

            later = function () {
                timeout = null;
                if ( ! immediate ) func.apply( context, args );
            };

            callNow = immediate && ! timeout;
            clearTimeout( timeout );
            timeout = setTimeout( later, wait );

            if ( callNow ) func.apply( context, args );
        };
    }

    /**
     * Resizes items in dom
     *
     * @since 1.1 Broken out from resize()
     * @since 1.3 Checks 'use' argument
     */
    function _resize_dom() {

        var d = new $.Deferred();

        if ( FLEX.dom.use ) {
            $.each( FLEX.dom.items, function ( i, v ) {
                _dom( v );
            } );
        }

        d.resolve();
        return d.promise();
    }

    /**
     * Makes configured DOM elements invisible via CSS visibility.
     *
     * @since 1.1 Returns promise
     * @since 1.1 Hides the screen behind menu on resize
     * @param [hider]  true to hide, false to show
     */
    function _resize_hide( hider ) {

        var vis    = hider !== false ? 'hidden' : 'visible',
            $mscrn = $( '.' + FLEX.menuscreen.screen ),
            d      = new $.Deferred();

        if ( FLEX.resizehide.use ) {

            $.each( FLEX.resizehide.items, function ( i, v ) {
                $( v.J_parent ).find( v.J_selector ).css( 'visibility', vis );
            } );
        }

        if ( $mscrn.length ) {
            $( FLEX.toowide.J_measure ).is( ':visible' ) && $( FLEX.togglerstateclass.J_toggle ).is( ':visible' ) ?
                $mscrn.addClass( 'active' ) : $mscrn.removeClass( 'active' );
        }

        d.resolve();
        return d.promise();
    }

    /**
     * Toggles the menu if the menu is wider than the container.
     *
     * @since 1.2  Bootstrap4 beta classes; checks allow optional max-width
     * @since 1.1  Inversed check of toggle class; should not overwrite smaller toggle point with larger
     * @since 1.1  Returns promise, allowing sequential application
     * @private
     */
    function _too_wide() {

        var $add,
            $against,
            $measure,
            subtract,
            max  = '',
            d    = new $.Deferred(),
            flag = false;

        if ( FLEX.toowide.use ) {

            $add      = $( FLEX.toowide.J_addto ).eq( 0 );
            $measure  = $( FLEX.toowide.J_measure ).eq( 0 );
            $against  = $( FLEX.toowide.J_against ).eq( 0 );
            subtract = FLEX.toowide.J_against_subtract === ''
                ? 0 : $( FLEX.toowide.J_against_subtract ).eq( 0 ).outerWidth();

            if ( FLEX.toowide.maxwidths[ BS ] !== 0 ) {

                max = typeof FLEX.toowide.maxwidths[ BS ] === 'number' ?
                    FLEX.toowide.maxwidths[ BS ] + 'px' : FLEX.toowide.maxwidths[ BS ];

                $against.css( 'max-width', max );
            }

            if ( $measure.outerWidth() > ( $against.outerWidth() - subtract ) && typeof( fitted[ BS ] ) === 'undefined' ) {

                $.each( FLEX.env.breakpoints, function ( i, v ) {

                    if ( i !== BS && ! flag && typeof( fitted[ i ] ) === 'undefined' ) {

                        fitted[ i ] = 1;

                    } else {

                        if ( ! flag ) {
                            fitted[ i ] = 1;
                        }

                        flag = i === BS ? v : flag;

                        if ( i === BS && i !== 'xl' ) {
                            $add.removeClass( FLEX.toggle + ' ' + FLEX.toggle + '-' + i )
                                .addClass( FLEX.toggle + '-' + v );
                        } else if ( i === BS && i === 'xl' ) {
                            $add.removeClass( FLEX.toggle + ' ' + FLEX.toggle + '-' + i )
                        }
                    }

                    if ( i !== BS && flag !== i ) {
                        $add.removeClass( FLEX.toggle + '-' + i );
                    }

                } );

                if ( flag ) {

                    // we have to check if $add is a member of FLEX.dom
                    $.each( FLEX.dom, function ( i, v ) {

                        if ( $( v.J_selector ).is( $add ) ) {
                            var addid = $add.attr( 'id' ) | 0;
                            _initial_class( v.id, addid, $add.attr( 'class' ) );
                        }
                    } );
                }
            }

            if ( FLEX.toowide.maxwidths[ BS ] !== 0 ) {
                $against.css( 'max-width', '' );
            }
        }

        d.resolve();
        return d.promise();
    }

} );