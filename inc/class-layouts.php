<?php
/**
 * SPB2 Layouts
 *
 * @package SPB2
 */

namespace SPB2;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SPB2 Layouts class.
 */
class Layouts {

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @param object $plugin The plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Create a new layout.
	 *
	 * @param  string $slug      The layout slug.
	 * @param  array  $templates The templates saved in the layout.
	 * @param  array  $args      Arguments passed to the layout.
	 */
	public function register_layout( $slug, $templates = array(), $args = array() ) {
		// Don't register anything if no layout name or templates were passed.
		if ( '' == $slug || empty( $templates ) ) {
			return false;
		}

		$defaults = array(
			'name'        => ucwords( str_replace( '-', ' ', $slug ) ),
			'description' => '',
		);
		$args = wp_parse_args( $args, $defaults );
		$this->registered_areas[ $slug ] = array(
			'name'        => esc_attr( $args['name'] ),
			'description' => esc_html( $args['description'] ),
			'templates'   => $templates,
		);
	}

	/**
	 * All our hooks
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'layouts_cpt' ) );
		if ( is_admin() ) {
			add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
			add_action( 'cmb2_init', array( $this, 'register_fields' ) );
		}
	}

	/**
	 * Register the layouts cpt.
	 */
	public function layouts_cpt() {
		$labels = array(
			'name'               => _x( 'Saved Layouts', 'post type general name', 'simple-page-builder' ),
			'singular_name'      => _x( 'Saved Layout', 'post type singular name', 'simple-page-builder' ),
			'menu_name'          => _x( 'Saved Layouts', 'admin menu', 'simple-page-builder' ),
			'name_admin_bar'     => _x( 'Saved Layout', 'add new on admin bar', 'simple-page-builder' ),
			'add_new'            => _x( 'Add New Layout', 'page builder layout', 'simple-page-builder' ),
			'add_new_item'       => __( 'Add New Layout', 'simple-page-builder' ),
			'new_item'           => __( 'New Layout', 'simple-page-builder' ),
			'edit_item'          => __( 'Edit Layout', 'simple-page-builder' ),
			'view_item'          => __( 'View Layout', 'simple-page-builder' ),
			'all_items'          => __( 'Saved Layouts', 'simple-page-builder' ),
			'search_items'       => __( 'Search Layouts', 'simple-page-builder' ),
			'not_found'          => __( 'No layouts found.', 'simple-page-builder' ),
			'not_found_in_trash' => __( 'No layouts found in Trash.', 'simple-page-builder' ),
		);
		$args = array(
			'labels'        => $labels,
			'public'        => false,
			'show_ui'       => true,
			'has_archive'   => false,
			'hierarchical'  => false,
			'supports'      => array( 'title' ),
			'show_in_menu' => 'edit.php?post_type=spb2_layouts',
		);
		register_post_type( 'spb2_layouts', $args );
	}

	/**
	 * Register the CMB2 fields used in layouts.
	 */
	public function register_fields() {
		$cmb = new_cmb2_box( array(
			'id'           => 'spb2_layout',
			'title'        => __( 'Page Builder Templates', 'simple-page-builder' ),
			'object_types' => array( 'spb2_layouts' ),
			'show_on_cb'   => array( $this->plugin->admin, 'maybe_enqueue_builder_js' ),
		) );

		$group_field = $cmb->add_field( array(
			'id'       => '_spb2_layout_template',
			'type'     => 'group',
			'options'  => array(
				'group_title'   => __( 'Template Part {#}', 'simple-page-builder' ),
				'add_button'    => __( 'Add another template part', 'simple-page-builder' ),
				'remove_button' => __( 'Remove template part', 'simple-page-builder' ),
				'sortable'      => true,
			),
		) );

		foreach ( $this->plugin->admin->get_group_fields() as $field ) {
			$cmb->add_group_field( $group_field, $field );
		}

		$advanced = new_cmb2_box( array(
			'id'           => 'spb2_layout_advanced',
			'title'        => __( 'Layout Defaults', 'simple-page-builder' ),
			'object_types' => array( 'spb2_layouts' ),
			'show_on_cb'   => array( $this->plugin->admin, 'maybe_enqueue_builder_js' ),
		) );

		$advanced->add_field( array(
			'id'      => '_spb2_default_post_type',
			'name'    => __( 'Post Type', 'simple-page-builder' ),
			'desc'    => __( 'Set this layout as the default layout for the these post types.', 'simple-page-builder' ),
			'type'    => 'multicheck',
			'options' => $this->plugin->options->get_post_types(),
		) );

		$advanced->add_field( array(
			'id'      => '_spb2_default_area',
			'name'    => __( 'Area', 'simple-page-builder' ),
			'desc'    => __( 'If the layout is set as a default layout for a post type, select what area the layout should be the default in.', 'simple-page-builder' ),
			'type'    => 'radio',
			'default' => 'page_builder_default',
			'options' => $this->get_area_list(),
		) );

		$advanced->add_field( array(
			'id'      => '_spb2_default_hide_metabox',
			'name'    => __( 'Hide Metabox', 'simple-page-builder' ),
			'desc'    => __( 'If checked, the metabox for this area will be hidden on these post types.', 'simple-page-builder' ),
			'type'    => 'checkbox',
		) );
	}

	/**
	 * Add a metabox to the option page.
	 */
	public function add_options_page_metabox() {
		$cmb = new_cmb2_box( array(
			'id'         => $this->plugin->options->metabox_id . '_default_area_layouts',
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->plugin->options->key ),
			),
		) );
		$registered_areas = $this->plugin->areas->get_registered_areas();
		foreach ( $registered_areas as $key => $area ) {
			$cmb->add_field( array(
				'name'             => esc_html( $area['name'] ),
				'id'               => $key,
				'type'             => 'select',
				'show_option_none' => true,
				'options'          => $this->get_saved_layouts(),
			) );
		}
	}

	/**
	 * Returns an array of saved layout ids/titles
	 *
	 * @param  array $meta_query Optional. You can pass WP_Query meta query args to
	 *                            filter your results by post type or area (or both).
	 * @return array              An array that's ready for a CMB2 field.
	 */
	public function get_saved_layouts( $meta_query = array() ) {
		$args = array(
			'post_type'      => 'spb2_layouts',
			'posts_per_page' => 9999,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => array( $meta_query ),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$layouts = new WP_Query( $args );
		$return = array();
		while ( $layouts->have_posts() ) :
			$layouts->the_post();
			$return[ get_the_ID() ] = get_the_title();
		endwhile;
		wp_reset_postdata();

		return $return;
	}

	/**
	 * Returns an array of all the areas registered.
	 *
	 * @return array A list of registered areas and area names.
	 */
	public function get_area_list() {
		$areas = $this->plugin->areas->get_registered_areas();

		$output['page_builder_default'] = __( 'Page Builder Default', 'simple-page-builder' );
		foreach ( $areas as $key => $values ) {
			$output[ $key ] = $values['name'];
		}

		return $output;
	}

	/**
	 * Function to return the layout object by a passed slug.
	 *
	 * @param  string $layout_name The slug of the layout.
	 * @return object              The WP_Post object for the layout post.
	 */
	public function get_saved_layout( $layout_name = '' ) {
		if ( ! $layout_name ) {
			return false;
		}

		$layout = get_page_by_path( $layout_name, OBJECT, 'spb2_layouts' );

		return $layout;
	}


	/**
	 * Return the default layouts for the given post type.
	 *
	 * @todo   This method is a stub. It's a WIP and does not do anything yet.
	 * @param  string $post_type The post type to get layouts from.
	 * @return mixed             An array of layouts (maybe?) or false if there are no default layouts for the post type.
	 */
	public function get_default_layouts_for_post_type( $post_type = '' ) {
		if ( ! $post_type ) {
			return false;
		}

	}
}
