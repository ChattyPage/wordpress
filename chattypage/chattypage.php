<?php
/**
 * Plugin Name:       ChattyPage Sections
 * Plugin URI:        https://chattypage.com/en/wordpress/
 * Description:       AI-designed sections for your WordPress site. Design in ChattyPage, place anywhere with a shortcode, block, or Elementor widget. Sections stay in sync automatically.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ChattyPage
 * Author URI:        https://chattypage.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chattypage
 *
 * This plugin connects to the ChattyPage service (https://chattypage.com) to fetch the section
 * designs you created in your ChattyPage account. See readme.txt for the service disclosure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHATTYPAGE_VERSION', '0.1.0' );
define( 'CHATTYPAGE_PLUGIN_FILE', __FILE__ );
define( 'CHATTYPAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHATTYPAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// The ChattyPage API base. Overridable for development against a local stack:
// define( 'CHATTYPAGE_API_BASE', 'http://host.docker.internal:3000' ) in wp-config.php.
if ( ! defined( 'CHATTYPAGE_API_BASE' ) ) {
	define( 'CHATTYPAGE_API_BASE', 'https://chattypage.com/api' );
}
// The ChattyPage app origin for "Edit in ChattyPage" deep links.
if ( ! defined( 'CHATTYPAGE_APP_BASE' ) ) {
	define( 'CHATTYPAGE_APP_BASE', 'https://app.chattypage.com' );
}

require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-api-client.php';
require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-renderer.php';
require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-rest.php';
require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-head.php';
require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-shortcode.php';
require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-gutenberg.php';
require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-admin.php';

ChattyPage_Rest::init();
ChattyPage_Head::init();
ChattyPage_Shortcode::init();
ChattyPage_Gutenberg::init();
ChattyPage_Admin::init();

// The Elementor widget registers only when Elementor is active — the rest of the plugin
// (shortcode + Gutenberg block) never depends on it.
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once CHATTYPAGE_PLUGIN_DIR . 'includes/class-chattypage-elementor-widget.php';
	$widgets_manager->register( new ChattyPage_Elementor_Widget() );
} );
