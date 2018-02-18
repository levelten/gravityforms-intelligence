<?php

/**
 * Included to assist in initial setup of plugin
 *
 * @link       getlevelten.com/blog/tom
 * @since      1.0.8
 *
 * @package    Intel
 */

if (!is_callable('intel_setup')) {
	include_once gf_intel()->dir . 'intel_com/intel.setup.inc';
}

class GF_Intel_Setup extends Intel_Setup {

	public $plugin_un = 'gf_intel';

}

function gf_intel_setup() {
	return GF_Intel_Setup::instance();
}
gf_intel_setup();
