<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Page_Builder_Admin' ) ) {

	class WDS_Page_Builder_Admin {

		public $part_slug;
		protected $parts_index = 0;
		protected $builder_js_required = false;
		protected $cmb = null;
		protected $data_fields = null;
		protected $prefix = '_wds_builder_';

		/**
		 * Constructor
		 * @since 0.1.0
		 */
		public function __construct( $plugin ) {
			$this->plugin = $plugin;
			$this->hooks();

			$this->basename       = $plugin->basename;
			$this->directory_path = $plugin->directory_path;
			$this->directory_url  = $plugin->directory_url;
			$this->part_slug      = '';
			$this->templates_loaded = false;
			$this->area           = '';
		}

		public function hooks() {
			if ( is_admin() ) {
				add_action( 'cmb2_init', array( $this, 'do_meta_boxes' ) );
			}
			add_action( 'cmb2_after_init', array( $this, 'wrapper_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
		}

		/**
		 * Handles conditionally loading the SPB admin css
		 * @since  1.6
		 * @param  string $hook Current page hook
		 * @return null
		 */
		public function load_admin_css( $hook ) {
			if ( in_array( $hook, array( 'post-new.php', 'post.php' ) ) && in_array( get_post_type(), wds_page_builder_get_option( 'post_types' ) ) ) {
				wp_enqueue_style( 'wds-simple-page-builder-admin', $this->directory_url . '/assets/css/admin.css', '', WDS_Simple_Page_Builder::VERSION );
			}
		}

		/**
		 * If we've set the option to use a wrapper around the page builder parts, add the actions
		 * to display those parts
		 * @since  1.5
		 * @return void
		 */
		public function wrapper_init() {
			if ( wds_page_builder_get_option( 'use_wrap' ) ) {
				add_action( 'wds_page_builder_before_load_template', array( $this, 'before_parts' ), 10, 2 );
				add_action( 'wds_page_builder_after_load_template', array( $this, 'after_parts' ), 10, 2 );
			}
		}

		/**
		 * Build our meta boxes
		 */
		public function do_meta_boxes() {

			$option = wds_page_builder_get_option( 'post_types' );
			$object_types = $option ? $option : array( 'page' );

			$this->cmb = new_cmb2_box( array(
				'id'           => 'wds_simple_page_builder',
				'title'        => __( 'Page Builder', 'wds-simple-page-builder' ),
				'object_types' => $object_types,
				'show_on_cb'   => array( $this, 'maybe_enqueue_builder_js' ),
			) );

			$this->cmb->add_field( array(
				'id'           => $this->prefix . 'template_group_title',
				'type'         => 'title',
				'name'         => __( 'Content Area Templates', 'wds-simple-page-builder' )
			) );

			$group_field_id = $this->cmb->add_field( array(
				'id'           => $this->prefix . 'template',
				'type'         => 'group',
				'options'      => array(
					'group_title'   => __( 'Template Part {#}', 'wds-simple-page-builder' ),
					'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true
				)
			) );

			foreach ( $this->get_group_fields() as $field ) {
				$this->cmb->add_group_field( $group_field_id, $field );
			}

			$this->register_all_area_fields();
		}

		/**
		 * Gets the fields for each group set.
		 *
		 * Has an internal filter to allow for the addition of fields based on the part slug.
		 * ie: To add fields for the template part-sample.php you would add_filter( 'wds_page_builder_fields_sample', 'myfunc' )
		 * The added fields will then only show up if that template part is selected within the group.
		 *
		 * @since 1.6
		 *
		 * @return array    A list CMB2 field types
		 */
		public function get_group_fields( $id = 'template_group' ) {

			$fields = array(
				array(
					'name'       => __( 'Template', 'wds-simple-page-builder' ),
					'id'         => $id,
					'type'       => 'select',
					'options'    => $this->get_parts(),
					'attributes' => array( 'class' => 'cmb2_select wds-simple-page-builder-template-select' ),
				),
			);

			return array_merge( $fields, $this->get_data_fields() );
		}

		/**
		 * Wrapper for wds_page_builder_get_parts which stores it's result
		 * @since  1.6
		 * @return array  Array of parts options
		 */
		public function get_parts() {
			if ( ! empty( $this->parts ) ) {
				return $this->parts;
			}

			$this->parts = wds_page_builder_get_parts();
			return $this->parts;
		}

		/**
		 * Retrieve all registered (via filters) additional data fields
		 * @since  1.6
		 * @return array  Array of additional fields
		 */
		public function get_data_fields() {
			if ( ! is_null( $this->data_fields ) ) {
				return $this->data_fields;
			}

			$this->data_fields = array();

			foreach ( $this->get_parts() as $part_slug => $part_value ) {
				$new_fields = apply_filters( "wds_page_builder_fields_$part_slug", array() );

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
		 * @since 1.6
		 * @param  string  $part_slug  The template part slug
		 * @param  array   $field_args The field arguments array
		 * @return array               The modified field arguments array
		 */
		public function add_wrap_to_field_args( $part_slug, $field_args ) {

			$field_args['_builder_group'] = $part_slug;

			// Add before wrap
			$field_args['before_row'] = isset( $field_args['before_row'] ) ? $field_args['before_row'] : '<div class="hidden-parts-fields hidden-parts-'. $part_slug .' hidden" >';

			// Add after wrap
			$field_args['after_row'] = isset( $field_args['after_row'] ) ? $field_args['after_row'] : '</div><!-- .hidden-parts-'. $part_slug .' -->';

			return $field_args;
		}

		/**
		 * Handles registering get_page_builder_areas fields
		 * @since  1.6
		 * @return null
		 */
		public function register_all_area_fields() {

			$areas = get_page_builder_areas();

			if ( ! $areas ) {
				return;
			}

			foreach( $areas as $area => $layout ) {
				// only show these meta fields if there's no defined layout for the area
				if ( empty( $layout['template_group'] ) ) {
					$this->register_area_fields( $area );
				}

			}

		}

		/**
		 * Handles registering fields for a single area
		 * @since  1.6
		 * @param  string $area   Area slug
		 * @return null
		 */
		public function register_area_fields( $area ) {

			$area_group_field_id = $area . '_group_field_id';

			$this->cmb->add_field( array(
				'id'       => $this->prefix . $area . '_' . 'title',
				'type'     => 'title',
				'name'     => sprintf( __( '%s Area Templates', 'wds-simple-page-builder' ), ucfirst( $area ) ),
			) );

			$$area_group_field_id = $this->cmb->add_field( array(
				'id'       => $this->prefix . $area . '_' . 'template',
				'type'     => 'group',
				'options'  => array(
					'group_title'   => sprintf( __( '%s Template Part {#}', 'wds-simple-page-builder' ), ucfirst( $area ) ),
					'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true,
				)
			) );

			foreach ( $this->get_group_fields() as $field ) {
				$this->cmb->add_group_field( $$area_group_field_id, $field );
			}
		}

		/**
		 * Enqueue builder JS if it's needed (based on additional fields being present)
		 * @since  1.6
		 * @return bool  Whether box should show (it should)
		 */
		public function maybe_enqueue_builder_js() {
			if ( $this->builder_js_required ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_builder_js' ) );
			}

			// We're just using this hook for adding the admin_enqueue_scripts hook.. return true to display the metabox
			return true;
		}

		/**
		 * Enqueue the builder JS
		 * @since  1.6
		 * @return null
		 */
		public function enqueue_builder_js() {
			wp_enqueue_script( 'wds-simple-page-builder', $this->directory_url . '/assets/js/builder.js', array( 'cmb2-scripts' ), WDS_Simple_Page_Builder::VERSION, true );
		}

	}
}