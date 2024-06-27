<?php
/**
 * Extension:  {eac}SimpleGTM Add Google Tag Manager (GTM) or Google Analytics (GA4) to WordPress
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0617.1
 *
 * included for admin_options_settings() method
 */

defined( 'ABSPATH' ) or exit;

$events = [
	'Page Views'	=> 'page-view',
	'Site Search'	=> 'site-search',
	'View Content (categories, terms, tags)'	=> 'view-content',
	'View Archive (archives, authors, dates)'	=> 'view-archive',
];
if ( $this->use_ecommerce ) {
	$events['E-Commerce (items, cart, checkout, purchase)'] = 'ecommerce';
	$events['Cart Actions (add, remove, update, coupon)'] 	= 'cart-actions';
}
$events['Page Not Found (404) Errors'] = 'track-404';

$this->delete_option('gtag_url_passthru');
$this->delete_option('gtag_load_on');

/* register this extension with group name on default tab, and settings fields */
$this->registerExtensionOptions( $this->className,
	[
		'gtag_container_id'		=> 	array(
				'type'			=> 	'text',
				'label'			=> 	'Google Tag Id',
				'info'			=> 	'The Google Tag Manager container id (GTM-XXXXXXX) or the Google Analytics measurement id (G-XXXXXXXXXX) for this site.',
				'help'			=>	"[info] Enter just 'GTM' or 'GA4' to only configure the dataLayer (assuming the script is loaded elsewere). ".
									"Leave blank to only use other features (i.e. events).",
				'attributes'	=> 	['placeholder'=>'GTM-XXXXXXX | G-XXXXXXXXXX'],
				'validate'		=>
						function($value){
							if (empty($value)) return $value;
							$value = strtoupper($value);
							if (preg_match("/^(GTM|GA4|\w{1,3}-\w{5,12})$/",$value)) return $value;
							return false;
						},
		),
		'gtag_consent'			=> 	array(
				'type'			=> 	'checkbox',
				'label'			=> 	'Consent Default',
				'options'		=> 	self::CONSENT_ATTRIBUTES,
				'default'		=>	[
						'analytics_storage',
						'functionality_storage',
						'security_storage',
				],
				'info'			=>	"Select the consent attributes that should be 'granted' by default. ".
									"This is typically not necessary and not recommended when using a CMP.",
				'help'			=> "[info] When the Google Tag Manager first loads, ".
									"the selected attributes are set to 'granted' before other tags are loaded or actions taken.",
			//	'style'			=> 	'display:inline-block;width:15em;',
				'style'			=> 	'display:block;',
				'advanced'		=>	true,
		),
		'gtag_options'		=> 	array(
				'type'			=> 	'checkbox',
				'label'			=> 	'Consent Options',
				'options'		=> 	[
					'URL Passthrough'		=> 'url_passthrough',
					'Allow Google Signals'	=> 'allow_google_signals',
					'Redact Ads Data'		=> 'ads_data_redaction',
				],
				'info'			=> 	'Consent configuration settings passed when initializing GTM or GA4. ',
				'help'			=>	'[info] '.
									'URL Passthrough passes information about ad clicks through URL parameters. '.
									'Google Signals allows session data that Google associates with users. '.
									'Redact Ads Data, when ad_storage is denied, ad click identifiers will be redacted.',
				'style'			=> 	'display:block;',
				'advanced'		=>	true,
		),
		'gtag_load_when'		=> 	array(
				'type'			=> 	'select',
				'label'			=> 	'Send Event Tags',
				'options'		=> 	[
					'In Page Footer'	=> 'inline',
					'On Document Load'	=> 'DOMContentLoaded',	// eveent name
					'On Window Ready'	=> 'load',				// eveent name
				],
				'default'		=> 	'inline',
				'info'			=> 	'When to send events to the browser; in-line, when the document has loaded or when the window is rendered.',
				'help'			=>	'[info] '.
									"'In Page Footer' sends events in the page footer as the page loads. ".
									"'On Document Load' sends events as soon as the document is loaded but before the browser renders the page. ".
									"'On Window Ready' waits for the page to be rendered (this may be helpful with late-loading CMP plugins).",
				'advanced'		=>	true,
		),
		'gtag_events'			=> array(
				'type'			=> 	'checkbox',
				'label'			=> 	'Events To Track',
				'options'		=>	$events,
				'default'		=>	['site-search','view-content','view-archive'],
				'info'			=> 	"Select optional events to be sent to Google Analytics ".
									"(page views are typically included in your tag container and may not be needed).",
				'help'			=>	"[info] ".
									"View Content includes categories, terms, and tags. ".
									"View Archive includes archives, authors, and dates. ".
									(($this->use_ecommerce)
										? "E-Commerce includes items, cart, checkout and purchase. ".
											"Cart Actions include add, remove, update and coupon"
										: ""
									),
				'style'			=> 	'display:block;',
		),
	]
);
