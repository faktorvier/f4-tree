<?php

namespace F4\TREE\Tree;

use F4\TREE\Core\Helpers as Core;

/**
 * Helpers: Tree
 *
 * The helpers for the Tree module.
 *
 * @since 1.0.0
 * @package F4\TREE\Tree
 */
class Helpers {
	protected static $tree_enabled = null;
	protected static $post_type_orderby = [];

	/**
	 * Check if tree is enabled on the current screen.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return bool True if the tree is enabled in the current screen.
	 */
	public static function is_tree_enabled() {
		global $current_screen;

		if(self::$tree_enabled === null) {
			$enabled_post_types = Core::get_settings('post-types');
			$enabled_screen_ids = [];

			foreach($enabled_post_types as $post_type_name => $post_type_enabled) {
				if(post_type_exists($post_type_name) && $post_type_enabled == '1') {
					$enabled_screen_ids[] = $post_type_name;
					$enabled_screen_ids[] = "edit-{$post_type_name}";
				}
			}

			self::$tree_enabled = apply_filters(
				'F4/TREE/Tree/is_tree_enabled',
				in_array($current_screen->id, $enabled_screen_ids),
				$current_screen
			);
		}

		return self::$tree_enabled;
	}

	/**
	 * Check if tree is enabled for this post type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return bool True if the tree is enabled for this post type.
	 */
	public static function is_tree_post_type($post_type) {
		if(is_object($post_type)) {
			$post_type = $post_type->post_type;
		} elseif(is_numeric($post_type)) {
			$post_id = $post_type;
			$post_type = get_post_type($post_id);

			if($post_type === 'revision') {
				$post_type = get_post_type(wp_get_post_parent_id($post_id));
			}
		}

		$enabled_post_types = Core::get_settings('post-types');
		$enabled_post_types['nav_menu_item'] = '1';

		$is_tree_post_type = apply_filters(
			'F4/TREE/Tree/is_tree_post_type',
			isset($enabled_post_types[$post_type]),
			$post_type
		);

		return $is_tree_post_type;
	}

	/**
	 * Update timestamp file.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param int $time The new timestamp.
	 */
	public static function update_timestamp_file($time) {
		if(!is_dir(dirname(F4_TREE_POLLING_FILE_PATH))) {
			mkdir(dirname(F4_TREE_POLLING_FILE_PATH));
		}

		file_put_contents(
			F4_TREE_POLLING_FILE_PATH,
			$time
		);
	}

	/**
	 * Update changed timestamp.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param int|null $time The new timestamp. NULL for the current timestamp.
	 * @return int The updated timestamp.
	 */
	public static function update_changed_timestamp($time = null) {
		if(!$time) {
			$time = time();
		}

		$time = apply_filters(
			'F4/TREE/Tree/update_changed_timestamp',
			$time
		);

		self::update_timestamp_file(
			$time
		);

		return $time;
	}

	/**
	 * Get changed timestamp.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return int The last changed timestamp.
	 */
	public static function get_changed_timestamp() {
		$changed_timestamp = 0;

		if(file_exists(F4_TREE_POLLING_FILE_PATH)) {
			$changed_timestamp = file_get_contents(
				F4_TREE_POLLING_FILE_PATH
			);
		} else {
			self::update_timestamp_file(
				$changed_timestamp
			);
		}

		$changed_timestamp = apply_filters(
			'F4/TREE/Tree/get_changed_timestamp',
			$changed_timestamp
		);

		return $changed_timestamp;
	}

	/**
	 * Get orderby and order by post type.
	 *
	 * @since 2.0.2
	 * @access public
	 * @static
	 * @param string $post_type The post type.
	 * @return array An array with both orderby and order.
	 */
	public static function get_post_type_order($post_type) {
		if(!isset(self::$post_type_orderby[$post_type])) {
			$is_hierarchical = is_post_type_hierarchical(
				$post_type
			);

			$orderby_query = new \WP_Query([
				'post_type' => $post_type,
				'post_parent' => 0,
				'posts_per_page' => 1,
				'post_status' => [
					'draft',
					'publish',
					'future',
					'pending',
					'private'
				]
			]);

			//preg_match('/ORDER\s+BY\s+wp_posts\.menu_order/i', $orderby_query->request));

			$orderby = $orderby_query->get('orderby');
			$order = $orderby_query->get('order');

			if(empty($orderby)) {
				if($is_hierarchical) {
					$orderby = 'menu_order title';
					$order = 'ASC';
				} else {
					$orderby = 'date';
					$order = 'DESC';
				}
			}

			self::$post_type_orderby[$post_type] = [
				'orderby' => apply_filters(
					'F4/TREE/Menu/get_tree_orderby',
					$orderby,
					$post_type
				),
				'order' =>  apply_filters(
					'F4/TREE/Menu/get_tree_order',
					$order,
					$post_type
				)
			];
		}

		return self::$post_type_orderby[$post_type];
	}

	/**
	 * Check if post type is sortable.
	 *
	 * @since 2.0.2
	 * @access public
	 * @static
	 * @param string $post_type The post type.
	 * @return boolean TRUE if the post type is sortable, otherwise FALSE.
	 */
	public static function post_type_is_sortable($post_type) {
		$post_type_order = self::get_post_type_order($post_type);

		$orderby = $post_type_order['orderby'];
		$orderby = is_array($orderby) ? array_keys($orderby) : explode(' ', $orderby);

		return apply_filters(
			'F4/TREE/Tree/post_type_is_sortable',
			($orderby[0] ?? '') === 'menu_order',
			$post_type
		);
	}

	/**
	 * Get the tree.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param int $post_id The current active post ID.
	 * @param string $post_type The current active post type.
	 * @return array The complete tree array.
	 */
	public static function get_tree($post_id, $post_type = 'page') {
		// Get post type object
		$post_type_object = get_post_type_object(
			$post_type
		);

		// Get child nodes
		$post_nodes = apply_filters(
			'F4/TREE/Tree/get_tree_post_nodes',
			self::get_children($post_type, 0, $post_id),
			$post_id,
			$post_type,
			$post_type_object
		);

		// Build tree
		$tree_nodes = apply_filters(
			'F4/TREE/Tree/get_tree_nodes_before',
			[],
			$post_id,
			$post_type,
			$post_type_object,
			$post_nodes
		);

		$tree_nodes[] =  apply_filters(
			'F4/TREE/Tree/get_tree_node',
			[
				'key' => "pool-{$post_type}",
				'type' => 'pool',
				'title' => $post_type_object->label,
				'tooltip' => $post_type_object->description,
				'expanded' => true,
				'unselectable' => true,
				'children' => array_values($post_nodes),

				'icon' => apply_filters(
					'F4/TREE/Menu/get_node_icon',
					'pool',
					'pool',
					null,
					null,
					null
				),

				'draggable' => false,
				'allow_children' => true
			]
		);

		$tree_nodes = apply_filters(
			'F4/TREE/Tree/get_tree_nodes_after',
			$tree_nodes,
			$post_id,
			$post_type,
			$post_type_object,
			$post_nodes
		);

		return array_values($tree_nodes);
	}

	/**
	 * Get tree children.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param string $post_type The post type.
	 * @param int $post_parent The post parent ID.
	 * @param int $post_id The post ID.
	 * @param boolean $recursive TRUE if all children should be loaded recursively. FALSE if not.
	 * @param array $args Additional get_posts arguments.
	 * @return array The tree children as array.
	 */
	public static function get_children($post_type = 'page', $post_parent = 0, $post_id = null, $recursive = true, $args = []) {
		if(!$post_id) {
			$post_id = get_the_ID();
		}

		$post_type_order = self::get_post_type_order(
			$post_type
		);

		$args = wp_parse_args($args, [
			'suppress_filters' => false,
			'nopaging' => true,
			'orderby' => $post_type_order['orderby'],
			'order' => $post_type_order['order'],
			'post_status' => [
				'draft',
				'publish',
				'future',
				'pending',
				'private'
			]
		]);

		$args['post_parent'] = $post_parent;
		$args['post_type'] = $post_type;

		$posts = get_posts($args);

		$tree_nodes = [];

		foreach($posts as $post_item) {
			if($post_item->post_status === 'private' && !current_user_can('read_private_posts', $post_item->ID)) {
				continue;
			}

			$tree_nodes[] = self::get_post_tree_node(
				$post_item,
				$post_id
			);
		}

		return $tree_nodes;
	}

	/**
	 * Get tree node for a post.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param object $post_item The post object.
	 * @param int $current_post_id The current post ID.
	 * @return array The node data.
	 */
	public static function get_post_tree_node($post_item, $current_post_id) {
		$url = get_admin_url(null, 'post.php?post=' . $post_item->ID . '&action=edit');

		$node_item = [
			'key' => "post-{$post_item->ID}",
			'type' => 'post',
			'title' => empty($post_item->post_title) ? __('(no title)') : $post_item->post_title,
			'tooltip' => "ID: {$post_item->ID}",
			'expanded' => false,
			'children' => [],
			'classes' => "wb-row--{$post_item->post_status}",

			'icon' => apply_filters(
				'F4/TREE/Menu/get_node_icon',
				'post',
				'post',
				$post_item->post_type,
				$post_item->ID,
				$post_item->post_status
			),

			'url' => $url,
			'active' => $post_item->ID == $current_post_id,
			'allow_children' => false,

			'post_id' => $post_item->ID,
			'post_type' => $post_item->post_type,
			'post_status' => $post_item->post_status
		];

		// Check if post is locked
		if($lock_user_id = wp_check_post_lock($post_item->ID)) {
			$lock_holder = get_userdata($lock_user_id);
			$node_item['classes'] .= ' wb-row--locked';
			$node_item['tooltip'] .= PHP_EOL . esc_html(sprintf(__('%s is currently editing'), $lock_holder->display_name));
		}

		// Check if post is password protected
		if(!empty($post_item->post_password)) {
			$node_item['icon'] = 'password';
		}

		// Disable drag and drop if no permission
		if(!Core::current_user_can_post_cap('edit_post', $post_item->ID, $post_item->post_type)) {
			$node_item['draggable'] = false;
		}

		// Add children if hierarchical
		if(is_post_type_hierarchical($post_item->post_type)) {
			$node_item['allow_children'] = true;

			$node_item['children'] = self::get_children(
				$post_item->post_type,
				$post_item->ID,
				$current_post_id
			);

			if(!empty($node_item['children'])) {
				$node_item['expanded'] = $node_item['active'];

				foreach($node_item['children'] as $child) {
					if($child['expanded'] || $child['active']) {
						$node_item['expanded'] = true;
						break;
					}
				}
			}
		}

		$node_item = apply_filters(
			'F4/TREE/Tree/get_post_tree_node',
			$node_item,
			$post_item,
			$current_post_id
		);

		$node_item =  apply_filters(
			'F4/TREE/Tree/get_tree_node',
			$node_item
		);

		return $node_item;
	}
}
