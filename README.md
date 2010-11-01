# Mute Screamer #

[PHPIDS](http://phpids.org/) for [Wordpress](http://wordpress.org/).

## Minimum Requirements ##

* PHP 5.2
* Wordpress 3.0

## Installation ##

1. Copy the mute-screamer folder into /wp-content/plugins
2. Activate Mute Screamer via the plugins page in the Wordpress admin
3. Checkout WP-Admin -> Settings -> Mute Screamer to configure

## Features ##

* View attack logs. Go to WP-Admin -> Dashboard -> Intrusions
* Send alert emails
* Configure PHPIDS exceptions, html and json fields
* Display a warning page
* Log users out of WP Admin
* Auto update default_filter.xml and Converter.php from phpids.org
* Auto update will show a diff of changes to be applied
* Removes all options and database tables when deleted via the Plugins admin page

## Screen Shots ##

![Intrusion logs](http://github.com/ampt/mute-screamer/raw/master/screenshot-1.png)

Intrusion logs

![Auto update diff confirmation](http://github.com/ampt/mute-screamer/raw/master/screenshot-2.png)

Auto update diff
