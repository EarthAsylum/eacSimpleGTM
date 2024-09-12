<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\google_tag_manager', false) )
{
	/**
	 * Extension: google_tag_manager - Add Google Tag Manager with consent defaults
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @version		1.x
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class google_tag_manager extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '24.0909.1';

		/**
		 * @var string gtm/ga4 script url
		 *
		 * By loading the Google Tag Manager (GTM) or Google Analytics (GA4) script,
		 * {eac}SimpleGTM causes data collected from your website *and from your users* to be transmitted to Google.
		 */
		const SCRIPT_URL = "https://www.googletagmanager.com/%s.js?id=%s";

		/**
		 * @var array consent attributes
		 */
		const CONSENT_ATTRIBUTES = [
				'ad_storage',
				'analytics_storage',
				'ad_user_data',
				'ad_personalization',
				'functionality_storage',
				'personalization_storage',
				'security_storage',
		];

		/**
		 * @var array GA events
		 */
		private $ga_events = [];

		/**
		 * @var string transient name (prefix)
		 */
		private $transientId = 'google_tag_events';

		/**
		 * @var string tage type - 'gtm' or 'gtag'
		 */
		private $tag_type = false;

		/**
		 * @var string ecommerce active
		 */
		private $use_ecommerce = false;

		/**
		 * @var array event options
		 */
		private $event_options = false;

		/**
		 * @var string currency code
		 */
		public $currency = 'USD';

		/**
		 * @var int number format decimals
		 */
		public $decimals = 2;


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::DEFAULT_DISABLED);

			if ($this->is_admin())
			{
				$this->registerExtension( [$this->className,'tracking'] );
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
				// Add contextual help
				$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
			}
			else
			{
				/**
				 * action {pluginname}_google_tag_event - allow actors to add events
				 * @example do_action('eacDoojigger_google_tag_event('event_name',[event attributes]));
				 * @param string event name
				 * @param array event parameters
				 * @param bool allow multiple
				 */
				$this->add_action('google_tag_event', 		array($this,'add_google_event'),10,3);
				$this->add_action('google_tag_data', 		array($this,'add_google_data'),10,2);
				$this->add_action('google_ecommerce_event', array($this,'add_ecommerce_event'),10,3);
			}

			// supporteed e-commerce plugins
			$this->use_ecommerce = (class_exists('woocommerce',false)) ? 'woocommerce' : false;
		}


		/**
		 * register options on options_settings_page
		 *
		 */
		public function admin_options_settings()
		{
			require "includes/simple_gtm.options.php";
		}


		/**
		 * Add help tab on admin page
		 *
		 */
		public function admin_options_help()
		{
			if (!$this->plugin->isSettingsPage('Tracking')) return;
			require "includes/simple_gtm.help.php";
		}


		/**
		 * Called after instantiating, loading extensions and initializing
		 *
		 */
		public function addActionsAndFilters(): void
		{
			parent::addActionsAndFilters();

			if ($this->is_admin()) return;

			// maybe load un-fired events
			$this->ga_events = $this->plugin->getVariable($this->transientId,[]);

			$this->currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() :'USD';
			$this->decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

			$this->event_options = (array)$this->get_option('gtag_events',[]);

			\add_action('wp_print_scripts', 				array($this,'output_tag_manager')); // do this early
			\add_action('wp_print_footer_scripts', 			array($this,'output_tag_events'));	// do this later

			/* E-Commerce */
			if ($this->use_ecommerce == 'woocommerce') {
				require 'includes/woocommerce_tag_manager.class.php';
				new woocommerce_tag_manager($this,$this->event_options);
			}

			/* track 404 */
			if ( in_array('track-404',$this->event_options) )
			{
				\add_action('set_404', function($wp_query)
					{
						$this->add_google_event('page_not_found',['request_uri'=>$this->varServer('REQUEST_URI')]);
					},10,2
				);
			}

			// maybe save un-fired events
			\add_action('shutdown', function()
				{
					if ($this->ga_events) {
						$this->plugin->setVariable($this->transientId,$this->ga_events);
					}
				},-2 	// before session save (may be woocommerce session manager or other)
			);
		}


		/**
		 * enqueue google tag manager on wp_print_scripts
		 *
		 */
    	public function output_tag_manager(): void
    	{
			$tag_id 	= $this->get_option('gtag_container_id');
			$this->tag_type = (!empty($tag_id) && substr($tag_id,0,3) == 'GTM') ? 'gtm' : 'gtag';

			// if we have a complete id, load the script
			if (!empty($tag_id) && preg_match("/\w{1,3}-\w{5,12}/",$tag_id))
			{
				$script = sprintf(self::SCRIPT_URL, $this->tag_type, $tag_id);
				// GTM/GA must load in the document head but may load asynchronously
				printf("<script async id='%s' type='text/javascript' src='%s'></script>\n",
					'google-tag-manager', esc_url($script)
				);
			}

			// set dataLayer & gtag function
			$script_safe = 	"window.dataLayer = window.dataLayer || [];\n".
							"function gtag(){dataLayer.push(arguments)};\n";

			// get default consent
			if ($consent = $this->get_option('gtag_consent',[]))
			{
				$consent = array_replace(
							array_fill_keys(self::CONSENT_ATTRIBUTES, 'denied'),
							array_fill_keys($consent, 'granted'),
							['wait_for_update' => 750]
				);
			}
			/**
			 * filter {pluginname}_google_tag_consent - allow actors to filter consent
			 * @param array consent array
			 */
			$consent 	= $this->apply_filters('google_tag_consent',$consent);

			// add default consent
			if (!empty($consent)) {
				$script_safe .=	"gtag('consent', 'default', ".wp_json_encode($consent).");\n";
			}

			// set runtime configuration
			$config = [
				'send_page_view' 		=> !in_array('page-view',$this->event_options),
				'url_passthrough'		=> $this->is_option('gtag_options','url_passthrough') ? true : null,
				'allow_google_signals' 	=> $this->is_option('gtag_options','allow_google_signals') ? true : null,
				'ads_data_redaction' 	=> $this->is_option('gtag_options','ads_data_redaction') ? true : null,
			];
			/**
			 * filter {pluginname}_google_tag_configuration - allow actors to filter configuration
			 * @param array configuration array
			 */
			$config 	= $this->apply_filters('google_tag_configuration',$config);
			$config 	= array_filter($config, function($v) {return !is_null($v);});

			// configure/initialize
			if (!empty($tag_id))
			{
				if ($this->tag_type == 'gtm') {
					$script_safe .= "gtag('gtm.start', new Date().getTime());\n".
									"gtag('event', 'gtm.js',".wp_json_encode($config).")\n";
				} else {
					$script_safe .= "gtag('js', new Date());\n".
  									"gtag('config', '".esc_attr($tag_id)."',".wp_json_encode($config).")\n";
				}
			}

			echo wp_get_inline_script_tag(trim($script_safe),['id'=>'google-tag-manager-inline']);
		}


		/**
		 * output google tag events on wp_print_footer_scripts
		 *
		 */
    	public function output_tag_events(): void
    	{
    		// ajax and pre-fetch pages may not process script tags
			if ($this->varServer("HTTP_X_REQUESTED_WITH") == "XMLHttpRequest"
			or  $this->varServer("HTTP_PURPOSE") == 'prefetch') return;

			// wp consent api says no marketing consent
			if (function_exists('wp_has_consent') && ! wp_has_consent('statistics-anonymous')) return;

			if (!empty($this->event_options))
			{
	    		$this->add_ga_events();
	    	}
			/**
			 * filter {pluginname}_google_tag_events - allow actors to filter events
			 * @param array custom events [event_name => [parameters]]
			 */
			$this->ga_events = $this->apply_filters('google_tag_events',$this->ga_events);

			if (empty($this->ga_events)) return;

			$tags_escaped = "";
			foreach ($this->ga_events as $event => $events)
			{
				list ($type,$event) = explode('.',$event);
				foreach ($events as $values)
				{
					switch ($type) {
						case 'set':
							$tags_escaped .= "gtag('set', '".esc_attr($event)."', ".wp_json_encode($values).");\n";
							break;
						case 'data':	// user_data -> userData
							$event = lcfirst(str_replace(['_','-'],'',ucwords($event,'_-')));
							$values = [esc_attr($event) => $values];
							$tags_escaped .= "dataLayer.push(".wp_json_encode($values).");\n";
							break;
						case 'ecommerce':
							$values = ['event' => esc_attr($event), $type => $values];
							$tags_escaped .= "dataLayer.push(".wp_json_encode($values).");\n";
							break;
						case 'gtm':
							$values = array_merge(['event' => esc_attr($event)], $values);
							$tags_escaped .= "dataLayer.push(".wp_json_encode($values).");\n";
							break;
						case 'gtag':
						default:
							$tags_escaped .= "gtag('event', '".esc_attr($event)."', ".wp_json_encode($values).");\n";
					}
				}
			}

			// when to trigger - 'inline', 'DOMContentLoaded', 'load'
			$when = esc_attr($this->get_option('gtag_load_when','inline'));
			$script_safe = ($when == 'inline')
				? "%s"
				: "addEventListener('{$when}',(e)=>{\n%s});";
			$script_safe = sprintf($script_safe,$tags_escaped);

			echo wp_get_inline_script_tag(trim($script_safe),['id'=>'google-tag-events-inline']);
			$this->plugin->logDebug($script_safe,__METHOD__);

			$this->ga_events = [];
			$this->plugin->setVariable($this->transientId,null);
		}


		/**
		 * Add standard GA event
		 *
		 * @param string $event event name
		 * @param array $params event attributes
		 * @param bool $allowMultiple allow multiple events of the same name
		 */
    	public function add_google_event(string $event, array $params = [], $allowMultiple = false): void
    	{
    		$type = $this->tag_type;
			$this->_push_event_array([$type,$event], $params, $allowMultiple);
		}


		/**
		 * Add ecommerce GA event
		 *
		 * @param string $event event name
		 * @param array $params event attributes
		 * @param bool $allowMultiple allow multiple events of the same name
		 */
    	public function add_ecommerce_event(string $event, array $params = [], $allowMultiple = false): void
    	{
    		$type = ($this->tag_type == 'gtm') ? 'ecommerce' : $this->tag_type;
			$this->_push_event_array([$type,$event], $params, $allowMultiple);
		}


		/**
		 * Set named GA data
		 *
		 * @param string $name data name
		 * @param array $params event attributes
		 */
    	public function add_google_data(string $name, array $params = []): void
    	{
    		$type = ($this->tag_type == 'gtm') ? 'data' : 'set';
			$this->_push_event_array([$type,$name], $params, false);
		}


		/**
		 * Add single GA event
		 * @internal
		 *
		 * @param array ['gtm',event_name] | ['ecommerce',event_name]
		 * @param array $params event attributes
		 * @param bool $allowMultiple allow multiple events of the same name
		 */
    	private function _push_event_array(array $event, array $params = [], $allowMultiple = false): void
    	{
    		$event = implode('.',$event);

			if ($allowMultiple) {
				$this->ga_events[ sanitize_text_field($event) ][]  = (array)$params;
			} else {
				$this->ga_events[ sanitize_text_field($event) ][0] = (array)$params;
			}
		}


		/**
		 * Add known GA events
		 *
		 */
    	public function add_ga_events(): void
    	{
			/* Page View */
			if ( in_array('page-view',$this->event_options) )
			{
				$this->add_google_event('page_view',array_merge($this->page_attributes(),[
					'user_agent'	=> $this->varServer('HTTP_USER_AGENT'),
					'page_encoding'	=> get_bloginfo('charset'),
					'language'		=> get_bloginfo('language')
				]));
			}

			/* Site Search */
			if ( in_array('site-search',$this->event_options) && is_search() )
			{
				$search = get_search_query();
				$this->add_google_event('search',['search_term'=>sanitize_text_field($search)]);
				return;
			}

			/* E-Commerce */
			if ( in_array('ecommerce',$this->event_options)
			||   in_array('enhanced-conv',$this->event_options)
			||   in_array('cart-actions',$this->event_options) )
			{
				/**
				 * filter {pluginname}__add_ecommerce_event
				 * @internal
				 * @param bool false set true when events are added
				 * @param object $this this GTM extension
				 */
				if ($this->apply_filters('_add_ecommerce_event',false,$this)) return;
			}

			/* View Content */
			if ( in_array('view-content',$this->event_options) )
			{
				if ( is_category() ) {
					$content = html_entity_decode(single_cat_title( '', false ));
					$this->add_google_event('select_content',['content_type'=>'category','content_id'=>$content]);
					return;
				} elseif ( is_tag() ) {
					$content = html_entity_decode(single_tag_title( '', false ));
					$this->add_google_event('select_content',['content_type'=>'tag','content_id'=>$content]);
					return;
				} elseif ( is_tax() ) {
					$content = html_entity_decode(single_term_title( '', false ));
					$this->add_google_event('select_content',['content_type'=>'term','content_id'=>$content]);
					return;
				}
			}

			/* View Archive */
			if ( in_array('view-archive',$this->event_options) )
			{
				if ( is_post_type_archive() ) {
					$content = html_entity_decode(post_type_archive_title( '', false ));
					$this->add_google_event('select_content',['content_type'=>'archive','content_id'=>$content]);
					return;
				} elseif ( is_author() ) {
					$content = html_entity_decode(get_the_author());
					$this->add_google_event('select_content',['content_type'=>'author','content_id'=>$content]);
					return;
				} elseif ( is_year() ) {
					$content = get_the_date( 'Y' );
					$this->add_google_event('select_content',['content_type'=>'date-year','content_id'=>$content]);
					return;
				} elseif ( is_month() ) {
					$content = get_the_date( 'F Y' );
					$this->add_google_event('select_content',['content_type'=>'date-month','content_id'=>$content]);
					return;
				} elseif ( is_day() ) {
					$content = get_the_date( 'F j, Y' );
					$this->add_google_event('select_content',['content_type'=>'date-day','content_id'=>$content]);
					return;
				} elseif ( is_date() ) {
					$content = get_the_date( 'F j, Y' );
					$this->add_google_event('select_content',['content_type'=>'date','content_id'=>$content]);
					return;
				}
			}
		}


		/**
		 * Get page attributes
		 *
		 */
		public function page_attributes(): array
		{
			$title 		= wp_get_document_title();
			$location	= ($this->varServer("HTTP_X_REQUESTED_WITH") == "XMLHttpRequest")
							? $this->varServer('HTTP_REFERER')
							: $this->plugin->currentURL();
			$referer 	= $this->varServer('HTTP_REFERER') ?: '';
			return [
				'page_title'	=> html_entity_decode($title),
				'page_location'	=> explode('?',$location)[0],
				'page_referrer' => explode('?',$referer)[0],
			];
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new google_tag_manager($this);
?>
