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
  
  if (isset($_POST['command']) && $_POST['command'] == 'write'){
    if ($_POST['timeslot']['id'] == -1){
      $where = NULL;
    }
    else{
      $where = array('id' => $_POST['timeslot']['id']);
    }
    $inputs['task_id'] = $_POST['timeslot']['task_id'];
    $inputs['begin'] = $_POST['timeslot']['begin'];
    $inputs['end'] = $_POST['timeslot']['end'];
    $inputs['desc'] = $_POST['timeslot']['desc'];
    $result = lwt_database_write(DB_NAME, 'timeslots', $inputs, $where);
  }
  
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
    if ($_POST['timeslot_id'] == -1){
      $timeslot = array(
        'id' => $_POST['timeslot_id'],
        'task_id' => $_POST['task_id'],
        'begin' => date('Y-m-d H:i:s'),
        'end' => '',
        'desc' => '',
      );
    }
    else{
      $timeslots = lwt_database_fetch_raw(DB_NAME, "SELECT * , (SELECT `title` FROM `tasks` WHERE `tasks`.`id` = `timeslots`.`task_id`) as `task_name` FROM `timeslots` WHERE `id` = {$_POST['timeslot_id']}");
      $timeslot = $timeslots[0];
      if ($timeslot['end'] == NULL){
        $timeslot['end'] = date('Y-m-d H:i:s');
      }
    }
?>
    <div class="dialogue">
      <h3>Editing Timeslot</h3>
      <form action="/ajax/" method="post" onsubmit="event.preventDefault();ajaxPost(this, 'timetracker', '');">
        <input type="hidden" name="command" value="write" />
        <input type="hidden" name="timeslot[id]" value="<?php echo $timeslot['id']; ?>" />
        <input type="hidden" name="timeslot[task_id]" value="<?php echo $timeslot['task_id']; ?>" />
        <input type="hidden" name="task_id" value="<?php echo $timeslot['task_id']; ?>" />     
        <label for="timeslot[begin]">Start</label><input type="text" name="timeslot[begin]" value="<?php echo $timeslot['begin']; ?>" /><br />
        <label for="timeslot[end]">Finish</label><input type="text" name="timeslot[end]" value="<?php echo $timeslot['end']; ?>" /><br />
        <label for="timeslot[desc]">Description</label><textarea name="timeslot[desc]"><?php echo $timeslot['desc']; ?></textarea><br />
        <input type="submit" name="write" value="Save" /><button onclick="event.preventDefault();ajaxPostLite('task_id=<?php echo $timeslot['task_id']; ?>', '/ajax/', 'timetracker', '');">Close</button>
      </form>
    </div>
    <div class="dialoguebg">&nbsp;</div>

<?php 
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
