<?php

use F4\TREE\Tree\Helpers as Tree;

global $post;

$config = apply_filters(
	'F4/TREE/Tree/get_js_variables',
	[],
	$post
);

$nonce = wp_create_nonce('f4_tree_ajax_action');

?>

<div class="f4-tree" data-tree-action-nonce="<?php echo esc_attr($nonce); ?>">
	<script>
		let F4TREE = <?php echo json_encode($config); ?>;
	</script>

	<div class="f4-tree__inner"></div>
</div>
