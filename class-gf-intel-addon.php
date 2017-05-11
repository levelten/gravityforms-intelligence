<?php

GFForms::include_addon_framework();

class GFIntelAddOn extends GFAddOn {

	protected $_version = GF_INTEL_VER;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gf_intel';
	protected $_path = 'gravityforms-intelligence/gf-intel.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Intelligence Add-On';
	protected $_short_title = 'Intelligence';
	protected $submissionProps = null;

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFIntlAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFIntelAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_pre_submission', array( $this, 'pre_submission' ), 10, 1 );
		add_filter( 'gform_confirmation', array( $this, 'custom_confirmation_message' ), 10, 4 );
		//add_filter( 'gform_pre_send_email', 'pre_send_email', 10, 3 );
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );

		add_action('gform_entry_info', array( $this, 'hook_gform_entry_info' ), 10, 2 );
		//add_action('gform_entry_detail_content_after', array( $this, 'hook_gform_entry_detail_content' ), 10, 2 );
		add_action('gform_entry_detail', array( $this, 'hook_gform_entry_detail_content' ), 10, 2 );

		//do_action( 'gform_entry_detail_content_after', $form, $lead );

	}

	public function hook_gform_entry_detail_content($form, $entry ) {
		if (!defined('INTEL_VER')) {
			return;
		}

		// enueue admin styling & scripts
		intel()->admin->enqueue_styles();
		intel()->admin->enqueue_scripts();

		$vars = array(
			'type' => 'gravityform',
			'fid' => $entry['form_id'],
			'fsid' => $entry['id'],
		);
		$submission = intel()->get_entity_controller('intel_submission')->loadByVars($vars);
		if (empty($submission)) {
			return;
		}
		$submission = array_shift($submission);
		if (empty($submission->vid)) {
			return;
		}
		$synced = $submission->getSynced();

		// if analytics data not stored, sync data
		if (!$submission->getSynced()) {
			$submission->syncData();
		}
		// check if data only paritially updated
		else if (empty($submission->data['analytics_session']) || empty($submission->data['analytics_session']['steps'])) {
			$submission->syncData();
		}
		// if we done have the analytics cronology (steps), don't return data.
		if (empty($submission->data['analytics_session']['steps'])) {
			return;
		}
		// check if enough time has passed since lasthit and the synced time to make
		// sure all GA data was fetched

		// only do check if time is greater than 60 secs since synced time.
		if ((time() - $synced) > 60 ) {
			$lasthit = 0;
			foreach ($submission->data['analytics_session']['steps'] as $step) {
				if ($step['time'] > $lasthit) {
					$lasthit = $step['time'];
				}
			}
			if (($synced - $lasthit) < 1800) {
				$submission->syncData();
				$synced = $submission->getSynced();
			}
		}

		$submission->build_content($submission);
		$visitor = intel()->get_entity_controller('intel_visitor')->loadOne($submission->vid);
		if (empty($visitor->vid)) {
			return;
		}
		$visitor->build_content($visitor);

		//d($visitor->content);
		$build = $visitor->content;
		foreach ($build as $k => $v) {
			if (empty($v['#region']) || ($v['#region'] == 'sidebar')) {
				unset($build[$k]);
			}
		}
		$build = array(
			'elements' => $build,
			'view_mode' => 'half',
		);
		$output = Intel_Df::theme('intel_visitor_profile', $build);

		$steps_table = Intel_Df::theme('intel_visit_steps_table', array('steps' => $submission->data['analytics_session']['steps']));
		?>
		<div id="normal-sortables" class="meta-box-sortables ui-sortable"><div id="notes" class="postbox ">
			<button type="button" class="handlediv button-link" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Notes</span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle ui-sortable-handle"><span>Intelligence</span></h2>
			<div class="inside bootstrap-wrapper intel-wrapper">
				<div class="intel-content half">
					<h4 class="card-header"><?php print __('Submitter profile', 'gf_intel'); ?></h4>
					<?php print $output; ?>
					<!-- <h4 class="card-header"><?php print __('Analytics', 'gf_intel'); ?></h4> -->
					<div class="card-deck-wrapper m-b-1">
						<div class="card-deck">
							<?php print Intel_Df::theme('intel_trafficsource_block', array('trafficsource' => $submission->data['analytics_session']['trafficsource'])); ?>
							<?php print Intel_Df::theme('intel_location_block', array('entity' => $submission)); ?>
							<?php print Intel_Df::theme('intel_browser_environment_block', array('entity' => $submission)); ?>
						</div>
					</div>
					<?php print Intel_Df::theme('intel_visitor_profile_block', array('title' => __('Visit chronology', 'gf_intel'), 'markup' => $steps_table, 'no_margin' => 1)); ?>
				</div>
			</div>
		</div></div>
		<?php
	}

	public function hook_gform_entry_info($fid, $entry ) {
		if (!defined('INTEL_VER')) {
			return;
		}
		$vars = array(
			'type' => 'gravityform',
			'fid' => $entry['form_id'],
			'fsid' => $entry['id'],
		);
		$submission = intel()->get_entity_controller('intel_submission')->loadByVars($vars);
		$submission = array_shift($submission);
		$visitor = intel()->get_entity_controller('intel_visitor')->loadOne($submission->vid);
		if (empty($visitor)) {
			return;
		}
		?>
		<?php esc_html_e( 'Contact', 'gf_intel' ); ?>:
		<?php print Intel_Df::l($visitor->name(), $visitor->uri()); ?>
		<br /><br />
		<?php
	}

	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		return parent::scripts();
		$scripts = array(
			array(
				'handle'  => 'my_script_js',
				'src'     => $this->get_base_url() . '/js/my_script.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'simpleaddon' ),
					'second' => esc_html__( 'Second Choice', 'simpleaddon' ),
					'third'  => esc_html__( 'Third Choice', 'simpleaddon' )
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'simpleaddon'
					)
				)
			),

		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'gf-intel-gfrom-settings',
				'src'     => $this->get_base_url() . '/css/gf-intel-gform-settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array(
							'form_settings',
						),
					),
				),
			),
		);

		$styles = array_merge( parent::styles(), $styles );

		return $styles;
	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Creates a custom page for this add-on.
	 */
	//public function plugin_page() {
	//	echo 'This page appears in the Forms menu';
	//}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	/*
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Intelligence Add-On Settings', 'gf_intel' ),
				'fields' => array(
					array(
						'name'              => 'mytextbox',
						'tooltip'           => esc_html__( 'This is the tooltip', 'gf_intel' ),
						'label'             => esc_html__( 'This is the label', 'gf_intel' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					)
				)
			)
		);
	}
	*/

	/**
	 * Configures the settings which should be rendered on the Form Settings > Intl Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		$ret = array();

		if (!defined('INTEL_VER')) {
			$ret[] = array(
				'title'       => esc_html__( 'Intelligence Settings', 'gf_intel' ),
				'description' => gf_intel_error_msg_missing_intel(),
				'fields' => array(),
			);
			//echo gf_intel_error_msg_missing_intel();
			return $ret;
		}

		require_once INTEL_DIR . "includes/intel.ga.inc";

		//$scorings = intel_get_scorings();

		//$intel_events = intel_get_intel_event_info();

		$eg_labels = gf_intel_intl_eventgoal_labels();

		$submission_goals = intel_get_event_goal_info('submission');

		$options = array();
		foreach ($eg_labels as $k => $v) {
			$options[] = array(
				'label' => $v,
				'value' => $k,
			);
		}
		/*
		$options[] = array(
			'label' => esc_html__( '-- None --', 'gf_intel' ),
			'value' => '',
		);
		$options[] = array(
			'label' => esc_html__( 'Event: Form submission', 'gf_intel' ),
			'value' => 'form_submission-',
		);
		$options[] = array(
			'label' => esc_html__( 'Valued event: Form submission!', 'gf_intel' ),
			'value' => 'form_submission',
		);

		foreach ($submission_goals AS $key => $goal) {
			$options[] = array(
				'label' => esc_html__( 'Goal: ', 'intel') . $goal['goal_title'],
				'value' => $key,
			);
		}
		*/

		$ret[] = array(
			'title'       => esc_html__( 'Intelligence Settings', 'gf_intel' ),
			'description' => '',
			'fields' => array(),
		);
		$ret[] = array(
			'title'       => esc_html__( 'Submission tracking', 'gf_intel' ),
			'description' => '',
			'fields'      => array(
				array(
					'name'     => 'trackingEventName',
					'label'    => esc_html__( 'Tracking event/goal', 'gf_intel' ),
					'type'     => 'select',
					'required' => true,
					'tooltip'  => '<h6>' . esc_html__( 'Tracking event/goal', 'gf_intel' ) . '</h6>' . esc_html__( 'Select a tracking event or goal that should be triggered when the form is successfuly submitted.', 'gf_intel' ),
					'choices'  => $options,
				),
				array(
					'name'     => 'trackingEventValue',
					'label'    => esc_html__( 'Tracking value', 'gf_intel' ),
					'type'     => 'text',
					'required' => false,
					'tooltip'  => '<h6>' . esc_html__( 'Tracking value', 'gf_intel' ) . '</h6>' . esc_html__( 'Enter a (utility) value to associate with the tracking event/goal. Leave blank to use default value.', 'gf_intel' ),
				),
				//array(
				//	'name'     => 'trackingConversions',
				//	'label'    => esc_html__( 'Track Conversions', 'gf_intel' ),
				//	'type'     => 'checkbox',
				//	'required' => false,
				//	'tooltip'  => '<h6>' . esc_html__( 'Tracking value', 'gf_intel' ) . '</h6>' . esc_html__( 'Enter a (utility) value to associate with the tracking event/goal. Leave blank to use default value.', 'gf_intel' ),
				//	'choices' => array(
				//		array(
				//			'label' => esc_html__( 'Enabled', 'gf_intel' ),
				//			'name'  => 'enabled',
				//		),
				//	),
				//),
			)
		);

		$prop_info = intel()->visitor_property_info();

		$prop_wf_info = intel()->visitor_property_webform_info();

		$ret[] = array(
			'title'       => esc_html__( 'Contact field mapping', 'gf_intel' ),
			'description' => '',
			//'dependency'  => 'mailchimpList',
			'fields'      => array(
				/*
				array(
					'name' => 'mappedProperties',
					'label' => esc_html__( 'Properties', 'gf_intel' ),
					'type'  => 'checkbox',
					'choices' => $prop_options,
					'class'   => 'scrollable',
				),
				*/
				array(
					'name'      => 'field_map',
					'label'     => esc_html__( 'Map Fields', 'gf_intel' ),
					'type'      => 'field_map',
					'field_map' => $this->merge_vars_field_map($prop_info, $prop_wf_info),
					'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'gf_intel' ) . '</h6>' . esc_html__( 'Associate Intelligence properties to the appropriate Gravity Form fields by selecting.', 'gf_intel' ),
				),
			),
		);
		return $ret;
	}

	/**
	 * Return an array of Intelligence list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @return array
	 */
	public function merge_vars_field_map($prop_info, $prop_wf_info) {

		$defs = array();
		$defs['data.email'] = array(
			'field_type' => array(
				'email',
				'hidden',
			),
		);

		$exclude = array(
			'data.name' => 1,
		);

		$field_map = array();

		foreach ($prop_wf_info as $k => $wf_info) {
			$info = $prop_info[$k];
			if (!empty($exclude[$k])) {
				continue;
			}
			$gk = str_replace('.', '_', $k);
			$title = !empty($wf_info['title']) ? $wf_info['title'] : $info['title'];
			$data = array(
				'name' => $gk,
				'label' => $title,
				'required' => 0,
				'field_type' => '',
			);
			if ($k == 'data.email') {
				$data['field_type'] = array(
					'email',
					'hidden',
				);
			}
			if (array_key_exists('@value', $info['variables'])) {
				$field_map[] = $data;
			}
			if (!empty($wf_info['variables']) && is_array($wf_info['variables'])) {
				foreach ($wf_info['variables'] as $vk => $vv) {
					if ($vk != '@value') {
						$data['name'] = $gk . '__' . $vk;
						$data['label'] = $title . ": $vk";
						$field_map[] = $data;
					}
				}
			}

		}
		return $field_map;
	}

	public function pre_submission( $form ) {
		if (!defined('INTEL_VER')) {
			return;
		}

		$props = array();
		// get visitor token from cookie
		include_once INTEL_DIR . 'includes/class-intel-visitor.php';
		if (isset($form['gf_intel']) && is_array($form['gf_intel'])) {
			foreach ($form['gf_intel'] as $k => $v) {
				if (!empty($v) && (strpos($k, 'field_map') === 0)) {
					$propKey = str_replace('field_map_', '', $k);
					$propKey = str_replace('_', '.', $propKey);
					$postKey = 'input_' . str_replace('.', '_', $v);
					if (isset($_POST[$postKey])) {
						$props[$propKey] = sanitize_text_field($_POST[$postKey]);
					}
				}
			}
		}

		//Intel_Df::watchdog('gfi_pre_submission form', print_r($form, 1));
		//Intel_Df::watchdog('gfi_pre_submission post', print_r($_POST, 1));
		//Intel_Df::watchdog('gfi_pre_submission subProps', print_r($props, 1));

		$this->submissionProps = $props;
	}

	public function pre_send_email($email, $message_format, $notification) {
		//d($email);
		//d($message_format);
		//d($notification);
	}

	public function custom_confirmation_message( $confirmation, $form, $entry, $ajax ) {
		if (!defined('INTEL_VER')) {
			return $confirmation;
		}
		$vars = intel_form_submission_vars_default();

		$submission = &$vars['submission'];
		$track = &$vars['track'];

		$vars['visitor_properties'] = $this->submissionProps;

		$submission->type = 'gravityform';
		$submission->fid = $entry['form_id'];
		$submission->fsid = $entry['id'];
		//$submission->submission_uri = "/wp-admin/admin.php?page=gf_entries&view=entry&id={$submission->fid}&lid={$submission->fsid}";
		$submission->form_title = $form['title'];

		if (!empty($form['gf_intel']['trackingEventName'])) {
			$track['name'] = $form['gf_intel']['trackingEventName'];
			if (substr($track['name'], -1) == '-') {
				$track['name'] = substr($track['name'], 0, -1);
				$track['valued_event'] = 0;
			}
			if (!empty($form['gf_intel']['trackingEventValue'])) {
				$track['value'] = $form['gf_intel']['trackingEventValue'];
			}
		}

		//Intel_Df::watchdog('custom_confirmation_message form', print_r($form, 1));

		//Intel_Df::watchdog('custom_confirmation_message entry', print_r($entry, 1));

		intel_process_form_submission($vars);

		// if form processed via ajax, return intel pushes with confirmation message
		//if ($ajax) {
			$script = intel()->tracker->get_pushes_script();
			$confirmation .= "\n$script";
		//}

		return $confirmation;
	}

	/**
	 * Performing a custom action at the end of the form submission process.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */
	public function after_submission( $entry, $form ) {
		return;
	}
}