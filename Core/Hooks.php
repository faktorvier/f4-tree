<?php

namespace F4\TREE\Core;

/**
 * Core Hooks
 *
 * All the WordPress hooks for the Core module
 *
 * @since 1.0.0
 * @package F4\TREE\Core
 */
class Hooks {
	/**
	 * Initialize the hooks
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		add_action('init', __NAMESPACE__ . '\\Hooks::core_loaded');
		add_action('init', __NAMESPACE__ . '\\Hooks::load_textdomain');
		add_action('F4/TREE/Core/set_constants', __NAMESPACE__ . '\\Hooks::set_default_constants', 98);
	}

	/**
	 * Fires once the core module is loaded
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function core_loaded() {
		do_action('F4/TREE/Core/set_constants');
		do_action('F4/TREE/Core/loaded');

		add_action('admin_head', __NAMESPACE__ . '\\Hooks::add_admin_styles');
		add_action('admin_init', __NAMESPACE__ . '\\Hooks::register_settings');
		add_action('admin_menu', __NAMESPACE__ . '\\Hooks::register_options_page', 99);
		add_filter('plugin_action_links_' . F4_TREE_BASENAME, __NAMESPACE__ . '\\Hooks::add_settings_link_to_plugin_list');
	}

	/**
	 * Sets the module default constants
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {}

	/**
	 * Load plugin textdomain
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_textdomain() {
		load_plugin_textdomain('f4-tree', false, plugin_basename(F4_TREE_PATH . 'Core/Lang') . '/');
	}

	/**
	 * Add admin styles
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_admin_styles() {
		echo '<style>
				.' . F4_EP_SLUG . '-submenu-item {
				display: flex;
				align-items: center;
				white-space: nowrap;
			}

			.' . F4_EP_SLUG . '-submenu-item .f4-icon {
				flex: 0 0 auto;
				width: 1em;
				height: 1em;
				margin-right: 0.4em;
				fill: currentColor;
			}
		</style>';
	}

	/**
	 * Register options page
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function register_options_page() {
		$submenu_label = '
		<span class="' . F4_EP_SLUG . '-submenu-item">
			<svg class="f4-icon" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"width="75px" height="100px" viewBox="0 0 75 100">
				<path d="M74.295,21.04c0,1.38-1.122,2.504-2.502,2.504H54.259c-1.384,0-2.504-1.124-2.504-2.504V3.504
					c0-1.379,1.12-2.504,2.504-2.504h17.534c1.38,0,2.502,1.125,2.502,2.504V21.04z"/>
				<path d="M74.295,46.562c0,1.384-1.122,2.506-2.502,2.506H54.259c-1.384,0-2.504-1.122-2.504-2.506V29.029
					c0-1.382,1.12-2.504,2.504-2.504h17.534c1.38,0,2.502,1.122,2.502,2.504V46.562z"/>
				<path d="M74.295,72.086c0,1.384-1.122,2.506-2.502,2.506H54.259c-1.384,0-2.504-1.122-2.504-2.506V54.557
					c0-1.387,1.12-2.506,2.504-2.506h17.534c1.38,0,2.502,1.119,2.502,2.506V72.086z"/>
				<path d="M48.769,46.562c0,1.384-1.12,2.506-2.502,2.506H28.733c-1.384,0-2.504-1.122-2.504-2.506V29.029
					c0-1.382,1.12-2.504,2.504-2.504h17.533c1.382,0,2.502,1.122,2.502,2.504V46.562z"/>
				<path d="M48.769,72.086c0,1.384-1.12,2.506-2.502,2.506H28.733c-1.384,0-2.504-1.122-2.504-2.506V54.557
					c0-1.387,1.12-2.506,2.504-2.506h17.533c1.382,0,2.502,1.119,2.502,2.506V72.086z"/>
				<path d="M23.247,72.086c0,1.384-1.124,2.506-2.503,2.506H3.21c-1.384,0-2.505-1.122-2.505-2.506V54.557
					c0-1.387,1.122-2.506,2.505-2.506h17.533c1.379,0,2.503,1.119,2.503,2.506V72.086z"/>
				<path d="M53.833,98.412c-1.086,1.085-2.078,0.581-2.078-0.799V80.077c0-1.38,1.12-2.501,2.504-2.501h17.534
					c1.38,0,1.768,1.106,0.798,2.075L53.833,98.412z"/>
				<path d="M46.691,2.708c1.084-1.087,2.077-0.583,2.077,0.796v17.534c0,1.382-1.12,2.506-2.502,2.506H28.733
					c-1.384,0-1.771-1.107-0.799-2.08L46.691,2.708z"/>
				<path d="M21.167,28.229c1.086-1.085,2.08-0.582,2.08,0.8v17.532c0,1.384-1.124,2.506-2.503,2.506H3.21
					c-1.384,0-1.773-1.107-0.801-2.078L21.167,28.229z"/>
			</svg>

			' .  __('Post Tree', 'f4-error-pages') . '
		</span>
		';

		add_options_page(
			__('F4 Post Tree settings', 'f4-tree'),
			$submenu_label,
			'manage_options',
			'f4-tree-settings',
			function() {
				include F4_TREE_PATH . 'Core/views/admin-settings.php';
			}
		);
	}

	/**
	 * Register settings for options page
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function register_settings() {
		register_setting(
			'f4-tree-settings',
			'f4-tree-settings',
			array(
				'default' => array(
					'post-types' => array(
						'page' => '1',
						'post' => '1'
					),
				)
			)
		);
	}

	/**
	 * Add settings link to plugin list
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_settings_link_to_plugin_list($links) {
		array_push(
			$links,
			'<a href="' . admin_url('options-general.php?page=f4-tree-settings') . '">' . __('Settings') . '</a>'
		);

		return $links;
	}
}

?>
