<style>
	@media screen and (min-width: 851px) {
		.wrap {
			display: flex;
		}

		.f4-options-form {
			flex: 1 1 100%;
			padding-right: 20px;
		}

		.f4-options-sidebar {
			display: block;
			padding-top: 12px;
			flex: 0 0 300px;
		}
	}
</style>

<div class="wrap fs-section">
	<div class="f4-options-form">
		<h1>
			<?php _e('Post Tree Settings', 'f4-tree'); ?>
		</h1>

		<!-- Tabs -->
		<nav class="nav-tab-wrapper">
			<a
				href="<?php echo admin_url('options-general.php?page=f4-tree-settings'); ?>"
				class="nav-tab fs-tab nav-tab-active"
				>
				<?php _e('Settings', 'f4-tree'); ?>
			</a>
		</nav>

		<?php do_action('F4/TREE/Core/before_admin_settings_form'); ?>

		<form method="POST" action="options.php" novalidate="novalidate">
			<?php settings_fields('f4-tree-settings'); ?>
			<?php do_action('F4/TREE/Core/admin_settings_fields'); ?>
			<?php submit_button(); ?>
		</form>

		<?php do_action('F4/TREE/Core/after_admin_settings_form'); ?>
	</div>

	<div class="f4-options-sidebar">
		<a class="f4-options-sidebar-link" href="https://www.faktorvier.ch" target="_blank">
			<img src="<?php echo F4_TREE_URL . 'assets/img/made-with-love-by-f4.png'; ?>" alt="F4" />
		</a>
	</div>
</div>
