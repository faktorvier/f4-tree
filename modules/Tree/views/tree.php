<?php

use F4\TREE\Tree\Helpers as Tree;

global $post;

$config = apply_filters(
	'F4/TREE/Tree/get_js_variables',
	[],
	$post
);

?>

<div class="f4-tree">
	<script>
		let F4TREE = <?php echo json_encode($config); ?>;
	</script>

	<div class="f4-tree__inner"></div>
</div>
