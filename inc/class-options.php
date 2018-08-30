<?php
/**
 * Add an options page. We want to define a template parts directory and a
 * file prefix that identifies template parts as such.
 *
 * @package SPB2
 */

namespace SPB2;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SPB2 Options class.
 */
class Options {

	/**
	 * Option key, and option page slug
	 *
	 * @var string
	 */
	private $key = 'spb2_options';

	/**
	 * Options page metabox id
	 *
	 * @var string
	 */
	private $metabox_id = 'spb2_option_metabox';

	/**
	 * Options Page title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 *
	 * @var string
	 */
	protected $options_page = '';

	protected $options = null;

	public $parts = array();

	/**
	 * Constructor
	 *
	 * @param object $plugin The plugin instance.
	 * @since 0.1.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		// Set our title.
		$this->title = apply_filters( 'spb2_options_title', __( 'Options', 'simple-page-builder' ) );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
		add_filter( "pre_update_option_{$this->key}", array( $this, 'prevent_blank_templates' ), 10, 2 );
	}

	/**
	 * All our hooks.
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		if ( is_admin() ) {
			add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
		}
		add_action( 'wds_register_page_builder_options', array( $this, 'register_settings' ) );
		add_action( 'spb2_add_theme_support', array( $this, 'add_theme_support' ) );
	}

	/**
	 * Registers the settings via wds_register_page_builder_options
	 *
	 * @since  1.5
	 * @param  array $options The options to update/register.
	 * @return void
	 */
	public function register_settings( $options = array() ) {
		if ( empty( $options ) ) {
			return;
		}

		wp_cache_delete( 'alloptions', 'options' );

		$old_options = $this->get_all();

		$new_options = wp_parse_args( $options, array(
			'hide_options'           => false,
			'parts_dir'              => 'parts',
			'parts_prefix'           => 'part',
			'use_wrap'               => 'on',
			'container'              => 'section',
			'container_class'        => 'pagebuilder-part',
			'post_types'             => array( 'page' ),
			'parts_global_templates' => isset( $old_options['parts_global_templates'] ) ? $old_options['parts_global_templates'] : '',
			'parts_saved_layouts'    => isset( $old_options['parts_saved_layouts'] ) ? $old_options['parts_saved_layouts'] : '',
		) );

		update_option( $this->key, $new_options );

		// Reset options array.
		$this->get_all( true );
	}


	/**
	 * Get parts directory.
	 *
	 * Set up parts dir
	 *
	 * @access public
	 * @return string
	 */
	public function get_parts_dir() {
		$directory = $this->get( 'parts_dir', 'parts' );
		return apply_filters( 'spb2_parts_directory', $directory );
	}

	/**
	 * Get parts path.
	 *
	 * @return string The path to the parts directory.
	 */
	public function get_parts_path() {
		$path = get_template_directory() . '/' . $this->get_parts_dir() . '/';
		return apply_filters( 'spb2_parts_path', $path );
	}

	/**
	 * Get parts prefix.
	 *
	 * @return string The prefix used on template parts.
	 */
	public function get_parts_prefix() {
		$prefix = $this->get( 'parts_prefix', 'part' );
		return apply_filters( 'spb2_parts_prefix', $prefix );
	}

	/**
	 * Get the Page Builder options
	 *
	 * @param  bool $reset Whether to reset the option.
	 * @return array       The Page Builder options array
	 */
	public function get_all( $reset = false ) {
		if ( ! is_null( $this->options ) && ! $reset ) {
			return $this->options;
		}

		$this->options = get_option( $this->key );

		return $this->options;
	}

	/**
	 * Get an option from the option array
	 *
	 * @since  1.6
	 * @param  string $key     The option to get from the array.
	 * @param  mixed  $default Optional. Default value to return if the option does not exist.
	 * @return mixed           The option value or false.
	 */
	public function get( $key, $default = false ) {
		$options = $this->get_all();
		if ( ! $options ) {
			return $default;
		}
		$option = array_key_exists( $key, $options ) ? $options[ $key ] : false;
		return false !== $option ? $option : $default;
	}

	/**
	 * Hooks to pre_update_option_{option name} to prevent empty templates from being saved
	 * to the Saved Layouts
	 *
	 * @param  mixed $new_value The new value.
	 * @param  mixed $old_value The old value.
	 * @return mixed            The filtered setting
	 * @link   https://codex.wordpress.org/Plugin_API/Filter_Reference/pre_update_option_(option_name)
	 * @since  1.4.1
	 */
	public function prevent_blank_templates( $new_value, $old_value ) {
		$saved_layouts = $new_value['parts_saved_layouts'];
		if ( empty( $saved_layouts ) ) {
			return $new_value;
		}
		$i = 0;
		foreach ( $saved_layouts as $layout ) {
			$layout['template_group'] = array_diff( $layout['template_group'], array( 'none' ) );
			$saved_layouts[ $i ] = $layout;
			$i++;
		}
		$new_value['parts_saved_layouts'] = $saved_layouts;
		return $new_value;
	}


	/**
	 * Support WordPress add_theme_support feature
	 *
	 * @since  1.5
	 * @uses   current_theme_supports
	 * @param  array $args            Array of Page Builder options to set.
	 * @link   http://justintadlock.com/archives/2010/11/01/theme-supported-features
	 */
	public function add_theme_support( $args ) {
		if ( current_theme_supports( 'simple-page-builder' ) ) {
			wds_register_page_builder_options( $args );
		}
	}


	/**
	 * Add menu options page
	 *
	 * @since 0.1.0
	 */
	public function add_options_page() {
		add_menu_page( __( 'Page Builder', 'simple-page-builder' ), __( 'Page Builder', 'simple-page-builder' ), 'edit_posts', 'edit.php?post_type=spb2_layouts', '', 'dashicons-list-view' );
		$this->options_page = add_submenu_page( 'edit.php?post_type=spb2_layouts', $this->title, __( 'Page Builder Options', 'simple-page-builder' ), 'manage_options', $this->key, array( $this, 'admin_page_display' ) );
		// Include CMB CSS in the head to avoid FOUT.
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		// Enqueue our JS in the footer.
		wp_enqueue_script( 'simple-page-builder-admin', $this->plugin->directory_url . '/assets/js/admin.js', array( 'jquery' ), SPB2::VERSION, true );
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
		?>
		<div class="wrap cmb2_options_page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<h3 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( remove_query_arg( 'tab' ) ); ?>" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings', 'simple-page-builder' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'default-area-layouts' ) ) ); ?>" class="nav-tab <?php echo $tab == 'default-area-layouts' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Global Area Layouts', 'simple-page-builder' ); ?></a>
			</h3>
			<?php
			if ( 'settings' === $tab ) {
				cmb2_metabox_form( $this->metabox_id, $this->key );
			}
			if ( 'default-area-layouts' === $tab ) {
				?>
				<p><?php esc_html_e( 'Select a global default layout to display as a fallback for each area.', 'wds-simpe-page-builder' ); ?></p>
				<?php
				cmb2_metabox_form( $this->metabox_id . '_default_area_layouts', $this->key . '_default_area_layouts' );
			}
			?>

		</div>
	<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 *
	 * @since  0.1.0
	 */
	public function add_options_page_metabox() {

		$disabled = ( isset( $this->options['hide_options'] ) && 'disabled' == $this->options['hide_options'] ) ? array( 'disabled' => '' ) : array();

		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Template Parts Directory', 'simple-page-builder' ),
			'desc'       => __( 'Where the template parts are located in the theme. Default is /parts', 'simple-page-builder' ),
			'id'         => 'parts_dir',
			'type'       => 'text_small',
			'default'    => 'parts',
			'show_on_cb' => array( $this, 'show_parts_dir' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Template Parts Prefix', 'simple-page-builder' ),
			'desc'       => __( 'File prefix that identifies template parts. Default is part-', 'simple-page-builder' ),
			'id'         => 'parts_prefix',
			'type'       => 'text_small',
			'default'    => 'part',
			'show_on_cb' => array( $this, 'show_parts_prefix' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Use Wrapper', 'simple-page-builder' ),
			'desc'       => __( 'If checked, a wrapper HTML container will be added around each individual template part.', 'simple-page-builder' ),
			'id'         => 'use_wrap',
			'type'       => 'checkbox',
			'show_on_cb' => array( $this, 'show_use_wrap' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Container Type', 'simple-page-builder' ),
			'desc'       => __( 'The type of HTML container wrapper to use, if Use Wrapper is selected.', 'simple-page-builder' ),
			'id'         => 'container',
			'type'       => 'select',
			'options'    => array(
				'section' => __( 'Section', 'simple-page-builder' ),
				'div'     => __( 'Div', 'simple-page-builder' ),
				'aside'   => __( 'Aside', 'simple-page-builder' ),
				'article' => __( 'Article', 'simple-page-builder' ),
			),
			'default'    => 'section',
			'show_on_cb' => array( $this, 'show_container' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Container Class', 'simple-page-builder' ),
			// Translators: 1, 2 and 3 are all HTML elements.
			'desc'       => sprintf( __( '%1$sThe default class to use for all template part wrappers. Specific classes will be added to each wrapper in addition to this. %2$sMultiple classes, separated by a space, can be added here.%3$s', 'simple-page-builder' ), '<p>', '<br />', '</p>' ),
			'id'         => 'container_class',
			'type'       => 'text_medium',
			'default'    => 'pagebuilder-part',
			'show_on_cb' => array( $this, 'show_container_class' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Allowed Post Types', 'simple-page-builder' ),
			'desc'       => __( 'Post types that can use the page builder. Default is Page.', 'simple-page-builder' ),
			'id'         => 'post_types',
			'type'       => 'multicheck',
			'default'    => 'page',
			'options'    => $this->get_post_types(),
			'show_on_cb' => array( $this, 'show_post_types' ),
			'attributes' => $disabled,
		) );

	}

	/**
	 * Get an array of post types for the options page multicheck array
	 *
	 * @uses   get_post_types
	 * @return array          An array of public post types
	 */
	public function get_post_types() {

		$post_types = apply_filters( 'spb2_post_types', get_post_types( array( 'public' => true ), 'objects' ) );

		foreach ( $post_types as $post_type ) {
			$types[ $post_type->name ] = $post_type->labels->name;
		}

		return $types;

	}

	/**
	 * Get an array of the locations of the parts in the parts directory.
	 *
	 * @return array An array of all parts found in the parts directory.
	 */
	public function get_part_files() {

		$stack = spb_get_template_stack();

		// If in admin refresh glob transient.
		if ( is_admin() ) {
			delete_transient( 'spb_part_glob' );
		}

		// Check for glob transient and if return instead of re-glob.
		if ( $parts = get_transient( 'spb_part_glob' ) ) {
			return $parts;
		}

		$parts = array();

		// Loop through stack and gobble up the templates, yum!
		foreach ( $stack as $item ) {
			array_push( $parts, glob( $item . '*.php', GLOB_NOSORT ) );
		}

		$parts = call_user_func_array( 'array_merge', $parts );

		// Stash glob results in a transient.
		set_transient( 'spb_part_glob', $parts, 365 * DAY_IN_SECONDS );

		return $parts;
	}


	/**
	 * Get parts.
	 *
	 * @access public
	 * @return array The parts.
	 */
	public function get_parts() {

		if ( ! $this->parts ) {
			$files = $this->get_part_files();

			$parts = array();
			foreach ( $files as $file ) {

				$data = get_file_data( $file, array(
					'name'        => 'Part Name',
					'description' => 'Description',
					'area'        => 'Area',
					'area'        => 'Areas',
				) );

				$areas = array();

				if ( $data['area'] ) {
					$areas = explode( ',', $data['area'] );
					$areas = array_map( 'trim', $areas );
					$areas = array_map( 'esc_attr', $areas );
				}

				$slug = explode( '/', str_replace( '.php', '', $file ) );
				$slug = str_replace( 'part-', '', end( $slug ) );

				$parts[ esc_attr( $slug ) ] = array(
					'name'        => $data['name'] ? esc_attr( $data['name'] ) : ucwords( str_replace( '-', ' ',  esc_attr( $slug ) ) ),
					'description' => esc_attr( $data['description'] ),
					'path'        => esc_attr( $file ),
					'area'        => $areas,
				);

				if( empty($data['name']) ) {
					unset( $parts[ esc_attr( $slug[1] ) ] );
				}

			}

			$this->parts = $parts;

		}
		return $this->parts;
	}


	/**
	 * Get part data.
	 *
	 * @access public
	 * @param mixed $slug The slug for the part to get data from.
	 * @return string     The part data.
	 */
	public function get_part_data( $slug ) {
		$parts = $this->get_parts();
		return isset( $parts[ $slug ] ) ? $parts[ $slug ] : false;
	}


	/**
	 * Get parts select
	 *
	 * @access public
	 * @return array An array of options.
	 */
	public function get_parts_select() {
		$parts = $this->get_parts();
		$options = array(
			// Add a generic 'none' option.
			'none' => __( '- No Template Parts -', 'simple-page-builder' ),
		);
		foreach ( $parts as $key => $part ) {
			$options[ $key ] = $part['name'];
		}

		return $options;
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  0.1.0
	 * @param  string $field Field to retrieve.
	 * @return mixed         Field value or exception is thrown
	 * @throws Exception     Invalid property if no field found.
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve.
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

	/**
	 * Helper proxy method for the CMB2 show_on callbacks.
	 *
	 * @since  1.5
	 * @param  string $check Key to check for show_on_cb.
	 * @return bool          Whether to show or hide the option.
	 */
	protected function check_if_show( $check ) {
		if ( 'disabled' === $this->get( 'hide_options' ) ) {
			return true;
		}
		$hide = $this->get( 'hide_options' ) && $this->get( $check );
		return ! $hide;
	}

	/**
	 * CMB2 show_on callback function for parts_dir option.
	 *
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_parts_dir() {
		return $this->check_if_show( 'parts_dir' );
	}

	/**
	 * CMB2 show_on callback function for parts_prefix option.
	 *
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_parts_prefix() {
		return $this->check_if_show( 'parts_prefix' );
	}

	/**
	 * CMB2 show_on callback function for use_wrap option.
	 *
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_use_wrap() {
		return $this->check_if_show( 'use_wrap' );
	}

	/**
	 * CMB2 show_on callback function for container option.
	 *
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_container() {
		return $this->check_if_show( 'container' );
	}

	/**
	 * CMB2 show_on callback function for container_class option.
	 *
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_container_class() {
		return $this->check_if_show( 'container_class' );
	}

	/**
	 * CMB2 show_on callback function for post_types option.
	 *
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_post_types() {
		return $this->check_if_show( 'post_types' );
	}

}
