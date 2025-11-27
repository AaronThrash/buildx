BuildX Divi Child Theme
A custom Divi child theme built for BuildX, a construction company specializing in Accessory Dwelling Units (ADUs) and home additions.
This theme turns a standard Divi site into a focused construction media + lead-gen platform, with:
•	A filterable ADU Floor Plans console
•	A filterable Learning Center (articles, videos, podcasts)
•	A data-driven popularity engine and analytics dashboard
•	Custom video modal, SEO schema, and admin tools
Everything is written as lean PHP/JS on top of Divi—no extra page-builder plugins.
________________________________________
Table of Contents
•	Overview
•	Key Features
o	ADU Floor Plans Console
o	Learning Center Console
o	Popularity Engine & Analytics
o	Video Modal System
o	SEO & Schema
o	Core Setup & Cache Control
o	Security Hardening
•	Technical Notes
•	Installation
•	Customization
•	Why It’s Useful for Construction Sites
________________________________________
Overview
This child theme extends Divi to support a content-heavy, construction-focused website:
•	Showcases ADU floor plans as a browsable “catalog” with filters.
•	Surfaces educational content (articles, videos, podcasts) through a dedicated Learning Center.
•	Tracks real-world viewer behavior (views + clicks) and uses that to:
o	Rank “Most Popular” floor plans.
o	Rank Learning Center content.
o	Display popularity analytics in the WordPress admin.
The codebase is intentionally:
•	Modular (inc/ directory for feature files).
•	Plugin-free for core site behavior (no SEO plugin, no popularity plugin).
•	Hardened for common WordPress security issues.
________________________________________
Key Features
ADU Floor Plans Console
File: inc/floor-plans.php
Front-end JS: learning-center.js (shared with LC console)
•	Filterable grid of floor plans based on:
o	Bedrooms, bathrooms, square footage, and other taxonomies.
•	AJAX-powered filtering:
o	Updates the grid without full page reload.
o	Preserves the current filter/pagination state in the URL.
o	Back/forward navigation is supported via history.pushState + popstate.
•	Dedicated REST endpoint for the filter API.
Most Popular 3-Card Strip
•	Shortcode: buildx_popular_adu_plans
•	Shows a 3-card “Our Most Popular ADU Plans” strip:
o	Card 1 = top plan by popularity score.
o	Card 2 = second most popular.
o	Card 3 = randomly chosen from the next tier of popular plans (e.g. top 3–8) so the third card rotates among strong performers.
________________________________________
Learning Center Console
File: inc/learning-center.php
Front-end JS: learning-center.js
•	Filterable Learning Center page for:
o	Articles, videos, podcasts (or other content formats).
•	AJAX search + filters + pagination:
o	Results are loaded via a custom REST endpoint.
o	URL query string reflects current state (?paged=2&format[]=video&topic[]=financing).
o	Back/forward buttons correctly restore both URL and grid state.
________________________________________
Popularity Engine & Analytics
Core: inc/popularity.php
Dashboard: inc/popularity-dashboard.php
Popularity Engine
•	Tracks views (server-side) and clicks (AJAX beacons) for:
o	Learning Center posts (category learning-center).
o	ADU content (detected via post type/taxonomies containing “adu” or categories like adu-floor-plans / floor-plans).
•	Rolling 30-day window:
o	History stored as an array per post, e.g.:
o	[
o	  ['d' => '2025-11-20', 'v' => 12, 'c' => 3],
o	  ...
o	]
•	Popularity score:
o	score = views × 1 + clicks × 3
o	Stored as:
	lc_pop_score for Learning Center
	adu_pop_score for ADU content
•	Tools:
o	Two admin pages under Tools →:
	Rebuild Popular ADU Plans
	Rebuild Popular Learning Center
o	Each button recomputes scores from the last 30 days.
Analytics Dashboard
•	Admin page: Tools → Popularity Analytics
•	Uses the same history data to render:
o	Views & clicks per day (30-day chart).
o	Top 5 posts (LC and ADU) by popularity.
•	Data is pre-aggregated in PHP and passed to JS as window.buildxPopAnalytics.
________________________________________
Video Modal System
File: inc/video.php
•	Global video modal injected once per page.
•	JS/PHP helper looks for:
o	Custom fields like lr_video_url, et_video_url, _et_pb_video_url.
o	Video URLs embedded in post content or Divi video module shortcodes.
•	Normalizes YouTube/Vimeo URLs and renders them in a unified modal for a consistent UX.
________________________________________
SEO & Schema
File: inc/seo.php
•	Outputs JSON-LD schema in <head> without an SEO plugin.
•	Schema includes:
o	Organization / LocalBusiness info (name, contact, logo, social links).
o	Website node with SearchAction for on-site search.
o	Page / Post nodes:
	For singular content, adds WebPage and BlogPosting where appropriate.
	Includes headline, description, author, datePublished, dateModified.
	Uses featured image as image when available.
All schema is generated from native WordPress data (no external dependencies).
________________________________________
Core Setup & Cache Control
File: inc/setup.php
•	Theme initialization and cache behavior wrappers:
o	Prevents caching of certain dynamic pages (e.g., Learning Center REST responses) by sending appropriate headers.
o	Adds strategic hooks like template_redirect and send_headers to control Cache-Control for specific routes (e.g., homepage HTML vs. REST API).
This keeps dynamic, filter-driven content fresh even on hosts with aggressive caching.
________________________________________
Security Hardening
Across inc/security.php, inc/popularity.php, inc/video.php, inc/seo.php, inc/setup.php and others:
•	Direct access blocking:
o	Every PHP file starts with:
o	if ( ! defined( 'ABSPATH' ) ) {
o	    exit;
o	}
•	AJAX security:
o	All AJAX handlers use check_ajax_referer() with a dedicated nonce.
o	post_id values are sanitized with absint() / intval() and wp_unslash().
o	Only minimal data is accepted and processed (views/clicks), no arbitrary writes.
•	Admin capability checks:
o	Admin pages and rebuild tools require manage_options.
o	All admin forms use check_admin_referer() to prevent CSRF.
•	Escaping & output safety:
o	HTML output in admin pages uses esc_html(), esc_url(), and wp_kses_post() where appropriate.
o	JSON is output via wp_json_encode().
The goal is to keep the theme’s custom logic safe even on a busy, public-facing construction site.
________________________________________
Technical Notes
•	Theme type: Divi child theme
•	Directory structure (key parts):
o	functions.php – loads modules from /inc and wires theme hooks.
o	/inc
	floor-plans.php – ADU floor plans console + 3-card strip.
	learning-center.php – Learning Center console + REST wiring.
	popularity.php – popularity engine core + admin tools hooks.
	popularity-dashboard.php – analytics dashboard page.
	video.php – video modal and URL resolution.
	seo.php – JSON-LD schema output.
	setup.php – core theme and cache control.
	security.php – additional guardrails / helper hardening.
o	/assets
	learning-center.js – shared JS for LC / floor-plans filtering + history.
	learning-center.css – styles for grids and cards.
	popularity-dashboard.js – admin analytics chart rendering.
________________________________________
Installation
1.	Clone or download this repository.
2.	Place the divi-child directory into:
3.	wp-content/themes/divi-child
4.	In WordPress Admin:
o	Go to Appearance → Themes.
o	Activate Divi Child (this theme), ensuring the parent Divi theme is installed.
5.	Flush any server/page cache after activation so new scripts and REST endpoints are recognized.
________________________________________
Customization
•	Shortcodes / Titles / URLs
o	Modify default labels and URLs in inc/floor-plans.php and inc/learning-center.php where shortcode_atts() is defined.
o	Adjust BUILDX_POP_* constants in inc/popularity.php to change:
	Window length (e.g. 14 vs 30 days).
	View/click weights.
	Category slugs for Learning Center or ADU content.
•	Schema
o	Update organization details, logo URL, and social profiles in inc/seo.php.
•	Video URLs
o	If your site uses different custom field names or modules for video sources, extend the lookup logic in inc/video.php.
________________________________________
Why It’s Useful for Construction Sites
This child theme is built specifically for construction / ADU builders who operate as both contractors and media publishers:
•	Buyers don’t just want a gallery—they want:
o	Plans they can filter and compare.
o	Educational content about zoning, financing, timelines, and real client stories.
•	The theme:
o	Turns Divi into a floor-plan catalog + learning hub.
o	Uses real user behavior (views & clicks) to:
	Surface the most popular plans.
	Show the most engaged content.
	Give the marketing team visibility into what prospects actually care about.
It stays close to vanilla WordPress + Divi, keeps dependencies minimal, and is designed to be transparent, inspectable, and extensible for agencies and developers building similar construction-focused sites.

