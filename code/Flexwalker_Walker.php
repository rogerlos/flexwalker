<?php

namespace flexwalker;
use flexwalker\functions as FU;

class Flexwalker_Walker extends \Walker_Nav_Menu {
	
	/**
	 * @var array $struct Holds structure of levels
	 */
	protected $struct = [];
	
	/**
	 * @var array $open_tags Keeps track of open tags across levels
	 */
	protected $open_tags = [];
	
	/**
	 * @var int $top  Tracks which top level item we're on, in theory
	 */
	protected $top = 0;
	
	/**
	 * @var array $el  Stores current item so we can close things properly
	 */
	protected $el = [];
	
	/**
	 * @since 1.0
	 *
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args
	 */
	public function start_lvl( &$output, $depth = 0, $args = [] ) {
		$output .= $this->build_tags( 'lvlo', $args );
	}
	
	/**
	 * @since 1.0
	 *
	 * @param string $output
	 * @param int    $depth
	 * @param array  $args
	 */
	public function end_lvl( &$output, $depth = 0, $args = [] ) {
		$output .= $this->build_tags( 'lvlc', $args, ( $this->top - 1 ) );
	}
	
	/**
	 * @since 1.0
	 *
	 * @param string   $output
	 * @param \WP_Post $item
	 * @param int      $depth
	 * @param array    $args
	 * @param int      $id
	 */
	public function start_el( &$output, $item, $depth = 0, $args = [], $id = 0 ) {
		
		// shut up IDE re: WordPress Mismatch of var type
		$args = (object) $args;

		// get the structure for this item, given depth, children, etc.
		$struct = $this->get_level( $depth, $args );
		$this->el[ $item->ID ] = $struct;
		
		// add opening tags
		$el_output = $this->build_tags( 'open', $args, $struct );
		
		$I = FU\return_array_by_id( $this->struct[ $this->top ]['item'], $args->flextags );
		$A = FU\return_array_by_id( $this->struct[ $this->top ]['link'], $args->flextags );
		
		// add active class
		if ( $item->current || $item->current_item_anchestor ) {
			$I['attr']['class'][] = 'active';
		}
		
		// reconcile classes into our array
		$I['attr']['class'][] = 'nav-item-'   . $item->ID;
		$I['attr']['class'][] = 'flexwalker-top-'    . $this->top;
		$I['attr']['class'][] = 'flexwalker-struct-' . $struct;
		$I['attr']['class']   = apply_filters( 'nav_menu_css_class', array_filter( $I['attr']['class'] ), $item, $args );
		$I['attr']['id']      = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args );
		
		// various attributes
		if ( ! empty( $item->attr_title ) ) {
			$A['attr']['title'] = esc_attr( $item->attr_title );
		}
		if ( ! empty( $item->target ) ) {
			$A['attr']['target'] = esc_attr( $item->target );
		}
		if ( ! empty( $item->xfn ) ) {
			$A['attr']['xfn'] = esc_attr( $item->xfn );
		}
		if ( ! empty( $item->url ) ) {
			$A['attr']['href'] = esc_attr( $item->url );
		}
		
		// filters title
		$title = $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		
		$el_output .= FU\open_tag( $I ) . $args->before;
		$el_output .= FU\open_tag( $A ) . $title . FU\close_tag( $A );
		$el_output .= $args->after;
		$el_output .= FU\close_tag( $I );
		
		// because this walker has the potential for a nested mess, calling the filter normally here is a bad idea
		$output .= $el_output;
	}
	
	/**
	 * Closes nested tags around the el. The el tag itself is closed within el.
	 *
	 * @since 1.0
	 *
	 * @param string   $output
	 * @param \WP_Post $item
	 * @param int      $depth
	 * @param array    $args
	 */
	public function end_el( &$output, $item, $depth = 0, $args = [] ) {
		
		$args   = (object) $args;
		$struct = $this->el[ $item->ID ];
		$output .= $this->build_tags( 'close', $args, $struct  );
		$this->top = $depth == 0 ? $this->top - 1 : $this->top;
	}
	
	/**
	 * Builds tags, keeping track of open and closed tags
	 *
	 * @since 1.0
	 *
	 * @param        $key
	 * @param        $args
	 * @param int    $topkey
	 *
	 * @return string
	 */
	private function build_tags( $key, $args, $topkey = null ) {
		
		$ret    = '';
		$A      = [];
		
		$A['key']    = $key;
		$A['topkey'] = $topkey === null ? $this->top : $topkey;
		$A['struct'] = $this->struct[ $A['topkey'] ][ $A['key'] ];
		$A['tags']   = explode( ' ', $A['struct'] );
		
		if ( empty( $A['struct'] ) || empty( $A['tags'] ) ) {
			return $ret;
		}
		
		$A = apply_filters( 'flexwalker_walker_tags', $A );
		
		$this->open_tags[ $A['topkey'] ] = empty( $this->open_tags[ $A['topkey'] ] ) ?
			[] : $this->open_tags[ $A['topkey'] ];
		
		foreach ( $A['tags'] as $tag ) {
			
			if ( $tag == '/' ) {
				
				$close = array_pop( $this->open_tags[ $A['topkey'] ] );
				$arr   = FU\return_array_by_id( $close, $args->flextags );
				$ret   .= FU\close_tag( $arr );
				
			} else {
				
				$this->open_tags[ $A['topkey'] ][] = $tag;
				$arr = FU\return_array_by_id( $tag, $args->flextags );
				$ret .= FU\open_tag( $arr );
			}
		}
		
		return $ret;
	}
	
	/**
	 * Gets the structure for this level.
	 *
	 * @since 1.0
	 *
	 * @param $depth
	 * @param $args
	 *
	 * @return mixed
	 */
	private function get_level( $depth, $args ) {
		
		foreach ( $args->flextemp as $lkey => $lev ) {
			
			if ( ! empty( $lev['check'] ) ) {
				
				$go = false;
				
				if ( $depth === $lev['check']['depth'] ||
				     ( $depth > $lev['check']['depth'] && $lev['check']['final'] ) ) {
					$go = true;
				}
				
				if ( $go && $this->has_children && $lev['check']['children'] === false ) {
					$go = false;
				}
				
				if ( $go ) {
					
					$this->top = $lkey;
					$this->struct[ $lkey ] = $lev['struct'];
					
					return $lkey;
				}
			}
		}
		return false;
	}
}