=== {eac}Doojigger Simple GTM Extension for WordPress ===
Plugin URI:         https://eacdoojigger.earthasylum.com/eacsimplegtm/
Author:             [EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag:         1.0.3
Last Updated:       17-Aug-2024
Requires at least:  5.8
Tested up to:       6.6
Requires PHP:       7.4
Requires EAC:       2.6
Contributors:       kevinburkholder
License:            GPLv3 or later
License URI:        https://www.gnu.org/licenses/gpl.html
Tags:               google tag manager, google analytics, tracking, analytics, {eac}Doojigger
WordPress URI:      https://wordpress.org/plugins/eacsimplegtm
GitHub URI:         https://github.com/EarthAsylum/eacsimplegtm

{eac}eacSimpleGTM installs and configures the Google Tag Manager (GTM) or Google Analytics (GA4) script with optional tracking events.

== Description ==

**{eac}Doojigger SimpleGTM** is an [{eac}Doojigger](https://eacDoojigger.earthasylum.com/) extension that installs the Google Tag Manager (GTM) or Google Analytics (GA4) script, sets default consent options, and enables tracking of page views, site searches, content views, and, when using [WooCommerce](https://woocommerce.com/), e-commerce actions.

_{eac}SimpleGTM_ is a very light-weight and simple extension that uses PHP to add small JavaScript snippets to your web pages for configuring and tracking with Google Analytics. Many web site owners will find this more than sufficient over more complicated (and over-bearing) alternatives.

= Default Consent (advanced) =

The selected consent attributes are set to 'granted' before other tags are loaded or actions taken. This does not make your site GDPR/CCPA compliant and should not be used in place of a Consent Management Platform (CMP). See Google's [Introduction to user consent](https://support.google.com/analytics/answer/12329599). This is typically not necessary and not recommended when using a CMP.

If no consent attributes are selected than the "consent default" configuration is not sent, otherwise, unselected attributes are set to "denied".

Default consent settings passed when initializing GTM or GA4:

    ad_storage	
    analytics_storage	
    ad_user_data	
    ad_personalization	
    functionality_storage	
    personalization_storage	
    security_storage	

= Consent Options (advanced) =

+   URL passthrough

When consent attributes `ad_storage` and/or `analytics_storage` are set to `denied`, pass information about ad clicks or analytics through URL parameters.

+   Allow Google Signals

Allows session data that Google associates with users who have signed in to their Google accounts, and who have turned on Ads Personalization.

+   Redact Ads Data

When ads_data_redaction is true and `ad_storage` is `denied`, ad click identifiers sent in network requests by Google Ads and Floodlight tags will be redacted. Network requests will also be sent through a cookieless domain.

= Send Event Tags =

When to send events to the browser. *In Page Footer* sends events in the page footer as the page loads.
*On Document Load* sends events as soon as the document is loaded but before the browser renders the page.
*On Window Ready* waits for the page to be rendered (this may be helpful with late-loading CMP plugins).

= Events To Track =

Custom events are simple events with limited data that use Google's recommended names and attributes

See Recommended events:
[Tag Manager](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtm)
[Google Tag](https://developers.google.com/analytics/devguides/collection/ga4/reference/events?client_type=gtag)

+   Page Views
    +   `page_view {page_title, page_location, page_referrer, user_agent, page_encoding, language}`

+   Site Search
    +   `search {search_term}`

+   View Content (category, tag, term)
    +   `select_content {content_type, content_id}`

+   View Archive (archive, author, date)
    +   `select_content {content_type, content_id}`

+   E-Commerce
    +   `view_item_list {item_list_id, item_list_name}`
    +   `view_item {currency, value, items}`
    +   `view_cart {currency, value, items}`
    +   `begin_checkout {currency, value, coupon, discount, items}`
    +   `purchase {transaction_id, currency, value, coupon, discount, shipping, tax, items}`
    +   *items = {item_id, item_name, price, discount, quantity, item_variant, item_category}*

+   Cart Actions
    +   `select_promotion {promotion_id, promotion_name}`
    +   `add_to_cart {currency, value, items}`
    +   `remove_from_cart {currency, value, items}`
    +   `update_cart_item {currency, value, items}`
    +   *items = {item_id, item_name, price, discount, quantity, item_variant, item_category}*

+   Page Not Found
    +   `page_not_found {request_uri}`

>   \* Session storage is used when cart actions are triggered. This requires enabling/setting *{eac}Doojigger → Session Extension*.

>   \* Page Views are typically included in your tag container, other tags & triggers may need to be configured in 
[Google Tag Manager](https://tagmanager.google.com/).

>   \* If enabled, [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) may block events when 'marketing' consent is denied.

= Actions and Filters =

+   eacDoojigger_google_tag_event 	- Action to add a custom event.
    +   `do_action( 'eacDoojigger_google_tag_event( 'event_name', [...event parameters...] ) );`

+   eacDoojigger_google_ecommerce_event - Action to add an ecommerce event.
    +   `do_action( 'eacDoojigger_google_ecommerce_event( 'event_name', [...event parameters...] ) );`

+   eacDoojigger_google_tag_consent - Filter the consent array.
    +   `add_filter( 'eacDoojigger_google_tag_consent', function($consent) {...} );`
    +   `$consent` is an array of `[ $option => 'granted|denied' ]`

+   eacDoojigger_google_tag_configuration - Filter the configuration array.
    +   `add_filter( 'eacDoojigger_google_tag_configuration', function($config) {...} );`
    +   `$config` is an array of `[ $option => bool ]`

+   eacDoojigger_google_tag_events 	- Filter the events array prior to output.
    +   `add_filter( 'eacDoojigger_google_tag_events', function($events) {...} );`
    +   `$events` is an array of `[ $event => [$attributes] ]`
    +   `$event` is an array `[type,event_name]` where type is 'gtm', 'gtag', or 'ecommerce'.

= 3rd Party Service =

By loading the Google Tag Manager (GTM) or Google Analytics (GA4) script, {eac}SimpleGTM causes data collected from your website *and from your users* to be transmitted to Google. 

+   [How Google Analytics works](https://support.google.com/analytics/answer/12159447?hl=en)
+   [Introduction to Google Tag Manager](https://support.google.com/tagmanager/answer/6102821?hl=en)

*You are responsible for the proper configuration of your Google Analytics property and/or Google Tag Manager settings as well as proper notification and consent from your users.*

+   [Introduction to user consent](https://support.google.com/analytics/answer/12329599)

= Privacy Disclosures Policy =

When you use Google Analytics on your site or application, you must disclose the use of Google Analytics and how it collects and processes data.

+   [Google's Privacy & Terms](https://www.google.com/policies/privacy/partners/)
+   [Safeguarding your data](https://support.google.com/analytics/answer/6004245)


== Installation ==

**{eac}SimpleGTM** is an extension plugin to and requires installation and registration of [{eac}Doojigger](https://eacDoojigger.earthasylum.com/).

= Automatic Plugin Installation =

This plugin is available from the [WordPress Plugin Repository](https://wordpress.org/plugins/search/earthasylum/) and can be installed from the WordPress Dashboard » *Plugins* » *Add New* page. Search for 'EarthAsylum', click the plugin's [Install] button and, once installed, click [Activate].

See [Managing Plugins -> Automatic Plugin Installation](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation-1)

= Upload via WordPress Dashboard =

Installation of this plugin can be managed from the WordPress Dashboard » *Plugins* » *Add New* page. Click the [Upload Plugin] button, then select the eacsimplegtm.zip file from your computer.

See [Managing Plugins -> Upload via WordPress Admin](https://wordpress.org/support/article/managing-plugins/#upload-via-wordpress-admin)

= Manual Plugin Installation =

You can install the plugin manually by extracting the eacsimplegtm.zip file and uploading the 'eacsimplegtm' folder to the 'wp-content/plugins' folder on your WordPress server.

See [Managing Plugins -> Manual Plugin Installation](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation-1)

= Settings =

Once installed and activated options for this extension will show in the 'Tracking' tab of {eac}Doojigger settings.


== Screenshots ==

1. {eac}SimpleGTM Extension
![{eac}SimpleGTM](https://ps.w.org/eacsimplegtm/assets/screenshot-1.png)

2. {eac}SimpleGTM Extension (advanced mode)
![{eac}SimpleGTM advanced](https://ps.w.org/eacsimplegtm/assets/screenshot-2.png)


== Other Notes ==

= Additional Information =

+   {eac}SimpleGTM is an extension plugin to and requires installation and registration of [{eac}Doojigger](https://eacDoojigger.earthasylum.com/).

+   Visit the [EarthAsylum GitHub Repository](https://github.com/EarthAsylum) or the [{eac}Doojigger Web Site](https://eacdoojigger.earthasylum.com/) for all plugins, extensions, and documentation.


== Copyright ==

= Copyright © 2024, EarthAsylum Consulting, distributed under the terms of the GNU GPL. =

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should receive a copy of the GNU General Public License along with this program. If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).


== Changelog ==

= Version 1.0.3 – August 17, 2024 =

+   Support WP Consent API.
+   Only send boolean gtag options, checked = true, default is null.
    +   url_passthrough, allow_google_signals, ads_data_redaction.
    +   External actors may set to false via filter.

= Version 1.0.2 – July 26, 2024 =

+   Additional documentation re: 3rd Party & Privacy.
+   Compatible with WordPress 6.6.
+   Safe/escaped variable naming convention.

= Version 1.0.1 – June 17, 2024 =

+   Added load option (inline/dom ready/window load).
+   Use session instead of transient when storing events.

= Version 1.0.0 – June 6, 2024 =

+   Initial release.
