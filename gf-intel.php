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
* @since             1.0.0
* @package           Intelligence
*
* @wordpress-plugin
* Plugin Name:       Gravity Forms Intelligence
* Plugin URI:        http://intelligencewp.com/plugin/gravityforms-intelligence/
* Description:       Integrates Intelligence with Gravity Forms enabling easy Google Analytics goal tracking and visitor intelligence gathering.
* Version:           1.0.0
* Author:            Tom McCracken
* Author URI:        getlevelten.com/blog/tom
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       gf_intel
* Domain Path:       /languages
* GitHub Plugin URI: https://github.com/levelten/gravityforms-intelligence
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

define('GF_INTEL_VER', '1.0.0');

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

function gf_intel_intl_eventgoal_labels() {

  $submission_goals = intel_get_event_goal_info('submission');

  $data = array();

  $data[''] = '-- ' . esc_html__( 'None', 'gf_intel' ) . ' --';
  $data['form_submission-'] = esc_html__( 'Event: Form submission', 'gf_intel' );
  $data['form_submission'] = esc_html__( 'Valued event: Form submission!', 'gf_intel' );


  foreach ($submission_goals AS $key => $goal) {
    $data[$key] = esc_html__( 'Goal: ', 'intel') . $goal['goal_title'];
  }

  return $data;
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
        $vars['options']['query']['view'] = 'entry';
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

/*
add_filter('intel_plugin_path_info', 'gf_intel_plugin_path_info');
function gf_intel_plugin_path_info($info) {
  $info['gravityforms'] = array(
    'directory' => array(
      'gravityforms'
    ),
    'filename' => array(
      'gravityforms.php'
    )
  );
  $info['gf_intel'] = array(
    'directory' => array(
      'gravityforms-intelligence',
      'gravityformsintel',
    ),
    'filename' => array(
      'gf-intel.php'
    )
  );
  return $info;
}
*/

add_filter('intel_form_type_forms_info', 'gf_intel_form_type_forms_info');
function gf_intel_form_type_forms_info($info) {
  $info['gravityforms'] = RGFormsModel::get_forms( null, 'title' );

  return $info;
}

add_filter('intel_form_type_gravityforms_form_setup', 'gf_intel_form_type_form_setup', 0, 2);
function gf_intel_form_type_form_setup($data, $info) {
  $form_meta = RGFormsModel::get_form_meta( $info->id );
  if (empty($form_meta)) {
    return $info;
  }
  $data['id'] = $info->id;
  $data['title'] = $form_meta['title'];
  if (!empty($form_meta['gf_intel'])) {
    if (!empty($form_meta['gf_intel']['trackingEventName'])) {
      $labels = gf_intel_intl_eventgoal_labels();
      $data['tracking_event'] = !empty($labels[$form_meta['gf_intel']['trackingEventName']]) ? $labels[$form_meta['gf_intel']['trackingEventName']] : $form_meta['gf_intel']['trackingEventName'];
    }

    $data['field_map'] = array();
    foreach ($form_meta['gf_intel'] as $k => $v) {
      if (!empty($v) && (strpos($k, 'field_map') === 0)) {
        $propKey = str_replace('field_map_', '', $k);
        $propKey = str_replace('_', '.', $propKey);
        $vp_info= intel()->visitor_property_info($propKey);
        if (!empty($vp_info)) {
          $data['field_map'][] = $vp_info['title'];
        }
      }
    }
  }

  $data['settings_url'] = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gf_intel&id=' . $info->id;

  return $data;
}

// dependencies notices
add_action( 'admin_notices', 'gf_intel_plugin_dependency_notice' );
function gf_intel_plugin_dependency_notice() {
  global $pagenow;

  if ( 'plugins.php' != $pagenow ) {
    return;
  }

  // check dependencies
  if (!function_exists('intel_is_plugin_active')) {
    echo gf_intel_error_msg_missing_intel(array('notice' => 1));
    return;
  }

  if (!intel_is_plugin_active('gravityforms')) {
    echo '<div class="error">';
    echo '<p>';
    echo '<strong>' . __('Notice:') . '</strong> ';
    _e('The Gravity Forms Intelligence plugin requires the Gravity Forms plugin to be installed and active.');
    echo '</p>';
    echo '</div>';
    return;
  }
}

function gf_intel_error_msg_missing_intel($options = array()) {
  $msg = '';

  if (!empty($options['notice'])) {
    $msg .=  '<div class="error">';
  }
  $msg .=  '<p>';
  $msg .=  '<strong>' . __('Notice:') . '</strong> ';
  $msg .=  __('The Gravity Forms Intelligence plugin requires the Intelligence plugin to be installed and active.');
  $msg .=  '</p>';
  if (!empty($options['notice'])) {
    $msg .=  '</div>';
  }
  return $msg;
}