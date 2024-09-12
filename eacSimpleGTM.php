<?php
namespace EarthAsylumConsulting;

/**
 * Add {eac}SimpleGTM extension to {eac}Doojigger
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger Extensions\{eac}SimpleGTM
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.earthasylum.com>
 *
 * @wordpress-plugin
 * Plugin Name:			{eac}SimpleGTM
 * Description:			{eac}SimpleGTM Adds Google Tag Manager (gtm) or Google Analytics (gtag) to WordPress
 * Version:				1.0.4
 * Requires at least:	5.8
 * Tested up to:		6.6
 * Requires PHP:		7.4
 * Plugin URI:			https://eacdoojigger.earthasylum.com/eacsimplegtm/
 * Author:				EarthAsylum Consulting
 * Author URI:			http://www.earthasylum.com
 * License:				GPLv3 or later
 * License URI:			https://www.gnu.org/licenses/gpl.html
 */

if (!defined('EAC_DOOJIGGER_VERSION'))
{
	\add_action( 'all_admin_notices', function()
		{
			echo '<div class="notice notice-error is-dismissible"><p>{eac}SimpleGTM requires installation & activation of '.
				 '<a href="https://eacdoojigger.earthasylum.com/eacdoojigger" target="_blank">{eac}Doojigger</a>.</p></div>';
		}
	);
	return;
}

class eacSimpleGTM
{
	/**
	 * constructor method
	 *
	 * @return	void
	 */
	public function __construct()
	{
		/*
		 * declare compliance with WP Consent API
		 */
		$plugin = plugin_basename( __FILE__ );
		add_filter( "wp_consent_api_registered_{$plugin}", '__return_true' );

		/*
		 * {pluginname}_load_extensions - get the extensions directory to load
		 *
		 * @param	array	$extensionDirectories - array of [plugin_slug => plugin_directory]
		 * @return	array	updated $extensionDirectories
		 */
		add_filter( 'eacDoojigger_load_extensions', function($extensionDirectories)
			{
				/*
    			 * Enable update notice (self hosted or wp hosted)
    			 */
				eacDoojigger::loadPluginUpdater(__FILE__,'wp');

				/*
    			 * Add links on plugins page
    			 */
				add_filter( (is_network_admin() ? 'network_admin_' : '').'plugin_action_links_' . plugin_basename( __FILE__ ),
					function($pluginLinks, $pluginFile, $pluginData) {
						return array_merge(
							[
								'settings'		=> eacDoojigger::getSettingsLink($pluginData,'tracking'),
								'documentation'	=> eacDoojigger::getDocumentationLink($pluginData),
								'support'		=> eacDoojigger::getSupportLink($pluginData),
							],
							$pluginLinks
						);
					},20,3
				);

				/*
    			 * Add our extension to load
    			 */
				$extensionDirectories[ plugin_basename( __FILE__ ) ] = [plugin_dir_path( __FILE__ ).'/Extensions'];
				return $extensionDirectories;
			}
		);
	}
}
new \EarthAsylumConsulting\eacSimpleGTM();
?>
