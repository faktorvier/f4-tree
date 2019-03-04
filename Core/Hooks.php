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
	 * Register options page
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function register_options_page() {
		add_options_page(
			__('F4 Post Tree settings', 'f4-tree'),
			__('F4 Post Tree', 'f4-tree'),
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
						'page' => 1
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
