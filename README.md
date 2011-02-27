# Mute Screamer #

[PHPIDS](http://phpids.org/) for [WordPress](http://wordpress.org/).

## Minimum Requirements ##

* PHP 5.2
* WordPress 3.0

## Installation ##

Use automatic installer or:

1. Copy the mute-screamer folder into /wp-content/plugins
2. Activate Mute Screamer via the plugins page in the WordPress admin
3. Checkout WP-Admin -> Settings -> Mute Screamer to configure

## Features ##

* View attack logs. Go to WP-Admin -> Dashboard -> Intrusions
* Send alert emails
* Configure PHPIDS exceptions, html and json fields
* Display a warning page and message
* Log users out of WP Admin
* Auto update default_filter.xml and Converter.php from phpids.org
* Auto update will show a diff of changes to be applied
* Ban user when attack is over the ban threshold
* Ban user when attack exceeds the repeat attack limit
* Display ban template and message

## Screen Shots ##

![Intrusion logs](https://github.com/ampt/mute-screamer/raw/master/screenshot-1.png)

Intrusion logs

![Auto update diff confirmation](https://github.com/ampt/mute-screamer/raw/master/screenshot-2.png)

Auto update diff
