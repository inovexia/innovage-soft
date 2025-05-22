=== Testimonial Slider ===
Contributors: slidervilla, tejaswini, DavidAnderson
Tags: testimonial, slider, slideshow, feedback, content slider, responsive, widget, content, jquery, gallery, custom post type, sidebar, wpmu
Donate link: https://david.dw-perspective.org.uk/donate
Requires at least: 4.2
Tested up to: 6.8
Stable tag: 1.3.3
License: GPLv2 or later

Display your happy customers' Testimonials in a neat Responsive Slider 

== Description ==

Testimonial Slider shows the testimonials and feedbacks submitted by your Happy Customers in a clean, responsive and beautiful Slider format. The "Testimonials" are a Custom Post Type so it is very easy to add, modify and delete testimonials. You can enter the Customer's Image/Avatar, Name, Company, Website in the Custom Fields for the Testimonial and the actual Testimonial text in the "Description". It is as simple as that!

= Important notice about this plugin's maintainership and future =

This plugin is (October 2018) under new maintainership. The previous maintainer (whom we sincerely thank for his work) was no longer working on it. Security issues had been discovered, and not fixed, and the wordpress.org repository had closed the plugin. We have taken over maintainership *solely for the purposes of keeping the plugin alive* (since we were using it on our own live sites) and secure. No future feature developments are planned, though, you are welcome to send in new code for us to evaluate for inclusion if you are interested yourself. As such, we *do not particularly recommend* using this plugin on new sites, as it has no planned future of new features, only of being kept alive. Accordingly, whilst users are of course welcome and encouraged to support eachother in the wordpress.org forums, we will be quite likely to pass over anything found there except significant bug reports.

= Features: =

* 5 Stylish Skins
* Create Unlimited Testimonials Slider Settings
* Add List View of Slider Quickly
* Add only specific "Testimonials" to Custom Slider
* Show "Category" specific Testimonials
* Show "Recent" testimonials auto fetched by the slider
* Show the Testimonials List using Template tag or Shortcode
* Shortcodes and Widgets available
* Multiple Sliders, Multiple Settings and Multiple transition effects supported.

* Language Files Available 

	* Spanish
	* Dutch
	* French

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your Admin, go to menu Plugins > Add
1. Search for Testimonial Slider
1. Click to install
1. Activate the plugin
1. A new sub-menu under Settings menu named `Testimonial Slider` will appear in your Admin

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to menu Plugins > Add
1. Select the tab "Upload"
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new sub-menu under Settings menu named `Testimonial Slider` will appear in your Admin

= 3. The old way (FTP) =
1. Upload `testimonial-slider` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new sub-menu under Settings menu named `Testimonial Slider` will appear in your Admin

== Usage ==

**Option 1 - Widget**

* Use Testimonial Slider - Simple Widget, Testimonial Category Widget or Testimonial Recent Slider Widget to insert the Slider in the Widgetized area of your WordPress theme

**Option 2 - Manual Insertion of Shortcode**

* To insert the Testimonial Slider, paste the below shortcode in the content section of your WordPress post/page:

`[testimonialslider]`

* Category Shortcode

`[testimonialcategory catg_slug="support"]`

* Recent Testimoniials in Slider

`[testimonialrecent]`

* Testimonials List

`[testimonialList]`

**Please refer the [Testimonial Slider Documentation](http://guides.slidervilla.com/testimonial-slider/) for more details.** N.B. The linked site is maintained by a previous maintainer. We cannot guarantee it will remain operative into the future.

== Screenshots ==

1. Testimonial Slider Skins
2. Settings Panel Overview

Visit the plugin page (http://slidervilla.com/testimonial-slider/) to read more about it. N.B. The linked site is maintained by a previous maintainer. We cannot guarantee it will remain operative into the future.


== Frequently Asked Questions ==

Visit the plugin page (http://slidervilla.com/testimonial-slider/) for recent FAQs. N.B. The linked site is maintained by a previous maintainer. We cannot guarantee it will remain operative into the future.


== Upgrade Notice ==

1.3.3 : Fixes an authenticated content injection vulnerability for sites with untrusted content creators - see changelog for full details. A recommended update for all.

== Changelog ==

= 1.3.3 - (2024/May/02) =

* SECURITY: Previous releases (going back to before we took over the plugin) allowed a WordPress user who had permission to author content to include unfiltered HTML within that content via the testimonialcategory shortcode, a permission which is only intended to be possible for site owners. This means that if your site has non-admin users with privileges to create content using shortcodes, then they could include unwanted HTML (including JavaScript). Thanks to Krzysztof Zając for the responsible disclosure.

= 1.3.2 - (2022/Dec/16) =

* SECURITY: Previous releases (for at least several years before we took over the plugin) included a targeted CSRF/XSS vulnerability due to a missing nonce check: if an attacker could persuade the site owner to click on a personally-targetted link whilst logged in, then the attacker could input arbitrary HTML into the plugin's admin area page
* TWEAK: Add output escaping functions in many places
* TWEAK: Update jQuery document ready style to the one not deprecated in jQuery 3.0
* TWEAK: Fix an SQL error in testimonial_carousel_posts_on_slider()

= 1.3.1 - (2020/Nov/06) =

* FIX: Some graphical elements (rarely used) were missing

= 1.3.0 (2018/Oct/18) =

* MAINTAINERSHIP: The plugin has a new maintainer, since it had become unmaintained and closed due to unfixed security issues. Our sole aim is to keep it functioning. We have no aim to add new features.
* SECURITY FIXES: Various actions were not protected by WordPress nonces, making targetted attacks against logged-in administrators possible. There were also unsanitised inputs.
* COMPATIBILITY: The "requires at least" field has been updated to WordPress 4.2+. Nothing in particular has been changed to stop it working on earlier versions (the previous value was 3.5+). We just lack resources to support earlier versions.
* PERFORMANCE: Pass the already existing WP_Post object to get_permalink(), saving one SQL query per slide
* TWEAK: Minor code-styling tweaks.
* TWEAK: Prevent direct access to files.
* TWEAK: Replacethe deprecated PHP function create_function()

= 1.2.5 (05/15/2017) =

1. Fix: Changes to make compatible with PHP7
2. Fix: Testimonial Post meta box => Customer's Avatar/Image URL Upload was not working
3. Fix: Security fixes.

= 1.2.3 (02/01/2016) =

1. Fix: Notices on sliders panel
2. Fix: Avatar image enable/disable switch was not working
3. Fix: Post meta box => Customer image link was not getting inserted.

= 1.2.1 (08/21/2015) =

1. Fix: Notice for WP_Widget constructor call 

= 1.2 (06/02/2015) =

1. New: Added star rating 
2. New: Added language support for following languages
	* Spanish
	* Dutch
	* French
2. Fix: Notices on edit testimonials panel


= 1.1.4 (11/15/2014) =

1. Fix: Excluded cycle script

= 1.1.3 (09/18/2014) =

1. Fix: On Preview settings page Preview on Settings Panel fields for selecting category and custom slider was not appearing in WordPress4.0
2. Fix: Settings tab extra padding was outsite the block in WordPress 4.0
3. Fix: Responsiveness for navigation arrows

= 1.1.2 (09/10/2014) =

1. Fix: In case of multisite WordPress installation there was an issue while saving setting set.
2. Fix: Appearance issue for testimonial images in 'Round' skin

= 1.1.1 (08/25/2014) =

1. Fix - CSS Issue with Category List View Slider
2. Fix - Removed unused "Pick Content From" multiple options

= 1.1 (06/19/2014) =

1. New: Four new skins, minimal, oval, round and textonly.
2. New: New option to disable avatar image is been added in Settings => Slider content => Gravtar/ Customer Image Tab.
3. New: New option in Settings => Slider content => Gravtar/ Customer Image Tab to change avatar shape as circle and square and can customize its shape by changing the radius.
4. New: Added new look to admin panel.
5. New: Form validations for admin side settings.
6. New: Quick embed shortcode and template tags are displayed at the right side of settings page.
7. New: Quick embed shortcode is displayed in popup for ease of copying shortcode.
8. New: On settings panel of Testimonial slider now it is possible to expand and collapse tabs which greatly reduces scrolling efforts.
9. New: Testimonial Slider is responsive by default. Responsiveness tab is been removed.
10. New: Preview Slider params simplified on Settings panel.
11. New: Color picker changed from Farbtastic to “wp-color-picker”.
12. Fix: Solved the debug errors in previous version.
13. Fix:  Solved the issues with responsiveness of admin panel settings page and sliders page.  
14. Fix: All database tables and plugin folder being deleted properly on plugin delete.

= 1.0.1 (12/12/2013) =

1. New: Template tag and Shortcodes to display Testimonials in list (one below/beside another).
2. New: HTML tags and shortcodes will be retained in the Testimonials content
3. Fix: WP Debug error fixes
