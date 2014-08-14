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

  
  //Write timeslot edits
  if (isset($_POST['command']) && $_POST['command'] == 'write_slot'){
    if ($_POST['timeslot']['id'] == -1){
      $where = NULL;
    }
    else{
      $where = array('id' => $_POST['timeslot']['id']);
    }
    $inputs['task_id'] = $_POST['timeslot']['task_id'];
    $inputs['begin'] = $_POST['timeslot']['begin'];
    $inputs['end'] = $_POST['timeslot']['end'];
    if ($inputs['end'] == ''){
      $inputs['end'] = NULL;
    }
    $inputs['desc'] = $_POST['timeslot']['desc'];
    $result = lwt_database_write(DB_NAME, 'timeslots', $inputs, $where);
  }
  
  //Write task edits
  if (isset($_POST['command']) && $_POST['command'] == 'write_task'){
    if ($_POST['task']['id'] == -1){
      $where = NULL;
    }
    else{
      $where = array('id' => $_POST['task']['id']);
    }
    $inputs['user_id'] = $_SESSION['authenticated']['id'];
    $inputs['parent_id'] = $_POST['task']['parent_id'];
    $inputs['title'] = $_POST['task']['title'];
    $inputs['desc'] = $_POST['task']['desc'];
    if ($inputs['desc'] == ''){
      $inputs['desc'] = NULL;
    }
    $inputs['budget'] = $_POST['task']['budget'];
    if ($inputs['budget'] == '' || !is_numeric($inputs['budget'])){
      $inputs['budget'] = NULL;
    }
    $inputs['progress'] = $_POST['task']['progress'];
    if ($inputs['progress'] == '' || !is_numeric($inputs['progress'])){
      $inputs['budget'] = 0;
    }
    $result = lwt_database_write(DB_NAME, 'tasks', $inputs, $where);
  }

  // show task tree
  lwt_render_tasktree();
  
  // Show tasks on selected task tree
  if (isset($_POST['task_id']) && is_numeric($_POST['task_id'])){
    $big_diff = 0;
    $timeslots = lwt_database_fetch_raw(DB_NAME, "SELECT * from `timeslots` WHERE `task_id` = {$_POST['task_id']} ORDER BY `begin` DESC");
?>
      <table style="font-family: LiberationSansNarrow; font-size: 14px;">
        <thead>
          <tr>
            <th style="width: 100px">Start</th>
            <th style="width: 100px">Finish</th>
            <th style="width: 50px">Duration</th>
            <th>Activity</th>
          </tr>
        </thead>
        <tbody>
<?php
    if (count($timeslots)>0){
      foreach ($timeslots as $timeslot){
        $begin = substr($timeslot['begin'], 0, 16);
        $begin_unix = strtotime($begin);
        $end = substr($timeslot['end'], 0, 16);
        $end_unix = strtotime($end);
        $diff_seconds = $end_unix - $begin_unix;
        $big_diff += $diff_seconds;
        if ($diff_seconds < 0){
          $diff = '0:00';
        }
        else{
          $hh = floor($diff_seconds / 3600);
          $mm = str_pad(floor(($diff_seconds - $hh * 3600) / 60), 2, '0', STR_PAD_LEFT);
          $diff = "$hh:$mm";
        }
        
?>
          <tr class="hand" onclick="ajaxPostLite('task_id=<?php echo $timeslot['task_id']; ?>&timeslot_id=<?php echo $timeslot['id']; ?>', '/ajax/', 'timetracker','');">
            <td style="text-align: right;"><?php echo $begin; ?></td>
            <td style="text-align: right;"><?php echo $end; ?></td>
            <td style="text-align: right;"><?php echo $diff; ?></td>
            <td style="text-align: left;"><?php echo $timeslot['desc']; ?></td>
          </tr>
<?php
      }
    }
    if ($big_diff < 0){
      $diff = '0:00';
    }
    else{
      $hh = floor($big_diff / 3600);
      $mm = str_pad(floor(($big_diff - $hh * 3600) / 60), 2, '0', STR_PAD_LEFT);
      $diff = "$hh:$mm";
    }
?>
        </tbody>
        <tfoot>
          <tr class="hand" onclick="ajaxPostLite('task_id=<?php echo $_POST['task_id']; ?>&timeslot_id=-1', '/ajax/', 'timetracker','');">
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td style="text-align: right; font-weight: bold;"><?php echo $diff; ?></td>
            <td style="text-align: left; font-weight: bold;">Total</td>
          </tr>
        </tfoot>
      </table>
<?php
  }
  else{
    echo "Please select a task";
  } 
  
  // Dialog box would be used to show any actively edited timeslot
  if (isset($_POST['timeslot_id']) && is_numeric($_POST['timeslot_id'])){
    if ($_POST['timeslot_id'] == -1){
      $timeslot = array(
        'id' => $_POST['timeslot_id'],
        'task_id' => $_POST['task_id'],
        'begin' => date('Y-m-d H:i') . ':00',
        'end' => '',
        'desc' => '',
      );
    }
    else{
      $timeslots = lwt_database_fetch_raw(DB_NAME, "SELECT * , (SELECT `title` FROM `tasks` WHERE `tasks`.`id` = `timeslots`.`task_id`) as `task_name` FROM `timeslots` WHERE `id` = {$_POST['timeslot_id']}");
      $timeslot = $timeslots[0];
      if ($timeslot['end'] == NULL){
        $timeslot['end'] = date('Y-m-d H:i') . ':00';
      }
    }
?>
    <div class="dialogue">
      <h3>Editing Timeslot</h3>
      <form action="/ajax/" method="post" onsubmit="event.preventDefault();ajaxPost(this, 'timetracker', '');">
        <input type="hidden" name="command" value="write_slot" />
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
  
  // Dialog box for editing/creating task
  if (isset($_POST['command']) && $_POST['command'] == 'edit_task'){
    if ($_POST['task_id'] == -1){
      $task = array(
        'id' => -1,
        'parent_id' => $_POST['parent_id'],
        'title' => '',
        'desc' => '',
        'budget' => '',
        'progress' => 0,
      );
      $taskid = $_POST['parent_id'];
    }
    else{
      $tasks = lwt_database_fetch_simple(DB_NAME, 'tasks', NULL, array('id' => $_POST['task_id']));
      $task = $tasks[0];
      $taskid = $_POST['task_id'];
    }
?>
    <div class="dialogue">
      <h3>Editing Task</h3>
      <form action="/ajax/" method="post" onsubmit="event.preventDefault();ajaxPost(this, 'timetracker', '');">
        <input type="hidden" name="command" value="write_task" />
        <input type="hidden" name="task[id]" value="<?php echo $task['id']; ?>" />
        <input type="hidden" name="task[parent_id]" value="<?php echo $task['parent_id']; ?>" />
        <label for="task[title]">Title</label><input type="text" name="task[title]" value="<?php echo $task['title']; ?>" /><br />
        <label for="task[budget]">Budget</label><input type="text" name="task[budget]" value="<?php echo $task['budget']; ?>" /><br />
        <label for="task[progress]">Progress</label><select name="task[progress]">
<?php
    for ($i = 0; $i <= 100; $i += 10){
      if ($i == $task['budget']){
        $selected = 'selected';
      }
      else{
        $selected = '';
      }
?>
          <option value="<?php echo $i; ?>"><?php echo $i; ?>%</option>
<?php
    }
?>
        </select><br />
        <label for="task[desc]">Description</label><textarea name="task[desc]"><?php echo $task['desc']; ?></textarea><br />
        <input type="submit" name="write" value="Save" /><button onclick="event.preventDefault();ajaxPostLite('task_id=<?php echo $taskid; ?>', '/ajax/', 'timetracker', '');">Close</button>
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
      <li>[<a href="javascript:;" onclick="ajaxPostLite('task_id=-1&parent_id=<?php echo $task['id']; ?>&command=edit_task', '/ajax/', 'timetracker', '');" onmousemove="showTooltip(event, 'Add Subtask');" onmouseout="hideTooltip(event);">+</a>] <span class="hand" onclick="ajaxPostLite('task_id=<?php echo $task['id']; ?>', '/ajax/', 'timetracker','');" ondblclick="ajaxPostLite('task_id=<?php echo $task['id']; ?>&command=edit_task', '/ajax/', 'timetracker','');" ><?php echo $task['title']; ?></span>
<?php lwt_render_tasktree($task['id']); ?>
      </li>
<?php
    }
?>
    </ul>
<?php
  }
}
