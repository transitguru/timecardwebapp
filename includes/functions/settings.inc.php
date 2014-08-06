<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Provides the minimal necessary site settings
 */

function lwt_settings_load(){
  define('DB_HOST', 'localhost');     /**< The host for the database connection */
  define('DB_NAME', 'timecardwebapp'); /**< The database name for the application's data */
  define('DB_USER', 'tcw');           /**< The username for the application's database user */
  define('DB_PASS', 'Wejkahleliuhj'); /**< The password for the application's database user */
  define('DB_PORT', 3306);            /**< The port for the database connection */
  return TRUE;
}
  
