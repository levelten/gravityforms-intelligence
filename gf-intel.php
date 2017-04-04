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

function gf_intel_intl_eventgoal_labels() {

  $submission_goals = intel_get_event_goal_info('submission');

  $data = array();

  $data[''] = '-- ' . esc_html__( 'None', 'gravityformsintel' ) . ' --';
  $data['form_submission-'] = esc_html__( 'Event: Form submission', 'gravityformsintel' );
  $data['form_submission'] = esc_html__( 'Valued event: Form submission!', 'gravityformsintel' );


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
  $info['gravityforms_intel'] = array(
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
  if (!empty($form_meta['gravityformsintel'])) {
    if (!empty($form_meta['gravityformsintel']['trackingEventName'])) {
      $labels = gf_intel_intl_eventgoal_labels();
      $data['tracking_event'] = !empty($labels[$form_meta['gravityformsintel']['trackingEventName']]) ? $labels[$form_meta['gravityformsintel']['trackingEventName']] : $form_meta['gravityformsintel']['trackingEventName'];
    }

    $data['field_map'] = array();
    foreach ($form_meta['gravityformsintel'] as $k => $v) {
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

  $data['settings_url'] = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gravityformsintel&id=' . $info->id;

  return $data;
}