timecardwebapp
==============

A web-based time tracker written in PHP, utilizing a MySQL backend.

Requirements
============
 - Apache2
 - PHP5
   - libapache2-mod-php5
   - php5-mysql
 
Installation
============
1. Install the prerequisites, make sure you restart Apache after installing the PHP packages
2. Git clone the repository https://github.com/transitguru/timecardwebapp
3. Copy the cloned folder to your web root
4. Create a MySQL user and database
  - CREATE USER 'tcw'@'localhost' IDENTIFIED BY 'yourpassword';
  - CREATE DATABASE timecardwebapp;
  - GRANT ALL on timecardwebapp.* to 'tcw'@'localhost'
5. Import the prebuilt schema:
  - $ mysql -u tcw -p timecardwebapp < includes/sql/schema.sql
  - Use the password you set above
6. Modify the configuration file  with your database credentials and database name
in includes/functions/settings.inc.php
7. Visit http://localhost/timecardwebapp to finish the install
