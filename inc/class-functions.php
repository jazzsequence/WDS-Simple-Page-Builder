<?php
/**
 * Handle the front-end display of Page Builder Parts.
 *
 * @package SPB2
 */

namespace SPB2;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SPB2 Functions class.
 */
class Functions {

	public $part_slug;
	protected $parts_index = 0;

	/**
	 * Construct function to get things started.
	 *
	 * @param object $plugin The plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();

		// Setup some base variables for the plugin.
		$this->basename       = $plugin->basename;
		$this->directory_path = $plugin->directory_path;
		$this->directory_url  = $plugin->directory_url;
		$this->part_slug      = '';
		$this->templates_loaded = false;
	}

	/**
	 * All our hooks.
	 */
	public function hooks() {
		add_action( 'spb2_load_parts', array( $this, 'add_template_parts' ), 10, 3 );
		add_action( 'spb2_after_load_parts', array( $this, 'templates_loaded' ) );
		add_action( 'cmb2_after_init', array( $this, 'wrapper_init' ) );
	}

	/**
	 * Toggles the templates-loaded status, triggered by the spb2_after_load_parts hook
	 *
	 * @since  1.5
	 */
	public function templates_loaded() {
		if ( false === $this->templates_loaded ) {
			$this->templates_loaded = true;
		}
	}

	/**
	 * Handle identifying the template parts to use and trigger loading those parts
	 *
	 * @param string $layout    Optional parameter to specify a specific layout to use.
	 * @param string $container The container to use.
	 * @param string $class     Custom class to use as wrapper.
	 */
	public function add_template_parts( $layout = '', $container = '', $class = '' ) {

		if ( '' == $layout ) {
			$this->templates_loaded = false;
			if ( ! spb2_get_option( 'parts_saved_layouts' ) && ( ! is_page() || spb2_get_option( 'post_types' ) && ! in_array( get_post_type(), spb2_get_option( 'post_types' ) ) ) ) {
				return;
			}
		}

		$post_id            = ( is_singular() ) ? get_queried_object()->ID : 0;
		$parts              = get_post_meta( $post_id, '_spb2_template', true );
		$global_parts       = spb2_get_option( 'parts_global_templates' );
		$saved_layouts      = spb2_get_option( 'parts_saved_layouts' );
		$registered_layouts = get_option( 'spb2_layouts' );

		// If there are no parts saved for this post, no global parts, no saved layouts, and no layout passed to the action.
		if ( ! $parts && ! $global_parts && ! $saved_layouts && '' == $layout ) {
			return;
		}

		// If a layout was passed or a layout is being used by default for this post type, we're going to check that first.
		if ( ! $parts && $saved_layouts || ! $parts && $registered_layouts ) {

			// Check if the layout requested is one that was registered.
			if ( $registered_layouts ) {

				if ( in_array( $layout, $registered_layouts ) ) {
					$saved_layouts = $registered_layouts;
				}
			}

			// Loop through the saved layouts, we'll check for the one we're looking for.
			foreach ( $saved_layouts as $saved_layout ) {

				// Is the layout the one that was named or one that was set for this post type?
				if ( isset( $saved_layout['layouts_name'] ) && $layout == $saved_layout['layouts_name'] ) {

					$parts = array();
					foreach ( $saved_layout['template_group'] as $template_group ) {
						$parts[] = array( 'template_group' => $template_group );
					}
				} elseif ( isset( $saved_layout['default_layout'] ) && is_array( $saved_layout['default_layout'] ) && in_array( get_post_type( $post_id ), $saved_layout['default_layout'] ) ) {

					// Loop through the template parts and prepare the $parts variable for the load_template_part method.
					foreach ( $saved_layout['template_group'] as $template_group ) {
						$parts[] = array( 'template_group' => $template_group );
					} // End template part loop.
				} // End layout check.
			} // End saved layouts loop.
		} // Done checking saved layouts.

		// Check for locally set template parts, make sure that the part isn't set to none, default to the globals if they aren't set.
		elseif ( ! $parts || in_array( 'none', $parts[0] ) ) {

			$parts = $global_parts;

		}

		// Loop through each part and load the template parts.
		if ( is_array( $parts ) && ! $this->templates_loaded ) {
			do_action( 'spb2_before_load_parts' );
			foreach ( $parts as $this->parts_index => $part ) {

				// Check if the current part was loaded already.
				if ( $this->get_part() && $this->get_part() !== $part['template_group'] ) {

					$this->load_part( $part, $container, $class );
				}
			}
			do_action( 'spb2_after_load_parts' );
		}

	}

	/**
	 * Helper function to keep things DRY, takes care of loading the specific template
	 * part requested
	 *
	 * @param array  $part      A template part array from either the global option or the
	 *                          post meta for the current page.
	 * @param string $container The container type to use.
	 * @param string $class     A custom class to use for the wrapper.
	 */
	public function load_part( $part = array(), $container = '', $class = '' ) {

		// Bail if nothing was passed.
		if ( empty( $part ) ) {
			return;
		}

		// Bail if, for some reason, there is no template_group array key.
		if ( ! isset( $part['template_group'] ) ) {
			return;
		}

		// Bail if no parts were set.
		if ( 'none' == $part['template_group'] ) {
			return;
		}

		$this->set_part( $part['template_group'] );
		$classes = ( $class ) ? $class . ' ' . $this->part_slug : $this->part_slug;

		$part_data = $this->plugin->options->get_part_data( $this->part_slug );

		// Bail if the part doesn't exist.
		if ( ! $part_data ) {
			return;
		}

		/**
		* The template part output.
		*/
		do_action( 'spb2_before_load_template', $container, $classes, $this->part_slug, $part_data );

		load_template( spb_locate_template( $part_data['path'] ), false );

		do_action( 'spb2_after_load_template', $container, $this->part_slug, $part_data );

	}

	/**
	 * Load the template parts.
	 *
	 * @param  string $parts     Template part to load.
	 * @param  string $container Wrapper to load parts in.
	 * @param  string $class     Class to apply to wrapper.
	 * @param  string $area      Area to put parts in.
	 * @return void
	 */
	public function load_parts( $parts = '', $container = '', $class = '', $area = 'page_builder_default' ) {
		$this->plugin->areas->set_current_area( $area );

		if ( ! is_array( $parts ) ) {
			do_action( 'spb2_load_parts', $parts, $container, $class );
			return;
		}

		// Parts are specified by their slugs, we pass them to the load_part function which uses the load_template_part method in the Main class.
		foreach ( $parts as $index => $part ) {
			$this->set_parts_index( $index );
			$this->load_part( array( 'template_group' => $part ) );
		}

		return;
	}

	/**
	 * Get the current parts index class variable
	 *
	 * @since  1.5
	 * @return string The current value of index
	 */
	public function get_parts_index() {
		return $this->parts_index;
	}

	/**
	 * Set the current parts index class variable
	 *
	 * @param string $index The index value to set.
	 */
	public function set_parts_index( $index ) {
		$this->parts_index = $index;
	}

	/**
	 * Get the current part_slug class variable
	 *
	 * @since  1.5
	 * @return string The current value of part_slug
	 */
	public function get_part() {
		return $this->part_slug;
	}

	/**
	 * Set the current part_slug class variable
	 *
	 * @since  1.5
	 * @param string $part Sets a new value for the part_slug class variable.
	 */
	public function set_part( $part ) {

		/**
		 * Filter to change the part slug of a part. Could be used to allow multiple
		 * instances of the same part to be loaded on a page.
		 */
		$this->part_slug = apply_filters( 'spb2_set_part', $part );

	}

	/**
	 * Returns an array of all the page builder template part slugs on the current page
	 *
	 * @since  1.5
	 * @return array The page builder part slugs
	 */
	public function page_builder_parts() {
		$some_files = array_filter( get_included_files(), array( $this, 'match_parts' ) );
		$the_files  = array();
		foreach ( $some_files as $file ) {
			$file = basename( $file );
			$the_files[] = stripslashes( str_replace( array(
				$this->plugin->options->get_parts_path(),
				'.php',
				'//',
			), '', $file ) );
		}
		return $the_files;
	}

	/**
	 * array_filter callback to match template parts
	 *
	 * @since  1.5
	 * @param  string $var The thing to check.
	 * @return bool        Whether the string was found
	 */
	private function match_parts( $var ) {
		return strpos( $var, 'part-' );
	}

	/**
	 * Adds opening wrap markup
	 *
	 * @since  1.5
	 * @param  string $container The part container.
	 * @param  string $class     The wrapper to put on the part.
	 */
	public function before_parts( $container = '', $class = '' ) {
		$container = ( ! $container ) ? $this->page_builder_container() : sanitize_title( $container );
		$classes = esc_attr( $this->get_classes( $class ) );
		$before = "<$container class=\"$classes\">";

		/**
		 * Filter the wrapper markup.
		 *
		 * Note, there's no filter for what the closing markup would look like, so if the
		 * container element is being changed, make sure to only change the container by
		 * filtering spb2_container.
		 *
		 * @since 1.5
		 * @param string $before The full opening container markup
		 */
		echo apply_filters( 'spb2_wrapper', $before );
	}

	/**
	 * Retrieve the class names for the template part as an array
	 *
	 * Based on post_class, but we're not getting as much information as post_class.
	 * We just want to return a generic class, the current template part slug, and any
	 * custom class names that were passed to the function.
	 *
	 * @param  string|array $class One or more classes to add to the class list.
	 * @return array               Array of classes.
	 */
	public function get_class( $class = '' ) {

		if ( $class ) {
			if ( ! is_array( $class ) ) {
				$class = preg_split( '#\s+#', $class );
			}
			$classes = array_map( 'esc_attr', $class );
		}

		$classes[] = $this->plugin->options->get( 'container_class' );

		return array_unique( $classes );

	}

	/**
	 * Get the classes to use.
	 *
	 * @param  string $class Additional classes to pass to the wrapper.
	 * @return string        All the classes.
	 */
	public function get_classes( $class = '' ) {
		// Separates classes with a single space, collates classes for template part wrapper DIV.
		$classes = join( ' ', $this->get_class( $class ) );

		/**
		 * Filter the list of CSS classes
		 *
		 * @since  1.5
		 * @param  array  $classes   An array of pagebuilder part classes
		 */
		return apply_filters( 'page_builder_classes', $classes );
	}

	/**
	 * Adds closing wrap markup
	 *
	 * @since  1.5
	 * @param  string $container The container type.
	 * @param  string $class     The class used for the container.
	 */
	public function after_parts( $container = '', $class = '' ) {
		$container = ( ! $container ) ? $this->page_builder_container() : esc_attr( $container );
		echo "</$container>";
		echo ( $class ) ? '<!-- .' . esc_attr( $class ) . ' -->' : '';
	}

	/**
	 * Helper function to return the main page builder container element
	 *
	 * @return string The container type.
	 */
	public function page_builder_container() {
		$container = ( $this->plugin->options->get( 'container' ) ) ? $this->plugin->options->get( 'container' ) : 'section';
		return esc_attr( apply_filters( 'spb2_container', $container ) );
	}

	/**
	 * If we've set the option to use a wrapper around the page builder parts, add the actions
	 * to display those parts
	 *
	 * @since  1.5
	 * @return void
	 */
	public function wrapper_init() {
		if ( $this->plugin->options->get( 'use_wrap' ) ) {
			add_action( 'spb2_before_load_template', array( $this, 'before_parts' ), 10, 2 );
			add_action( 'spb2_after_load_template', array( $this, 'after_parts' ), 10, 2 );
		}
	}

}


/**
 * Wrapper for get_parts_path
 *
 * Callback for add_filter that returns theme path to spb_register_template_stack().
 *
 * @access public
 * @return string
 */
function page_builder_get_theme_compat_dir() {

	$options = new Options( spb2() );

	/**
	 * Filters the absolute path of the teamplate locations.
	 *
	 * @param string $dir The absolute path of the template package in use.
	 */
	return apply_filters( 'page_builder_get_theme_compat_dir', $options->get_parts_path() );
}


/**
 * SPB2 templates path.
 *
 * Callback for add_filter that returns plugin path to spb_register_template_stack().
 *
 * @access public
 * @return string
 */
function page_builder_get_plugin_compat_dir() {

	/**
	 * Filters the absolute path of the teamplate locations.
	 *
	 * @param string $dir The absolute path of the template package in use.
	 */
	return apply_filters( 'page_builder_get_plugin_compat_dir', SPB2_VERSION_PATH . 'templates/pagebuilder/' );
}


/**
 * Adds the template folder option directory to template stack
 *
 * @access public
 * @return void
 */
function page_builder_set_theme_compat_dir() {
	spb_register_template_stack( 'page_builder_get_theme_compat_dir', 10 );
	spb_register_template_stack( 'page_builder_get_plugin_compat_dir', 10 );
}
add_action( 'spb_init', 'page_builder_set_theme_compat_dir' );



/**
 * Checks through all locatons to find a template then return its path.
 *
 * @access public
 * @param mixed $template_names List of templates to locate.
 * @param bool  $load           Whether to load the template (default: false).
 * @param bool  $require_once   Whether to require the template (default: true).
 * @return string
 */
function spb_locate_template( $template_names, $load = false, $require_once = true ) {

	// No file found yet.
	$located            = false;
	$template_locations = spb_get_template_stack();

	// Try to find a template file.
	foreach ( (array) $template_names as $template_name ) {

		$template_name = explode( '/', $template_name );

		// Continue if template is empty.
		if ( empty( $template_name ) ) {
			continue;
		}

		// Trim off any slashes from the template name.
		$template_name  = ltrim( end( $template_name ), '/' );

		// Loop through template stack.
		foreach ( (array) $template_locations as $template_location ) {

			// Continue if $template_location is empty.
			if ( empty( $template_location ) ) {
				continue;
			}

			// Check child theme first.
			if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'pagebuilder/' . $template_name ) ) {
				$located = trailingslashit( get_stylesheet_directory() ) . 'pagebuilder/' . $template_name;
				break 2;

				// Check parent theme next.
			} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'pagebuilder/' . $template_name ) ) {
				$located = trailingslashit( get_template_directory() ) . 'pagebuilder/' . $template_name;
				break 2;

				// Check template stack last.
			} elseif ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
				$located = trailingslashit( $template_location ) . $template_name;
				break 2;
			}
		}
	}

	do_action( 'spb_locate_template', $located, $template_name, $template_names, $template_locations, $load, $require_once );

	// Maybe load the template if one was located.
	$use_themes = defined( 'WP_USE_THEMES' ) && WP_USE_THEMES;
	$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
	if ( ( $use_themes || $doing_ajax ) && ( true == $load ) && ! empty( $located ) ) {
		load_template( $located, $require_once );
	}

	return $located;
}
