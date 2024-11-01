=== Airship Web Notifications ===
Contributors: rittesh.patel, s3rgiosan, 10up, urbanairship
Tags: Notification, Push Notification
Requires at least: 4.0
Requires PHP: 5.2
Tested up to: 5.2
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Push notification for WordPress using Airship's Web Notify product.

== Description ==

Seamlessly connect your WordPress site to Airship’s web notification delivery service so that you can selectively deliver on-demand notifications to your readers as you publish your content.

Marketing and digital experience teams at thousands of the world’s most admired companies rely on Airship’s Customer Engagement Platform to create deeper connections with customers by delivering incredibly relevant, orchestrated messages on any channel.

Founded in 2009 as a pioneer in push notifications, Airship now gives brands the user-level data, engagement channels, AI orchestration and services they need to deliver push notifications, emails, SMS, in-app messages, mobile wallet cards and more to exactly the right person in exactly the right moment — building trust, boosting engagement, driving action and growing value.

To use this plugin, you’ll need to set up an account with Airship. You can [sign up for a free](https://www.urbanairship.com/products/web-push-notifications/pricing) starter plan with unlimited web notifications and up to 1,000 addressable users.

Plugin Features:

* Support for Google Chrome, Mozilla Firefox, Opera, and Safari on desktop and Google Chrome, Mozilla Firefox, and Opera on Android mobile.
* Two methods for your site visitors to opt-in for notifications:
     * Apply a custom CSS class to any element to turn it into an opt-in prompt
     * Automatically display the browser opt-in prompt based upon page views
* A "Send web notification" checkbox right below your publish button.
* The ability to customize notification interaction, text, action URL, and icon per post
* The ability to automatically send a web notification when publishing or updating any public post type (including custom post types).
* A custom web notification content type allowing you to send one-off notifications from within WordPress.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or simply install from the plugin repository.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Use the Settings -> Web Notifications screen to configure the plugin.

= Initial configuration =

1. Upload the SDK Bundle Zip file.
1. Alternatively, you can define a `UA_WEB_NOTIFICATION_BUNDLE` constant in your wp-config.php or functions.php, with an array with the required data for the plugin to work. This array should be encoded using json_encode or wp_json_encode, when available. Below is an example:

~~~~
$ua_bundle = array(
	'default-icon'       => '',
	'default-title'      => '',
	'default-action-url' => '',
	'app-key'            => '',
	'token'              => '',
	'vapid-pub-key'      => '',
	'website-push-id'    => '',
	'secure-bridge'      => true|false,
);

define( 'UA_WEB_NOTIFICATION_BUNDLE', json_encode( $ua_bundle ) );
~~~~

== Frequently Asked Questions ==

For full technical documentation on Airship’s Web Notification solution, [please visit the Airship documentation website](https://docs.urbanairship.com/platform/web/).

= What are web notifications? =

Web notifications are notifications that can be sent to a user via desktop web and mobile web. Please [see Web Notifications Explained](https://www.urbanairship.com web-push-notifications-explained) for more information.

= Which browsers is this plugin and Airship service compatible with? =

Currently Google Chrome (52+), Mozilla Firefox (48+), Opera (39+) and Safari (12+, via Apple Push Notifications Service).

= How can users unsubscribe from receiving notifications? =

Each browser has settings to manually/disable push notifications:

1. Chrome: <https://support.google.com/chrome/answer/3220216>
1. Firefox: <https://support.mozilla.org/en-US/kb/push-notifications-firefox#w_how-do-i-revoke-web-push-permissions-for-a-specific-site>
1. Opera: <http://help.opera.com/opera/Mac/2393/en/controlPages.html#manageNotifications>
1. Safari: <https://support.apple.com/guide/safari/customize-website-notifications-sfri40734/mac>

= How much does Airship’s web notification solution cost? =

The free starter plan includes unlimited web notifications and up to 1,000 addressable users. See the [Airship pricing page](https://www.airship.com/platform/pricing/) for more details.

= How can I use web notifications on a website that is not fully HTTPS? =

Airship provides a "secure bridge" component that is required for integration on sites that are not fully HTTPS. To use the plugin on a mixed HTTPS site, check the box next to "Yes, I will be integrating the SDK on a mixed HTTPS site." in the Airship Dashboard when configuring your setup files. From there, reference Airship's [Secure Integration documentation](https://docs.urbanairship.com/platform/web/secure-integration/) for details on how to host the necessary files and registration page securely.

= Where can I see analytics for my web notifications? =

Analytics are available in the Airship dashboard, where you can see the number of people who have opted-in to your notifications, the number of notifications sent, the click through rate, and the number of sessions attributed to your push notification. For more details, view the [documentation on message reports](https://docs.urbanairship.com/engage/message-reports/#web-engagement) and [opt-in report](https://docs.airship.com/reference/reports/#devices-report).

= Why I am not receiving notifications? =

There are several causes as to why you are not receiving notifications:

1. Make sure your have opted in to receive notifications/have not blocked receiving notifications from your website in browser settings.
1. Ensure "Do Not Disturb" (Apple devices) or “Quiet Hours” (Windows devices) is off so that notifications are not muted.
1. Visit (https://status.urbanairship.com/) to determine if Airship’s systems are operational
1. Make sure push worker is accessible. To do that, try accessing {site URL}/push-worker.js is accessible, if not, try saving permalinks again.
1. If none of the above helps, [Contact Airship Support](https://support.urbanairship.com/hc/en-us) to further troubleshoot.


Please refer to Airship’s [Web Notifications page](https://www.airship.com/platform/channels/web-notifications/) for more information.

== Screenshots ==

1. Your website visitors simply click “Allow” to start receiving notifications.
1. All it takes is a single click. Simply check the “send web notification on publish” option, make optional custom adjustments to your notification, and click publish.
1. Instantly deliver web notifications upon publish or update.
1. Use the custom web notification content type to send one-off notifications directly within WordPress.
1. Seamlessly integrate Airship Web Notifications with your WordPress site.
1. Configure your settings in the Airship dashboard, including your default title and icon.
1. Visit the Devices report in your Airship dashboard to see how many website visitors have opted in to notifications.
1. Check out your message reports in the Airship dashboard to see important metrics for your sent messages, such as click through rates and attributed sessions.

== Changelog ==

= 1.3.4 =
* Removed meta box order override

= 1.3.3 =
* Fixed a bug with the ua_wn_snippet_additional_params filter

= 1.3.2 =
* Tested up to WordPress 5.2

= 1.3.1 =
* Updated installation instructions
* Added missing JS file

= 1.3.0 =
* Updated to reflect new branding
* Added support to Safari

= 1.2.3 =
* Fixed HTML entity display for the Default Title field

= 1.2.2 =
* Applied WPCS standards
* Tested up to WordPress 5.1.1
* Tested with PHP 7.2

= 1.2.1 =
* Fixed a bug with the post notification text
* Fixed a bug with the custom notification url

= 1.2.0 =
* Fixed HTML entity display

= 1.1 =
* Added ability to customize notification interaction, text, action URL, and icon per post
* Added custom web notification content type allowing you to send one-off notifications from within WordPress

= 1.0 =
* First version
