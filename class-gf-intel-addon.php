<?php

GFForms::include_addon_framework();

class GFIntelAddOn extends GFAddOn {

	protected $_version = GF_INTEL_VER;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gf_intel';
	protected $_path = 'gf-intelligence/gf-intel.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Intelligence Add-On';
	protected $_short_title = 'Intelligence';
	protected $submissionProps = null;

	private static $plugin_un = 'gf_intel';

	private static $_instance = null;

	/**
	 * Plugin Directory
	 *
	 * @since 3.0
	 * @var string $dir
	 */
	public static $dir = '';

	/**
	 * Plugin URL
	 *
	 * @since 3.0
	 * @var string $url
	 */
	public static $url = '';

	/**
	 * Get an instance of this class.
	 *
	 * @return GFIntlAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFIntelAddOn();
		}

		self::$dir = plugin_dir_path(__FILE__);

		self::$url = plugin_dir_url(__FILE__);

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();

		if (is_callable('intel')) {
			add_action( 'gform_pre_submission', array( $this, 'pre_submission' ), 10, 1 );
			add_filter( 'gform_confirmation', array( $this, 'custom_confirmation_message' ), 10, 4 );
			add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );

			if (version_compare(INTEL_VER, '1.2.7', '>=')) {
				if (intel_is_api_level('pro') && get_option('intel_form_feedback_submission_profile', 1)) {
					// adds visitor profile to entry info box
					add_action('gform_entry_info', array( $this, 'hook_gform_entry_info' ), 10, 2 );

					// adds submission profile
					add_action('gform_entry_detail', array( $this, 'hook_gform_entry_detail_content' ), 10, 2 );
				}
			}
		}
		// plugin setup hooks
		else {
			// Add pages for plugin setup
			add_action( 'admin_menu', array( $this, 'intel_setup_menu' ));
		}

		// setup notice on plugins page
		global $pagenow;

		if ( 'plugins.php' == $pagenow ) {
			add_action( 'admin_notices', array( $this, 'plugin_setup_notice') );
		}

	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		wp_enqueue_style('intel-gf-settings', self::$url . 'css/gf-intel-gf-settings.css');

		$items = array();

		$items[] = '<div class="wrap">';
		$items[] = '<h1>' . esc_html__( 'Intelligence Settings', self::$plugin_un ) . '</h1>';
		$items[] = '</div>';

		$connect_desc = __('Not connected.', self::$plugin_un);
		$connect_desc .= ' ' . sprintf(
				__( ' %sSetup Intelligence%s', self::$plugin_un ),
				'<a href="/wp-admin/admin.php?page=intel_admin&plugin=' . self::$plugin_un . '&q=admin/config/intel/settings/setup/' . self::$plugin_un . '" class="button">', '</a>'
			);
		if($this->is_intel_installed()) {
			$connect_desc = __('Connected');
		}

		$items[] = '<table class="form-table">';
		$items[] = '<tbody>';
		$items[] = '<tr>';
		$items[] = '<th>' . esc_html__( 'Intelligence API', self::$plugin_un ) . '</th>';
		$items[] = '<td>' . $connect_desc . '</td>';
		$items[] = '</tr>';


		if ($this->is_intel_installed()) {
			$eventgoal_options = intel_get_form_submission_eventgoal_options();
			$default_name = get_option('intel_form_track_submission_default', 'form_submission');
			$value = !empty($eventgoal_options[$default_name]) ? $eventgoal_options[$default_name] : Intel_Df::t('(not set)');
			$l_options = Intel_Df::l_options_add_destination('wp-admin/admin.php?page=gf_settings&subview=gf_intel');
			$l_options['attributes'] = array(
				'class' => array('button'),
			);
			$value .= ' ' . Intel_Df::l(esc_html__('Change', self::$plugin_un), 'admin/config/intel/settings/form/default_tracking', $l_options);
			$items[] = '<tr>';
			$items[] = '<th>' . esc_html__( 'Default submission event/goal', self::$plugin_un ) . '</th>';
			$items[] = '<td>' . $value . '</td>';
			$items[] = '</tr>';

			$default_value = get_option('intel_form_track_submission_value_default', '');
			$items[] = '<tr>';
			$items[] = '<th>' . esc_html__( 'Default submission value', self::$plugin_un ) . '</th>';
			$items[] = '<td>' . (!empty($default_value) ? $default_value : Intel_Df::t('(default)')) . '</td>';
			$items[] = '</tr>';
		}
		$items[] = '</tbody>';
		$items[] = '</table>';

		$output = implode("\n", $items);

		return array(
			array(
				'description' => '<p>' . $output . '</p>',
				'fields'      => array(

				),
			),
		);

	}

	public function hook_gform_entry_detail_content($form, $entry ) {

		if (!is_callable('intel')) {
			return;
		}



		// enueue admin styling & scripts
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
			_e('Submission entry not found.', 'gf_intel');
			return;
		}
		$submission = array_shift($submission);

		$vars = array(
			'page' => 'intel_admin',
			'q' => 'submission/' . $submission->get_id() . '/profile',
			'query' => array(
				'embedded' => 1,
			),
			'current_path' => "wp-admin/admin.php?page=gf_entries&view=entry&id={$entry['form_id']}&lid={$entry['id']}",
		);

		// json loading
		$return_type = 'markup';
		if ($return_type == 'markup') {
			include_once INTEL_DIR . 'admin/intel.admin_submission.inc';
			$options = array(
				'embedded' => 1,
				'current_path' => "wp-admin/admin.php?page=gf_entries&view=entry&id={$entry['form_id']}&lid={$entry['id']}",
			);
			$output = intel_submission_profile_page($submission, $options);
		}
		else {
			include_once INTEL_DIR . 'includes/intel.reports.inc';

			intel_add_report_headers();

			$output = intel_get_report_ajax_container($vars);
		}

		print '<div id="normal-sortables" class="meta-box-sortables ui-sortable">';
		print   '<div id="notes" class="postbox ">';
		print     '<button type="button" class="handlediv button-link" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Notes</span><span class="toggle-indicator" aria-hidden="true"></span></button>';
		print     '<h2 class="hndle ui-sortable-handle"><span>' . Intel_Df::t('Intelligence') . '</span></h2>';
		print     '<div class="inside">';
		print       $output;
		print     '</div>';
		print   '</div>';
		print '</div>';
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

		wp_enqueue_script('gf_intel_form_settings', self::$url . 'js/gf-intel-gform-settings.js');

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

		$eg_labels = intel_get_form_submission_eventgoal_options();

		//$submission_goals = intel_get_event_goal_info('submission');

		$eventgoal_options = array();
		foreach ($eg_labels as $k => $v) {
			$eventgoal_options[] = array(
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

		// create add goal link
		$id = !empty($_GET['id']) ? $_GET['id'] : '';
		$l_options = array(
			'attributes' => array(
				'class' => array('button', 'intel-add-goal'),
			)
		);
		$l_options = Intel_Df::l_options_add_destination('wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gf_intel&id=' . $id, $l_options);
		$add_goal = Intel_Df::l( '+' . Intel_Df::t('Add goal'), 'admin/config/intel/settings/goal/add', $l_options);

		$ret[] = array(
			'title'       => esc_html__( 'Tracking', 'gf_intel' ),
			'description' => '',
			'fields'      => array(
				array(
					'name'     => 'trackSubmission',
					'label'    => esc_html__( 'Submission event/goal', 'gf_intel' ),
					'type'     => 'select',
					'required' => true,
					'tooltip'  => '<h6>' . esc_html__( 'Submission event/goal', 'gf_intel' ) . '</h6>' . esc_html__( 'Select a tracking event or goal that should be triggered when the form is successfuly submitted.', 'gf_intel' ), // . '<br><br>'  . $add_goal,
					'choices'  => $eventgoal_options,
					'description' => $add_goal,
				),
				array(
					'name'     => 'trackSubmissionValue',
					'label'    => esc_html__( 'Submission value', 'gf_intel' ),
					'type'     => 'text',
					'required' => false,
					'tooltip'  => '<h6>' . esc_html__( 'Submission value', 'gf_intel' ) . '</h6>' . esc_html__( 'Enter a (utility) value to associate with the tracking event/goal. Leave blank to use default value.', 'gf_intel' ),
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
	 * Define the markup for the my_custom_field_type type field.
	 *
	 * @param array $field The field properties.
	 */
	public function settings_markup_field_type( $field ) {
		echo $field['args']['markup'];
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
//Intel_Df::watchdog('custom_confirmation_message() confirmation', print_r($confirmation, 1));
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

		// if tracking event/value settings are empty, use defaults
		if (empty($form['gf_intel']['trackSubmission'])) {
			$form['gf_intel']['trackSubmission'] = get_option('intel_form_track_submission_default', 'form_submission');
		}
		if (!empty($form['gf_intel']['trackingEventValue'])) {
			$form['gf_intel']['trackSubmissionValue'] = get_option('intel_form_track_submission_value_default', '');
		}

		if (!empty($form['gf_intel']['trackSubmission'])) {
			$track['name'] = $form['gf_intel']['trackSubmission'];
			if (substr($track['name'], -1) == '-') {
				$track['name'] = substr($track['name'], 0, -1);
				$track['valued_event'] = 0;
			}
			if (!empty($form['gf_intel']['trackSubmissionValue'])) {
				$track['value'] = $form['gf_intel']['trackSubmissionValue'];
			}
		}

		//Intel_Df::watchdog('custom_confirmation_message form', print_r($form, 1));
		//Intel_Df::watchdog('custom_confirmation_message() var', print_r($vars, 1));

		intel_process_form_submission($vars);

		// if form processed via ajax, return intel pushes with confirmation message
		//if ($ajax) {

		// For redirects, confirmation will be an array with 'redirect' key for the
		// url or might already converted to javascript string for redirect.
		// Only append data for confirmation messages which are strings.
		if (is_string($confirmation)) {
			// redirect confirmation response might already be converted to string.
			// detect if that has happened.
			if (strpos($confirmation, 'gformRedirect()')) {
				// save pushes to quick cache to be fired on redirect page
				intel_save_flush_page_intel_pushes();
				// search script for redirect url
				if (function_exists('intel_cache_busting_url')) {
					$pattern = '/document.location.href=["\'](.*?)["\']/';
					preg_match($pattern, $confirmation, $matches);
					if (!empty($matches[1])) {
						$url = intel_cache_busting_url($matches[1]);
						if ($url != $matches[1]) {
							$confirmation = str_replace($matches[1], $url, $confirmation);
						}
					}
				}
			}
			else {
				$script = intel()->tracker->get_pushes_script();
				$confirmation .= "\n$script";
			}

		}
		else {
			// save the page flushes to cache
			intel_save_flush_page_intel_pushes();
			// append cache busting query
			if (function_exists('intel_cache_busting_url')) {
				if (is_array($confirmation) && !empty($confirmation['redirect'])) {
					$confirmation['redirect'] = intel_cache_busting_url($confirmation['redirect']);
				}
			}
		}

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

	/*************************************
	 * Plugin setup functions
	 */

	public function is_intel_installed($level = 'min') {
		if (!is_callable('intel_is_installed')) {
			return FALSE;
		}
		return intel_is_installed($level);
	}

	public function plugin_setup_notice() {
		// check dependencies
		if (!function_exists('intel_is_plugin_active')) {
			echo '<div class="error">';
			echo '<p>';
			echo '<strong>' . __('Notice:') . '</strong> ';

			_e('Gravity Forms Intelligence plugin needs to be setup:', self::$plugin_un);
			echo ' ' . sprintf(
				__( ' %sSetup plugin%s', 'gf_intel' ),
				'<a href="/wp-admin/admin.php?page=intel_admin&plugin=' . self::$plugin_un . '&q=admin/config/intel/settings/setup/' . self::$plugin_un . '" class="button">', '</a>'
			);

			echo '</p>';
			echo '</div>';
			return;
		}

		if (!intel_is_plugin_active('gravityforms')) {
			echo '<div class="error">';
			echo '<p>';
			echo '<strong>' . __('Notice:') . '</strong> ';
			_e('The Gravity Forms Intelligence plugin requires the Gravity Forms plugin to be installed and active.', self::$plugin_un);
			echo '</p>';
			echo '</div>';
			return;
		}
	}

	/**
	 * Implements hook_activated_plugin()
	 *
	 * Used to redirect back to wizard after intel is activated
	 *
	 * @param $plugin
	 */
	public function intel_setup_activated_plugin($plugin) {
		require_once( self::$dir . 'intel_com/intel.setup.inc' );
		intel_setup_activated_plugin($plugin);
	}

	public function intel_setup_menu() {
		global $wp_version;

		// check if intel is installed, if so exit
		if (is_callable('intel')) {
			return;
		}

		add_menu_page(esc_html__("Intelligence", self::$plugin_un), esc_html__("Intelligence", self::$plugin_un), 'manage_options', 'intel_admin', array($this, 'intel_setup_page'), version_compare($wp_version, '3.8.0', '>=') ? 'dashicons-analytics' : '');
		add_submenu_page('intel_admin', esc_html__("Setup", self::$plugin_un), esc_html__("Setup", self::$plugin_un), 'manage_options', 'intel_admin', array($this, 'intel_setup_page'));

		add_action('activated_plugin', array( $this, 'intel_setup_activated_plugin' ));
	}

	public function intel_setup_page() {
		if (!empty($_GET['plugin']) && $_GET['plugin'] != self::$plugin_un) {
			return;
		}
		$output = $this->intel_setup_plugin_instructions();

		print $output;
	}

	public function intel_setup_plugin_instructions($options = array()) {

		require_once( self::$dir . 'intel_com/intel.setup.inc' );


		// initialize setup state option
		$intel_setup = get_option('intel_setup', array());
		$intel_setup['active_path'] = 'admin/config/intel/settings/setup/' . self::$plugin_un;
		update_option('intel_setup', $intel_setup);

		intel_setup_set_activated_option('intelligence', array('destination' => $intel_setup['active_path']));

		$items = array();

		$items[] = '<h1>' . __('Gravity Forms Intelligence Setup', self::$plugin_un) . '</h1>';
		$items[] = __('To continue with the setup please install the Intelligence plugin.', self::$plugin_un);

		$items[] = "<br>\n<br>\n";

		$vars = array(
			'plugin_slug' => 'intelligence',
			'card_class' => array(
				'action-buttons-only'
			),
			//'activate_url' => $activate_url,
		);
		$vars = intel_setup_process_install_plugin_card($vars);

		$items[] = '<div class="intel-setup">';
		$items[] = intel_setup_theme_install_plugin_card($vars);
		$items[] = '</div>';

		return implode(' ', $items);
	}
}