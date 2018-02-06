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
* Version:           1.0.4
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

define('GF_INTEL_VER', '1.0.4');

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

function gf_intel_addon() {
    return GFIntelAddOn::get_instance();
}

function gf_intel_activation() {
  if (is_callable('intel_activate_plugin')) {
    intel_activate_plugin('gf_intel');
  }
}
register_activation_hook( __FILE__, 'gf_intel_activation' );

function _gf_intel_uninstall() {
  require_once plugin_dir_path( __FILE__ ) . 'gf-intel.install';
  gf_intel_uninstall();
}
register_uninstall_hook( __FILE__, '_gf_intel_uninstall' );

/**
 * Implements hook_intel_system_info()
 *
 * @param array $info
 * @return array
 */
function gf_intel_intel_system_info($info = array()) {
  $info['gf_intel'] = array(
    'plugin_file' => 'gf-intel.php', // Main plugin file
    'plugin_path' => GF_INTEL_DIR, // Relative path to the directory containing file
    'plugin_dir' => GF_INTEL_DIR, // Absolute path to the directory containing file
    'plugin_url' => GF_INTEL_URL, // The url path to the
    'update_file' => 'gf-intel.install', // default [plugin_un].install
  );
  return $info;
}
add_filter('intel_system_info', 'gf_intel_intel_system_info');

/**
 * Implements hook_intel_form_type_info()
 */
function gf_intel_form_type_info($info = array()) {
  $info['gravityform'] = array(
    'title' => __( 'Gravity Form', 'gravityforms' ),
    'plugin' => array(
      'name' => __( 'Gravity Form', 'gravityforms' ),
      'slug' => 'gravityforms',
      'text_domain' => 'gravityforms',
    ),
    'submission_data_callback' => 'gf_intel_form_type_submission_data',
  );
  return $info;
}
// Register hook_intel_form_type_forms_info()
add_filter('intel_form_type_info', 'gf_intel_form_type_info');

/**
 * Implements hook_intel_form_type_FORM_TYPE_UN_form_data()
 */
function gf_intel_form_type_form_info($data = NULL, $options = array()) {
  $info = &Intel_Df::drupal_static( __FUNCTION__, array());
  if (!empty($info) && empty($options['refresh'])) {
    return $info;
  }
  $g_forms = RGFormsModel::get_forms( null, 'title' );

  $intel_eventgoal_options = intel_get_form_submission_eventgoal_options();
  foreach ($g_forms as $k => $form) {
    $form_meta = RGFormsModel::get_form_meta($form->id);
    $row = array(
      'settings' => array(),
    );
    $row['id'] = $form->id;
    $row['title'] = $form_meta['title'];
    if (!empty($form_meta['gf_intel'])) {
      if (!empty($form_meta['gf_intel']['trackSubmission'])) {
        $row['settings']['track_submission'] = $form_meta['gf_intel']['trackSubmission'];
        $row['settings']['track_submission__title'] = !empty($intel_eventgoal_options[$form_meta['gf_intel']['trackSubmission']]) ? $intel_eventgoal_options[$form_meta['gf_intel']['trackSubmission']] : $form_meta['gf_intel']['trackSubmission'];
      }
      if (!empty($form_meta['gf_intel']['trackSubmissionValue'])) {
        $row['settings']['track_submission_value'] = $form_meta['gf_intel']['trackSubmissionValue'];
      }

      $row['settings']['field_map'] = array();
      foreach ($form_meta['gf_intel'] as $k => $v) {
        if (!empty($v) && (strpos($k, 'field_map') === 0)) {
          $propKey = str_replace('field_map_', '', $k);
          $propKey = str_replace('_', '.', $propKey);
          $vp_info = intel()->visitor_property_info($propKey);
          if (!empty($vp_info)) {
            $row['settings']['field_map'][] = $vp_info['title'];
          }
        }
      }
    }
    $row['settings_url'] = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gf_intel&id=' . $form->id;

    $info[$form->id] = $row;
  }

  return $info;
}
// Register hook_intel_form_type_forms_info()
add_filter('intel_form_type_gravityform_form_info', 'gf_intel_form_type_form_info');


/*
 * Implements hook_intel_form_type_form_setup()
 */
function gf_intel_form_type_submission_data($fid, $fsid) {
  $data = array();
  $fs = GFAPI::get_entry($fsid);
  if (!empty($fs['form_id'])) {
    $fd = GFAPI::get_form($fs['form_id']);
  }
  $data['title'] = $fd['title'];
  $data['submission_data_url'] = ':gravityform:' . $fid . ':' . $fsid;
  $data['field_values'] = array();
  $data['field_titles'] = array();
  foreach ($fd['fields'] as $field) {
    $id = (string)$field->id;
    $k = intel_format_un($field->label);
    if (!empty($fs[$id])) {
      $data['field_values'][$k] = $fs[$id];
      $data['field_titles'][$k] = $field->label;
    }
    if (is_array($field->inputs)) {
      foreach ($field->inputs as $input) {
        $id = (string)$input['id'];
        if (!empty($fs[$id])) {
          $k = intel_format_un($field->label . '__' . $input['label']);
          $data['field_values'][$k] = $fs[$id];
          $data['field_titles'][$k] = $field->label . ': ' . $input['label'];
        }
      }
    }
  }
  return $data;
}

/**
 * Implements hook_intel_form_type_forms_info()
 * @param $info
 * @return mixed
 */
function gf_intel_form_type_forms_info($info) {
  $info['gravityforms'] = RGFormsModel::get_forms( null, 'title' );

  return $info;
}
// Register hook_intel_form_type_forms_info()
add_filter('intel_form_type_forms_info', 'gf_intel_form_type_forms_info');



/*
 * Implements hook_intel_form_TYPE_form_setup()
 */
function gf_intel_form_type_gravityforms_form_setup($data, $info) {
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
// Register hook_intel_form_TYPE_form_setup()
add_filter('intel_form_type_gravityforms_form_setup', 'gf_intel_form_type_gravityforms_form_setup', 0, 2);

/**
 * Implements hook_intel_url_urn_resolver()
 */
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
// Register hook_intel_url_urn_resolver()
add_filter('intel_url_urn_resovler', 'gf_intel_url_urn_resovler');

/**
 * Implements hook_intel_test_url_parsing_alter()
 */
function gf_intel_test_url_parsing_alter($urls) {
  $urls[] = ':gravityform:1';
  $urls[] = 'urn::gravityform:1';
  $urls[] = ':gravityform:1:1';
  $urls[] = 'urn::gravityform:1:1';
  return $urls;
}
// Register hook_intel_test_url_parsing_alter()
add_filter('intel_test_url_parsing_alter', 'gf_intel_test_url_parsing_alter');

/**
 *  Implements of hook_intel_menu()
 */
function gf_intel_menu($items = array()) {
  $items['admin/config/intel/settings/setup/gf_intel'] = array(
    'title' => 'Setup',
    'description' => Intel_Df::t('Gravity Forms Intelligence initial plugin setup'),
    'page callback' => 'gf_intel_admin_setup_page',
    'access callback' => 'user_access',
    'access arguments' => array('admin intel'),
    'type' => Intel_Df::MENU_LOCAL_ACTION,
    //'weight' => $w++,
    'file' => 'admin/gf_intel.admin_setup.inc',
    'file path' => plugin_dir_path(__FILE__),
  );
  return $items;
}
// Register hook_intel_menu()
add_filter('intel_menu_info', 'gf_intel_menu');

/*
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
*/