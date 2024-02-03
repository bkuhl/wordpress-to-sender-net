<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    WordpressToSender
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option(WordpressToSender::OPTION_API_TOKEN);
delete_option(WordpressToSender::OPTION_POST_TYPE);
delete_option(WordpressToSender::OPTION_AUTOPUBLISH);
delete_option(WordpressToSender::OPTION_SELECTED_GROUPS);
delete_option(WordpressToSender::OPTION_REPLY_TO);
delete_option(WordpressToSender::OPTION_MAIL_TEMPLATE);
