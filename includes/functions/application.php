<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Renders the main timetracker page
 */

/**
 * Updates the timetracker application using AJAX
 *
 */
function lwt_render_timetracker(){

  // Left pane shows task tree
  lwt_render_tasktree();
  
  
  // Right pane shows tasks on selected task tree
  if (isset($_POST['task_id']) && is_numeric($_POST['task_id'])){
    $timeslots = lwt_database_fetch_raw(DB_NAME, "SELECT * from `timeslots` WHERE `task_id` = {$_POST['task_id']} ORDER BY `begin` DESC");
?>
      <table>
        <thead>
          <tr>
            <th style="width: 150px">Start</th>
            <th style="width: 150px">Finish</th>
            <th>Activity</th>
          </tr>
        </thead>
        <tbody>
<?php
    if (count($timeslots)>0){
      foreach ($timeslots as $timeslot){
?>
          <tr class="hand" onclick="ajaxPostLite('task_id=<?php echo $timeslot['task_id']; ?>&timeslot_id=<?php echo $timeslot['id']; ?>', '/ajax/', 'timetracker','');">
            <td><?php echo $timeslot['begin']; ?></td>
            <td><?php echo $timeslot['end']; ?></td>
            <td><?php echo $timeslot['desc']; ?></td>
          </tr>
<?php
      }
    }
?>
          <tr class="hand" onclick="ajaxPostLite('task_id=<?php echo $_POST['task_id']; ?>&timeslot_id=-1', '/ajax/', 'timetracker','');">
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>(new task)</td>
          </tr>
        </tbody>
      </table>
<?php
  }
  else{
    echo "Please select a task";
  } 
  
  // Dialog box would be used to show any actively edited task
  if (isset($_POST['timeslot_id']) && is_numeric($_POST['timeslot_id'])){
    echo "editing timeslot {$_POST['timeslot_id']}";
  }

}

function lwt_render_tasktree($parent_id = NULL){
  $tasks = array();
  if ($parent_id === NULL){
    $tasks = lwt_database_fetch_raw(DB_NAME, "SELECT * FROM `tasks` WHERE `parent_id` IS NULL AND `user_id` = {$_SESSION['authenticated']['id']}");
  }
  else{
    $tasks = lwt_database_fetch_raw(DB_NAME, "SELECT * FROM `tasks` WHERE `parent_id`={$parent_id} AND `user_id` = {$_SESSION['authenticated']['id']}");
  }
  if (count($tasks)>0){
?>
    <ul>
<?php
    foreach ($tasks as $task){
?>
      <li><span class="hand" onclick="ajaxPostLite('task_id=<?php echo $task['id']; ?>', '/ajax/', 'timetracker','');" ><?php echo $task['title']; ?></span>
<?php lwt_render_tasktree($task['id']); ?>
      </li>
<?php
    }
?>
    </ul>
<?php
  }
}
