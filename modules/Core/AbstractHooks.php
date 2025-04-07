<?php

namespace F4\TREE\Core;

/**
 * Base hooks
 *
 * Base WordPress hooks class from which implementations extend.
 *
 * @since 2.0.0
 * @package F4\TREE\Core
 * @abstract
 */
abstract class AbstractHooks {
	/**
	 * Add an action.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param string $tag The name of the action to which the $function_to_add is hooked.
	 * @param string $function_to_add The name of the function you wish to be called.
	 * @param int $priority The hook priority.
	 * @param int $accepted_args The number of arguments the function accepts.
	 * @return boolean
	 */
	public static function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		return add_action($tag, static::class . '::' . $function_to_add, $priority, $accepted_args);
	}

	/**
	 * Add a filter.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param string $tag The name of the action to which the $function_to_add is hooked.
	 * @param string $function_to_add The name of the function you wish to be called.
	 * @param int $priority The hook priority.
	 * @param int $accepted_args The number of arguments the function accepts.
	 * @return boolean
	 */
	public static function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
		$function_name = static::class . '::' . $function_to_add;

		// Dont add namespace to functions beginning with __ (__return_false, __return_true etc.)
		if(substr($function_to_add, 0, 2) === '__') {
			$function_name = $function_to_add;
		}

		return add_filter($tag, $function_name, $priority, $accepted_args);
	}

	/**
	 * Add a shortcode.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 * @param string $tag Shortcode tag to be searched in post content
	 * @param string $callback The callback function to run when the shortcode is found.
	 * @return boolean
	 */
	public static function add_shortcode($tag, $callback) {
		return add_shortcode($tag, static::class . '::' . $callback);
	}
}
