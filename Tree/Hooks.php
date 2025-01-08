<?php

namespace F4\TREE\Tree;

use F4\TREE\Core\Helpers as Core;
use F4\TREE\Tree\Helpers as Tree;

/**
 * Tree
 *
 * The custom Tree funcionality for this plugin
 *
 * @since 1.0.0
 * @package F4\TREE\Tree
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
		add_action('F4/TREE/Core/set_constants', __NAMESPACE__ . '\\Hooks::set_default_constants', 99);
		add_action('F4/TREE/Core/loaded', __NAMESPACE__ . '\\Hooks::loaded');
	}

	/**
	 * Sets the module default constants
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {
		if(!defined('F4_TREE_CSS_URL')) {
			define('F4_TREE_CSS_URL', F4_TREE_URL . 'Tree/assets/css/');
		}

		if(!defined('F4_TREE_JS_URL')) {
			define('F4_TREE_JS_URL', F4_TREE_URL . 'Tree/assets/js/');
		}

		if(!defined('F4_TREE_IGNORE_POST_TYPES')) {
			define('F4_TREE_IGNORE_POST_TYPES', [
				'attachment',
				'wp_block',
				'wp_navigation',
				'acf-taxonomy',
				'acf-post-type',
				'acf-ui-options-page',
				'acf-field-group',
				'skuld_content',
				'skuld_fieldgroup',
				'skuld_layout',
				'skuld_snippet',
				'shop_order',
				'shop_coupon',
				'wc_reminder_email',
				'br_product_filter',
				'br_filters_group'
			]);
		}
	}

	/**
	 * Fires once the module is loaded
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function loaded() {
		add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\Hooks::admin_enqueue_scripts');
		add_action('in_admin_header', __NAMESPACE__ . '\\Hooks::show_tree_in_backend');

		add_action('wp_ajax_f4_tree_load_tree', __NAMESPACE__ . '\\Hooks::ajax_load_tree');
		add_action('wp_ajax_f4_tree_move_post', __NAMESPACE__ . '\\Hooks::ajax_move_tree_post');
		add_action('wp_ajax_f4_tree_refresh', __NAMESPACE__ . '\\Hooks::ajax_refresh_tree');
		add_filter('page_attributes_dropdown_pages_args', __NAMESPACE__ . '\\Hooks::remove_page_attributes_parent_id');

		add_action('updated_post_meta', __NAMESPACE__ . '\\Hooks::updated_edit_lock', 10, 4);
		add_filter('deleted_post_meta', __NAMESPACE__ . '\\Hooks::updated_edit_lock', 10, 4);

		add_action('save_post', __NAMESPACE__ . '\\Hooks::update_post');
		add_action('delete_post', __NAMESPACE__ . '\\Hooks::update_post');

		add_action('F4/TREE/Core/admin_settings_fields', __NAMESPACE__ . '\\Hooks::add_admin_settings_fields');
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function admin_enqueue_scripts() {
		if(!Tree::is_tree_enabled()) {
			return;
		}

		// Styles
		wp_enqueue_style(
			'f4-tree',
			F4_TREE_CSS_URL . 'tree.css',
			[],
			F4_TREE_VERSION
		);

		wp_enqueue_style(
			'fancytree-custom-theme',
			F4_TREE_CSS_URL . 'fancytree.css',
			[],
			F4_TREE_VERSION
		);

		// Scripts
		wp_enqueue_script(
			'jquery-fancytree',
			F4_TREE_JS_URL . 'jquery.fancytree.min.js',
			array('jquery', 'jquery-effects-core', 'jquery-effects-blind'),
			F4_TREE_VERSION,
			true
		);

		wp_enqueue_script(
			'jquery-fancytree-dnd',
			F4_TREE_JS_URL . 'jquery.fancytree.dnd.min.js',
			array('jquery-fancytree', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-position'),
			F4_TREE_VERSION,
			true
		);

		wp_enqueue_script(
			'f4-tree',
			F4_TREE_JS_URL . 'tree.js',
			array('jquery-fancytree', 'jquery-fancytree-dnd'),
			F4_TREE_VERSION,
			true
		);

		wp_localize_script(
			'f4-tree',
			'F4_tree_config',
			array(
				'labels' => array(
					'loading' => __('Tree is loading...', 'f4-tree'),
					'error' => __('An error occured', 'f4-tree'),
				)
			)
		);
	}

	/**
	 * Show the tree in backend
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function show_tree_in_backend() {
		if(!Tree::is_tree_enabled()) {
			return;
		}

		include F4_TREE_PATH . 'Tree/views/tree.php';
	}

	/**
	 * Ajax load tree
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_load_tree() {
		$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
		$post_type = isset($_REQUEST['post_type']) ? sanitize_title($_REQUEST['post_type']) : 'page';
		$lang = isset($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : '';

		Core::maybe_change_current_language($lang);

		$treeview = Tree::get_tree($post_id, $post_type);
		$treeview = apply_filters('F4/TREE/Tree/ajax_load_tree', $treeview);
		echo wp_json_encode($treeview);

		die();
	}

	/**
	 * Ajax move post
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_move_tree_post() {
		global $wpdb;

		$posts_sorted = json_decode(stripslashes($_REQUEST['posts_sorted'] ?? '[]'), true);

		if(!is_array($posts_sorted)) {
			$posts_sorted = [];
		}

		$posts_sorted = apply_filters('F4/TREE/Tree/move_tree_post_get_sorted_post_ids', $posts_sorted);
		$lang = isset($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : '';

		Core::maybe_change_current_language($lang);

		foreach($posts_sorted as $post_index => $post_item) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $wpdb->posts SET post_parent = %d, menu_order = %d WHERE ID = %d",
					$post_item['parent_post_id'],
					$post_index,
					$post_item['post_id']
				)
			);

			clean_post_cache($post_item['post_id']);
		}

		do_action('F4/TREE/Tree/move_tree_post', $posts_sorted);

		echo Tree::update_changed_timestamp();

		die();
	}

	/**
	 * Ajax refresh tree
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_refresh_tree() {
		session_write_close();
		ignore_user_abort(true);

		$last_request_timestamp = isset($_REQUEST['timestamp']) ? intval($_REQUEST['timestamp']) : null;
		$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : -1;
		$post_type = isset($_REQUEST['post_type']) ? sanitize_title($_REQUEST['post_type']) : 'page';
		$lang = isset($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : '';

		Core::maybe_change_current_language($lang);

		set_time_limit(299); // 5 min - 1 sec

		while(true) {
			echo ' ';
			ob_flush();
			flush();

			if(connection_status() != CONNECTION_NORMAL) {
				break;
				die();
			}

			clearstatcache();
			Tree::clear_changed_timestamp_cache();
			$last_change_timestamp = Tree::get_changed_timestamp();

			if($last_request_timestamp === null || $last_change_timestamp > $last_request_timestamp) {
				wp_cache_init();

				$response = array(
					//'sample-permalink' => get_sample_permalink_html($post_id), // @todo: temporarily disabled because of manual change overwrite
					'sample-permalink' => '',
					'data' => Tree::get_tree($post_id, $post_type),
					'timestamp' => $last_change_timestamp
				);

				$responseJSON = wp_json_encode($response);

				echo $responseJSON;

				break;
			} else {
				sleep(1);
			}
		}

		die();
	}

	/**
	 * Remove parent id dropdown
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function remove_page_attributes_parent_id($args) {
		if(Tree::is_tree_enabled()) {
			$args['child_of'] = '-10';
		}

		return $args;
	}

	/**
	 * Update last change time if the post edit lock changes
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function updated_edit_lock($meta_id, $post_id, $meta_key, $meta_value) {
		if($meta_key === '_edit_lock' && Tree::is_tree_post_type($post_id)) {
			Tree::update_changed_timestamp();
		}
	}

	/**
	 * Update last change time if post is saved or deleted
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function update_post($post_id) {
		if(Tree::is_tree_post_type($post_id)) {
			Tree::update_changed_timestamp();
		}
	}

	/**
	 * Show tree admin settings
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_admin_settings_fields() {
		include 'views/admin-settings-fields.php';
	}
}

?>
