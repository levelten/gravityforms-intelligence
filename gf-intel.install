<?php

/**
 * Fired when the intel plugin is installed and contains schema info and updates.
 *
 * @link       getlevelten.com/blog/tom
 * @since      1.2.7
 *
 * @package    Intel
 */


function gf_intel_install() {

}

/**
 * Implements hook_uninstall();
 *
 * Delete plugin settings
 *
 */
function gf_intel_uninstall() {
	// uninstall plugin related intel data
	if (is_callable('intel_uninstall_plugin')) {
		intel_uninstall_plugin('gf_intel');
	}
}

/**
 * Migrate submission tracking setting properties
 */
function gf_intel_update_1001() {

	$forms = GFAPI::get_forms();

	foreach ($forms as $form) {
		$update = 0;
		if (!empty($form['gf_intel']['trackingEventName'])) {
			$form['gf_intel']['trackSubmission'] = $form['gf_intel']['trackingEventName'];
			unset($form['gf_intel']['trackingEventName']);
			$update = 1;
		}
		if (!empty($form['gf_intel']['trackingEventValue'])) {
			$form['gf_intel']['trackSubmissionValue'] = $form['gf_intel']['trackingEventValue'];
			unset($form['gf_intel']['trackingEventValue']);
			$update = 1;
		}
		if ($update) {
			GFAPI::update_form( $form );
		}
	}

}