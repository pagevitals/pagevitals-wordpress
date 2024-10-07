=== PageVitals ===
Contributors: pagevitals
Tags: rum, corewebvitals, analytics, performance, monitoring
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.0
License: MIT
License URI: https://en.wikipedia.org/wiki/MIT_License

Integrates PageVitals Field Testing (RUM) into your WordPress site.

== Description ==

**PageVitals** is a RUM plugin that integrates PageVitals into your WordPress site, monitoring real user performance metrics. **Note**: This plugin relies on the external PageVitals service to process data. No personal user behavior is tracked, but anonymized performance data is sent to PageVitals.

**Data Collected** includes:
- Browser performance metrics (LCP, CLS, INP and other anonymous performance metrics).
- Country (via the IP address), browser, and device type.
- No IP addresses or user-agent strings are stored.
- No cookies are read or written
- No user behavior is monitored, such as mouse or keyboard events

For full privacy details, refer to our [Privacy Policy](https://pagevitals.com/privacy/#4.-end-users-visiting-a-website-that-uses-pagevitals-field-testing-script).

### Privacy and Terms

This plugin relies on PageVitals services for performance monitoring. Refer to the [Privacy Policy](https://pagevitals.com/privacy) and [Terms of Service](https://pagevitals.com/terms).

**Benefits**

PageVitals can help you:

1. **Track Core Web Vitals**: Understand critical performance metrics like LCP, INP, and CLS that directly impact your SEO rankings and user satisfaction.
2. **Optimize Website Speed**: By analyzing how real users experience your website, you can make data-driven decisions to improve loading times and responsiveness.
3. **Identify Bottlenecks**: Monitor slow-loading assets, heavy scripts, and third-party services that may be holding back your site’s performance.
4. **Boost Engagement and SEO**: Sites that load faster keep visitors engaged longer and improve your rankings on search engines, giving your site an edge over competitors.
5. **Make Informed Decisions**: Use real user data to decide what areas of your site need optimization and measure the results of your changes.

== Features ==

- **Real User Monitoring**: Collect real-time data from actual visitors.
- **Core Web Vitals**: Monitor LCP, INP, and TTFB automatically.
- **Flexible Setup**: Choose which pages to monitor and adjust CSP settings.

== Installation ==

1. Download and install the plugin through your WordPress dashboard or upload the `pagevitals.zip` file manually.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Navigate to **Settings > PageVitals** to configure your settings.
4. Enter your PageVitals Website ID, which you can find in your PageVitals account under Settings.
5. Configure Content Security Policy (CSP) settings and page monitoring preferences to match your site's needs.

== Changelog ==

= 1.0 =  
* Initial release.

== Frequently Asked Questions ==

= Where can I find my Website ID? =
You can find your Website ID in your PageVitals account under the Settings section.

= What does enabling CSP do? =
Enabling CSP ensures that the PageVitals script is properly included in your site’s security policies without disrupting other security measures.

= Can I limit which pages are monitored? =
Yes, the plugin allows you to select whether all pages, specific pages, or all pages except certain ones are monitored.

== License ==

This plugin is licensed under the MIT License. For more details, see the license file included with this plugin.
