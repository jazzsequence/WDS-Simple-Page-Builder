<?php
/**
 * SPB Admin class.
 *
 * Handles everything that happens in the admin.
 *
 * @package SPB2
 */

namespace SPB;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main SPB2 Admin class.
 */
class Admin {

	public $part_slug;
	protected $parts_index = 0;
	protected $parts = array();
	protected $builder_js_required = false;
	protected $cmb = null;
	protected $data_fields = null;
	protected $prefix = '_spb2_';

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @param object $plugin The plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		$this->basename       = $plugin->basename;
		$this->directory_path = $plugin->directory_path;
		$this->directory_url  = $plugin->directory_url;
		$this->part_slug      = '';
		$this->templates_loaded = false;
		$this->area           = '';
	}

	/**
	 * Admin hooks.
	 */
	public function hooks() {
		if ( is_admin() ) {
			add_action( 'cmb2_init', array( $this, 'do_meta_boxes' ) );
			add_filter( 'spb2_area_parts_select', array( $this, 'limit_part_to_area' ), 10, 3 );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
	}

	/**
	 * Handles conditionally loading the SPB admin css
	 *
	 * @since  1.6
	 * @param  string $hook Current page hook.
	 */
	public function load_admin_css( $hook ) {
		if ( in_array( $hook, array( 'post-new.php', 'post.php' ) ) &&
			( 'spb2_layouts' == get_post_type() ||
			is_array( spb2_get_option( 'post_types' ) ) &&
			in_array( get_post_type(), spb2_get_option( 'post_types' ) ) ) ) {
			wp_enqueue_style( 'simple-page-builder-admin', $this->directory_url . '/assets/css/admin.css', '', SPB2::VERSION );


			wp_enqueue_script( 'simple-page-builder-admin', $this->plugin->directory_url . '/assets/js/admin.js', array( 'jquery' ), SPB2::VERSION, true );
		}
	}

	/**
	 * Build our meta boxes
	 */
	public function do_meta_boxes() {
		$this->register_all_area_fields();
	}

	/**
	 * Gets the fields for each group set.
	 *
	 * Has an internal filter to allow for the addition of fields based on the part slug.
	 * ie: To add fields for the template part-sample.php you would add_filter( 'spb2_fields_sample', 'myfunc' )
	 * The added fields will then only show up if that template part is selected within the group.
	 *
	 * @since 1.6
	 *
	 * @return array    A list CMB2 field types
	 */
	public function get_group_fields( $id = 'template_group' ) {
		$fields = array(
			array(
				'name'       => __( 'Template', 'simple-page-builder' ),
				'id'         => $id,
				'type'       => 'select',
				'options'    => apply_filters( 'spb2_area_parts_select', $this->plugin->options->get_parts_select(), $this->plugin->options->get_parts(), $this->area ),
				'attributes' => array( 'class' => 'cmb2_select simple-page-builder-template-select' ),
			),
		);

		return array_merge( $fields, $this->get_data_fields() );
	}

	/**
	 * Retrieve all registered (via filters) additional data fields
	 *
	 * @since  1.6
	 * @return array  Array of additional fields
	 */
	public function get_data_fields() {
		if ( ! is_null( $this->data_fields ) ) {
			return $this->data_fields;
		}

		$this->data_fields = array();

		foreach ( $this->plugin->options->get_parts() as $part_slug => $part ) {
			$new_fields = apply_filters( "spb2_fields_$part_slug", array() );

			if ( ! empty( $new_fields ) && is_array( $new_fields ) ) {

				$this->builder_js_required = true;

				foreach ( $new_fields as $new_field ) {
					$this->data_fields[] = $this->add_wrap_to_field_args( $part_slug, $new_field );
				}
			}
		}

		return $this->data_fields;
	}

	/**
	 * Modify fields to have a before_row/after_row wrap
	 *
	 * @since 1.6
	 * @param  string $part_slug  The template part slug.
	 * @param  array  $field_args The field arguments array.
	 * @return array              The modified field arguments array
	 */
	public function add_wrap_to_field_args( $part_slug, $field_args ) {

		$field_args['_builder_group'] = $part_slug;

		// Add before wrap.
		$field_args['before_row'] = isset( $field_args['before_row'] ) ? $field_args['before_row'] : '<div class="hidden-parts-fields hidden-parts-' . $part_slug . ' hidden" >';

		// Add after wrap.
		$field_args['after_row'] = isset( $field_args['after_row'] ) ? $field_args['after_row'] : '</div><!-- .hidden-parts-' . $part_slug . ' -->';

		return $field_args;
	}

	/**
	 * Handles registering get_page_builder_areas fields
	 *
	 * @since  1.6
	 * @return null
	 */
	public function register_all_area_fields() {

		$areas = get_page_builder_areas();

		if ( ! $areas ) {
			return;
		}

		foreach ( $areas as $area => $layout ) {
			// Only show these meta fields if there's no defined layout for the area.
			if ( empty( $layout['template_group'] ) ) {
				$this->register_area_fields( $area );
			}
		}

	}

	/**
	 * Handles registering fields for a single area
	 *
	 * @since  1.6
	 * @param  string $area Area slug.
	 * @return null
	 */
	public function register_area_fields( $area ) {

		$this->area = $area;
		$area_data = $this->plugin->areas->get_registered_area( $area );

		if ( false === $area_data['edit_on_page'] ) {
			return;
		}

		if ( 'page_builder_default' == $area ) {
			$area_key = '';
		} else {
			$area_key = $area . '_';
		}

		// Get the post type so we can check if there's a saved layout for this area.
		$post_type = '';
		if ( isset( $_GET['post'] ) || isset( $_GET['post_type'] ) ) {
			$post_type = isset( $_GET['post'] ) ? get_post_type( $_GET['post'] ) : $_GET['post_type'];
		}
		$saved_layout = $this->plugin->areas->get_saved_layout( $area, $post_type );

		// If we have a saved layout for this area, see if we need to hide the area.
		if ( $saved_layout ) {
			$hide_area = ( 'on' == get_post_meta( $saved_layout->ID, '_spb2_default_hide_metabox', true ) ) ? true : false;
			// We're hiding the area.
			if ( $hide_area ) {
				return;
			}
		}

		$object_types = $this->plugin->options->get( 'post_types', array( 'page' ) );

		/**
		 * Filter fires before registering the CMB2 fields for the Page Builder areas. Return false here to short
		 * circuit if you don't want to show the metaboxes in certain instances.
		 */
		$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;
		$do = apply_filters( 'page_builder_display_area_fields', true, $area, $post_type, $post_id, $area_data, $area_key );
		if ( ! $do ) {
			return;
		}

		$cmb = new_cmb2_box( array(
			'id'           => 'spb2_' . $area,
			// Translators: %s is the area name.
			'title'        => sprintf( __( '%s Page Builder Templates', 'simple-page-builder' ), esc_html( $area_data['name'] ) ),
			'object_types' => $object_types,
			'show_on_cb'   => array( $this, 'maybe_enqueue_builder_js' ),
		) );

		if ( $area_data['description'] ) {
			$cmb->add_field( array(
				'id' => $this->prefix . $area_key . 'description',
				'type' => 'title',
				'desc' => esc_html( $area_data['description'] ),
			) );
		}

		$group_field = $cmb->add_field( array(
			'id'       => $this->prefix . $area_key . 'template',
			'type'     => 'group',
			'options'  => array(
				// Translators: %s is the area name.
				'group_title'   => sprintf( __( '%s Template Part {#}', 'simple-page-builder' ), esc_html( $area_data['name'] ) ),
				'add_button'    => __( 'Add another template part', 'simple-page-builder' ),
				'remove_button' => __( 'Remove template part', 'simple-page-builder' ),
				'sortable'      => true,
			),
		) );

		foreach ( $this->get_group_fields() as $field ) {
			$cmb->add_group_field( $group_field, $field );
		}
	}

	/**
	 * Enqueue builder JS if it's needed (based on additional fields being present)
	 *
	 * @since  1.6
	 * @return bool  Whether box should show (it should)
	 */
	public function maybe_enqueue_builder_js() {
		if ( $this->builder_js_required ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_builder_js' ) );
		}

		// We're just using this hook for adding the admin_enqueue_scripts hook.. return true to display the metabox.
		return true;
	}

	/**
	 * Enqueue the builder JS
	 *
	 * @since  1.6
	 */
	public function enqueue_builder_js() {
		wp_enqueue_script( 'simple-page-builder', $this->directory_url . '/assets/js/builder.js', array( 'cmb2-scripts' ), SPB2::VERSION, true );
		$areas = $this->plugin->areas->get_registered_areas();
		// Fake layouts as an area, so JS loads for the Layouts CPT.
		$areas['layout'] = '';
		$data = array();
		foreach ( $areas as $key => $area ) {
			$data[] = $key;
		}
		wp_localize_script( 'simple-page-builder', 'page_builder_areas', $data );
	}

	/**
	 * Used to filter the drop-down options for areas and to limit a part to only working in declared areas.
	 *
	 * @param array  $options The optiosn passed to the area.
	 * @param array  $parts   The parts assigned to the area.
	 * @param string $area    The current area being edited.
	 *
	 * @return mixed
	 */
	public function limit_part_to_area( $options, $parts, $area ) {
		global $post;

		// Don't filter the parts by area if we're editing a saved layout.
		if ( ( $post && 'spb2_layouts' !== $post->post_type ) || isset( $_GET['post_type'] ) && 'spb2_layouts' !== $_GET['post_type'] ) {
			foreach ( $parts as $slug => $part ) {
				if ( ! $part['area'] ) {
					continue;
				}
				if ( ! in_array( $area, $part['area'] ) ) {
					unset( $options[ $slug ] );
				}
			}
		}

		return $options;
	}

}
