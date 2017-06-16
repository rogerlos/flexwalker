<?php

namespace flexwalker;

use flexwalker\functions as FU;

class Flexwalker {
	
	/**
	 * @since 1.0
	 * @var array $cfg Configuration from JSON files, acts as defaults.
	 */
	protected $cfg = [
		'location'  => '',
		'template'  => '',
		'nid'       => '',
		'display'   => [],
		'tags'      => [],
		'templates' => [],
		'walker'    => [],
		'js'        => [],
	];
	
	/**
	 * @since 1.0
	 * @var array $args Parsed arguments, combination of $cfg and $args param.
	 */
	protected $args = [];
	
	/**
	 * @since 1.0
	 * @var array $core Configuration added via constructor arguments
	 */
	protected $core = [
		'path'    => '',
		'url'     => '',
		'version' => '',
	];
	
	/**
	 * @since 1.0
	 * @var array $template Current template
	 */
	protected $template = [];
	
	/**
	 * @since 1.0
	 * @var array $open_tags Currently open tags
	 */
	protected $open_tags = [];
	
	/**
	 * Bootstrap4_Menus constructor.
	 *
	 * @since 1.0
	 *
	 * @param array $core
	 */
	public function __construct( $core ) {
		foreach ( $core as $key => $val ) {
			$this->core[ $key ] = $val;
		}
	}
	
	/**
	 * Builds menu. Any item in the configured JSON items can be overwritten.
	 *
	 * @since 1.0
	 *
	 * @param    array $args
	 *     $args = [
	 *         'template' => (string) key for the template in $args[templates], required.
	 *         'location' => (string) WordPress menu slug, required.
	 *     ]
	 *
	 * This will be merged recursively with $this->cfg, so any keys there can be overwritten by importing the
	 * same keys in your arguments array.
	 *
	 * $append_numeric uses boolean value of $args['append_numeric']. It only affects arrays with numeric indexes:
	 *   PASSED                  INITIAL                    TRUE                               FALSE
	 *   $args['foo']['d','e']   $cfg['foo']['a','b','c']   $cfg['foo']['a','b','c','d','e']   $cfg['foo']['d','e','c']
	 * Default is TRUE.
	 *
	 * @return string
	 */
	public function menu( $args = [] ) {
		
		$append_numeric = isset( $args['append_numeric'] ) ? (bool) $args['append_numeric'] : true;
		
		// merge imported args with $this->cfg
		$this->args = FU\parse_args_r( $args, $this->cfg, $append_numeric );
		
		// get matching template
		$this->template = FU\return_array_by_id( $this->args['template'], $this->args['templates'] );
		
		// check required arguments
		if ( ! $this->required() ) {
			return '';
		}
		
		// random ID is generated for menu if one is not passed
		$this->args['nid'] = empty( $this->args['nid'] ) ? FU\random_id() : $this->args['nid'];
		
		// add a filter to the nav walker allowing our configured tags and templates in
		$this->walker_filter();
		
		return $this->navbar( $this->template['struct'] );
	}
	
	/**
	 * Enqueues javascript
	 *
	 * @since 1.0
	 */
	public function js() {
		
		if ( isset( $this->cfg['display']['use'] ) && $this->cfg['display']['use'] ) {
			
			add_action( 'wp_enqueue_scripts', function () {
				
				wp_enqueue_script(
					FLXW,
					$this->core['url'] . 'code/' . FLXW . '.js',
					$this->cfg['display']['js'],
					$this->core['version'],
					TRUE
				);
				
				// Allows localized vars sent to JS to be modified
				$local = apply_filters( FLXW . '_localize', $this->cfg['display'] );
				
				wp_localize_script( FLXW, 'FLEX', $local );
			} );
		}
	}
	
	/**
	 * Allows viewing of the plugin's config; optionally allows setting the cfg, key must be empty
	 * and array structure should match $this->cfg.
	 *
	 * @since 1.0
	 *
	 * @param string     $key
	 * @param null|array $set
	 *
	 * @return mixed
	 */
	public function cfg( $key = '', $set = NULL ) {
		
		if ( ! $key && $set !== NULL ) {
			
			$append_numeric = isset( $set['append_numeric'] ) ? (bool) $set['append_numeric'] : true;
			$this->cfg      = FU\parse_args_r( $set, $this->cfg, $append_numeric );
			
			return $this->cfg;
		}
		
		return FU\dots( $key, $this->cfg );
	}
	
	/**
	 * Builds navbar. 'walker' is a special key which fires up the modified walker.
	 *
	 * @since 1.0
	 *
	 * @param $structure
	 *
	 * @return string
	 */
	private function navbar( $structure ) {
		
		$ret  = '';
		$tags = explode( ' ', $structure );
		
		foreach ( $tags as $tag ) {
			
			if ( $tag == 'walker' ) {
				
				$ret .= $this->get_walker();
				
			} else if ( $tag == '/' ) {
				
				$close = array_pop( $this->open_tags );
				$arr   = FU\return_array_by_id( $close, $this->args['tags'] );
				
				$ret .= FU\close_tag( $arr );
				
			} else {
				
				$this->open_tags[] = $tag;
				$arr               = FU\return_array_by_id( $tag, $this->args['tags'] );
				
				$ret .= FU\open_tag( $arr );
			}
		}
		
		return FU\tokens( $ret, $this->args );
	}
	
	/**
	 * Gets WP_Nav_Menu
	 *
	 * @since 1.0
	 *
	 * @return false|object
	 */
	private function get_walker() {
		
		$walk_args = FU\tokens( $this->args['walker'], $this->args );
		
		$walk_args['walker']     = new $walk_args['walker']();
		$walk_args['items_wrap'] = $this->wrapper();
		
		return wp_nav_menu( apply_filters( FLXW . '_wpnavmenu_arguments', $walk_args ) );
	}
	
	/**
	 * Returns a sprintf string for WP to play with.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private function wrapper() {
		
		$t = FU\dots( 'tags.menu.tag', $this->args );
		
		return '<' . $t . ' id="%1$s" class="%2$s">%3$s</' . $t . '>';
	}
	
	/**
	 * Checks if required arguments are present.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private function required() {
		
		// check for missing items we can't proceed without
		$ret = ! $this->args['location'] ? FALSE : TRUE;
		$ret = $ret && empty( $this->template ) ? FALSE : $ret;
		
		// if nothing is missing, make sure depth is set
		$this->args['depth'] = ! empty( $this->args['depth'] ) ? $this->args['depth'] : $this->template['depth'];
		
		return $ret;
	}
	
	/**
	 * Adds filter to nav walker allow tags/templates to be referenced.
	 *
	 * @since 1.0
	 */
	private function walker_filter() {
		
		add_filter( 'wp_nav_menu_args', function ( $args ) {
			
			if ( $args['theme_location'] == $this->args['location'] ) {
				$args['flextags'] = $this->args['tags'];
				$args['flextemp'] = $this->template['lvl'];
				$args['nid']      = $this->args['nid'];
			}
			
			return $args;
		} );
	}
}