=== Gravity Forms Google Analytics Intelligence ===
Contributors: tomdude
Donate link: getlevelten.com/blog/tom
Tags: analytics, form, google analytics, gravity forms, marketing, metrics, stats, tracking, web form
Requires at least: 4.5
Tested up to: 4.9.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automates Gravity Forms Google Analytics tracking and visitor intelligence gathering.

== Description ==

### Best Gravity Forms Google Analytics Goal Tracking Plugin

Google Analytics goal tracking for Gravity Forms made easy. 

If you want to do Google Analytics right, tracking form submission as conversion goals is a must. Tracking conversion goals is vital to truly understanding the value of your traffic sources, content and visitors. Yet less than one in ten sites implement this powerful feature.

Why? Because it's a pain, particularly if you are not a techie or analytics guru. 

We believe better data leads to better user experiences, content and marketing. We believe everybody should have the best data available. It was just a matter of making simpler.

Introducing the Gravity Forms Google Analytics Intelligence plugin:
https://www.youtube.com/watch?v=6vFl4hbPkfQ

#### Key Features

* Track any Gravity Form submission as Google Analytics goals or events
* Accurately tracks both redirected and non-redirected forms
* Create and manage Google Analytics goals directly in WordPress
* Set a default goal that triggers on all Gravity Form submissions
* Customize goals per form
* Customize goal values per form
* No coding required
* No advanced Google Analytics skills needed
* No Google Tag Manager setup needed
* 5 minute installation

Works as a standalone Google Analytics plugin or with other popular Google Analytics plugins including:

- [Google Analytics Dashboard for WP](https://wordpress.org/plugins/google-analytics-dashboard-for-wp/) by Alin Marcu (recommended)
- [Google Analytics by MonsterInsights](https://wordpress.org/plugins/google-analytics-for-wordpress/)
- [Google Analytics](https://wordpress.org/plugins/googleanalytics/) by ShareThis
- [DuracellTomi's Google Tag Manager for WordPress](https://wordpress.org/plugins/duracelltomi-google-tag-manager/)

#### Enhanced Google Analytics

Intelligence is a framework for enabling websites to generate actionable, results oriented Google Analytics. This plugin enables you to easily trigger analytics goals and events for Gravity Form submissions. The Pro version also enables you to progressively build contact profiles of your site visitors based on form submissions and 3rd party data.

To learn more about Intelligence for WordPress visit [intelligencewp.com](https://intelligencewp.com)

== Installation ==

=== Install Files Within WordPress ===

1. Visit 'Admin Menu > Plugins > Add New'
1. Search for 'Gravity Forms Intelligence'
1. Activate Gravity Forms Intelligence from your Plugins page.
1. Go to "plugin setup" below.

=== Install Files Manually ===

1. Download the [Gravity Forms Intelligence plugin](https://wordpress.org/plugins/gf-intelligence) and the [Intelligence plugin](https://wordpress.org/plugins/intelligence)
1. Add `gf-intelligence` and `intelligence` folders to the `/wp-content/plugins/` directory
1. Activate the Gravity Forms Intelligence plugin through the 'Plugins' menu in WordPress
1. Go to "plugin setup" below.

=== Plugin Setup ===

1. You should see a notice at the top of the Plugins page. Click "Setup plugin" to launch the setup wizard. You can also launch the wizard from the Gravity Forms settings, 'Admin Menu > Forms > Intelligence'.
1. Go through the setup wizard and set up the plugin for your site.
1. You're done! 

=== Changing default tracking ===
1. Go to 'Admin Menu > Forms > Settings'. Click the "Intelligence" tab. Next to the "Default submission event/goal" value, click the "Change" button.
1. One the Default form tracking page, use the "Submission event/goal" dropdown to select an existing goal. If you want to create a new goal, click the "Add Goal" link.
1. When done, click "Save"

=== Custom Form Tracking ===
1. To customize the tracking of a form from the default tracking, go to 'Admin Menu > Forms'.
1. Click to edit a form you want to customize and then click the "Settings > Intelligence" in the tab menu.
1. On the "Intelligence Settings" page, use the "Submission event/goal" drop down to select how you want to track the form. 
1. Input the "Submission value" if you want to set a custom goal value for the form.
1. Click "Update Settings" when done.

=== Popular settings ===
Track and manage Intelligence goals and events in existing Google Analytics tracking ID:

1. Go to "Admin Menu > Intelligence > Settings"
1. Under "Tracking settings" fieldset, open the "Base Google Analytics profile" fieldset
1. If base profile is not set, click "Set profile" link to set your existing tracking ID
1. Check "Track Intelligence events & goals in base profile"
1. Check "Sync Intelligence goals configuration to base profile"
1. Click "Save settings" button at bottom of page

Embed Google Analytics tracking code if site does not already embed tracking code through some other method.

1. Go to "Admin Menu > Intelligence > Settings"
1. Under "Tracking settings" fieldset, open the "Advanced" fieldset.
1. For "Include Google Analytics tracking code" select the "Analytics" option
1. Click "Save settings" button at bottom of page

== Screen Shots ==

1. Select a goal and goal value to track on different form submissions
2. Easily add goals to your in Google Analytics
3. Manage Google Analytics goals without leaving WordPress
4. Automatically trigger goals on form submission
5. Set a default goal to make sure no form submissions are missed

== Changelog ==

= 1.0.0 =
* Initial version

= 1.0.1 =
* Submission redirect support
* Cache busting support

= 1.0.2 =
* Support for intel_system API
* Support for intel_form_type API

= 1.0.5 =
* Added form testing/demo

= 1.0.6 =
* Support php file extension migration

= 1.0.7 =
* Improved documentation

== Upgrade Notice ==

= 1.0.0 =
No notices