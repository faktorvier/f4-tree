<?php

namespace F4\TREE\Tree;

use F4\TREE\Core\Helpers as Core;
use F4\TREE\Tree\Helpers as Tree;

/**
 * Hooks: Tree
 *
 * The WordPress hooks for the Tree module.
 *
 * @since 1.0.0
 * @package F4\TREE\Tree
 */
class Hooks extends \F4\TREE\Core\AbstractHooks {
	/**
	 * Initialize the hooks.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		self::add_action('F4/TREE/Core/set_constants', 'set_default_constants', 99);
		self::add_action('F4/TREE/Core/loaded', 'loaded');
	}

	/**
	 * Fires once the module is loaded.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function loaded() {
		self::add_action('admin_enqueue_scripts', 'admin_enqueue_scripts');
		self::add_action('in_admin_header', 'show_tree_in_backend');

		self::add_action('wp_ajax_f4_tree_refresh', 'ajax_refresh_tree');
		self::add_action('wp_ajax_f4_tree_move_post', 'ajax_move_tree_post');
		self::add_filter('page_attributes_dropdown_pages_args', 'remove_page_attributes_parent_id');

		// self::add_action('updated_post_meta', 'updated_edit_lock', 10, 4);
		// self::add_filter('deleted_post_meta', 'updated_edit_lock', 10, 4);

		self::add_action('save_post', 'insert_post', 10, 3);
		self::add_action('post_updated', 'update_post', 10, 3);
		self::add_action('delete_post', 'delete_post', 10, 2);

		self::add_action('F4/TREE/Core/admin_settings_fields', 'add_admin_settings_fields');
		self::add_filter('F4/TREE/Tree/get_post_tree_node', 'change_node_types_for_special_pages', 20, 3);
		self::add_filter('F4/TREE/Tree/get_post_tree_node', 'fix_deprecated_node', 99, 3);
		self::add_filter('F4/TREE/Tree/get_js_variables', 'set_js_variables', 10, 2);

		self::add_action('wp_ajax_f4_tree_update_width', 'ajax_update_width');
		self::add_filter('admin_head', 'load_user_container_width');
	}

	/**
	 * Sets default constants.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {
		if(!defined('F4_TREE_CSS_URL')) {
			define(
				'F4_TREE_CSS_URL',
				F4_TREE_URL . 'assets/css/'
			);
		}

		if(!defined('F4_TREE_JS_URL')) {
			define(
				'F4_TREE_JS_URL',
				F4_TREE_URL . 'assets/js/'
			);
		}

		if(!defined('F4_TREE_POLLING_FILE_PATH')) {
			define(
				'F4_TREE_POLLING_FILE_PATH',
				WP_CONTENT_DIR . '/f4-tree/tree.json'
			);
		}

		if(!defined('F4_TREE_POLLING_FILE_URL')) {
			define(
				'F4_TREE_POLLING_FILE_URL',
				str_replace(ABSPATH, '/', F4_TREE_POLLING_FILE_PATH)
			);
		}

		if(!defined('F4_TREE_POLLING_TIMEOUT')) {
			define(
				'F4_TREE_POLLING_TIMEOUT',
				3000
			);
		}

		if(!defined('F4_TREE_IGNORE_POST_TYPES')) {
			define(
				'F4_TREE_IGNORE_POST_TYPES',
				[
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
				]
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function admin_enqueue_scripts() {
		if(!Tree::is_tree_enabled()) {
			return;
		}

		wp_enqueue_style(
			'f4-tree-wunderbaum',
			'https://cdn.jsdelivr.net/npm/wunderbaum@0.13/dist/wunderbaum.min.css',
			[],
			F4_TREE_VERSION
		);

		wp_enqueue_style(
			'f4-tree',
			F4_TREE_CSS_URL . 'tree.css',
			[],
			F4_TREE_VERSION
		);

		wp_enqueue_script_module(
			'f4-tree',
			F4_TREE_JS_URL . 'tree.js',
			[],
			F4_TREE_VERSION
		);
	}

	/**
	 * Show the tree view in the backend.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function show_tree_in_backend() {
		if(!Tree::is_tree_enabled()) {
			return;
		}

		include F4_TREE_PATH . 'modules/Tree/views/tree.php';
	}

	/**
	 * AJAX: Refresh the tree.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_refresh_tree() {
		$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
		$post_type = isset($_REQUEST['post_type']) ? sanitize_title($_REQUEST['post_type']) : 'page';
		$lang = isset($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : '';

		Core::maybe_change_current_language(
			$lang
		);

		$treeview = apply_filters(
			'F4/TREE/Tree/ajax_load_tree',
			Tree::get_tree(
				$post_id,
				$post_type
			)
		);

		wp_send_json([
			'tree' => $treeview,
			'samplePermalink' => $post_id ? get_sample_permalink_html($post_id) : 0,
			'menuOrder' => $post_id ? get_post_field('menu_order', $post_id) : -1
		]);
	}

	/**
	 * AJAX: Move a post.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_move_tree_post() {
		global $wpdb;

		check_ajax_referer('f4_tree_ajax_action', 'nonce');

		$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
		$post_type = isset($_REQUEST['post_type']) ? sanitize_title($_REQUEST['post_type']) : 'page';
		$lang = isset($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : '';
		$posts_sorted = json_decode(stripslashes($_REQUEST['posts_sorted'] ?? '[]'), true);

		if(!is_array($posts_sorted)) {
			$posts_sorted = [];
		}

		$posts_sorted = apply_filters('F4/TREE/Tree/move_tree_post_get_sorted_post_ids', $posts_sorted);
		$lang = isset($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : '';

		$post_type_obj = get_post_type_object($post_type);

		if(!$post_type_obj || empty($posts_sorted)) {
			wp_send_json_error();
		}

		if(!current_user_can($post_type_obj->cap->edit_posts)) {
			wp_send_json_error();
		}

		Core::maybe_change_current_language(
			$lang
		);

		$current_post_menu_order = -1;

		foreach($posts_sorted as $post_index => $post_item) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $wpdb->posts SET post_parent = %d, menu_order = %d WHERE ID = %d",
					(int)$post_item['parent_post_id'],
					(int)$post_index,
					(int)$post_item['post_id']
				)
			);

			clean_post_cache($post_item['post_id']);

			if((int)$post_item['post_id'] === $post_id) {
				$current_post_menu_order = $post_index;
			}
		}

		do_action(
			'F4/TREE/Tree/move_tree_post',
			$posts_sorted
		);

		wp_send_json([
			'latestChange' => Tree::update_changed_timestamp(),
			'samplePermalink' => $post_id ? get_sample_permalink_html($post_id) : 0,
			'menuOrder' => $current_post_menu_order
		]);
	}

	/**
	 * Remove parent_id dropdown from edit page.
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
	 * Update last change time if the post edit lock changes.
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
	 * Update last change time if post is created.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function insert_post($post_id, $post, $update) {
		if(Tree::is_tree_post_type($post_id) && !$update) {
			Tree::update_changed_timestamp();
		}
	}

	/**
	 * Update last change time if post is updated.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function update_post($post_id, $post_after, $post_before) {
		if(!Tree::is_tree_post_type($post_id)) {
			return;
		}

		$changed_fields = array_diff_assoc((array)$post_after, (array)$post_before);
		unset($changed_fields['post_modified'], $changed_fields['post_modified_gmt']);

		if(!empty($changed_fields)) {
			Tree::update_changed_timestamp();
		}
	}

	/**
	 * Update last change time if post is deleted.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function delete_post($post_id, $post) {
		if(Tree::is_tree_post_type($post_id)) {
			Tree::update_changed_timestamp();
		}
	}

	/**
	 * Show tree admin settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_admin_settings_fields() {
		include 'views/admin-settings-fields.php';
	}

	/**
	 * Change node type for special pages.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function change_node_types_for_special_pages($node_item, $post_item, $current_post_id) {
		if($node_item['type'] === 'post') {
			if($post_item->post_type === 'page') {
				// Home page
				$page_on_front = (int)Core::maybe_translate_post_id(get_option('page_on_front'));

				if($page_on_front && $post_item->ID === $page_on_front) {
					$node_item['icon'] = 'home';
				}

				// Privacy page
				$privacy_policy = (int)Core::maybe_translate_post_id(get_option('wp_page_for_privacy_policy'));

				if($privacy_policy && $post_item->ID === $privacy_policy) {
					$node_item['icon'] = 'privacy-policy';
				}

				// Woocommerce
				if(function_exists('is_woocommerce')) {
					switch($post_item->ID) {
						case wc_get_page_id('shop'):
							$node_item['icon'] = 'shop';
							break;

						case wc_get_page_id('cart'):
							$node_item['icon'] = 'shop-cart';
							break;

						case wc_get_page_id('checkout'):
							$node_item['icon'] = 'shop-checkout';
							break;

						case wc_get_page_id('myaccount'):
							$node_item['icon'] = 'shop-myaccount';
							break;
					}
				}
			} elseif($post_item->post_type !== 'post') {
				// Dashicons for custom post types
				$menu_icon = get_post_type_object($post_item->post_type)->menu_icon ?? false;
				$menu_icon = !$menu_icon ? 'dashicons-admin-post' : $menu_icon;
				$node_item['icon'] = 'dashicons-before ' . $menu_icon;
			}
		}

		return $node_item;
	}

	/**
	 * Fix deprecated node.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function fix_deprecated_node($node_item, $post_item, $current_post_id) {
		// Redirect pages
		if(isset($node_item['source_ID'])) {
			$node_item['active'] = $post_item->ID == $current_post_id;
			$node_item['icon'] = 'redirect';
			unset($node_item['ID']);
		}

		// Old html icons
		if(isset($node_item['icon']['html'])) {
			$node_item['icon'] = $node_item['icon']['html'];
		}

		return $node_item;
	}

	/**
	 * Set JS variables.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function set_js_variables($vars, $post) {
		$post_type = get_current_screen()->post_type ?? '';

		$vars = wp_parse_args($vars, [
			'postId' => $post->ID ?? -1,
			'postType' => $post_type,
			'lang' => Core::maybe_get_current_language(),

			'isSortable' => Tree::post_type_is_sortable($post_type),
			'lastChange' => (int)Tree::get_changed_timestamp(),
			'pollingFile' => F4_TREE_POLLING_FILE_URL,
			'pollingTimeout' => F4_TREE_POLLING_TIMEOUT,

			'iconMap' => apply_filters('F4/TREE/Tree/get_icon_map', [
				'pool' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M2.75 2.85714C2.26602 2.85714 1.875 3.24018 1.875 3.71429V12.2857C1.875 12.7598 2.26602 13.1429 2.75 13.1429H13.25C13.734 13.1429 14.125 12.7598 14.125 12.2857V5.42857C14.125 4.95446 13.734 4.57143 13.25 4.57143H8.98164C8.5168 4.57143 8.07109 4.39196 7.74297 4.07054L6.76133 3.10893C6.59727 2.94821 6.37578 2.85714 6.14336 2.85714H2.75ZM1 3.71429C1 2.76875 1.78477 2 2.75 2H6.14336C6.6082 2 7.05391 2.17946 7.38203 2.50089L8.36367 3.4625C8.52773 3.62321 8.74922 3.71429 8.98164 3.71429H13.25C14.2152 3.71429 15 4.48304 15 5.42857V12.2857C15 13.2313 14.2152 14 13.25 14H2.75C1.78477 14 1 13.2313 1 12.2857V3.71429Z"/>
				</svg>',

				'post' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M4 1C3.44687 1 3 1.44687 3 2V14C3 14.5531 3.44687 15 4 15H12C12.5531 15 13 14.5531 13 14V2C13 1.44687 12.5531 1 12 1H4ZM2 2C2 0.896875 2.89688 0 4 0H12C13.1031 0 14 0.896875 14 2V14C14 15.1031 13.1031 16 12 16H4C2.89688 16 2 15.1031 2 14V2ZM4.5 4H11.5C11.775 4 12 4.225 12 4.5C12 4.775 11.775 5 11.5 5H4.5C4.225 5 4 4.775 4 4.5C4 4.225 4.225 4 4.5 4ZM4.5 7H11.5C11.775 7 12 7.225 12 7.5C12 7.775 11.775 8 11.5 8H4.5C4.225 8 4 7.775 4 7.5C4 7.225 4.225 7 4.5 7ZM4.5 10H8.5C8.775 10 9 10.225 9 10.5C9 10.775 8.775 11 8.5 11H4.5C4.225 11 4 10.775 4 10.5C4 10.225 4.225 10 4.5 10Z"/>
				</svg>',

				'home' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M8.27658 1.10942C8.12033 0.964502 7.88335 0.964502 7.72449 1.10942L0.641159 7.67192C0.469284 7.83052 0.451055 8.10942 0.604701 8.28989C0.758347 8.47036 1.02137 8.4895 1.19324 8.32817L2.1672 7.42583V12.8125C2.1672 14.0211 3.09949 15 4.25053 15H11.7505C12.9016 15 13.8339 14.0211 13.8339 12.8125V7.42583L14.8078 8.32817C14.9797 8.48677 15.2427 8.47036 15.3964 8.28989C15.55 8.10942 15.5318 7.83325 15.3599 7.67192L8.27658 1.10942ZM3.00053 12.8125V6.652L8.00053 2.01997L13.0005 6.652V12.8125C13.0005 13.5372 12.4406 14.125 11.7505 14.125H10.0839V9.75005C10.0839 9.26606 9.71147 8.87505 9.25053 8.87505H6.75053C6.2896 8.87505 5.9172 9.26606 5.9172 9.75005V14.125H4.25053C3.56043 14.125 3.00053 13.5372 3.00053 12.8125ZM6.75053 14.125V9.75005H9.25053V14.125H6.75053Z"/>
				</svg>',

				'privacy-policy' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M7.65822 2.01982C7.87697 1.92686 8.12306 1.92686 8.34181 2.01982L13.1735 4.07061C13.4824 4.20186 13.6902 4.49443 13.6875 4.82529C13.6738 7.39561 12.6102 11.9292 8.34181 13.9718C8.12579 14.0757 7.8715 14.0757 7.65548 13.9718C3.38712 11.9265 2.32618 7.39287 2.31251 4.82256C2.30978 4.4917 2.51759 4.19912 2.82657 4.06787L7.65822 2.01709V2.01982ZM8.68361 1.21318C8.24611 1.02725 7.75392 1.02725 7.31642 1.21318L2.48478 3.26397C1.88322 3.51826 1.43478 4.11162 1.43751 4.82803C1.45118 7.54053 2.56681 12.5034 7.27814 14.7593C7.73478 14.978 8.26525 14.978 8.72189 14.7593C13.4332 12.5034 14.5488 7.54053 14.5625 4.82803C14.5652 4.11162 14.1168 3.51826 13.5152 3.26397L8.68361 1.21318ZM10.934 6.55889C11.1035 6.38936 11.1035 6.11045 10.934 5.94092C10.7645 5.77139 10.4856 5.77139 10.316 5.94092L7.12501 9.13193L5.684 7.69092C5.51447 7.52139 5.23556 7.52139 5.06603 7.69092C4.8965 7.86045 4.8965 8.13935 5.06603 8.30889L6.81603 10.0589C6.98556 10.2284 7.26447 10.2284 7.434 10.0589L10.934 6.55889Z"/>
				</svg>',

				'password' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M6.25 5.8125C6.25 3.63867 8.01367 1.875 10.1875 1.875C12.3613 1.875 14.125 3.63867 14.125 5.8125C14.125 7.98633 12.3613 9.75 10.1875 9.75C9.88398 9.75 9.58594 9.71445 9.30156 9.65156C9.15391 9.61875 9.00078 9.6625 8.89414 9.76914L8.03828 10.625H6.6875C6.44687 10.625 6.25 10.8219 6.25 11.0625V12.375H4.9375C4.69687 12.375 4.5 12.5719 4.5 12.8125V14.125H1.875V11.8992L6.32656 7.44766C6.44141 7.33281 6.48242 7.16055 6.4332 7.00469C6.31289 6.62734 6.25 6.22812 6.25 5.80977V5.8125ZM10.1875 1C7.52969 1 5.375 3.15469 5.375 5.8125C5.375 6.22539 5.42695 6.62734 5.52539 7.01289L1.12852 11.4098C1.04648 11.4918 1 11.6039 1 11.7187V14.5625C1 14.8031 1.19687 15 1.4375 15H4.9375C5.17812 15 5.375 14.8031 5.375 14.5625V13.25H6.6875C6.92812 13.25 7.125 13.0531 7.125 12.8125V11.5H8.21875C8.33359 11.5 8.4457 11.4535 8.52773 11.3715L9.34805 10.5512C9.62148 10.6004 9.90039 10.625 10.1875 10.625C12.8453 10.625 15 8.47031 15 5.8125C15 3.15469 12.8453 1 10.1875 1ZM11.0625 5.59375C11.2365 5.59375 11.4035 5.52461 11.5265 5.40154C11.6496 5.27847 11.7187 5.11155 11.7187 4.9375C11.7187 4.76345 11.6496 4.59653 11.5265 4.47346C11.4035 4.35039 11.2365 4.28125 11.0625 4.28125C10.8885 4.28125 10.7215 4.35039 10.5985 4.47346C10.4754 4.59653 10.4062 4.76345 10.4062 4.9375C10.4062 5.11155 10.4754 5.27847 10.5985 5.40154C10.7215 5.52461 10.8885 5.59375 11.0625 5.59375Z"/>
				</svg>',

				'redirect' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M12.4984 3C12.7734 3 12.9984 3.225 12.9984 3.5V10.5C12.9984 10.775 12.7734 11 12.4984 11C12.2234 11 11.9984 10.775 11.9984 10.5V4.70625L3.85156 12.8531C3.65781 13.0469 3.33906 13.0469 3.14531 12.8531C2.95156 12.6594 2.95156 12.3406 3.14531 12.1469L11.2922 4H5.49844C5.22344 4 4.99844 3.775 4.99844 3.5C4.99844 3.225 5.22344 3 5.49844 3H12.4984Z"/>
				</svg>',

				'shop' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M14.5175 4.30586C15.0369 5.11523 14.5258 6.12695 13.7064 6.23633C13.6341 6.24453 13.5619 6.25 13.4869 6.25C13.0286 6.25 12.6203 6.05312 12.3341 5.74414C12.1647 5.56094 11.9258 5.45703 11.6758 5.45703C11.4258 5.45703 11.1869 5.56094 11.0175 5.74414C10.7341 6.05039 10.3286 6.24727 9.86747 6.24727C9.40914 6.24727 8.99803 6.05039 8.71747 5.74414C8.54803 5.56094 8.30914 5.45703 8.05914 5.45703C7.80914 5.45703 7.56747 5.56094 7.40081 5.74414C7.11192 6.05312 6.70358 6.25 6.24247 6.25C5.78414 6.25 5.37303 6.05313 5.09247 5.74688C4.92303 5.56367 4.68414 5.45977 4.43414 5.45977C4.18414 5.45977 3.94247 5.56367 3.77581 5.74688C3.4897 6.05313 3.08414 6.25 2.62303 6.25C2.55081 6.25 2.47858 6.24453 2.40358 6.23633C1.5897 6.12695 1.07858 5.11523 1.59525 4.30586L3.15636 1.875H12.9564L14.5175 4.30586ZM13.4841 7.125C13.598 7.125 13.7091 7.1168 13.8203 7.10313C15.3619 6.90078 16.0925 5.1207 15.2675 3.83828L13.6758 1.3582C13.5341 1.13672 13.2814 1 13.0119 1H3.10081C2.83136 1 2.57858 1.13672 2.43692 1.3582L0.842473 3.83828C0.0202511 5.11797 0.748029 6.89805 2.28414 7.10313C2.39525 7.1168 2.50914 7.125 2.62025 7.125C3.1647 7.125 3.66192 6.95 4.0647 6.66016C4.19803 6.56445 4.32025 6.45234 4.43136 6.33477C4.54247 6.45508 4.6647 6.56445 4.79803 6.66016C5.20081 6.95 5.69803 7.125 6.24247 7.125C6.78692 7.125 7.28414 6.95 7.68692 6.66016C7.82025 6.56445 7.94247 6.45234 8.05358 6.33203C8.1647 6.45234 8.28692 6.56172 8.42025 6.65742C8.82303 6.94727 9.32025 7.12227 9.8647 7.12227C10.4147 7.12227 10.9147 6.94453 11.3175 6.64922C11.448 6.55352 11.5675 6.44687 11.6758 6.3293C11.7841 6.44687 11.9064 6.55625 12.0369 6.65195C12.4397 6.94453 12.9397 7.12227 13.4869 7.12227L13.4841 7.125ZM2.27858 8C2.03414 8 1.83414 8.19688 1.83414 8.4375V10.625V11.0625V11.5V13.25C1.83414 14.2152 2.63136 15 3.61192 15H12.5008C13.4814 15 14.2786 14.2152 14.2786 13.25V11.5V11.0625V10.625V8.4375C14.2786 8.19688 14.0786 8 13.8341 8C13.5897 8 13.3897 8.19688 13.3897 8.4375V10.625H2.72303V8.4375C2.72303 8.19688 2.52303 8 2.27858 8ZM2.72303 11.5H13.3897V13.25C13.3897 13.734 12.9925 14.125 12.5008 14.125H3.61192C3.12025 14.125 2.72303 13.734 2.72303 13.25V11.5Z"/>
				</svg>',

				'shop-cart' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M0.444444 1C0.2 1 0 1.19687 0 1.4375C0 1.67813 0.2 1.875 0.444444 1.875H1.49722C1.70833 1.875 1.89167 2.01992 1.93333 2.225L3.56944 10.1C3.73889 10.9148 4.46667 11.5 5.31111 11.5H13.7778C14.0222 11.5 14.2222 11.3031 14.2222 11.0625C14.2222 10.8219 14.0222 10.625 13.7778 10.625H5.31111C4.88889 10.625 4.525 10.3324 4.43889 9.925L4.22222 8.875H12.9611C13.7778 8.875 14.4889 8.32812 14.6861 7.54883L15.85 2.96328C15.9889 2.41094 15.5667 1.875 14.9889 1.875H2.75278C2.56944 1.35547 2.06667 1 1.49722 1H0.444444ZM2.94722 2.75H14.9861L13.8222 7.33828C13.7222 7.72656 13.3667 8 12.9583 8H4.03889L2.94722 2.75ZM4.66667 13.4688C4.66667 13.2947 4.7369 13.1278 4.86193 13.0047C4.98695 12.8816 5.15652 12.8125 5.33333 12.8125C5.51014 12.8125 5.67971 12.8816 5.80474 13.0047C5.92976 13.1278 6 13.2947 6 13.4688C6 13.6428 5.92976 13.8097 5.80474 13.9328C5.67971 14.0559 5.51014 14.125 5.33333 14.125C5.15652 14.125 4.98695 14.0559 4.86193 13.9328C4.7369 13.8097 4.66667 13.6428 4.66667 13.4688ZM6.88889 13.4688C6.88889 13.2677 6.84865 13.0685 6.77048 12.8828C6.69231 12.697 6.57772 12.5282 6.43328 12.386C6.28883 12.2438 6.11735 12.131 5.92862 12.0541C5.73989 11.9771 5.53761 11.9375 5.33333 11.9375C5.12905 11.9375 4.92678 11.9771 4.73805 12.0541C4.54932 12.131 4.37784 12.2438 4.23339 12.386C4.08894 12.5282 3.97436 12.697 3.89619 12.8828C3.81801 13.0685 3.77778 13.2677 3.77778 13.4688C3.77778 13.6698 3.81801 13.869 3.89619 14.0547C3.97436 14.2405 4.08894 14.4093 4.23339 14.5515C4.37784 14.6937 4.54932 14.8065 4.73805 14.8834C4.92678 14.9604 5.12905 15 5.33333 15C5.53761 15 5.73989 14.9604 5.92862 14.8834C6.11735 14.8065 6.28883 14.6937 6.43328 14.5515C6.57772 14.4093 6.69231 14.2405 6.77048 14.0547C6.84865 13.869 6.88889 13.6698 6.88889 13.4688ZM12.4444 12.8125C12.6213 12.8125 12.7908 12.8816 12.9158 13.0047C13.0409 13.1278 13.1111 13.2947 13.1111 13.4688C13.1111 13.6428 13.0409 13.8097 12.9158 13.9328C12.7908 14.0559 12.6213 14.125 12.4444 14.125C12.2676 14.125 12.0981 14.0559 11.973 13.9328C11.848 13.8097 11.7778 13.6428 11.7778 13.4688C11.7778 13.2947 11.848 13.1278 11.973 13.0047C12.0981 12.8816 12.2676 12.8125 12.4444 12.8125ZM12.4444 15C12.857 15 13.2527 14.8387 13.5444 14.5515C13.8361 14.2643 14 13.8749 14 13.4688C14 13.0626 13.8361 12.6732 13.5444 12.386C13.2527 12.0988 12.857 11.9375 12.4444 11.9375C12.0319 11.9375 11.6362 12.0988 11.3445 12.386C11.0528 12.6732 10.8889 13.0626 10.8889 13.4688C10.8889 13.8749 11.0528 14.2643 11.3445 14.5515C11.6362 14.8387 12.0319 15 12.4444 15Z"/>
				</svg>',

				'shop-checkout' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M6.28571 3.625V4.5H9.71429V3.625C9.71429 2.65977 8.94554 1.875 8 1.875C7.05446 1.875 6.28571 2.65977 6.28571 3.625ZM5.42857 5.375H3.28571C3.05 5.375 2.85714 5.57188 2.85714 5.8125V12.375C2.85714 13.3402 3.62589 14.125 4.57143 14.125H11.4286C12.3741 14.125 13.1429 13.3402 13.1429 12.375V5.8125C13.1429 5.57188 12.95 5.375 12.7143 5.375H10.5714V7.5625C10.5714 7.80312 10.3786 8 10.1429 8C9.90714 8 9.71429 7.80312 9.71429 7.5625V5.375H6.28571V7.5625C6.28571 7.80312 6.09286 8 5.85714 8C5.62143 8 5.42857 7.80312 5.42857 7.5625V5.375ZM5.42857 4.5V3.625C5.42857 2.17578 6.58036 1 8 1C9.41964 1 10.5714 2.17578 10.5714 3.625V4.5H12.7143C13.4241 4.5 14 5.08789 14 5.8125V12.375C14 13.8242 12.8482 15 11.4286 15H4.57143C3.15179 15 2 13.8242 2 12.375V5.8125C2 5.08789 2.57589 4.5 3.28571 4.5H5.42857Z"/>
				</svg>',

				'shop-myaccount' => '<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
					<path d="M11 4C11 3.60603 10.9224 3.21593 10.7716 2.85195C10.6209 2.48797 10.3999 2.15726 10.1213 1.87868C9.84274 1.6001 9.51203 1.37913 9.14805 1.22836C8.78407 1.0776 8.39397 1 8 1C7.60603 1 7.21593 1.0776 6.85195 1.22836C6.48797 1.37913 6.15726 1.6001 5.87868 1.87868C5.6001 2.15726 5.37913 2.48797 5.22836 2.85195C5.0776 3.21593 5 3.60603 5 4C5 4.39397 5.0776 4.78407 5.22836 5.14805C5.37913 5.51203 5.6001 5.84274 5.87868 6.12132C6.15726 6.3999 6.48797 6.62087 6.85195 6.77164C7.21593 6.9224 7.60603 7 8 7C8.39397 7 8.78407 6.9224 9.14805 6.77164C9.51203 6.62087 9.84274 6.3999 10.1213 6.12132C10.3999 5.84274 10.6209 5.51203 10.7716 5.14805C10.9224 4.78407 11 4.39397 11 4ZM4 4C4 2.93913 4.42143 1.92172 5.17157 1.17157C5.92172 0.421427 6.93913 0 8 0C9.06087 0 10.0783 0.421427 10.8284 1.17157C11.5786 1.92172 12 2.93913 12 4C12 5.06087 11.5786 6.07828 10.8284 6.82843C10.0783 7.57857 9.06087 8 8 8C6.93913 8 5.92172 7.57857 5.17157 6.82843C4.42143 6.07828 4 5.06087 4 4ZM2 15H14C13.9625 12.5094 11.9313 10.5 9.42813 10.5H6.57188C4.07188 10.5 2.04062 12.5094 2 15ZM1 15.0719C1 11.9937 3.49375 9.5 6.57188 9.5H9.42813C12.5063 9.5 15 11.9937 15 15.0719C15 15.5844 14.5844 16 14.0719 16H1.92813C1.41563 16 1 15.5844 1 15.0719Z"/>
				</svg>',
			]),

			'tree' => apply_filters('F4/TREE/Tree/get_tree_options', [
				'strings' => [
					'loading' => __('Tree is loading...', 'f4-tree'),
					'error' => __('An error occured', 'f4-tree')
				],

				'iconMap' => [
					'error' => "fa-light fa-fw fa-triangle-exclamation",
					'loading' => "fa-light fa-fw fa-chevron-right fa-beat",
					'noData' => "fa-light fa-fw fa-circle-question",
					'expanderExpanded' => "",
					'expanderCollapsed' => "",
					'expanderLazy' => "fa-light fa-fw fa-chevron-right wb-helper-lazy-expander",
					'checkChecked' => "fa-light fa-fw fa-square-check",
					'checkUnchecked' => "fa-light fa-fw fa-square",
					'checkUnknown' => "fa-light fa-fw fa-square-minus",
					'radioChecked' => "fa-light fa-fw fa-circle",
					'radioUnchecked' => "fa-light fa-fw fa-circle",
					'radioUnknown' => "fa-light fa-fw fa-circle-question",
					'folder' => "fa-light fa-fw fa-folder-closed",
					'folderOpen' => "fa-light fa-fw fa-folder-open",
					'folderLazy' => "fa-light fa-fw fa-folder-plus",
					'doc' => "fa-light fa-fw fa-memo", // done
					'colSortable' => "fa-light fa-fw fa-sort",
					'colSortAsc' => "fa-light fa-fw fa-sort-up",
					'colSortDesc' => "fa-light fa-fw fa-sort-down",
					'colFilter' => "fa-light fa-fw fa-filter",
					'colFilterActive' => "fa-light fa-fw fa-filter wb-helper-invalid",
					'colMenu' => "fa-light fa-fw fa-ellipsis-v"
				],
			])
		]);

		return $vars;
	}

	/**
	 * AJAX: Update tree width.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_update_width() {
		$width = sanitize_title($_REQUEST['width'] ?? '');

		if(!empty($width)) {
			update_user_meta(
				get_current_user_id(),
				'f4-tree-container-width',
				$width
			);
		} else {
			delete_user_meta(
				get_current_user_id(),
				'f4-tree-container-width'
			);
		}

		wp_send_json_success();
	}

	/**
	 * Load user defined container width.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function load_user_container_width() {
		if(!Tree::is_tree_enabled()) {
			return;
		}

		$width = get_user_meta(
			get_current_user_id(),
			'f4-tree-container-width',
			true
		);

		if(!empty($width)) {
			echo '<style id="f4-tree-container-width-style">
				#wpwrap {
					--f4-tree-container-width: ' . esc_html($width) . ';
				}
			</style>';
		}
	}
}
