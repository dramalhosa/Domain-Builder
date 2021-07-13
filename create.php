<?php
  if($data['access'] != "Super User"){
    redirect('pages/index');
  }
  require APPROOT . '/views/inc/header.php';
?>

  <h1><?php echo $data['title']; ?></h1>
  <p><?php echo $data['description']; ?></p>

  <!-- Submit page to self -->
  <?php if(isset($data['err'])){ echo  "<div class='alert alert-warning'>" . $data['err']  . "</div>"; } ?>
  <form action="<?php echo URLROOT ?>domains/create" method="post" name="upload_excel" enctype="multipart/form-data">
    <fieldset>
      <legend>Import</legend>
      <div class="form-group">
        <label class="col-md-4 control-label" for="filebutton">Select excel file to import</label>
        <div class="col-md-4">
          <input type="file" name="userList" id="file" class="input-large">
        </div>
      </div>
    </fieldset>
    <label>Select what part of the workbook to import</label>
    <div class="form-group" style="width:15%;min-width:100px">
      <select class="form-control" name="option">
        <option selected>Select an option</option>
        <option>User List</option>
        <option>Call Flow</option>
        <option>Everything</option>
      </select>
    </div>
    <a href="{Your URL}/DomainBuilderTemplate.xlsx" target="_blank">Download Template</a>
    <div class="col-auto my-1">
      <button type="submit" name="submit" value="validate" class="btn btn-primary">Validate</button>
    </div>
  </form>
</div>
<?php
if(!empty($data['domainCreate']->warnings)){
  echo "<h4>There are " . count($data['domainCreate']->warnings) . " warnings that should be addressed</h4>";
  foreach($data['domainCreate']->warnings as $key => $warnings){
    echo $key+1 . ":  " . $warnings . "<br>";
  }
}

  if($_POST['submit'] == "validate"){
    if(!empty($data['domainCreate']->errors)){
      echo "<h4>There are " . count($data['domainCreate']->errors) . " errors that need to be addressed</h4>";
      foreach($data['domainCreate']->errors as $key => $error){
        echo $key+1 . ":  " . $error . "<br>";
      }

    } else {
      $data['domainCreate']->option = $_POST['option'];
      echo "<h3>Description</h3>";
      echo "<strong>Name:  </strong>" . $data['domainCreate']->name . "<br>";
      echo "<strong>Reseller:  </strong>" . $data['domainCreate']->reseller . "<br>";
      echo "<strong>Description:  </strong>" . $data['domainCreate']->description . "<br>";
      echo "<strong>Email Sender:  </strong>" . $data['domainCreate']->emailSender . "<br>";
      echo "<strong>Dial Policy:  </strong>" . $data['domainCreate']->dialPolicy . "<br>";
      echo "<strong>Time Zone:  </strong>" . $data['domainCreate']->timeZone . "<br>";
      echo "<strong>Area Code:  </strong>" . $data['domainCreate']->areaCode . "<br>";
      echo "<strong>Caller ID Name:  </strong>" . $data['domainCreate']->callerIDName . "<br>";
      echo "<strong>Caller ID Number:  </strong>" . $data['domainCreate']->callerIDNumber . "<br>";
      echo "<strong>911 Number:  </strong>" . $data['domainCreate']->emergencyNumber . "<br>";
      echo "<strong>911 Email Notification:  </strong>" . $data['domainCreate']->emergencyEmail . "<br>";
      echo "<strong># of Call Paths:  </strong>" . $data['domainCreate']->callPaths . "<br>";
      echo "<strong>Preferred Server:  </strong>" . $data['domainCreate']->preferredServer . "<br>";
      echo "<strong>Port Date:  </strong>" . $data['domainCreate']->portDate . "<br>";

    if($_POST['option'] == "User List" || $_POST['option'] == "Everything"){
      echo "<br><h3>Users</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">First</th>
            <th scope="col">Last</th>
            <th scope="col">Login</th>
            <th scope="col">Password</th>
            <th scope="col">Email</th>
            <th scope="col">PIN</th>
            <th scope="col">Department</th>
            <th scope="col">Site</th>
            <th scope="col">Voicemail</th>
            <th scope="col">Answer</th>
            <th scope="col">Time Zone</th>
            <th scope="col">Area Code</th>
            <th scope="col">Caller ID Number</th>
            <th scope="col">Caller ID Name</th>
            <th scope="col">911 Caller ID</th>
            <th scope="col">Dial Permission</th>
            <th scope="col">Audio Directory</th>
            <th scope="col">Visual Directory</th>
            <th scope="col">Transcribe</th>
            <th scope="col">Email Voicemail</th>
            <th scope="col">Recording</th>
            <th scope="col">Scope</th>
            <th scope="col">License</th>
            <th scope="col">Hardware</th>
            <th scope="col">MAC</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->users as $users){
          echo "<tr>";
          foreach($users as $user){
            echo "<td>" . $user . "</td>";
          }
          echo "</tr>";
        }
      echo "</table>";
    }

    if($_POST['option'] == "Call Flow" || $_POST['option'] == "Everything"){
      echo "<br><h3>Mailbox</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">First</th>
            <th scope="col">Last</th>
            <th scope="col">Login</th>
            <th scope="col">Password</th>
            <th scope="col">Email</th>
            <th scope="col">PIN</th>
            <th scope="col">Department</th>
            <th scope="col">Site</th>
            <th scope="col">Email Voicemail</th>
            <th scope="col">Shared Line</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->mailboxes as $mailboxes){
          echo "<tr>";
          foreach($mailboxes as $mailbox){
            echo "<td>" . $mailbox . "</td>";
          }
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Auto Attendants</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">Name</th>
            <th scope="col">Options</th>
            <th scope="col">Treatment</th>
            <th scope="col">Destination</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->AAs as $AAs){
          echo "<tr>";
            echo "<td>" . $AAs['ext'] . "</td>";
            echo "<td>" . $AAs['name'] . "</td>";
          foreach($AAs['options'] as $key => $options){
            echo "<tr>";
              echo "<td></td><td></td>";
              echo "<td>" . $key . "</td>";
              list($treatment, $destination) = explode(" - ", $options);
              echo "<td>" . $treatment . "</td>";
              echo "<td>" . $destination . "</td>";
            echo "</tr>";
          }
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Time Frames</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Name</th>
            <th scope="col">Type</th>
            <th scope="col">Times</th>
            <th scope="col">From</th>
            <th scope="col">To</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->timeFrames as $timeFrames){
          echo "<tr>";
            echo "<td>" . $timeFrames['name'] . "</td>";
            echo "<td>" . $timeFrames['type'] . "</td>";
            if($timeFrames['type'] == "range"){
              foreach($timeFrames['times'] as $key => $time){
                echo "<tr>";
                  echo "<td></td><td></td>";
                  echo "<td>" . $key . "</td>";
                  list($from, $to) = explode(" - ", $time);
                  echo "<td>" . $from . "</td>";
                  echo "<td>" . $to . "</td>";
                echo "</tr>";
              }
            } else {
              foreach($timeFrames['times'] as $key => $time){
                echo "<tr>";
                  echo "<td></td><td></td><td></td>";
                  list($from, $to) = explode(" - ", $time);
                  echo "<td>" . $from . "</td>";
                  echo "<td>" . $to . "</td>";
                echo "</tr>";
              }
            }
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Ring Groups & Forwards</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">First</th>
            <th scope="col">Last</th>
            <th scope="col">Answer Time</th>
            <th scope="col">Unanswered Treatment</th>
            <th scope="col">Unanswered Destination</th>
            <th scope="col">Offline Treatment</th>
            <th scope="col">Offline Treatment</th>
            <th scope="col">Ring</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->utilities as $utilities){
          echo "<tr>";
          echo "<td>" . $utilities['ext'] . "</td>";
          echo "<td>" . $utilities['firstName'] . "</td>";
          echo "<td>" . $utilities['lastName'] . "</td>";
          echo "<td>" . $utilities['ringTime'] . "</td>";
          echo "<td>" . $utilities['unansweredTreatment'] . "</td>";
          echo "<td>" . $utilities['unansweredDestination'] . "</td>";
          echo "<td>" . $utilities['offlineTreatment'] . "</td>";
          echo "<td>" . $utilities['offlineDestination'] . "</td>";
          echo "<td>";
          $ringList = "";
            foreach($utilities['ring'] as $ring){
              $ringList .= $ring . ", ";
            }
            echo substr($ringList, 0, -2);
          echo "</td>";
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>ToDs</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">First</th>
            <th scope="col">Last</th>
            <th scope="col">Time Frame</th>
            <th scope="col">Treatment</th>
            <th scope="col">Destination</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->ToDs as $ToDs){
          echo "<tr>";
          echo "<td>" . $ToDs['ext'] . "</td>";
          echo "<td>" . $ToDs['firstName'] . "</td>";
          echo "<td>" . $ToDs['lastName'] . "</td>";
          echo "<td>";
          foreach($ToDs['timeFrame'] as $key => $timeFrame){
            echo "<tr>";
              echo "<td></td><td></td><td></td>";
              echo "<td>" . $key . "</td>";
              list($treatment, $destination) = explode(" - ", $timeFrame);
              echo "<td>" . $treatment . "</td>";
              echo "<td>" . $destination . "</td>";
            echo "</tr>";
          }
          echo "</td>";
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Queues</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">Name</th>
            <th scope="col">Type</th>
            <th scope="col">Department</th>
            <th scope="col">Site</th>
            <th scope="col">Require Agents</th>
            <th scope="col">Max Expected Wait</th>
            <th scope="col">Max Length</th>
            <th scope="col">Callback</th>
            <th scope="col">Voicemail</th>
            <th scope="col">Unanswered Treatment</th>
            <th scope="col">Unanswered Destination</th>
            <th scope="col">Ring Time</th>
            <th scope="col">Unavailable Treatment</th>
            <th scope="col">Unavailable Destination</th>
            <th scope="col">Agent Timeout</th>
            <th scope="col">Initial Group</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->queues as $queues){
          echo "<tr>";
          foreach($queues as $queue){
            echo "<td>" . $queue . "</td>";
          }
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Agents</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Ext</th>
            <th scope="col">Queue</th>
            <th scope="col">Wrap Up</th>
            <th scope="col">Max Calls</th>
            <th scope="col">Order</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->agents as $agents){
          echo "<tr>";
          foreach($agents as $agent){
            echo "<td>" . $agent . "</td>";
          }
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Conference Bridges</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Name</th>
            <th scope="col">Ext</th>
            <th scope="col">Leader Pin</th>
            <th scope="col">Participant Pin</th>
            <th scope="col">Max Participants</th>
            <th scope="col">Min to Start</th>
            <th scope="col">Require Leader</th>
            <th scope="col">Announce Participants</th>
            <th scope="col">Prompt Participants</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->conferences as $conferences){
          echo "<tr>";
          foreach($conferences as $conference){
            echo "<td>" . $conference . "</td>";
          }
          echo "</tr>";
        }
      echo "</table>";

      echo "<br><h3>Phone Numbers</h3>";
      ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th scope="col">Number</th>
            <th scope="col">Treatment</th>
            <th scope="col">Destination</th>
            <th scope="col">Notes</th>
            <th scope="col">SMS</th>
            <th scope="col">SMS Destination</th>
          </tr>
        </thead>
      <?php
        foreach($data['domainCreate']->numbers as $numbers){
          echo "<tr>";
          foreach($numbers as $number){
            echo "<td>" . $number . "</td>";
          }
          echo "</tr>";
        }
      echo "</table>";
    }
    echo "<form action='" . URLROOT . "domains/create' method='post'>";
      echo "<div class='col-auto my-1'>";
        echo "<input type='hidden' name='value' value='" . serialize($data['domainCreate']) . "'>";
        echo "<button type='submit' name='submit' value='submit' class='btn btn-primary'>Submit</button>";
      echo "</div>";
    echo "</form>";
  }
}
  require APPROOT . '/views/inc/footer.php';
?>
