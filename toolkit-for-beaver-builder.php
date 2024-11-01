<?php
/**
 * Plugin Name: Toolkit for Beaver Builder
 * Plugin URI:  https://presscargo.io/plugins/toolkit-for-beaver-builder
 * Description: A collection of features that make developing for Beaver Builder easier
 * Version:     1.1
 * Author:      Press Cargo
 * Author URI:  https://presscargo.io
 * License: GPLv2 or later
 * Text Domain: bb-toolkit
 *
 * @package   BBToolkit
 * @version   1.1
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace BBToolkit;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloads files with classes when needed.
 *
 * @since  0.0.0
 * @param  string $class_name Name of the class being requested.
 */
if ( ! function_exists( 'bbt_autoload_classes' ) ) {
	function bbt_autoload_classes( $class_name ) {
		// If our class doesn't have our namespace, don't load it.
		if ( 0 !== strpos( $class_name, 'BBToolkit' ) ) {
			return;
		}

		// Set up our filename.
		$class_name = str_replace( 'BBToolkit\\', '', $class_name );
		$filename = strtolower( str_replace( '_', '-', $class_name ) );

		// Include our file.
		Plugin::include_file( 'classes/class-' . $filename );
	}
}

spl_autoload_register( __NAMESPACE__ . '\bbt_autoload_classes' );

final class Plugin {
	/**
	 * Plugin Name.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $name = 'Beaver Builder Toolkit';

		/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	const VERSION = '1.0.0';

	/**
	 * Minimum required PHP version.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	private $php_version = '7.0.0';

	/**
	 * Plugin directory path.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $path = '';

	/**
	 * Plugin directory URI.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $url = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  0.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    Plugin
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );

		if ( file_exists( $this->path . 'vendor/CMB2/init.php' ) ) {
			require_once $this->path . 'vendor/CMB2/init.php';
		}

		require_once( $this->path . 'inc/functions.php' );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.0.0
	 */
	public function plugin_classes() {
		$classes = array(
			'admin' => 'BBToolkit\Admin',
			'functions' => 'BBToolkit\Functions'
		);

		foreach ( $classes as $variable_name => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->variable_name = new $class_name( $this );
			}
		}
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Include a file from the includes directory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $filename Name of the file to be included.
	 * @return boolean          Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );

		if ( file_exists( $file ) ) {
			return require_once $file;
		}
		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $path (optional) appended path.
	 * @return string       URL and path.
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}

	/**
	 * Add hooks and filters.
	 *
	 * @since  0.0.0
	 */
	public function hooks() {
		$this->plugins_loaded();

		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Activate the plugin.
	 *
	 * @since  0.0.0
	 */
	public function _activate() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  0.0.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

	public function plugins_loaded() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		$this->plugin_classes();

		// Load translated strings for plugin.
		load_plugin_textdomain( 'bb-toolkit', false, dirname( $this->basename ) . '/languages/' );

		do_action( 'bbt_loaded' );
	}

	/**
	 * Init hooks
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function init() {

	}


	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements() {

		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  0.0.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since  0.0.0
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {
		if ( version_compare( phpversion(), $this->php_version, '<' ) ) {
			$this->activation_errors[] = $this->name . ' requires <strong>PHP version ' . $this->php_version . ' or higher</strong>.';
			return false;
		}

		if ( ! empty( $this->activation_errors ) ) {
			return false;
		}

		// Do checks for required classes / functions or similar.
		// Add detailed messages to $this->activation_errors array.
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since  0.0.0
	 */
	public function requirements_not_met_notice() {

		// Compile default message.
		$default_message = sprintf( __( $this->name . ' detected that your system does not meet the minimum requirements. We\'ve <a href="%s">deactivated</a> the plugin to make sure nothing breaks.', 'smartbanked' ), admin_url( 'plugins.php' ) );

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
			$details = '<ul><li>' . implode( '</li><li>', $this->activation_errors ) . '</li></ul>';
		}

		// Output errors.
		?>
		<div class="notice notice-error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	/**
	 * Loads files needed by the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function includes() {

		// Check if we meet the minimum PHP version.
		if ( version_compare( phpversion(), $this->php_version, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
			return;
		}

		if ( file_exists( $this->dir . 'vendor/CMB2/init.php' ) ) {
			require_once $this->dir . 'vendor/CMB2/init.php';
		}

		require_once( $this->dir . 'classes/class-functions.php' );
		require_once( $this->dir . 'inc/functions.php' );
		
		if ( is_admin() ) {
			require_once( $this->dir . 'classes/class-admin.php' );
		}
	}
}


function plugin() {
	return Plugin::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( plugin(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( plugin(), '_activate' ) );
register_deactivation_hook( __FILE__, array( plugin(), '_deactivate' ) );