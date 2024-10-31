<?php
/*
	Plugin Name: Never Outdated!
	Plugin URI: http://www.neveroutdated.com
	Description: Never let your Wordpress be outdated, receive an email whenever a new version of your favorite CMS is available. In order to use this plugin, you must freely register on the related website: <a href="http://www.neveroutdated.com">NeverOutdated.com</a>.
	Version: 1.0.2
	Author: Tesial
	Author URI: http://www.tesial.be
	License: GPLv2
*/

/*
	Copyright Tesial (C) 2012 Renaud Laloux (Contact us: http://goo.gl/piab6)
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

$uri = $_SERVER['REQUEST_URI'];
if (!strpos($uri, 'plugin-editor.php') && !strpos($uri, 'update.php')) {
	// Check that we are not trying to edit the file
	$uri = basename($_SERVER['REQUEST_URI'], '.php');
	$uri = strrpos($uri, '.') ? substr($uri, 0, strrpos($uri, '.')) : $uri;
}

if ($uri === basename(__FILE__, '.php')) {
	// File is accessed directly, not edited nor updated
	header('Content-type: application/json');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: 0');
	
	$out = null;
		
	if (!isset($_GET['skey'])) {
		$out = array(
			'status' => 'error',
			'cause'  => 'missing secure key'
		);
	} else {
		$secureKey = $_GET['skey'];
		
		// Include wordpress framework
		$wp_base_path = substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), 'wp-content'));
		require_once $wp_base_path.'wp-load.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if (!is_plugin_active('never-outdated/wp-never-outdated.php')) {
			// Plugin is not activated 
			$out = array(
				'status' => 'error',
				'cause'  => 'plugin is inactive',
			);
		} else if (empty($secureKey)) {
			// secure key is empty or missing
			$out = array(
				'status' => 'error',
				'cause'  => 'missing secure key',
			);
		} else {
			// Validate the secure key
			$plugin_wp_never_outdated_settings = get_option('plugin_wp_never_outdated_settings');
			
			if (empty($plugin_wp_never_outdated_settings['secure_key'])) {
				$out = array(
					'status' => 'error',
					'cause'  => 'secure key not configured',
				);
			} else if ($secureKey == $plugin_wp_never_outdated_settings['secure_key']) {
				require_once ABSPATH . WPINC . '/version.php';
				
				// Get wordpress's mysql version
				global $wpdb;
				if (method_exists( $wpdb, 'db_version' )) {
					$mysql_version = preg_replace('/[^0-9.].*/', '', $wpdb->db_version());
				} else {
					$mysql_version = 'N/A';
				}
				
				// Explore plugin's version
				$plugins = array();
				foreach(get_plugins () as $plugin_file => $plugin_data) {
					$plugins[$plugin_data['Title']] = $plugin_data['Version'];
				}
				
				$out = array(
					'status'		=> 'success',
					'wp-version'    => $wp_version,
					'wp-db-version' => $wp_db_version,
					'sql-version'   => $mysql_version,
					'php-version'   => phpversion(),
					'plugins'		=> $plugins
				);
			} else {
				$out = array (
					'status' => 'error',
					'cause'	 => 'secure key is wrong'
				);
			}
		}
	}
	
	// Ensure that no other plugin can output anything else
	echo json_encode($out);
	exit;
	
} else {
	// Common behavior, define the class of the plugin and use it
	if (!class_exists('WPNeverOutdated')) {
		class WPNeverOutdated {
			
			function __construct() {
				// i18n
				add_action('plugins_loaded', array(&$this, 'actionPluginInitialize'));
				
				// Activation/Deactivation hook
				register_activation_hook(__FILE__, array(&$this, 'hookActivation'));
				register_deactivation_hook(__FILE__, array(&$this, 'hookDeactivation'));

				// Add link beneath the plugin entry in the 'Plugin' page
				add_filter('plugin_action_links', array(&$this, 'actionPluginLinks'), 10, 2);
				// Add link into 'Settings' menu
				add_action('admin_menu', array(&$this, 'actionAdminMenu'));
				// Add the form in the settings
				add_action('admin_init', array(&$this, 'actionAdminInit'));
			}
			
			function actionPluginInitialize() {
				error_log('Loading i18n');
				$plugin_dir = basename(dirname(__FILE__)).'/language';
				load_plugin_textdomain('wp-never-outdated', false, $plugin_dir);
			}
			
			function actionPluginLinks($links, $file) {
				$plugin_file = basename(__FILE__);
				if (basename($file) == $plugin_file) {
					$settings_link = '<a href="options-general.php?page='.$plugin_file.'">' . __('Settings', 'wp-never-outdated') . '</a>';
					array_unshift($links, $settings_link);
				}
				
				return $links;
			}
			
			function actionAdminMenu() {
				$this->pagehook = add_options_page(
					__('WP Never Outdated Options', 'wp-never-outdated'), 
					__('WP Never Outdated ', 'wp-never-outdated'), 
					'manage_options', basename(__FILE__), array(&$this, 'showAdminScreen'));
			}
			
			function actionAdminInit() {
				// Add the form validation on submit
				register_setting('wp-never-outdated-settings', 'plugin_wp_never_outdated_settings', array(&$this, '_formValidate'));
	
				// Add a section to the settings
				add_settings_section('general_settings', 
					__('General settings', 'wp-never-outdated'), 
					array($this, 'general_section_text'), __FILE__);
				
				// Add a field to the settings
				add_settings_field('secure_key', 
					__('Secure Key', 'wp-never-outdated'), 
					array($this, 'formTextInput'), __FILE__, 
					'general_settings', array('dbfield' => 'secure_key', 'section' => 'general'));
			}
			
			function general_section_text($args) {
				echo '<p></p>';
			}
			
			function _formValidate($inputs) {
				error_log('Calling _formValidate: ');
				$plugin_wp_never_outdated_settings = get_option('plugin_wp_never_outdated_settings');
	
				// Array containing the validity and error message for each input
				$validInputs = array(
					'secure_key' => array(
										'valid' => array_keys($this->_formValidDefaults(array('dbfield' => 'secure_key'))),
										'default' => '',
										'errormsg' => __('General settings', 'wp-never-outdated') . ' > ' 
													. __('Secure Key', 'wp-never-outdated') . ': ' 
													. __('The key may only contains caracter a-z, A-Z or numeric', 'wp-never-outdated'),
									),
				);

				// Check the validity of the input
				if(strlen($inputs['secure_key']) > 10 || !preg_match('#[a-zA-Z0-9]{1,10}#', $inputs['secure_key'])) {
					$plugin_wp_never_outdated_settings['secure_key'] = $validInputs['secure_key']['default'];
					add_settings_error('plugin_wp_never_outdated_settings', 'settings_updated', $validInputs['secure_key']['errormsg']);
				} else {
					$plugin_wp_never_outdated_settings['secure_key'] = $inputs['secure_key'];
				}

				return $plugin_wp_never_outdated_settings;
			}
			
			function _formValidDefaults($args) {
				$values	= false;
	
				switch($args['dbfield']) {
					case 'secure_key': $values = array('alphanum' => 'alphanum'); break;
				}
	
				return $values;
			}
			
			function _formInfoText($args) {
			switch($args['dbfield']) {
					case 'secure_key': $infotext = __('Key obtained while registering on CMS Never Outdated', 'wp-never-outdated'); break;
				}
	
				return $infotext;
			}
			
			function formTextInput($args) {
				// Get current options from the database.
				extract(get_option('plugin_wp_never_outdated_settings'));
	
				$infotext	= $this->_formInfoText($args);
	
				if (array_key_exists('dbfield', $args) && isset($infotext)) {
					echo '<input name="plugin_wp_never_outdated_settings[' . $args['dbfield'] . ']" size="30" type="text" value="' . $$args['dbfield'] . '" /><br />' . $infotext;
				}
			}
			
			/**
			 * Create the form with our options.
			 */
			function _optionsForm() {
				// check user has access to change settings for this plugin.
				if (!current_user_can('manage_options')) {
					wp_die( __('You do not have sufficient permissions to access this page.', 'wp-never-outdated') );
				}
	
				// Get current options from the database.
				extract(get_option('plugin_wp_never_outdated_settings'));
				
				?>
				<div class="wrap">
					<h2><?php echo __('Never Outdated', 'wp-never-outdated'); ?></h2>
					
					<form method="post" action="options.php">
						<?php settings_fields('wp-never-outdated-settings'); ?>
						<?php do_settings_sections(__FILE__); ?>
						<p class="submit">
							<input name="wp_never_outdated_update" value="<?php _e('Save Changes', 'wp-never-outdated'); ?>" type="submit" class="button-primary" />
						</p>
					</form>
				</div>
				<?php 
			}
			
			/**
			 * Hook for add_option_page.
			 * Show the form containing our options.
			 */
			public function showAdminScreen() {
				return $this->_optionsForm();
			}

			/**
			 * Activate the setting, check if there is options 
			 * defined or create them otherwise.
			 */
			function hookActivation() {
				global $wpdb, $wp_roles, $wp_version;

				// Check for capability
				if (!current_user_can('activate_plugins'))
					return;
	
				// Get any settings from the database
				$options = get_option('plugin_wp_never_outdated_settings');
				if (empty($options)) {
					$options = array();
				}
	
				// Check if any missing settings
				$options = array_merge($options, array_diff_key(array( 'secure_key' => '' ), $options));
	
				// Install settings into the database
				update_option('plugin_wp_never_outdated_settings', $options);
			}
			
			/**
			 * De-activate the plugin, remove the related settings.
			 */
			function hookDeactivation() {
				global $wpdb, $wp_roles, $wp_version;
				
				//delete_option('plugin_wp_never_outdated_settings');
			}
		}
	}
	
	if (class_exists('WPNeverOutdated')) {
		error_log('Instanciating WPNeverOutdated !');
		$WPNeverOutdated = new WPNeverOutdated();
	}
}
?>