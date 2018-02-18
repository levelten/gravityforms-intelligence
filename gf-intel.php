<?php
/*
* Gravity Forms Intelligence bootstrap file
*
* This file is read by WordPress to generate the plugin information in the plugin
* admin area. This file also includes all of the dependencies used by the plugin,
* registers the activation and deactivation functions, and defines a function
* that starts the plugin.
*
* @link              getlevelten.com/blog/tom
* @since             1.1.0
* @package           Intelligence
*
* @wordpress-plugin
* Plugin Name:       Gravity Forms Intelligence
* Plugin URI:        https://wordpress.org/plugins/gf-intelligence
* Description:       Integrates Intelligence with Gravity Forms enabling easy Google Analytics goal tracking and visitor intelligence gathering.
* Version:           1.0.5.0-dev
* Author:            LevelTen
* Author URI:        https://intelligencewp.com
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       gf_intel
* Domain Path:       /languages/
* GitHub Plugin URI: https://github.com/levelten/wp-gf-intelligence
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

define('GF_INTEL_VER', '1.0.5.0-dev');

define( 'GF_INTEL_DIR', plugin_dir_path( __FILE__ ) );

define( 'GF_INTEL_URL', plugin_dir_url( __FILE__ ) );

add_action( 'gform_loaded', array( 'GF_Intel_AddOn_Bootstrap', 'load' ), 5 );

class GF_Intel_AddOn_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gf-intel-addon.php' );

        GFAddOn::register( 'GFIntelAddOn' );
    }

}

function gf_intel() {
    return GFIntelAddOn::get_instance();
}

function _gf_intel_activation() {
  if (is_callable('intel_activate_plugin')) {
    intel_activate_plugin('gf_intel');
  }
}
register_activation_hook( __FILE__, '_gf_intel_activation' );

function _gf_intel_uninstall() {
  require_once plugin_dir_path( __FILE__ ) . 'gf-intel.install';
  gf_intel_uninstall();
}
register_uninstall_hook( __FILE__, '_gf_intel_uninstall' );
