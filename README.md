timecardwebapp
==============

A web-based time tracker written in PHP, utilizing a MariaDB or MySQL backend. This is currently being developed and a beta release is expected in the winter early in 2015.

Requirements
============
 - Apache2
   - ModRewrite must be enabled
   - Your VirtualHost must have the AllowOverride All applied to your webroot
 - PHP5
   - libapache2-mod-php5
   - php5-mysqlnd
 
Installation
============
This is subject to change as a more automated install process would be provided.
1. Install the prerequisites, make sure you restart Apache after installing the PHP packages
2. Git clone the repository https://github.com/transitguru/timecardwebapp
3. Copy the cloned folder to a directory that apache can access, set it as your webroot (plans are in the works to enable use within a pre-existing web root)
4. Visit your webapp's root web address, an installer form should show up
