<?php

namespace F4\TREE\Tree;

use F4\TREE\Core\Helpers as Core;

/**
 * Tree Helpers
 *
 * All the WordPress helpers for the Tree module
 *
 * @since 1.0.0
 * @package F4\TREE\Tree
 */
class Helpers {
	/**
	 * Check if tree is enabled on this screen
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return bool True if the tree is enabled in the current screen
	 */
	public static function is_tree_enabled() {
		global $current_screen;

		$enabled_post_types = Core::get_settings('post-types');
		$enabled_screen_ids = array();

		foreach($enabled_post_types as $post_type_name => $post_type_enabled) {
			if(post_type_exists($post_type_name) && $post_type_enabled == '1') {
				$enabled_screen_ids[] = $post_type_name;
				$enabled_screen_ids[] = 'edit-' . $post_type_name;
			}
		}

		$is_enabled = in_array($current_screen->id, $enabled_screen_ids);
		$is_enabled = apply_filters('F4/TREE/Tree/is_tree_enabled', $is_enabled, $current_screen);

		return $is_enabled;
	}

	/**
	 * Check if tree is enabled for this post type
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return bool True if the tree is enabled for this post type
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

		$is_tree_post_type = isset($enabled_post_types[$post_type]);
		$is_tree_post_type = apply_filters('F4/TREE/Tree/is_tree_post_type', $is_tree_post_type, $post_type);

		return $is_tree_post_type;
	}

	/**
	 * Update changed timestamp
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return int The updated timestamp
	 */
	public static function update_changed_timestamp($time = null) {
		if(!$time) {
			$time = time();
		}

		$time = apply_filters('F4/TREE/Tree/update_changed_timestamp', $time);

		update_option('f4-tree-changed-timestamp', $time);

		return $time;
	}

	/**
	 * Get changed timestamp
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return int The last changed timestamp
	 */
	public static function get_changed_timestamp() {
		$changed_timestamp = get_option('f4-tree-changed-timestamp', 0);
		$changed_timestamp = apply_filters('F4/TREE/Tree/get_changed_timestamp', $changed_timestamp);

		return $changed_timestamp;
	}

	/**
	 * Clear changed timestamp cache
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function clear_changed_timestamp_cache() {
		wp_cache_delete('alloptions', 'options');
		do_action('F4/TREE/Tree/clear_changed_timestamp_cache');
	}

	/**
	 * Get the icon for a node
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_node_icon($type, $object, $id, $status) {
		// Icon: Default (external link)
		$icon = array(
			'html' => '<span class="fancytree-custom-icon-inline fancytree-custom-icon-status-' . $status . '">
				<svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" xml:space="preserve">
					<g>
						<path d="M75.394,58.138l12.673-12.675c9.245-9.243,9.245-24.286,0-33.529c-9.244-9.246-24.286-9.246-33.53,0L36.248,30.223 c-9.245,9.243-9.245,24.286,0,33.529c1.365,1.366,2.859,2.524,4.44,3.486l9.791-9.792c-1.865-0.446-3.634-1.387-5.086-2.838 c-4.202-4.202-4.202-11.04,0-15.241l18.289-18.289c4.202-4.202,11.04-4.202,15.241,0c4.202,4.202,4.202,11.039,0,15.241 l-5.373,5.374C75.764,46.904,76.376,52.635,75.394,58.138z"></path>
						<path d="M24.607,41.862L11.934,54.536c-9.246,9.244-9.246,24.286,0,33.53c9.243,9.245,24.286,9.245,33.53,0l18.288-18.289 c9.245-9.244,9.244-24.286,0-33.529c-1.364-1.366-2.858-2.524-4.439-3.486l-9.791,9.792c1.864,0.447,3.633,1.386,5.086,2.838 c4.202,4.202,4.202,11.039,0,15.241l-18.29,18.289c-4.202,4.202-11.039,4.202-15.241,0c-4.202-4.202-4.202-11.039,0-15.241 l5.374-5.373C24.236,53.097,23.624,47.365,24.607,41.862z"></path>
					</g>
				</svg>
			</span>'
		);

		// Icon: Post types
		if($type === 'post_type') {
			if($object === 'page') {
				$icon = array(
					'html' => '<span class="fancytree-custom-icon-inline fancytree-custom-icon-status-' . $status . '">
						<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 300 300" xml:space="preserve">
							<path d="M18.75,0v300H262.5V93.75h-93.75V0H18.75z M187.5,0v75h75L187.5,0z M56.25,75h75v18.75h-75V75z M56.25,131.25H225V150H56.25V131.25z M56.25,187.5H187.5v18.75H56.25V187.5z M56.25,243.75H225v18.75H56.25V243.75z"></path>
						</svg>
					</span>'
				);
			} else {
				$menu_icon = get_post_type_object($object)->menu_icon;
				$menu_icon = !$menu_icon ? 'dashicons-admin-post' : $menu_icon;

				$icon = array(
					'html' => '<span class="dashicons-before ' . $menu_icon . ' fancytree-custom-icon-status-' . $status . '"></span>'
				);
			}

			// Icon: Home
			if($object === 'page') {
				$page_on_front = (int)Core::maybe_translate_post_id(get_option('page_on_front'));

				if($page_on_front && $id === $page_on_front) {
					$icon = array(
						'html' => '<span class="fancytree-custom-icon-inline fancytree-custom-icon-status-' . $status . '">
							<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 300 300" xml:space="preserve">
								<path d="M300,162.504L150,0L0,162.504V300h125.001v-87.498h50.001V300H300V162.504z"></path>
							</svg>
						</span>'
					);
				}
			}
		} elseif($type === 'taxonomy') {
			$icon = array(
				'html' => '<span class="fancytree-custom-icon-inline fancytree-custom-icon-status-' . $status . '">
					<svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" xml:space="preserve">
						<path d="M93.423,45.799l-48.26,48.262c-2.855,2.854-7.487,2.851-10.338,0L6.094,65.327c-2.853-2.852-2.853-7.479,0-10.334 l48.262-48.26c1.609-1.609,3.779-2.278,5.881-2.073l-0.14-0.143l25.111,5.581L85.204,10.1c1.063,0.167,3.622,0.994,4.653,4.7  l5.164,23.075C96.096,40.516,95.563,43.658,93.423,45.799z M81.354,18.798c-2.977-2.974-7.794-2.974-10.763,0  c-2.977,2.975-2.977,7.792-0.007,10.765c2.976,2.976,7.793,2.976,10.77,0C84.33,26.59,84.33,21.772,81.354,18.798z"></path>
					</svg>
				</span>'
			);
		}

		// Modify node icon
		$icon = apply_filters('F4/TREE/Menu/get_node_icon', $icon, $type, $object, $id, $status);

		return $icon;
	}

	/**
	 * Get tree node for a post
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_post_tree_node($post_item, $current_post_id) {
		// Create default node
		$node_item = array(
			'key' => 'post-' . uniqid(),
			'title' => (empty($post_item->post_title) ? __( '(no title)') : $post_item->post_title),
			'tooltip' => 'ID: ' . $post_item->ID,
			'icon' => self::get_node_icon('post_type', $post_item->post_type, $post_item->ID, $post_item->post_status),
			'active' => ($post_item->ID == $current_post_id),
			'expanded' => ($post_item->ID == $current_post_id),
			'extraClasses' => array(),
			'data' => array(
				'url' => get_admin_url(null, 'post.php?post=' . $post_item->ID . '&action=edit'),
				'type' => 'post',
				'post_id' => $post_item->ID,
				'post_type' => $post_item->post_type,
				'post_status' => $post_item->post_status,
				'allow_children' => false
			)
		);

		// Disable drag and drop if no permission
		if(!Core::current_user_can_post_cap('edit_post', $post_item->ID, $post_item->post_type)) {
			$node_item['extraClasses'][] = 'node-disable-dnd';
		}

		// Add children if hierarchical
		if(is_post_type_hierarchical($post_item->post_type)) {
			$node_item['data']['allow_children'] = true;
			$node_item['children'] = self::get_children($post_item->post_type, $post_item->ID, $current_post_id);
		}

		$node_item = apply_filters('F4/TREE/Tree/get_post_tree_node', $node_item, $post_item, $current_post_id);

		return $node_item;
	}

	/**
	 * Get tree children
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_children($post_type = 'page', $post_parent = 0, $post_id = null, $recursive = true, $args = array()) {
		if(!$post_id) {
			$post_id = get_the_ID();
		}

		// Set defaults
		$args = wp_parse_args(
			$args,
			array(
				'nopaging' => true,
				'orderby' => array(
					'menu_order' => 'ASC',
					'title' => 'ASC'
				),
				'post_status' => array(
					'draft',
					'publish',
					'future',
					'pending',
					'private'
				)
			)
		);

		// Force arguments
		$args['post_parent'] = $post_parent;
		$args['post_type'] = $post_type;

		// Get posts
		$posts = get_posts($args);

		// Get tree nodes
		$tree_nodes = array();

		foreach($posts as $post_item) {
			// Skip private posts if no permission
			//if($post_item->post_status === 'private' && !Core::current_user_can_post_cap('read_private_posts', $post_item->ID, $post_item->post_type)) {
			if($post_item->post_status === 'private' && !current_user_can('read_private_posts', $post_item->ID)) {
				continue;
			}

			$tree_nodes[] = self::get_post_tree_node($post_item, $post_id);
		}

		return $tree_nodes;
	}

	/**
	 * Get the tree
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_tree($post_id, $post_type = 'page') {
		// Get post type object
		$post_type_object = get_post_type_object($post_type);

		// Get child nodes
		$post_nodes = self::get_children($post_type, 0, $post_id);
		$post_nodes = apply_filters('F4/TREE/Tree/get_tree_post_nodes', $post_nodes, $post_id, $post_type, $post_type_object);

		// Build tree
		$tree_nodes = apply_filters('F4/TREE/Tree/get_tree_nodes_before', array(), $post_id, $post_type, $post_type_object, $post_nodes);

		$tree_nodes[] = array(
			'key' => 'pool-' . $post_type,
			'title' => $post_type_object->label,
			'expanded' => true,
			'folder' => 1,
			'unselectable' => 1,
			'extraClasses' => 'node-disable-dnd tree-pool',
			'children' => array_values($post_nodes),
			'icon' => array(
				'html' => '<span class="fancytree-custom-icon-inline">
					<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 108.2 108.2" xml:space="preserve">
						<path d="M0,99.2h108.2l-0.9-79.7c-23.2,0-46.3,0-69.5,0V9.1H10.3L0.7,19.5L0,99.2z"></path>
					</svg>
				</span>'
			),
			'tooltip' => $post_type_object->description,
			'data' => array(
				'type' => 'pool',
				'allow_children' => true
			)
		);

		$tree_nodes = apply_filters('F4/TREE/Tree/get_tree_nodes_after', $tree_nodes, $post_id, $post_type, $post_type_object, $post_nodes);
		$tree_nodes = array_values($tree_nodes);

		// Check for duplicate active nodes
		$has_active_node = false;

		array_walk_recursive($tree_nodes, function(&$v, $k) {
			global $has_active_node;

			if($k === 'active') {
				if($has_active_node) {
					$v = false;
				}

				if($v) {
					$has_active_node = true;
				}
			}
		});

		return $tree_nodes;
	}
}

?>
