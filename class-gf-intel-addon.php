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

	/**
	 * Intel plugin unique name
	 * @var string
	 */
	public $plugin_un = 'gf_intel';

	/**
	 * Intel form type unique name
	 * @var string
	 */
	public $form_type_un = 'gravityform';

	/**
	 * Plugin Directory
	 *
	 * @since 3.0.0
	 * @var string $dir
	 */
	public $dir = '';

	/**
	 * Plugin URL
	 *
	 * @since 3.0.0
	 * @var string $url
	 */
	public $url = '';

	/**
	 * @var array
	 * @since 3.0.0
	 */
	public $plugin_info = array();

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

		$this->plugin_info = $this->intel_plugin_info();

		$this->dir = plugin_dir_path(__FILE__);

		$this->url = plugin_dir_url(__FILE__);
		//if (is_callable('intel')) {
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
		//}
		// plugin setup hooks
		//else {
			// Add pages for plugin setup
			add_action('wp_loaded', array($this, 'wp_loaded'));
		//}

		/**
		 * Intelligence hooks
		 */

		// Register hook_intel_system_info()
		add_filter('intel_system_info', array( $this, 'intel_system_info' ));

		// Register hook_intel_menu()
		add_filter('intel_menu_info', array( $this, 'intel_menu_info' ));

		// Register hook_intel_demo_pages()
		add_filter('intel_demo_posts', array( $this, 'intel_demo_posts' ));

		// Register hook_intel_form_type_info()
		add_filter('intel_form_type_info', array( $this, 'intel_form_type_info'));

		// Register hook_intel_form_type_FORM_TYPE_UN_form_info()
		add_filter('intel_form_type_' . $this->form_type_un . '_form_info', array( $this, 'intel_form_type_form_info' ));

		// Register hook_intel_url_urn_resolver()
		add_filter('intel_url_urn_resolver', array( $this, 'intel_url_urn_resolver'));

		// Register hook_intel_test_url_parsing_alter()
		add_filter('intel_test_url_parsing_alter', array( $this, 'intel_test_url_parsing_alter'));

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

		wp_enqueue_style('intel-gf-settings', $this->url . 'css/gf-intel-gf-settings.css');

		$items = array();

		$items[] = '<div class="wrap">';
		$items[] = '<h1>' . esc_html__( 'Intelligence Settings', $this->plugin_un ) . '</h1>';
		$items[] = '</div>';

		$connect_desc = __('Not connected.', $this->plugin_un);
		$connect_desc .= ' ' . sprintf(
				__( ' %sSetup Intelligence%s', $this->plugin_un ),
				'<a href="/wp-admin/admin.php?page=intel_admin&plugin=' . $this->plugin_un . '&q=admin/config/intel/settings/setup/' . $this->plugin_un . '" class="button">', '</a>'
			);
		if($this->is_intel_installed()) {
			$connect_desc = __('Connected');
		}

		$items[] = '<table class="form-table">';
		$items[] = '<tbody>';
		$items[] = '<tr>';
		$items[] = '<th>' . esc_html__( 'Intelligence API', $this->plugin_un ) . '</th>';
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
			$value .= ' ' . Intel_Df::l(esc_html__('Change', $this->plugin_un), 'admin/config/intel/settings/form/default_tracking', $l_options);
			$items[] = '<tr>';
			$items[] = '<th>' . esc_html__( 'Default submission event/goal', $this->plugin_un ) . '</th>';
			$items[] = '<td>' . $value . '</td>';
			$items[] = '</tr>';

			$default_value = get_option('intel_form_track_submission_value_default', '');
			$items[] = '<tr>';
			$items[] = '<th>' . esc_html__( 'Default submission value', $this->plugin_un ) . '</th>';
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

		wp_enqueue_script('gf_intel_form_settings', $this->url . 'js/gf-intel-gform-settings.js');

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
Intel_Df::watchdog('gfi_pre_submission form', print_r($form, 1));
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
Intel_Df::watchdog('custom_confirmation_message() confirmation', print_r($confirmation, 1));
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
Intel_Df::watchdog('gf_submission vars', print_r($vars, 1));
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



	/**
	 * Implements hook_wp_loaded()
	 *
	 * Used to check if Intel is not loaded and include setup process if needed.
	 * Alternatively this check can be done in hook_admin_menu() if the plugin
	 * implements hook_admin_menu()
	 */
	public function wp_loaded() {
		// check if Intel is installed, add setup processing if not
		if (!$this->is_intel_installed()) {
			require_once( $this->dir . 'gf-intel.setup.inc' );
		}
	}

	/**
	 * Returns if Intelligence plugin is installed and setup.
	 *
	 * @param string $level
	 * @return mixed
	 *
	 * @see intel_is_installed()
	 */
	public function is_intel_installed($level = 'min') {
		if (!is_callable('intel_is_installed')) {
			return FALSE;
		}
		return intel_is_installed($level);
	}

	/**
	 * Provides plugin data for hook_intel_system_info()
	 *
	 * @param array $info
	 * @return array
	 *
	 * @see Intel::
	 */
	public function intel_plugin_info($info = array()) {
		$info = array(
			// The unique name for this plugin
			'plugin_un' => $this->plugin_un,
			// Title of the plugin
			'plugin_title' => __('Gravity Forms Google Analytics Intelligence', $this->plugin_un),
			// Shorter version of title used when reduced characters are desired
			'plugin_title_short' => __('Gravity Forms GA Intelligence', $this->plugin_un),
			// Main plugin file
			'plugin_file' => 'gf-intel.php', // Main plugin file
			// The server path to the plugin files directory
			'plugin_dir' => $this->dir,
			// The browser path to the plugin files directory
			'plugin_url' => $this->url,
			// The install file for the plugin if different than [plugin_un].install
			// Used to auto discover database updates
			'update_file' => 'gf-intel.install', // default [plugin_un].install
			// If this plugin extends a plugin other than Intelligence, include that
			// plugin's info in 'extends_' properties
			// The extends plugin unique name
			'extends_plugin_un' => 'gravityforms',
			// the extends plugin text domain key
			'extends_plugin_text_domain' => 'gravityforms',
			// the extends plugin title
			'extends_plugin_title' => __('Gravity Forms', 'gravityforms'),
		);
		return $info;
	}

	/**
	 * Implements hook_intel_system_info()
	 *
	 * Registers plugin with intel_system
	 *
	 * @param array $info
	 * @return array
	 */
	public function intel_system_info($info = array()) {
		// array of plugin info indexed by plugin_un
		$info[$this->plugin_un] = $this->intel_plugin_info();
		return $info;
	}

	/**
	 * Implements hook_intel_menu_info()
	 *
	 * @param array $items
	 * @return array
	 */
	public function intel_menu_info($items = array()) {
		// route for Admin > Intelligence > Settings > Setup > Ninja Forms
		$items['admin/config/intel/settings/setup/' . $this->plugin_un] = array(
			'title' => 'Setup',
			'description' => $this->plugin_info['plugin_title'] . ' ' . __('initial plugin setup', $this->plugin_un),
			'page callback' => $this->plugin_un . '_admin_setup_page',
			'access callback' => 'user_access',
			'access arguments' => array('admin intel'),
			'type' => Intel_Df::MENU_LOCAL_TASK,
			'file' => 'admin/' . $this->plugin_un . '.admin_setup.inc',
			'file path' => $this->dir,
		);
		// rout for Admin > Intelligence > Help > Demo > Ninja Forms
		$items['admin/help/demo/' . $this->plugin_un] = array(
			'title' => $this->plugin_info['extends_plugin_title'],
			'page callback' => array($this, 'intel_admin_help_demo_page'),
			'access callback' => 'user_access',
			'access arguments' => array('admin intel'),
			'intel_install_access' => 'min',
			'type' => Intel_Df::MENU_LOCAL_TASK,
			'weight' => 10,
		);
		return $items;
	}

	/*
   * Provides an Intelligence > Help > Demo > Example page
   */
	public function intel_admin_help_demo_page() {
		$output = '';

		$demo_mode = get_option('intel_demo_mode', 0);

		$output .= '<div class="card">';
		$output .= '<div class="card-block clearfix">';

		$output .= '<p class="lead">';
		$output .= Intel_Df::t('Try out your Ninja Forms tracking!');
		//$output .= ' ' . Intel_Df::t('This tutorial will walk you through the essentials of extending Google Analytics using Intelligence to create results oriented analytics.');
		$output .= '</p>';

		/*
    $l_options = Intel_Df::l_options_add_class('btn btn-info');
    $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path(), $l_options);
    $output .= Intel_Df::l( Intel_Df::t('Demo settings'), 'admin/config/intel/settings/general/demo', $l_options) . '<br><br>';
    */

		$output .= '<div class="row">';
		$output .= '<div class="col-md-6">';
		$output .= '<p>';
		$output .= '<h3>' . Intel_Df::t('First') . '</h3>';
		$output .= __('Launch Google Analytics to see conversions in real-time:', $this->plugin_un);
		$output .= '</p>';

		$output .= '<div>';
		$l_options = Intel_Df::l_options_add_target('ga');
		$l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
		$url = 	$url = intel_get_ga_report_url('rt_goal');
		$output .= Intel_Df::l( Intel_Df::t('View real-time conversion goals'), $url, $l_options);

		$output .= '<br>';

		$l_options = Intel_Df::l_options_add_target('ga');
		$l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
		$url = 	$url = intel_get_ga_report_url('rt_event');
		$output .= Intel_Df::l( Intel_Df::t('View real-time events'), $url, $l_options);
		$output .= '</div>';
		$output .= '</div>'; // end col-x-6

		$output .= '<div class="col-md-6">';

		$output .= '<p>';
		$output .= '<h3>' . Intel_Df::t('Next') . '</h3>';
		$output .= __('Pick one of your forms to test:', $this->plugin_un);
		$output .= '</p>';

		$forms = $this->intel_form_type_form_info();

		$l_options = Intel_Df::l_options_add_target($this->plugin_un . '_demo');
		$l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
		$l_options['query'] = array();
		$output .= '<div>';
		foreach ($forms as $form) {
			$l_options['query']['fid'] = $form['id'];
			$output .= Intel_Df::l( __('Try', $this->plugin_un) . ': ' . $form['title'], 'intelligence/demo/' . $this->plugin_un, $l_options);
			$output .= '<br>';
		}
		$output .= '</div>';

		$output .= '</div>'; // end col-x-6
		$output .= '</div>'; // end row

		$output .= '</div>'; // end card-block
		$output .= '</div>'; // end card

		return $output;
	}

	/**
	 * Implements hook_intel_demo_pages()
	 *
	 * Adds a demo page to test tracking for this plugin.
	 *
	 * @param array $posts
	 * @return array
	 */
	public function intel_demo_posts($posts = array()) {
		$id = -1 * (count($posts) + 1);

		$forms = $this->intel_form_type_form_info();

		$content = '';
		if (!empty($_GET['fid']) && !empty($forms[$_GET['fid']])) {
			$form = $forms[$_GET['fid']];
			$content .= '<br>';
			$content .= '[ninja_form id="' . $form['id'] . '"]';
		}
		elseif (!empty($forms)) {
			$form = array_shift($forms);
			$content .= '<br>';
			$content .= '[ninja_form id="' . $form['id'] . '"]';
		}
		else {
			$content = __('No Ninja forms were found', $this->plugin_un);
		}
		$posts["$id"] = array(
			'ID' => $id,
			'post_type' => 'page',
			'post_title' => __('Demo') . ' ' . $this->plugin_info['extends_plugin_title'],
			'post_content' => $content,
			'intel_demo' => array(
				'url' => 'intelligence/demo/' . $this->plugin_un,
			),
		);

		return $posts;
	}

	/**
	 * Implements hook_form_type_info()
	 *
	 * Registers a form type provided by or connected to this plugin. Only needed
	 * if the plugin provides a form such as Contact Form 7, Ninja Forms or Gravity Forms.
	 *
	 * Optional: Remove if plugin does not provide a form type to be tracked
	 *
	 * @param array $info
	 * @return array
	 */
	public function intel_form_type_info($info = array()) {
		$info[$this->form_type_un] = array(
			// A machine name to uniquely identify the form type provided by this plugin.
			'un' => $this->form_type_un,
			// Human readable name of the form type provided by this plugin.
			'title' => __( 'Gravity Form', $this->plugin_info['extends_plugin_text_domain'] ),
			// The plugin unique name for this plugin
			'plugin_un' => $this->plugin_un,
			// form tracking features addon supports
			'supports' => array(
				'track_submission' => 1,
				'track_submission_value' => 1,
			),
			// Callback to get data for form submissions
			'submission_data_callback' => array($this, 'intel_form_type_submission_data'),
		);
		return $info;
	}

	/**
	 * Implements hook_intel_form_type_FORM_TYPE_UN_form_data()
	 */
	function intel_form_type_form_info($data = NULL, $options = array()) {
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

	/*
   * Implements hook_intel_form_type_form_setup()
   */
	function intel_form_type_submission_data($fid, $fsid) {
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
	 * Implements hook_intel_url_urn_resolver()
	 */
	function intel_url_urn_resolver($vars) {
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
	function intel_test_url_parsing_alter($urls) {
		$urls[] = ':gravityform:1';
		$urls[] = 'urn::gravityform:1';
		$urls[] = ':gravityform:1:1';
		$urls[] = 'urn::gravityform:1:1';
		return $urls;
	}

}