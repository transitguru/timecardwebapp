<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Functions to check for installation in the databases, and installs them
 * if user accepts
 * 
 * @todo create these functions and databases
 * 
 */


/**
 * Checks to see if the database and settings are defined
 * 
 * @return Request URI if no install needed
 */
 
function lwt_install($request){
  $install = FALSE;
  
  // Check to see if lwt can log in
  $creds = lwt_database_get_credentials(DB_NAME);
  $conn = mysqli_connect('localhost', $creds['user'], $creds['pass'], DB_NAME, DB_PORT);
  if (!$conn){
    $install = TRUE;
  }
  
  // Check for existence of admin user password or homepate
  if (!$install){
    $users = lwt_database_fetch_simple(DB_NAME, 'passwords', NULL, array('user_id' => 1));
    if (count($users) == 0){
      $install = TRUE;
    }
  }
  
  if ($install && $request != '/install/'){
    header('Location: /install/');
    exit;
  }
  elseif ($install){
    if (isset($_POST['db'])){
      $db_name = DB_NAME;
      $db_pass = DB_PASS;
      $db_host = DB_HOST;
      $db_user = DB_USER;
      
      if ($_POST['db']['admin_pass'] == $_POST['db']['confirm_pass']){
        $conn = mysqli_connect(DB_HOST, $_POST['db']['root_user'], $_POST['db']['root_pass'], null, DB_PORT);
        if (!$conn){
          echo 'error in database settings!';
        }
        else{
          $error = false;
          
          // Drop the database if it already exists (fresh install)
          $sql = "DROP DATABASE IF EXISTS `{$db_name}`";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken drop";
          }
          
          // Create the LWT database
          $sql = "CREATE DATABASE `{$db_name}` DEFAULT CHARACTER SET utf8";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken create db";
          }
          
          // The following lines must be uncommented if replacing a user
          $sql = "DROP USER '{$db_user}'@'{$db_host}'";
          $conn->real_query($sql);
          
          // Create the database user
          $sql = "CREATE USER '{$db_user}'@'{$db_host}' IDENTIFIED BY '{$db_pass}'";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken create user";
          }
          
          // Grant user to database
          $sql = "GRANT ALL PRIVILEGES ON `{$db_name}`.* TO '{$db_user}'@'{$db_host}'";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken grant";
          }
          
          // Grant user to database
          $sql = "FLUSH PRIVILEGES";
          $conn->real_query($sql);
          if ($conn->errno > 0){
            $error = true;
            echo "Broken flush";
          }
          
          
          // Close the temporary connection
          $conn->close();
          
          if ($error){
            // Show that there is an error
            echo 'Error creating database';
          }
          else{
            // Install the databases using the database functions
            $status = lwt_install_database();
            if ($status == 0){
              header("Location: /");
            }
            else{
              echo "There was an error in the installation process!";
            }
          }
        }
      }
      else{
        echo "passwords don't match";
      }
    }
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Install Time-Ca-rd We:ba:pp</title>
  </head>
  <body>
    <p>The site appears to not be installed, Please fill out the fields below to begin installing the TimeCard Webapp. Before you do so, make sure to adjust the site's <strong>/includes/functions/settings.inc.php</strong> file to your desired settings.</p>
    <form action="" method="post" >
      <table>
        <tr><td><label for="db[root_user]">DB Root User</label></td><td><input type="text" name="db[root_user]" /></td></tr>
        <tr><td><label for="db[root_pass]">DB Root Password</label></td><td><input type="password" name="db[root_pass]" /></td></tr>
        <tr><td><label for="db[admin_user]">Website Admin User</label></td><td><input type="text" name="db[admin_user]" /></td></tr>
        <tr><td><label for="db[admin_pass]">Website Admin Password</label></td><td><input type="password" name="db[admin_pass]" /></td></tr>
        <tr><td><label for="db[confirm_pass]">Confirm Website Admin Password</label></td><td><input type="password" name="db[confirm_pass]" /></td></tr>
        <tr><td><label for="db[admin_email]">Website Admin Email</label></td><td><input type="text" name="db[admin_email]" /></td></tr>
      </table>
      <input type="submit" name="db[submit]" value="Install" />
    </form>
  </body>
</html>
<?php
    exit;
    
  }
  return $request;
}

function lwt_install_database(){
  $file = $_SERVER['DOCUMENT_ROOT'] . '/includes/sql/schema.sql';
  $sql = file_get_contents($file);
  
  $status = lwt_database_multiquery(DB_NAME, $sql);

  if ($status['error'] != 0){
    return $status['error'];
  }
  echo "<pre>";
  //Create the group that is "root" (typically no users get assigned this group except the admin)
  $status = lwt_database_write_raw(DB_NAME, "INSERT INTO `groups` (`name`) VALUES ('Everyone')");
  echo $status['error'] . "\n";
  $status = lwt_database_write_raw(DB_NAME, "UPDATE `groups` SET `id`=0");
  echo $status['error'] . "\n";
  $status = lwt_database_write_raw(DB_NAME, "ALTER TABLE `groups` AUTO_INCREMENT=1");
  echo $status['error'] . "\n";
  
  //Add groups starting back at ID 1
  $sql = "INSERT INTO `groups` (`name`) VALUES ('Unauthenticated'), ('Authenticated'), ('Internal'), ('External')";
  $status = lwt_database_write_raw(DB_NAME, $sql);  
  echo $status['error'] . "\n";
  
  // Set group hierarchy
  $sql = "INSERT INTO `group_hierarchy` (`parent_id`,`group_id`) VALUES 
  (0,(SELECT `id` FROM `groups` WHERE `name`='Everyone')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Unauthenticated')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Authenticated')), 
  ((SELECT `id` FROM `groups` WHERE `name`='Authenticated'), (SELECT `id` FROM `groups` WHERE `name`='Internal')),
  ((SELECT `id` FROM `groups` WHERE `name`='Authenticated'), (SELECT `id` FROM `groups` WHERE `name`='External'))";
  $status = lwt_database_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Create the "unauthenticated" role (noone is associated to this role!)
  $status = lwt_database_write_raw(DB_NAME, "INSERT INTO `roles` (`name`, `desc`) VALUES ('Unauthenticated User', 'Non-logged in user')");
  echo $status['error'] . "\n";
  $status = lwt_database_write_raw(DB_NAME, "UPDATE `roles` SET `id`=0");
  echo $status['error'] . "\n";
  $status = lwt_database_write_raw(DB_NAME, "ALTER TABLE `roles` AUTO_INCREMENT=1");
  echo $status['error'] . "\n";
  
  // Create the Administrator role (always set it to an ID of one) and the Authenticated User
  $sql = "INSERT INTO `roles` (`name`, `desc`) VALUES 
  ('Administrator','Administers website'),
  ('Authenticated User', 'Basic user')";
  $status = lwt_database_write_raw(DB_NAME, $sql);
  echo $status['error'] . "\n";
  
  // Add the Admin User
  $inputs = array(
    'login' => $_POST['db']['admin_user'],
    'firstname' => 'Site',
    'lastname' => 'Administrator',
    'email' => $_POST['db']['admin_email'],
    'desc' =>  'Site Administrator',
  );
  $status = lwt_database_write(DB_NAME, 'users', $inputs);
  echo $status['error'] . "\n";
  $status = lwt_database_write(DB_NAME, 'user_roles', array('role_id' => 1, 'user_id' => 1));
  echo $status['error'] . "\n";
  $status = lwt_database_write(DB_NAME, 'user_groups', array('group_id' => 0, 'user_id' => 1));
  echo $status['error'] . "\n";
  $status = lwt_auth_session_setpassword(1, $_POST['db']['admin_pass']);
  echo $status['error'] . "\n";
  
  echo "</pre>";
  return 0;
}

