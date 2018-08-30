<?php
/**
 * Plugin Name: SPB2 (Simple Page Builder 2)
 * Plugin URI: https://github.com/jazzsequence/SPB2/wiki
 * Description: Uses existing template parts in the currently-active theme to build a customized page with rearrangeable elements.
 * Author: jazzs3quence
 * Author URI: https://chrisreynolds.io
 * Version: 2.0
 * License: GPLv2
 *
 * @package SPB2
 */

namespace SPB2;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPB2_VERSION', 2.0 );
define( 'SPB2_VERSION_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Main plugin class
 */
class Main {

	/**
	 * Current version number
	 *
	 * @var   string
	 * @since 1.5
	 */
	const VERSION = SPB2_VERSION;

	/**
	 * Singleton instance of plugin
	 *
	 * @var    object
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.1.0
	 * @return A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Construct function to get things started.
	 */
	protected function __construct() {
		// Setup some base variables for the plugin.
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		// TODO: remove CMB2.
		require_once( $this->directory_path . 'inc/cmb2/init.php' );

		// Include any required files.
		require_once( $this->directory_path . 'inc/class-options.php' );
		require_once( $this->directory_path . 'inc/class-admin.php' );
		require_once( $this->directory_path . 'inc/class-areas.php' );
		require_once( $this->directory_path . 'inc/class-data.php' );
		require_once( $this->directory_path . 'inc/class-functions.php' );
		require_once( $this->directory_path . 'inc/class-layouts.php' );
		require_once( $this->directory_path . 'inc/template-tags.php' );

		$this->plugin_classes();
		$this->hooks();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.1.0
	 */
	private function plugin_classes() {
		$this->admin = new Admin( $this );
		$this->options = new Options( $this );
		$this->functions = new Functions( $this );
		$this->areas = new Areas( $this );
		$this->layouts = new Layouts( $this );
		$this->data = new Data( $this );
	}

	/**
	 * Add hooks and filters
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );

		// Make sure we have our requirements, and disable the plugin if we do not have them.
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		// Run our options hooks.
		$this->options->hooks();
		// Run our admin hooks.
		$this->admin->hooks();
		// Run layouts hooks.
		$this->layouts->hooks();
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 */
	public function init() {
		// Load Textdomain.
		load_plugin_textdomain( 'simple-page-builder', false, dirname( $this->basename ) . '/languages' );

		do_action( 'spb_init' );
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @return boolean
	 */
	public static function meets_requirements() {
		// Make sure we have CMB so we can use it.
		if ( ! defined( 'CMB2_LOADED' ) ) {
			return false;
		}

		// We have met all requirements.
		return true;
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 */
	public function maybe_disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			// Display our error.
			echo '<div id="message" class="error">';
			// Translators: %s is the URL of the plugins page.
			echo '<p>' . esc_html( sprintf( __( 'SPB2 (Simple Page Builder) requires CMB2 but could not find it. The plugin has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'simple-page-builder' ), admin_url( 'plugins.php' ) ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin.
			deactivate_plugins( $this->basename );
		}
	}

}

/**
 * Public wrapper function
 */
function spb2() {
	return Main::get_instance();
}
spb2();
