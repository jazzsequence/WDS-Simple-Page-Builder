<?php
/**
 * SPB2 Areas
 *
 * @package SBP2
 */

namespace SPB2;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the logic around Page Builder Areas
 */
class Areas {
	/**
	 * Constructor
	 *
	 * @param object $plugin The parent plugin object.
	 * @since 0.1.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->registered_areas = array();
		$this->current_area = '';
		$this->parts = array();
		$this->area = '';

		$this->hooks();
	}

	/**
	 * Run our hooks
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_default_area' ) );
	}

	/**
	 * Registers the page_builder_default area which is used as a fallback.
	 */
	public function register_default_area() {
		$this->register_area(
			'page_builder_default',
			array(
				'name'        => __( 'Default Page Builder Area', 'simple-page-builder' ),
				'description' => __( 'This is the default area. Place the template tag spb2_area() in your theme file to display. You can also create custom areas.', 'simple-page-builder' ),
			)
		);
	}

	/**
	 * Allows new areas to be registered.
	 *
	 * @param  string $slug      The area name/slug.
	 * @param  array  $args      An array of meta data about the area.
	 * @param  array  $templates Hard code some templates that use this area.
	 * @return void
	 */
	public function register_area( $slug, $args = array(), $templates = array() ) {
		$defaults = array(
			'name'         => ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ),
			'description'  => '',
			'edit_on_page' => true,
		);
		$args = wp_parse_args( $args, $defaults );
		$this->registered_areas[ $slug ] = array(
			'name'         => esc_attr( $args['name'] ),
			'description'  => esc_html( $args['description'] ),
			'templates'    => $templates,
			'edit_on_page' => $args['edit_on_page'],
		);
	}

	/**
	 * Gets all the registered areas.
	 *
	 * @return array An array of all the areas that have been registered.
	 */
	public function get_registered_areas() {
		return $this->registered_areas;
	}

	/**
	 * Gets information about the registered area.
	 *
	 * @param  string $slug The area we want information about.
	 * @return array        The registered area data.
	 */
	public function get_registered_area( $slug ) {
		$area = isset( $this->registered_areas[ $slug ] ) ? $this->registered_areas[ $slug ] : false;
		return $area;
	}

	/**
	 * Get the current area variable
	 *
	 * @return string The area slug.
	 */
	public function get_current_area() {
		return $this->current_area;
	}

	/**
	 * Set the current area variable
	 *
	 * @param  string $area The slug of the area you are setting.
	 * @return void
	 */
	public function set_current_area( $area ) {
		$this->current_area = $area;
	}


	/**
	 * Return a saved layout object by its slug.
	 * Note: This only works with layouts created after 1.6.
	 *
	 * @since  1.6.0
	 * @param  string $layout_name The post slug of the pagebuilder layout.
	 * @return object              The WP_Post object for the pagebuilder layout.
	 */
	public function get_saved_layout_by_slug( $layout_name = '' ) {
		if ( '' == $layout_name ) {
			return false;
		}

		$layout = get_posts( array(
			'post_type'      => 'spb2_layouts',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'name'           => sanitize_title( $layout_name ),
		) );

		$post_id   = $layout[0]->ID;
		return $this->do_area( $area, $post_id );
	}

	/**
	 * Return all the saved layouts for a given area and post type.
	 *
	 * @since  1.6.0
	 * @param  string $area      The pagebuilder area to query by.
	 * @param  string $post_type The post type to get layouts from.
	 * @return object            The WP_Post object for the most recent pagebuilder layout.
	 */
	public function get_saved_layout( $area, $post_type = '' ) {

		$post_type = ( '' !== $post_type && in_array( $post_type, get_post_types() ) ) ? $post_type : get_post_type( get_queried_object_id() );

		$layouts = get_posts( array(
			'post_type'      => 'spb2_layouts',
			'post_status'    => 'publish',
			'posts_per_page' => count( get_post_types() ),
			'meta_query'     => array(
				array(
					'key'     => '_spb2_default_area',
					'value'   => $area,
					'compare' => '=',
				),
			),
		) );

		if ( ! empty( $layouts ) ) {
			foreach ( $layouts as $layout ) {
				// Skip over any saved layouts that aren't for the passed post type.
				$parts = get_post_meta( $layout->ID, '_spb2_default_post_type', true );
				if ( ! $parts || ! in_array( $post_type, $parts ) ) {
					continue;
				}
				// Return the first layout we come to for the correct post type.
				return $layout;
			}
		}

		// We don't have any layouts.
		return false;
	}

	/**
	 * Get the templates for an area.
	 *
	 * @since  1.6.0
	 * @param  string  $area    The area to get templates from.
	 * @param  integer $post_id The post ID we're checking.
	 * @return array            An array of template parts to load.
	 */
	public function get_area( $area, $post_id = 0 ) {
		$area_data = $this->get_registered_area( $area );

		// If there were no page builder areas, bail.
		if ( ! $area_data && 'page_builder_default' !== $area ) {
			return;
		}

		// If no post ID was passed, try to get one.
		if ( 0 == $post_id ) {
			$post_id = get_queried_object_id();
		}

		$post_type = get_post_type( $post_id );

		// Check for a saved layout.
		$saved_layout = $this->get_saved_layout( $area, $post_type );
		if ( $saved_layout && ! is_wp_error( $saved_layout ) ) {
			$post_id = $saved_layout->ID;
		}

		// If it's not singular -- like an archive or a 404 or something -- you can only add template parts by registering the area.
		if ( ! is_singular() && ( ! is_home() && ! $post_id ) ) {
			return isset( $area_data['template_group'] ) ? $area_data['template_group'] : false;
		}

		// If we have a saved layout, store the templates for later.
		$templates = false;
		if ( $saved_layout ) {
			$area_default = get_post_meta( $post_id, '_spb2_default_area', true );
			if ( $area == $area_default ) {
				$templates = get_post_meta( $post_id, '_spb2_layout_template', true );
			}
		}

		$area_key = $area ? $area . '_' : '';
		if ( 'page_builder_default' == $area ) {
			$area_key = '';
		}

		if ( 'spb2_layouts' == $post_type ) {
			$templates = get_post_meta( $post_id, '_spb2_layout_template', true );
		}

		// Either use the templates we got earlier, or get the templates from the current post.
		$templates = ( ! $templates ) ? get_post_meta( $post_id, '_spb2_' . esc_attr( $area_key ) . 'template', true ) : $templates;

		// If we have templates, loop through them and prepare the output.
		if ( $templates ) {
			foreach ( $templates as $template ) {
				$out[] = isset( $template['template_group'] ) ? $template['template_group'] : '';
			}

			return $out;
		}

		// Byeeeee!
		return;
	}


	/**
	 * Handle rendering the template parts.
	 *
	 * @param  string  $area    The area we're getting parts from.
	 * @param  integer $post_id The post ID to check for parts.
	 * @return bool             True if the function was successful. False if not.
	 */
	public function do_area( $area = '', $post_id = 0 ) {
		// Bail if no area was specified.
		if ( '' == $area ) {
			return false;
		}

		// If no post ID was passed, try to get one.
		if ( 0 == $post_id ) {
			$post_id = get_queried_object_id();
		}

		/**
		 * Filer allowing you to short-circuit and not display the area.
		 */
		$do = apply_filters( 'spb2_do_area', true, $area, $post_id );
		if ( ! $do ) {
			return false;
		}

		$parts = $this->get_area( $area, $post_id );

		// If the area's parts are not set, use the default layout.
		if ( ! $parts || 'none' == $parts[0] ) {
			$option = get_option( $this->plugin->options->key . '_default_area_layouts' );
			if ( ! isset( $option[ $area ] ) ) {
				return false;
			}
			$metas = get_post_meta( $option[ $area ], '_spb2_layout_template', true );
			if ( ! $metas ) {
				return false;
			}
			$parts = array();
			foreach ( $metas as $meta ) {
				$parts[] = $meta['template_group'];
			}
		}

		$this->parts = $parts;
		$this->area = $area;

		do_action( 'spb2_load_parts', $parts, $area, $post_id );

		do_action( 'spb2_before_load_parts', $parts, $area, $post_id );
		$this->plugin->functions->load_parts( $parts, '', '', $area );
		do_action( 'spb2_after_load_parts', $parts, $area, $post_id );

		return true;
	}
}
