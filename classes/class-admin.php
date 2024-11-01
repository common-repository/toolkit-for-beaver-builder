<?php
/**
 *
 * @package    BBToolkit
 * @subpackage Classes
 * @author     Press Cargo <david@presscargo.io>
 * @copyright  Copyright (c) 2022, Press Cargo
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace BBToolkit;

/**
 * Admin class.
 *
 * @since  1.0.0
 * @access public
 */
final class Admin {

	/**
	 * Parent plugin class.
	 *
	 * @since 1.0.0
	 *
	 */
	protected $plugin = null;

	public $options_page = 'bb_toolkit';

	/**
	 * Initialize the class.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Sets up class actions and filters.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	public function hooks() {
		add_filter( 'plugin_action_links_bb-toolkit/bb-toolkit.php', array( $this, 'add_settings_links' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'cmb2_admin_init', array( $this, 'register_main_options_metabox' ) );
	}

	/**
	 * Add links to the actions links list in the plugin list table.
	 *
	 * @param array $links Links array for the current plugin.
	 */
	public function add_settings_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'options-general.php?page=bb_toolkit_options' ) ),
				esc_html__( 'Settings', 'bb-toolkit' )
			)
		);
		return $links;
	}

	/**
	 * Enqueue a script in the WordPress admin.
	 *
	 * @param int $hook Hook suffix for the current admin page.
	 */
	public function enqueue( $hook ) {
		if ( 'settings_page_bb_toolkit_options' != $hook ) {
			return;
		}

	    wp_enqueue_style( 'bb-toolkit-admin', $this->plugin->path . 'assets/css/admin.css', array(), '1.0.0' );
	}

	/**
	 * Hook in and register a metabox to handle a theme options page and adds a menu item.
	 */
	public function register_main_options_metabox() {

		$args = array(
			'id'           => 'bb_toolkit_options_page',
			'title'        => __( 'Toolkit for Beaver Builder', 'bb-toolkit' ),
			'menu_title'   => __( 'Toolkit for Beaver Builder', 'bb-toolkit' ),
			'object_types' => array( 'options-page' ),
			'option_key'   => 'bb_toolkit_options',
			'parent_slug'  => 'options-general.php',
			'capability'   => 'manage_options'
		);

		/**
		 * Registers main options page menu item and form.
		 */
		$main_options = new_cmb2_box( $args );

		$main_options->add_field( array(
			'name' => __( 'True vw Unit', 'bb-toolkit' ),
			'desc' => __( 'Use pure vw font size units in Beaver Builder instead of the <a href="https://docs.wpbeaverbuilder.com/beaver-builder/advanced-builder-techniques/css-length-height-units/#notes-on-using-the-vw-unit-for-font-size" target="_blank">calc function</a>', 'bb-toolkit' ),
			'id'   => 'true_vw',
			'type' => 'checkbox',
		) );

		$main_options->add_field( array(
			'name' => __( 'Apply Global Button Styles to Gravity Forms', 'bb-toolkit' ),
			'desc' => __( 'Make Gravity Forms use the button styles set in Global Styles', 'bb-toolkit' ),
			'id'   => 'gravity_forms_global_styles',
			'type' => 'checkbox',
		) );

		$main_options->add_field( array(
			'name' => __( 'Default Editor', 'bb-toolkit' ),
			'desc' => __( 'Make Beaver Builder the default editor for new pages.', 'bb-toolkit' ),
			'id'   => 'default_editor',
			'type' => 'checkbox',
		) );

		$main_options->add_field( array(
			'name' => __( 'Sticky Header on Mobile', 'bb-toolkit' ),
			'desc' => __( 'Make the header sticky on mobile devices.', 'bb-toolkit' ),
			'id'   => 'sticky_header',
			'type' => 'checkbox',
		) );

		$main_options->add_field( array(
			'name' => __( 'Order Saved Rows and Modules', 'bb-toolkit' ),
			'desc' => __( 'Order your saved rows and modules by date.', 'bb-toolkit' ),
			'id'   => 'order_saved_rows',
			'type'             => 'select',
			'default'          => 'default',
			'options'          => array(
				'default'      => __( 'Default', 'bb-toolkit' ),
				'date_asc'     => __( 'Date Ascending', 'bb-toolkit' ),
				'date_desc'    => __( 'Date Descending', 'bb-toolkit' ),
			),
		) );

		// https://community.wpbeaverbuilder.com/c/customization/snippets/15

		// https://community.wpbeaverbuilder.com/t/change-which-module-group-is-the-default-selected/8738/3
	}
}