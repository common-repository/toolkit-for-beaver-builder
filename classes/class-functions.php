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
 * Functions class.
 *
 * @since  1.0.0
 * @access public
 */
final class Functions {
	/**
	 * Parent plugin class.
	 *
	 * @since 1.0.0
	 */
	protected $plugin = null;

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
	private function hooks() {
		add_filter( 'fl_builder_pre_render_css_rules', array( $this, 'bb_css_rules' ), 1, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_stacking_order_css' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_sticky_header_css' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_gravity_forms_css' ) );
		add_action( 'wp_insert_post', array( $this, 'make_bb_default_editor' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'order_saved_rows_modules' ) );
	}

	/**
	 * Use pure vw instead of the default.
	 */
	public function bb_css_rules( $rules ) {

		if ( ! class_exists( 'FLBuilderModel' ) || false == bb_toolkit_get_option( 'bb_toolkit_options', 'true_vw' ) ) return $rules;

		foreach ( $rules as &$rule ) {

			if ( is_array( $rule['props'] ) && isset( $rule['props']['font-size'] ) ) {
				if ( is_string($rule['props']['font-size']) && strpos( $rule['props']['font-size'], 'vw' ) ) {
					$rule['props']['font-size'] = preg_replace( '/calc\((\d+)px \+ ((\d+(\.\d*)?)|(\.\d+))vw\)/', '$2vw', $rule['props']['font-size'] );
				}
			}

		}

		return $rules;
	}

	public function enqueue_stacking_order_css() {
		if ( ! class_exists( 'FLBuilderModel' ) || false == bb_toolkit_get_option( 'bb_toolkit_options', 'medium_stacking_order' ) ) return;

		$global_settings = \FLBuilderModel::get_global_settings();
		$arr = array( 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1 );

		$css = '@media (min-width: ' . ( $global_settings->responsive_breakpoint + 1 ) . 'px) and (max-width: ' . ( $global_settings->medium_breakpoint + 1 ) . 'px) {';

		$css .= '.fl-col-group.fl-col-group-responsive-reversed {
			display: -webkit-box;
			display: -moz-box;
			display: -ms-flexbox;
			display: -moz-flex;
			display: -webkit-flex;
			display: flex;
			flex-flow: row wrap;
			-ms-box-orient: horizontal;
			-webkit-flex-flow: row wrap;
		}
		.fl-col-group.fl-col-group-responsive-reversed .fl-col {
			-webkit-box-flex: 0 0 100%;
		  	 -moz-box-flex: 0 0 100%;
		  	  -webkit-flex: 0 0 100%;
		  		  -ms-flex: 0 0 100%;
		  			  flex: 0 0 100%;
				 min-width: 0;
		}';

		for ( $i = 1; $i <= 12; $i++ ) {
			$css .= '.fl-col-group-responsive-reversed .fl-col:nth-of-type(' . $i . ') {';
			$css .= '-webkit-box-ordinal-group: ' . $arr[($i-1)] . ';';
			$css .= '-moz-box-ordinal-group: ' . $arr[($i-1)] . ';';
			$css .= '-ms-flex-order: ' . $arr[($i-1)] . ';';
			$css .= 'order: ' . $arr[($i-1)] . ';';
			$css .= '}';
		}

		$css .= '}';

		wp_register_style( 'bbt-stacking-order', false );
		wp_enqueue_style( 'bbt-stacking-order' );
		wp_add_inline_style( 'bbt-stacking-order', wp_strip_all_tags( $css ) );
	}

	/* 
	 * https://github.com/lukecav/awesome-beaver-builder#common-snippets
	 */
	public function make_bb_default_editor( $post_ID, $post, $update ) {
		if ( ! class_exists( 'FLBuilderModel' ) || false == bb_toolkit_get_option( 'bb_toolkit_options', 'default_editor' ) ) return;

		// Enable BB Editor by default?
		$enabled = true;

		// On the first insert (not an update), set BB enabled to true.
		if ( !$update ) {
			update_post_meta( $post_ID, '_fl_builder_enabled', $enabled );
		}
	}

	/*
	 * https://community.wpbeaverbuilder.com/t/sticky-header-on-mobile/8457
	 */
	public function enqueue_sticky_header_css() {
		if ( ! class_exists( 'FLBuilderModel' ) || false == bb_toolkit_get_option( 'bb_toolkit_options', 'sticky_header' ) ) return;

		$global_settings = \FLBuilderModel::get_global_settings();

		$css = '@media (max-width: ' . $global_settings->responsive_breakpoint . 'px) {';

		$css .= 'header.fl-builder-content[data-type=header] {
			z-index: 999;
			min-width: 100%;
			width: 100%;
			position: -webkit-sticky;
			position: sticky;
			top: 0;
		}';

		$css .= '}';

		wp_register_style( 'bbt-sticky-header', false );
		wp_enqueue_style( 'bbt-sticky-header' );
		wp_add_inline_style( 'bbt-sticky-header', wp_strip_all_tags( $css ) );
	}

	/*
	 * https://community.wpbeaverbuilder.com/t/order-saved-modules-rows-etc-by-date-in-ui/10300
	 */
	public function order_saved_rows_modules( $query ) {
		$order = bb_toolkit_get_option( 'bb_toolkit_options', 'order_saved_rows' );

		if ( ! class_exists( 'FLBuilderModel' ) || 'default' == $order ) return;

		if ( 'fl-builder-template' === $query->get( 'post_type' ) ) {
			$query->set( 'orderby', array(
				'post_date' => $order == 'date_asc' ? 'ASC' : 'DESC',
				'title'     => 'ASC',
			) );
		}
	}

	public function enqueue_gravity_forms_css() {
		if ( ! class_exists( 'FLBuilderModel' ) || false == bb_toolkit_get_option( 'bb_toolkit_options', 'gravity_forms_global_styles' ) ) return;

		$global_settings = \FLBuilderModel::get_global_settings();
		$globals = \FLBuilderGlobalStyles::get_settings( false );

		$styles = [
			'.gform-theme.gform-theme--framework' => [
				'--gf-ctrl-btn-color-primary' => '#' . $globals->button_color,
				'--gf-ctrl-btn-color-hover-primary' => '#' . $globals->button_hover_color,
				'--gf-ctrl-btn-bg-color-primary' => '#' . $globals->button_background,
				'--gf-ctrl-btn-bg-color-hover-primary' => $globals->button_hover_background ? '#' . $globals->button_hover_background : '#' . $globals->button_background,
				'--gf-ctrl-btn-border-color-primary' => '#' . $globals->button_border['color'],
				'--gf-ctrl-btn-border-color-hover-primary' => '#' . $globals->button_border_hover_color,
				'--gf-ctrl-btn-border-style-primary' => $globals->button_border['style'],
				'--gf-ctrl-btn-font-family' => $globals->button_typography['font_family'],
				'--gf-ctrl-btn-font-weight' => $globals->button_typography['font_weight'],
				'--gf-ctrl-btn-line-height' => $globals->button_typography['line_height']['length'] . $globals->button_typography['line_height']['unit'],
				'--gf-ctrl-btn-letter-spacing' => $globals->button_typography['letter_spacing']['length'] . $globals->button_typography['letter_spacing']['unit'],
				'--gf-ctrl-btn-text-transform' => $globals->button_typography['text_transform'],
			],
			'.gform-theme.gform-theme--framework .gform_button' => [
				'border-top-width' => $globals->button_border['width']['top'] . 'px !important',
				'border-bottom-width' => $globals->button_border['width']['bottom'] . 'px !important',
				'border-left-width' => $globals->button_border['width']['left'] . 'px !important',
				'border-right-width' => $globals->button_border['width']['right'] . 'px !important',
				'border-top-left-radius' => $globals->button_border['radius']['top_left'] . 'px !important',
				'border-top-right-radius' => $globals->button_border['radius']['top_right'] . 'px !important',
				'border-bottom-left-radius' => $globals->button_border['radius']['bottom_left'] . 'px !important',
				'border-bottom-right-radius' => $globals->button_border['radius']['bottom_right'] . 'px !important',
				'font-size' => $globals->button_typography['font_size']['length'] . $globals->button_typography['font_size']['unit'] . ' !important'
			]
		];

		$css = Functions::render_css( $styles );

		$styles_large = [
			'.gform-theme.gform-theme--framework' => [
				'--gf-ctrl-btn-border-color-primary' => '#' . $globals->button_border_large['color'],
				'--gf-ctrl-btn-border-style-primary' => $globals->button_border_large['style'],
				'--gf-ctrl-btn-line-height' => $globals->button_typography_large['line_height']['length'] . $globals->button_typography_large['line_height']['unit'],
				'--gf-ctrl-btn-letter-spacing' => $globals->button_typography_large['letter_spacing']['length'] . $globals->button_typography_large['letter_spacing']['unit'],
				'--gf-ctrl-btn-text-transform' => $globals->button_typography_large['text_transform'],
			],
			'.gform-theme.gform-theme--framework .gform_button' => [
				'border-top-width' => $globals->button_border_large['width']['top'] . 'px !important',
				'border-bottom-width' => $globals->button_border_large['width']['bottom'] . 'px !important',
				'border-left-width' => $globals->button_border_large['width']['left'] . 'px !important',
				'border-right-width' => $globals->button_border_large['width']['right'] . 'px !important',
				'border-top-left-radius' => $globals->button_border_large['radius']['top_left'] . 'px !important',
				'border-top-right-radius' => $globals->button_border_large['radius']['top_right'] . 'px !important',
				'border-bottom-left-radius' => $globals->button_border_large['radius']['bottom_left'] . 'px !important',
				'border-bottom-right-radius' => $globals->button_border_large['radius']['bottom_right'] . 'px !important',
				'font-size' => $globals->button_typography_large['font_size']['length'] . $globals->button_typography_large['font_size']['unit'] . ' !important'
			]
		];

		$css_large = '@media (max-width: ' . $global_settings->large_breakpoint . 'px) {';
		$css_large .= Functions::render_css( $styles_large );
		$css_large .= '}';

		$styles_medium = [
			'.gform-theme.gform-theme--framework' => [
				'--gf-ctrl-btn-border-color-primary' => '#' . $globals->button_border_medium['color'],
				'--gf-ctrl-btn-border-style-primary' => $globals->button_border_medium['style'],
				'--gf-ctrl-btn-line-height' => $globals->button_typography_medium['line_height']['length'] . $globals->button_typography_medium['line_height']['unit'],
				'--gf-ctrl-btn-letter-spacing' => $globals->button_typography_medium['letter_spacing']['length'] . $globals->button_typography_medium['letter_spacing']['unit'],
				'--gf-ctrl-btn-text-transform' => $globals->button_typography_medium['text_transform'],
			],
			'.gform-theme.gform-theme--framework .gform_button' => [
				'border-top-width' => $globals->button_border_medium['width']['top'] . 'px !important',
				'border-bottom-width' => $globals->button_border_medium['width']['bottom'] . 'px !important',
				'border-left-width' => $globals->button_border_medium['width']['left'] . 'px !important',
				'border-right-width' => $globals->button_border_medium['width']['right'] . 'px !important',
				'border-top-left-radius' => $globals->button_border_medium['radius']['top_left'] . 'px !important',
				'border-top-right-radius' => $globals->button_border_medium['radius']['top_right'] . 'px !important',
				'border-bottom-left-radius' => $globals->button_border_medium['radius']['bottom_left'] . 'px !important',
				'border-bottom-right-radius' => $globals->button_border_medium['radius']['bottom_right'] . 'px !important',
				'font-size' => $globals->button_typography_medium['font_size']['length'] . $globals->button_typography_medium['font_size']['unit'] . ' !important'
			]
		];

		$css_medium = '@media (max-width: ' . $global_settings->medium_breakpoint . 'px) {';
		$css_medium .= Functions::render_css( $styles_medium );
		$css_medium .= '}';


		$styles_responsive = [
			'.gform-theme.gform-theme--framework' => [
				'--gf-ctrl-btn-border-color-primary' => '#' . $globals->button_border_responsive['color'],
				'--gf-ctrl-btn-border-style-primary' => $globals->button_border_responsive['style'],
				'--gf-ctrl-btn-line-height' => $globals->button_typography_responsive['line_height']['length'] . $globals->button_typography_responsive['line_height']['unit'],
				'--gf-ctrl-btn-letter-spacing' => $globals->button_typography_responsive['letter_spacing']['length'] . $globals->button_typography_responsive['letter_spacing']['unit'],
				'--gf-ctrl-btn-text-transform' => $globals->button_typography_responsive['text_transform'],
			],
			'.gform-theme.gform-theme--framework .gform_button' => [
				'border-top-width' => $globals->button_border_responsive['width']['top'] . 'px !important',
				'border-bottom-width' => $globals->button_border_responsive['width']['bottom'] . 'px !important',
				'border-left-width' => $globals->button_border_responsive['width']['left'] . 'px !important',
				'border-right-width' => $globals->button_border_responsive['width']['right'] . 'px !important',
				'border-top-left-radius' => $globals->button_border_responsive['radius']['top_left'] . 'px !important',
				'border-top-right-radius' => $globals->button_border_responsive['radius']['top_right'] . 'px !important',
				'border-bottom-left-radius' => $globals->button_border_responsive['radius']['bottom_left'] . 'px !important',
				'border-bottom-right-radius' => $globals->button_border_responsive['radius']['bottom_right'] . 'px !important',
				'font-size' => $globals->button_typography_responsive['font_size']['length'] . $globals->button_typography_responsive['font_size']['unit'] . ' !important'
			]
		];

		$css_responsive = '@media (max-width: ' . $global_settings->responsive_breakpoint . 'px) {';
		$css_responsive .= Functions::render_css( $styles_responsive );
		$css_responsive .= '}';

		$css = $css . $css_large . $css_medium . $css_responsive;

		wp_register_style( 'bbt-gravity-forms-css', false );
		wp_enqueue_style( 'bbt-gravity-forms-css' );
		wp_add_inline_style( 'bbt-gravity-forms-css', wp_strip_all_tags( $css ) );

	}

	public function render_css( $styles ) {
		$css = '';
		foreach( $styles as $selector => $style ) {
			$css .= $selector . ' {';

			foreach( $style as $property => $value ) {
				if ( !empty( $value ) && $value != '#' && $value != 'px !important' && $value != 'px' ) {
					$css .= $property . ': ' . $value . ';';
				}
			}
			
			$css .= '}';
		}

		return $css;
	}
}