<?php

/*
Plugin Name: F4 Post Tree
Plugin URI: https://www.f4dev.ch
Description: Adds a tree to the pages
Version: 1.1.11
Author: FAKTOR VIER
Author URI: https://www.f4dev.ch
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: f4-tree
Domain Path: /languages*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
define( 'F4_TREE_VERSION', '1.1.11' );
define( 'F4_TREE_SLUG', 'f4-tree' );
define( 'F4_TREE_MAIN_FILE', __FILE__ );
define( 'F4_TREE_BASENAME', plugin_basename( F4_TREE_MAIN_FILE ) );
define( 'F4_TREE_PATH', dirname( F4_TREE_MAIN_FILE ) . DIRECTORY_SEPARATOR );
define( 'F4_TREE_URL', plugins_url( '/', F4_TREE_MAIN_FILE ) );
define( 'F4_TREE_PLUGIN_FILE', basename( F4_TREE_BASENAME ) );
define( 'F4_TREE_PLUGIN_FILE_PATH', F4_TREE_PATH . F4_TREE_PLUGIN_FILE );

if ( function_exists( 'ft_tree_fs' ) ) {
    ft_tree_fs()->set_basename( false, __FILE__ );
} else {

    if ( !function_exists( 'ft_tree_fs' ) ) {
        // Create a helper function for easy SDK access.
        function ft_tree_fs()
        {
            global  $ft_tree_fs ;

            if ( !isset( $ft_tree_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $ft_tree_fs = fs_dynamic_init( array(
                    'id'             => '6913',
                    'slug'           => 'f4-tree',
                    'premium_slug'   => 'f4-tree-pro',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_9fe9dc7631d539c9f0edd5ec059f2',
                    'is_premium'     => false,
                    'premium_suffix' => 'PRO',
                    'navigation'     => 'tabs',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                    'slug'    => 'f4-tree-settings',
                    'support' => false,
                    'contact' => false,
                    'pricing' => false,
                    'parent'  => array(
                    'slug' => 'options-general.php',
                ),
                ),
                    'is_live'        => true,
                ) );
            }

            return $ft_tree_fs;
        }

        // Init Freemius.
        ft_tree_fs();
        // Add plugin icon
        ft_tree_fs()->add_filter( 'plugin_icon', function () {
            return F4_TREE_PATH . 'assets/img/f4-tree.jpg';
        } );
        // Signal that SDK was initiated.
        do_action( 'ft_tree_fs_loaded' );
    }

}

// Add autoloader
spl_autoload_register( function ( $class ) {
    $class = ltrim( $class, '\\' );
    $ns_prefix = 'F4\\TREE\\';
    if ( strpos( $class, $ns_prefix ) !== 0 ) {
        return;
    }
    $class_name = str_replace( $ns_prefix, '', $class );
    $class_path = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
    $class_file = F4_TREE_PATH . $class_path . '.php';
    if ( file_exists( $class_file ) ) {
        require_once $class_file;
    }
} );
// Init core
F4\TREE\Core\Hooks::init();
// Init modules
F4\TREE\Tree\Hooks::init();
