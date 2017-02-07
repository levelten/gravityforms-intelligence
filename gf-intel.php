<?php
/*
Plugin Name: Gravity Forms Intelligence Add-On
Plugin URI: http://www.gravityforms.com
Description: An intelligent add-on to demonstrate the use of the Add-On Framework
Version: 2.1
Author: LevelTen
Author URI: http://getlevelten.com
*/

define( 'GF_INTEL_ADDON_VERSION', '2.1' );

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

function gf_intel_addon() {
    return GFIntelAddOn::get_instance();
}

/**
 * Implements hook_intel_url_urn_resolver()
 */
add_filter('intel_url_urn_resovler', 'gf_intel_url_urn_resovler');
function gf_intel_url_urn_resovler($vars) {
    $urn_elms = explode(':', $vars['path']);
    if ($urn_elms[0] == 'urn') {
        array_shift($urn_elms);
    }
    if ($urn_elms[0] == '') {
      if ($urn_elms[1] == 'gravityform' && !empty($urn_elms[2])) {
        $vars['path'] = 'wp-admin/admin.php';
        $vars['options']['query']['page'] = 'gf_edit_forms';
        $vars['options']['query']['id'] = $urn_elms[2];
        // if 3rd element set, urn specifies an form entry. If only 2, then a
        // form.
        if (!empty($urn_elms[3])) {
          $vars['options']['query']['lid'] = $urn_elms[3];
        }
      }
    }

    return $vars;
}

/**
 * Implements hook_intel_test_url_parsing_alter()
 */
add_filter('intel_test_url_parsing_alter', 'gf_intel_test_url_parsing_alter');
function gf_intel_test_url_parsing_alter($urls) {
    $urls[] = ':gravityform:1';
    $urls[] = 'urn::gravityform:1';
    $urls[] = ':gravityform:1:1';
    $urls[] = 'urn::gravityform:1:1';
    return $urls;
}