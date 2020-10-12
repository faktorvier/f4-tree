<?php

$post_types_public = get_post_types( array(
    'show_ui' => true,
), 'objects' );
$post_types_enabled = \F4\TREE\Core\Helpers::get_settings( 'post-types' );
?>

<h2>
	<?php 
_e( 'Enable tree for post types', 'f4-tree' );
?>:
</h2>

<?php 
foreach ( $post_types_public as $post_type_name => $post_type_object ) {
    ?>
	<?php 
    if ( !in_array( $post_type_name, array( 'post', 'page' ) ) ) {
        continue;
    }
    ?>

	<input
		name="f4-tree-settings[post-types][<?php 
    echo  $post_type_name ;
    ?>]"
		id="f4-tree-settings-post-types-<?php 
    echo  $post_type_name ;
    ?>"
		type="checkbox"
		value="1"
		<?php 
    checked( 1, isset( $post_types_enabled[$post_type_name] ) && $post_types_enabled[$post_type_name] );
    ?>
	/>

	<label for="f4-tree-settings-post-types-<?php 
    echo  $post_type_name ;
    ?>">
		<strong><?php 
    echo  $post_type_object->label ;
    ?></strong> <i>(<?php 
    echo  $post_type_name ;
    ?>)</i>
		<br />
	</label>
<?php 
}