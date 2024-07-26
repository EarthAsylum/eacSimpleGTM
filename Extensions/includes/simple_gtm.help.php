<?php
/**
 * Extension:  {eac}SimpleGTM Add Google Tag Manager (GTM) or Google Analytics (GA4) to WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0726.1
 *
 * included for admin_options_help() method
 */

defined( 'ABSPATH' ) or exit;

ob_start();
?>
	<p>The {eac}SimpleGTM extension installs the Google Tag Manager (GTM) or Google Analytics (GA4) script
	(based on the tag id entered), sets default consent options, and enables tracking of
	page views, site searches, content views, and, when using WooCommerce, e-commerce actions.</p>

	<p>* You are responsible for the proper configuration of your Google Analytics property and/or Google Tag Manager settings
	as well as proper notification and consent from your users.</p>

	<details><summary>More...</summary>

	<p>The selected consent attributes (advanced mode) are set to 'granted' before other tags are loaded or actions taken.
	This does not make your site GDPR/CCPA compliant and should not be used in place of a Consent Management Platform (CMP).
	See Google's <a href='https://support.google.com/analytics/answer/12329599' target='_blank'>Introduction to user consent management</a>.</p>

	<p>Consent options are passed when GTM or GA4 are initialized.
	When consent attributes `ad_storage` and/or `analytics_storage` are set to 'denied', enabling <em>URL Passthrough</em> will pass information about ad clicks or analytics through URL parameters.
	<em>Google Signals</em> allows session data that Google associates with users who have signed in to their Google accounts, and who have turned on Ads Personalization.
	<em>Redact Ads Data</em>, when ad_storage is denied, ad click identifiers will be redacted.</p>

	<p>Custom events are simple events with limited data that use Google's
	<a href='https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtm' target='_blank'>recommended names and attributes</a>.
	Page Views are typically included in your tag container,
	other tags &amp; triggers may need to be configured in
	<a href='https://tagmanager.google.com/' target='_blank'>Google Tag Manager.</a></p>
	</details>
<?php
$content = ob_get_clean();

$this->addPluginHelpTab('Tracking',$content,['Google Tag Manager','open']);

$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-google'></span>{eac}SimpleGTM",
	"https://eacdoojigger.earthasylum.com/eacsimplegtm/",
	"{eac}SimpleGTM Extension Plugin"
);
