<?php
/**
 * Template Tags
 * Public functions that can be used in themes and plugins.
 *
 * @package SPB2
 */

namespace SPB2;

/**
 * Helper function for loading a single template part
 *
 * @since  1.3
 * @param  string $part The part slug.
 * @return null
 */
function spb2_load_part( $part = '' ) {
	// Bail if no part was specified.
	if ( '' == $part ) {
		return;
	}

	spb2()->functions->load_part( array( 'template_group' => $part ) );
}

/**
 * Gets an array of page builder parts.
 *
 * Note, this function ONLY returns values AFTER the parts have been loaded, so hook into
 * spb2_after_load_parts or later for this to be populated.
 *
 * @since  1.5
 * @return array An array of template parts in use on the page
 */
function get_page_builder_parts() {
	return spb2()->functions->page_builder_parts();
}

/**
 * Function to register a new page builder "area"
 *
 * @param  string $slug      The slug of the area.
 * @param  string $name      The descriptive name of the area.
 * @param  array  $templates You can define the templates that go in this area the same way you would with register_page_builder_layout.
 * @return void
 */
function register_page_builder_area( $slug = '', $name = '', $templates = array() ) {
	// Bail if no name was passed.
	if ( ! $slug ) {
		return;
	}

	spb2()->areas->register_area( $slug, $name, $templates );
}

/**
 * Gets the page builder areas
 *
 * @return mixed False if there are no areas or an array of layouts if there's more than one.
 */
function get_page_builder_areas() {
	$areas = spb2()->areas->get_registered_areas();

	if ( ! $areas ) {
		return false;
	}

	return $areas;
}

/**
 * Function that can be used to return a specific page builder area
 *
 * @param  string  $area    The area by slug/name.
 * @param  integer $post_id Optional. The post id. If none is passed, we will try to get one if
 *                          it's necessary.
 * @return void
 */
function get_page_builder_area( $area = '', $post_id = 0 ) {
	spb2()->areas->get_area( $area, $post_id );
}

/**
 * The function to load a page builder area
 *
 * @since  1.6.0
 * @param  string  $area    Which area to load. If no page builder area is found, will look for a saved layout with the same name.
 * @param  integer $post_id Optional. The post id.
 * @todo                    Add support for custom container elements and classes.
 * @return void
 */
function spb2_area( $area = 'page_builder_default', $post_id = 0 ) {
	spb2()->areas->do_area( $area, $post_id );
}

/**
 * Function to programmatically set certain Page Builder options
 *
 * Possible $args values:
 *    'parts_dir'       The directory that template parts are saved in.
 *    'parts_prefix'    The template part prefix being used.
 *    'use_wrap'        'on' to use the container wrap, empty string to omit.
 *    'container'       A valid HTML container type.
 *    'container_class' The container class.
 *    'post_types'      A post type name as a string or array of post types.
 *    'hide_options'    True to hide options that have been set, disabled to display them as uneditable fields.
 *
 * @param  array $args An array of arguments matching Page Builder settings in the options table.
 * @return void
 */
function wds_register_page_builder_options( $args = array() ) {
	$defaults = array(
		'hide_options'    => true,
	);

	$args = wp_parse_args( $args, $defaults );

	do_action( 'wds_register_page_builder_options', $args );
}

/**
 * Helper function to add Page Builder theme support
 *
 * Because theme features are all hard-coded, we can't pass arguments directly to  add_theme_supports (at least, not that I'm aware of...). This helper function MUST be used in combination with `add_theme_support( 'simple-page-builder' )` in order to pass the correct values to the Page Builder options.
 *
 * Possible $args values:
 *    'parts_dir'       The directory that template parts are saved in.
 *    'parts_prefix'    The template part prefix being used.
 *    'use_wrap'        'on' to use the container wrap, empty string to omit.
 *    'container'       A valid HTML container type.
 *    'container_class' The container class.
 *    'post_types'      A post type name as a string or array of post types.
 *    'hide_options'    True to hide options that have been set, disabled to display them as uneditable fields.
 *
 * @since  1.5
 * @param  array $args An array of arguments matching Page Builder settings in the options table.
 * @return void
 */
function spb2_theme_support( $args = array() ) {
	$defaults = array(
		'hide_options'    => true,
	);

	$args = wp_parse_args( $args, $defaults );
	do_action( 'spb2_add_theme_support', $args );
}

/**
 * Grabs the value of the current template part's meta key.
 *
 * @since 1.6
 * @param string $meta_key  The meta key to find the value of.
 *
 * @return mixed|null       Null on failure or the value of the meta key on success.
 */
function spb2_get_this_part_data( $meta_key ) {
	$part_slug = spb2()->functions->get_part();

	if ( $part_slug ) {
		return spb2_get_part_data( $part_slug, $meta_key );
	}

	return null;
}

/**
 * Grabs the value of specific meta keys for specific template parts.
 *
 * $part_slug should be the slug of the template part, for instance if the template
 * part is `part-sample.php` where part is the prefix, the slug would be `sample` excluding
 * the .php extension.
 *
 * @since 1.6
 * @param string $part     The template part slug or index/slug array.
 * @param string $meta_key The meta to find the value of.
 * @param int    $post_id  The Post ID to retrieve the data for (optional).
 * @param string $area     The area to pull the meta data from (optional).
 *
 * @return null|mixed      Null on failure, the stored meta value on success.
 */
function spb2_get_part_data( $part, $meta_key, $post_id = 0, $area = '' ) {
	return spb2()->data->get( $part, $meta_key, $post_id, $area );
}

/**
 * Wrapper function around Options::get()
 *
 * @since  0.1.0
 * @param  string $key     Options array key.
 * @param  string $default A default value for the option.
 * @return mixed           Option value
 */
function spb2_get_option( $key = '', $default = false ) {
	return spb2()->options->get( $key, $default );
}


/**
 * Helper function to return the main page builder container element
 *
 * @return string The class name
 */
function spb2_container() {
	$container = spb2_get_option( 'container' );
	return esc_attr( apply_filters( 'spb2_container_class', $container ) );
}

/**
 * Helper function to return the main page builder container class
 *
 * @return string The class name
 */
function spb2_container_class() {
	$class = spb2_get_option( 'container_class' );
	return esc_attr( apply_filters( 'spb2_container_class', $class ) );
}

/**
 * Get a list of the template parts in the current theme, return them in an array.
 *
 * @return array An array of template parts
 */
function spb2_get_parts() {
	$parts = spb2()->options->get_parts();

	return $parts;
}

/**
 * Return a saved layout object by its slug.
 *
 * @since  1.6.0
 * @param  string $layout_name The post slug of the pagebuilder layout.
 * @return object              The WP_Post object for the pagebuilder layout.
 */
function get_saved_page_builder_layout_by_slug( $layout_name = '' ) {
	return spb2()->areas->get_saved_layout_by_slug( $layout_name );
}

/**
 * Return the last saved layout for a given area and post type.
 *
 * @since  1.6.0
 * @param  string $area      The pagebuilder area to query by.
 * @param  string $post_type The post type of the post displaying the area.
 * @return array             A get_posts array of pagebuilder layouts.
 */
function get_saved_page_builder_layout( $area = '', $post_type = '' ) {
	return spb2()->areas->get_saved_layout( $area, $post_type );
}

/**
 * Register a template parts folder to the Page Builder template stack.
 *
 * @access public
 * @param string $location_callback Callback function that registers the folder in the template stack.
 * @param int    $priority          Priority for registering the folder.
 * @link  https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Page-Builder-Template-Stack
 * @return string                   Filtered callback function, if successful.
 */
function spb_register_template_stack( $location_callback = '', $priority = 10 ) {

	// Bail if no location, or function/method is not callable.
	if ( empty( $location_callback ) || ! is_callable( $location_callback ) ) {
		return false;
	}

	// Add location callback to template stack.
	return add_filter( 'spb_template_stack', $location_callback, (int) $priority );
}


/**
 * Get all the template files in the page builder template stack.
 *
 * @since  1.6.0
 * @access public
 * @return array
 */
function spb_get_template_stack() {
	global $wp_filter, $merged_filters, $wp_current_filter;

	// Setup some default variables.
	$tag  = 'spb_template_stack';
	$args = $stack = array();

	// Add 'spb_template_stack' to the current filter array.
	$wp_current_filter[] = $tag;

	// Sort.
	if ( class_exists( 'WP_Hook' ) ) {
		$filter = $wp_filter[ $tag ]->callbacks;
	} else {
		$filter = &$wp_filter[ $tag ];

		// Sort.
		if ( ! isset( $merged_filters[ $tag ] ) ) {
			ksort( $filter );
			$merged_filters[ $tag ] = true;
		}
	}

	// Ensure we're always at the beginning of the filter array.
	reset( $wp_filter[ $tag ] );

	// Loop through 'spb_template_stack' filters, and call callback functions.
	do {
		foreach ( (array) current( $filter ) as $the_ ) {
			if ( ! is_null( $the_['function'] ) ) {
				$args[1] = $stack;
				$stack[] = call_user_func_array( $the_['function'], array_slice( $args, 1, (int) $the_['accepted_args'] ) );
			}
		}
	} while ( next( $filter ) !== false );

	// Remove 'spb_template_stack' from the current filter array.
	array_pop( $wp_current_filter );

	// Remove empties and duplicates.
	$stack = array_unique( array_filter( $stack ) );

	/**
	 * Filters the "template stack" list of registered directories where templates can be found.
	 *
	 * @param array $stack Array of registered directories for template locations.
	 */
	return (array) apply_filters( 'spb_get_template_stack', $stack );
}


/**
 * Check if a page has a specific page builder part.
 *
 * @since  1.6.0
 * @access public
 * @param  mixed $template The template we're looking for.
 * @return boolean         Returns true if loaded on page.
 */
function has_page_builder_part( $template = null ) {

	$parts = spb2()->areas->parts;

	if ( in_array( $template, $parts ) ) {
		return true;
	}

	return false;

}



/**
 * Checks if this is a page that has page builder templates assigned to it.
 *
 * @since  1.6.0
 * @access public
 * @return string
 */
function is_page_builder_page() {

	if ( spb2()->areas->area ) {
		return spb2()->areas->area;
	}

	return false;

}


/**
 * Get all the page builder part files across all Pagebuilder-based plugins/themes.
 *
 * @since  1.6.0
 * @return array An array of Pagebuilder template parts.
 */
function get_page_builder_part_files() {
	if ( spb2()->options->get_part_files() ) {
		return spb2()->options->get_part_files();
	}
	return false;
}
