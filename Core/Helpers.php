<?php

namespace F4\TREE\Core;

/**
 * Core Helpers
 *
 * All the WordPress helpers for the Core module
 *
 * @since 1.0.0
 * @package F4\TREE\Core
 */
class Helpers {
	protected static $post_capabilities = array();

	/**
	 * Get plugin infos
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $info_name The info name to show
	 * @static
	 */
	public static function get_plugin_info($info_name) {
		if(!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		$info_value = null;
		$plugin_infos = get_plugin_data(F4_TREE_PLUGIN_FILE_PATH);

		if(isset($plugin_infos[$info_name])) {
			$info_value = $plugin_infos[$info_name];
		}

		return $info_value;
	}

	/**
	 * Get plugin settings
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_settings($name = null) {
		$settings = get_option('f4-tree-settings', array());

		if(!is_array($settings)) {
			$settings = array();
		}

		$settings = wp_parse_args($settings, array(
			'post-types' => array('page', 'post')
		));

		$settings = apply_filters('F4/TREE/Tree/get_settings', $settings, $name);

		if($name) {
			$setting = isset($settings[$name]) ? $settings[$name] : null;
			$setting = apply_filters('F4/TREE/Tree/get_setting', $setting, $settings, $name);

			return $setting;
		}

		return $settings;
	}

	/**
	 * Multilang: Get translated post id
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function maybe_translate_post_id($post_id) {
		if(function_exists('pll_get_post')) {
			$post_id = pll_get_post($post_id);
		} elseif(class_exists('SitePress')) {
			$post_id = apply_filters('wpml_object_id', $post_id, get_post_type($post_id), true);
		}

		return $post_id;
	}

	/**
	 * Multilang: Get current language
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function maybe_get_current_language() {
		$language = '';

		if(function_exists('pll_get_post_language')) {
			$language = pll_current_language();
		} elseif(class_exists('SitePress')) {
			$language = ICL_LANGUAGE_CODE;
		}

		return $language;
	}

	/**
	 * Multilang: Change current language
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function maybe_change_current_language($lang) {
		global $sitepress;

		if(function_exists('pll_get_post_language') && !empty($lang)) {
			PLL()->curlang = PLL()->model->get_language($lang);
		} elseif(class_exists('SitePress')) {
			$sitepress->get_sitepress()->switch_lang($lang, false);
		}
	}

	/**
	 * Multilang: Change current language
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function current_user_can_post_cap($capability, $post_id, $post_type) {
		$cap_name = $post_type . '-' . $capability;

		if(!isset(self::$post_capabilities[$cap_name])) {
			$cap_args = new \stdClass();
			$cap_args->map_meta_cap = true;
			$cap_args->capability_type = $post_type;
			$cap_args->capabilities = array();

			$post_capabilities = get_post_type_capabilities($cap_args);

			$current_user_can = false;

			if($post_capabilities) {
				$current_user_can = current_user_can($post_capabilities->$capability, $post_id);
			}

			self::$post_capabilities[$cap_name] = $current_user_can;
		}

		return self::$post_capabilities[$cap_name];
	}
}

?>
