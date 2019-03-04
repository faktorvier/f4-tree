<div class="wrap">
	<h1>
		<?php _e('F4 Post Tree settings', 'f4-tree'); ?>
	</h1>

	<?php do_action('F4/TREE/Core/before_admin_settings_form'); ?>

	<form method="POST" action="options.php" novalidate="novalidate">
		<?php settings_fields('f4-tree-settings'); ?>
		<?php do_action('F4/TREE/Core/admin_settings_fields'); ?>
		<?php submit_button(); ?>
	</form>

	<?php do_action('F4/TREE/Core/after_admin_settings_form'); ?>
</div>
