<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Bootstrap file for TimeCardWebapp
 * 
 * This bootstraps the entire application and will provide a means to 
 * access all available modules.
 * 
 */

// Load all core modules
$PATH = $_SERVER['DOCUMENT_ROOT'] . '/includes/functions';
$includes = scandir($PATH);
foreach ($includes as $include){
  if (is_file($PATH . '/' . $include) && fnmatch("*.php", $include)){
    include ($PATH . '/' . $include);
  }
}


// Load settings so that database can connect
lwt_settings_load();

$request = $_SERVER['REQUEST_URI']; /**< Request URI from user */

lwt_install($request);

$maintenance = FALSE; /**< Set maintenance mode */
$request = lwt_auth_session_gatekeeper($request, $maintenance);

// Process Page
$success = lwt_render_wrapper($request); /**< Returns true if function completes! */

