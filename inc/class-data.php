<?php
/**
 * SPB2 Data
 *
 * Handles all the page builder data.
 *
 * @package SPB2
 */

namespace SPB2;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SPB2 Data class.
 */
class Data {
	/**
	 * Constructor
	 *
	 * @param object $plugin The plugin instance.
	 * @since 0.1.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}


	/**
	 * Magic getter for SPB2 data.
	 *
	 * @param  string  $part     The SPB2 part.
	 * @param  string  $meta_key The meta key you're trying to retrieve.
	 * @param  integer $post_id  The post ID.
	 * @param  string  $area     The area the part lives in.
	 * @return mixed             Either the data requested or null if none could be found.
	 */
	public function get( $part, $meta_key, $post_id = 0, $area = '' ) {
		// Can specify the index if parts are used multiple times on a page.
		if ( is_array( $part ) ) {

			// Oops? you're doing it wrong!
			if ( ! isset( $part['index'], $part['slug'] ) ) {
				return new WP_Error( 'index_slug_defined_incorrectly', 'The index/slug array was defined incorrectly. Try array( \'index\' => 0, \'slug\' => \'slug-name\' )' );
			}

			$part_index = $part['index'];
			$part_slug  = $part['slug'];

		} else {
			// Get current part index.
			$part_index = ( ! $post_id ) ? spb2()->functions->get_parts_index() : 0;
			$part_slug  = $part;
		}

		$area     = ( $area ) ? $area : spb2()->areas->get_current_area();
		$area_key = $area ? $area . '_' : '';
		if ( 'page_builder_default' == $area ) {
			$area_key = '';
		}
		$post_id = $post_id ? $post_id : get_queried_object_id();
		$meta    = get_post_meta( $post_id, '_spb2_' . esc_attr( $area_key ) . 'template', 1 );

		if ( ! $meta || 'none' == $meta[0]['template_group'] ) {
			// Get default layout for this area.
			$option = get_option( $this->plugin->options->key . '_default_area_layouts' );
			$meta = isset( $option[ $area ] ) ? get_post_meta( $option[ $area ], '_spb2_layout_template', true ) : false;
		}
		if (
			// If index exists and the template_group index is there...
			isset( $meta[ $part_index ]['template_group'] )
			// ...and the template group is the same we're looking for...
			&& $part_slug == $meta[ $part_index ]['template_group']
			// ...and we have the meta_key they're looking for...
			&& isset( $meta[ $part_index ][ $meta_key ] )
		) {
			// Send it back.
			return $meta[ $part_index ][ $meta_key ];
		}

		return null;
	}

}
