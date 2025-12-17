=== False Positive Bounce Manager for FluentCRM ===
Contributors: Your Name
Tags: fluentcrm, amazon ses, amazon sns, bounce management, email marketing
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage and protect your FluentCRM subscribers from false-positive bounces caused by Amazon SES/SNS (Out-of-Office, Auto-replies, etc).

== Description ==

The **False Positive Bounce Manager for FluentCRM** acts as a gatekeeper for incoming Amazon SNS bounce notifications. It intercepts "Transient" and "Undetermined" bounce types, logs them for manual review, and allows administrators to "Allow List" specific emails.

When an email is added to the Allow List, the plugin automatically restores their status to 'Subscribed' in FluentCRM and prevents any future bounce notifications for that specific address from changing their status again.

This is particularly useful for B2B lists where aggressive Out-of-Office replies or mailbox-full triggers often cause Amazon SES to report a bounce, leading to legitimate subscribers being unsubscribed.

== Installation ==

1. Upload the `false-postive-bounce-manager-fcrm` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure your Amazon SNS Topic is correctly pointing to your FluentCRM bounce handler URL.
4. Navigate to the "Bounce Manager" menu in your WordPress sidebar to review logs.

== Frequently Asked Questions ==

= Does this bypass CAN-SPAM regulations? =
No. This plugin provides a manual review process. It is the administrator's responsibility to ensure that they only re-subscribe users who have not had a permanent failure.

= Why use the 'init' hook instead of FluentCRM filters? =
Using the 'init' hook allows the plugin to intercept the raw data from Amazon before any other processing occurs, ensuring that even if a signature is weird or a cache is active, the bounce is logged.

== Screenshots ==

1. The Bounce Manager dashboard showing pending review logs and the protected allow list.
2. The JSON payload viewer for deep inspection of Amazon SES notifications.

== Changelog ==

= 1.3 =
* Added JSON payload viewer to the Admin UI.
* Added a Debug Log system with a browser-accessible link.
* Implemented regex email extraction for "Friendly Name <email@domain.com>" formats.

= 1.2 =
* Migrated to `init` hook interception for better reliability.
* Standardized all prefixes to `fpbmfcrm`.
* Separated database and admin logic into includes files.

= 1.1 =
* Added the "Allow List" (Protection) logic.
* Added manual approval and resubscription workflow.

= 1.0 =
* Initial release with basic interceptor and log table.

== Upgrade Notice ==
= 1.3 =
This version adds a JSON viewer for better troubleshooting. It is highly recommended to update if you are seeing "Undetermined" bounce types.
