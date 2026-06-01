## {eac}SimpleGTM - Google Tag Manager tags, triggers, and variables

### Description

The included *eacSimpleGTM_workspace.json* file may be imported to your Google Tag Manager workspace.

When doing so, the tags, triggers, and variables needed for all events sent via *{eac}SimpleGTM* will be created and used to pass event data to Google Analytics.

#### Importing to GTM

From your [Google Tag Manager](https://tagmanager.google.com/) workspace:
1.  Select _Admin_ from the top left menu.
2.  Select _Import Container_ from the list on the right.
3.  Click _Choose container file_ and select the *eacSimpleGTM_workspace.json* file.
4.  Chose workspace - select _New_ or _Existing_ to create a new, empty workspace or use an existing workspace.
5.  Choose an import option - select _Overwrite_ or _Merge_.

**Important:** After importing, go to your workspace, _Variables_, click on *eac-const-measurement-id* to edit the variable. Click on _Value_ and change the value to your Google Analytics measurement id (G-XXXXXXXXXX).

### Tags, Triggers, and Variables

The following tags, triggers, and variables are included in the *eacSimpleGTM_workspace.json* file and imported to the `eacSimpleGTM` folder.

#### Tags

+	*eac-ga-content*
	+	Google Analytics: GA4 Event
	+	Event name: `{{Event}}`
	+	Event Parameter: `content_id`, `content_type`, `request_uri`, `search_term`, `user_data`
	+   Triggering: `eac-trigger-content`

+	*eac-ga-ecommerce*
	+	Google Analytics: GA4 Event
	+	Event name: `{{Event}}`
	+   Event Parameter: `user_data`
	+	More Settings - Ecommerce
		+	Send Ecommerce data: checked
		+	Data source: Data Layer
	+   Triggering: `eac-trigger-ecommerce`

+	*eac-ga-pageview* (may be equivalent to default GA4 tag)
	+	Google Tag
	+	Tag ID: `{{eac-const-measurement-id}}`
	+	Configuration settings
		+	Configuration Parameter: send_page_view
		+	Value: true
	+   Event Parameter: `user_data`
	+   Advanced Settings - Tag firing options: Once per page
	+   Triggering: All Pages

#### Triggers

+	*eac-trigger-content*
    +   Custom Event
    +   Event name: `page_not_found|page_view|search|select_content`
    +   Use regex matching: checked

+	*eac-trigger-ecommerce*
    +   Custom Event
    +   Event name: `purchase|add_payment_info|add_shipping_info|begin_checkout|view_cart|update_cart_item|remove_from_cart|add_to_cart|view_item|view_item_list|select_promotion`
    +   Use regex matching: checked

#### Variables (custom)

+	*eac-const-measurement-id* 
    +   Constant
	+	Value: Set to your GA4 measurement id (G-XXXXXXXXXX)

+	*eac-var-content_id*
    +   Data Layer Variable
	+	Data Layer Variable Name: `eventModel.content_id`

+	*eac-var-content_type*
    +   Data Layer Variable
	+	Data Layer Variable Name: `eventModel.content_type`

+	*eac-var-request_uri*
    +   Data Layer Variable
	+	Data Layer Variable Name: `eventModel.request_uri`

+	*eac-var-search_term*
    +   Data Layer Variable
	+	Data Layer Variable Name: `eventModel.search_term`

+	*eac-var-event_data*
    +   Data Layer Variable
	+	Data Layer Variable Name: `eventModel`

+	*eac-var-user_data*
    +   Data Layer Variable
	+	Data Layer Variable Name: `userData`

#### Variables (built-in)

+   *Page URL*
+   *Page Hostname*
+   *Page Path*
+   *Referrer*
+   *Event*

