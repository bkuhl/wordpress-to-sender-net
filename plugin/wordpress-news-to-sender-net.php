<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           WordpressToSender
 *
 * @wordpress-plugin
 * Plugin Name:       News to Sender.net Mailer
 * Plugin URI:        https://github.com/bible-bowl/wordpress-news-to-sender-net
 * Description:       Automatically creates and sends campaigns on Sender.net when News is posted
 * Version:           1.0.0
 * Author:            Ben Kuhl
 * Author URI:        https://github.com/bkuhl
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       20_01-52-58_news-to-sender-net
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WordpressToSender_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wordpress-news-to-sender-net-activator.php
 */
function wtosender_activate_WordpressToSender() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-news-to-sender-net-activator.php';
	WordpressToSender_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wordpress-news-to-sender-net-deactivator.php
 */
function wtosender_deactivate_WordpressToSender() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-news-to-sender-net-deactivator.php';
	WordpressToSender_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'wtosender_activate_WordpressToSender' );
register_deactivation_hook( __FILE__, 'wtosender_deactivate_WordpressToSender' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wordpress-news-to-sender-net.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function wtosender_run_WordpressToSender() {

	$plugin = new WordpressToSender();
	$plugin->run();


}
wtosender_run_WordpressToSender();
