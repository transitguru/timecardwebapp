<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Functions for processing requests and finding the right page
 * 
 * @todo put the lookup information into the core database
 */

/**
 * Processes Login on login page (must be tied to a content item in the database)
 * 
 * @return void
 * 
 */

function lwt_process_authentication(){
  if (isset($_SESSION['redirect']) && $_SESSION['redirect'] != ''){
    $redirect = $_SESSION['redirect'];
  }
  elseif (!isset($_SESSION['requested_page']) || $_SESSION['requested_page'] == '/login/'){
    $redirect = "/";
  }
  else{
    $redirect = $_SESSION['requested_page'];
  }
  if (isset($_POST['login']) && $_POST['login'] == 'Log In') {
     // strip whitespace from user input
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // authenticate user
    $success = lwt_auth_authenticate_user($username, $password);
    if ($success){
      session_regenerate_id();
      unset($_SESSION['redirect']);
      header("Location: {$redirect}");
      exit;
    }
    else{
      header("Location: /login/");
      exit;
    }
  }
}

/**
 * Processes Logout on logout page (must be tied to a content item in the database)
 * 
 * @return void
 * 
 */

function lwt_process_logout(){
  if (isset($_COOKIE[session_name()])){
    setcookie(session_name(), '', time()-86400, '/');
  }
  // end session and redirect
  session_destroy();
  header("Location: /");
  exit;
}

/**
 * Provides a title for a given request URI and runs any preprocess calls
 * @todo migrate gatekeeper logic here and use permissions database values
 * 
 * @param string $request Request URI
 * @return string Title to place in Title tags
 */
function lwt_process_title($request){
  // Switchboard
  if ($request == "/"){
    $output['access'] = TRUE;
    $output['title'] = "TimeCard Webapp";
  }
  elseif ($request == "/login/"){
    if ($_SESSION['authenticated']['user']){
      header("location: /");
    }
    else{
      lwt_process_authentication();
      $output['access'] = TRUE;
      $output['title'] = "Login";
    }
  }
  elseif ($request == "/logout/"){
    lwt_process_logout();
  }
  elseif ($request == "/profile/"){
    if ($_SESSION['authenticated']['user']){
      $output['access'] = TRUE;
    }
    else{
      $output['access'] = FALSE;
    }
    $output['title'] = "Profile";
  }
  elseif ($request == "/password/"){
    if ($_SESSION['authenticated']['user']){
      $output['access'] = TRUE;
    }
    else{
      $output['access'] = FALSE;
    }
    $output['title'] = "Password";
  }
  else{
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    $output['access'] = FALSE;
    $output['title'] = 'Not Found';
  }
  return $output;
}

  

/**
 * Processes permissions to content based on group and role
 * 
 * @param int $content_id Content id
 * 
 * @return boolean Access to content
 */
 
function lwt_process_permissions($content_id, $groups, $roles){
  //First, never ever lockout THE Admin user
  if (isset($_SESSION['authenticated']['user_id']) &&  $_SESSION['authenticated']['user_id'] == 1){
    return TRUE;
  }
  
  //Assume no access
  $access = FALSE;
  
  $content_access = lwt_database_fetch_simple(DB_NAME, 'group_access', NULL, array('content_id' => $content_id));
  if (count($content_access)>0){
    foreach ($content_access as $record){
      if (in_array($record['group_id'],$groups)){
        $access = TRUE;
      }
    }
  }
  
  // Check for Role overrides (if unset, means everyone can access!)
  $role_access = lwt_database_fetch_simple(DB_NAME, 'role_access', NULL, array('content_id' => $content_id));
  if (count($role_access)>0){
    //Reset access to false
    $access = FALSE;
    foreach ($role_access as $record){
      if (in_array($record['role_id'],$roles)){
        $access = TRUE;
      }
    }
  }
  
  return $access;
}

/**
 * Provides user group IDs that can be accessed, based on input
 * 
 * @param type $group Group ID where the search up and down the tree begins
 * @param array $groups Group IDs already found from previous iterations
 * @return array All Group IDs that a user may access
 */
function lwt_process_grouptree($group, $groups){
  if ($group === NULL){
    return $groups;
  }
  
  //find children
  $groups = lwt_process_get_children($group, $groups);
  
  //find parents until we reach root
  $search = $group;
  $loop = true;
  while($loop){
    $record = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('group_id' => $group));
    if ($record[0]['parent_id'] == 0){
      $loop = false;
      $groups[0] = 0;
    }
    else{
      $groups[$search] = $search = $record[0]['parent_id'];
    }
  }
  return $groups;  
}

/**
 * Finds children to the group IDs for a given parent
 * 
 * @param int $parent Parent ID to find the children
 * @param type $groups Array of group IDs that are available to keep appending
 * @return array Array of Group IDs (this gets appended to the input)
 */

function lwt_process_get_children($parent, $groups){
  $groups[$parent] = $parent;
  $children = lwt_database_fetch_simple(DB_NAME, 'group_hierarchy', NULL, array('parent_id' => $parent));
  if (count($children)>0){
    foreach ($children as $child){
      if ($child['group_id'] != 0){
        $groups = lwt_process_get_children($child['group_id'],$groups);
      }
    }
  }
  return $groups;
}

/**
 * Finds children to the content IDs for a given parent
 * 
 * @param int $parent Parent ID to find the children
 * @param type $contents Array of content IDs that are available to keep appending
 * @return array Array of Content IDs (this gets appended to the input)
 */

function lwt_process_get_contentchildren($parent, $contents){
  $contents[$parent] = $parent;
  $children = lwt_database_fetch_simple(DB_NAME, 'content_hierarchy', NULL, array('parent_id' => $parent));
  if (count($children)>0){
    foreach ($children as $child){
      if ($child['content_id'] != 0){
        $contents = lwt_process_get_contentchildren($child['content_id'],$contents);
      }
    }
  }
  return $contents;
}


/**
 * Provides markup for the page_content div
 * 
 * @param string $request Request URI
 * @return string HTML Markup from the individual section that was requested
 */
function lwt_process_url($request){
  // Switchboard
  if ($request == "/"){
    lwt_render_home();
  }
  elseif ($request == "/login/"){
    lwt_render_login();
  }
  elseif ($request == "/profile/"){
    lwt_render_profile();
  }
  elseif ($request == "/password/"){
    lwt_render_password();
  }
}


/**
 * Processes File Downloads
 * 
 * @return void
 */
function lwt_process_download(){
  // Stop output buffering
  ob_clean();
  
  // Don't Cache the result
  header('Cache-Control: no-cache');
  $chars = strlen(APP_ROOT);
  $request = trim(substr($_SERVER['REQUEST_URI'],$chars),"/ ");
  
  //This is the only information that gets sent back!
  $included = $_SERVER['DOCUMENT_ROOT']."/FILES/core/".$request;
  $size = filesize($included);
  $type = mime_content_type($included);
  header('Pragma: ');         // leave blank to avoid IE errors
  header('Cache-Control: ');  // leave blank to avoid IE errors
  header('Content-Length: ' . $size);
  // This next line forces a download so you don't have to right click...
  header('Content-Disposition: attachment; filename="'.basename($included).'"');
  header('Content-Type: ' .$type);
  sleep(0); // gives browser a second to digest headers
  readfile($included);
  exit;
}

