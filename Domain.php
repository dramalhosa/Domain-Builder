<?php
  class Domain {
    private $db;
    public function __construct() {
      error_reporting(E_ALL & ~E_NOTICE);
      $this->db = new Database;
    }

    public function validate($list){
      $file = $_FILES["userList"]["tmp_name"];
      $errors = [];
      $warnings = [];
      $exclude = ["911","411","933","988","5000","5001"];
      $userArray = [];
      $queueArray = [];
      $treatments = ["User","Conference","Queue","Directory","Voicemail","Voicemail Management","External Number","Message","Repeat","AA","Available Number","","Follow 1","Follow 2","Follow 3","Follow 4","Follow 5","Follow 6","Follow 7","Follow 8","Follow 9","Follow 0"];
      $queueTypes = ["Ring All","Linear Hunt","Linear Cascade","Call Park","Round-robin"];
      $options = ["0","1","2","3","4","5","6","7","8","9","0","*","invalid","nothing"];
      $timeFramesArray = [];
      if ( $xlsx = SimpleXLSX::parse( $file ) ) {
        //Process Description list
        $description = $xlsx->rows();

        $domainKey = array_keys(array_column($list->domain, "domain"), $description[0][1]);
        if(!empty($domainKey)){
          array_push($warnings, $description[0][1] . " already exists");
        }
        if(strlen($description[0][1]) > 63){
          array_push($warnings, $description[0][1] . " is more than 63 characters.  It will get cut");
        }
        $domainCreate->name = str_replace(' ', '', $description[0][1]); //63 characters, no spaces, must be unique

        if(!in_array($description[1][1], $list->reseller)){
          array_push($errors, $description[1][1] . " is not a reseller");
        }
        $domainCreate->reseller = $description[1][1];  //must match a reseller

        if($description[2][1] == ""){
          array_push($errors, "Description is empty");
        } else if(strlen($description[2][1]) > 40){
          array_push($warnings, "Description is more than 40 characters.  It will get cut");
          $domainCreate->description = substr($description[2][1], 0, 40);
        } else {
        $domainCreate->description = $description[2][1]; //40 characters, give warning over 40
        }

        if (!filter_var($description[3][1], FILTER_VALIDATE_EMAIL)) {
          array_push($errors, $description[3][1] . " is not a valid email");
        }
        if(strlen($description[3][1]) > 63){
          array_push($errors, $description[3][1] . " is more than 40 characters");
        }
        $domainCreate->emailSender = $description[3][1]; //40 characters, must be email

        if(!in_array($description[4][1], $list->dialPlan)){
          array_push($errors, "Invalid dial permission");
        }
        $domainCreate->dialPolicy = $description[4][1]; //must match a dial policy

        $timeZones = ["US/Alaska","US/Central","US/Eastern","US/Hawaii","US/Mountain","US/Pacific"];
        if(!in_array($description[5][1], $timeZones)){
          array_push($errors, "Invalid timezone");
        }
        $domainCreate->timeZone = $description[5][1];  //Must match a timezone

        if(strlen($description[6][1]) != 3){
          array_push($errors, $description[6][1] . " is not an area code");
        }
        $domainCreate->areaCode = $description[6][1]; //3

        if(strlen($description[7][1]) > 15){
          array_push($warnings, "Caller ID Name is greater than 15 characters.  It will get cut");
          $domainCreate->callerIDName = substr($description[7][1], 0, 15); //15, give warning if more than 15 or no caller ID name
        } else if($description[7][1] == ""){
          array_push($warnings, "Caller ID Name is blank");
        } else {
          $domainCreate->callerIDName = substr($description[7][1], 0, 15);
        }

        if($description[8][1] == "" || strlen($description[8][1]) != 10){
          array_push($errors, "Invalid Caller ID Number");
        }
        $domainCreate->callerIDNumber = $description[8][1]; //10

        if($description[9][1] == "" || strlen($description[9][1]) != 10){
          array_push($errors, "Invalid 911 Number");
        }
        $domainCreate->emergencyNumber = $description[9][1]; //10

        if (!filter_var($description[10][1], FILTER_VALIDATE_EMAIL)) {
          array_push($errors, $description[10][1] . " is not a valid email");
        }
        if(strlen($description[10][1]) > 63){
          array_push($errors, $description[10][1] . " is more than 40 characters");
        }
        $domainCreate->emergencyEmail = $description[10][1]; //verify email

        if($description[11][1] == ""){
          array_push($errors, "Number of call paths is blank");
        }
        $domainCreate->callPaths = $description[11][1];

        $server = ["PT1","PT2","TX1","TX2"];
        if(!in_array($description[12][1], $server)){
          array_push($errors, "Invalid provisioning server");
        }
        $domainCreate->preferredServer = $description[12][1]; //verify server

        $portDate = date("Y-m-d", strtotime($description[13][1]));
        if($portDate < date("Y-m-d")){
          array_push($errors, "Port date has to be today or greater.  Preferrably the day they will port");
        }
        $domainCreate->portDate = $portDate; //verify date

        //Process User list
        $userList = $xlsx->rows(1);
        foreach($userList as $index => $users){
          if($index === 0){
            continue;
          }
          $domainCreate->users[$index-1] = $users;

          if(in_array($users[0], $exclude) || in_array($users[0], $userArray) || strlen($users[0]) < 3 || strlen($users[0]) > 4){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid extension number");
          } else {
            array_push($userArray, $users[0]);
          }
          if (!filter_var($users[5], FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "Row " . $index . " on the Users tab has an invalid email");
          }
          if(!in_array($users[11], $timeZones)){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid timezone");
          }
          if(strlen($users[12]) != 3){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid area code");
          }
          if($users[13] == "" || strlen($users[13]) != 10){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid Caller ID Number");
          }
          if(strlen($users[14]) > 15){
            array_push($warnings, "Row " . $index . " Caller ID Name is greater than 15 characters.  It will get cut");
          $domainCreate->users[$index-1][14] = substr($users[14], 0, 15);
          } else if($users[14] == ""){
            array_push($warnings, "Row " . $index . " on the Users tab has a blank Caller ID Name");
          }
          if($users[15] == "" || strlen($users[15]) != 10){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid 911 Number");
          }
          if(!in_array($users[16], $list->dialPlan)){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid dial permission");
          }
          if($users[17] != "yes"){
            $domainCreate->users[$index-1][17] = "no";
          }
          if($users[18] != "yes"){
            $domainCreate->users[$index-1][18] = "no";
          }
          if($users[19] != "google"){
            $domainCreate->users[$index-1][19] = "no";
          }
          if($users[20] != "attnew" && $users[20] != "atttrash"){
            $domainCreate->users[$index-1][20] = "no";
          }
          if($users[21] != "yes"){
            $domainCreate->users[$index-1][21] = "no";
          }
          if($users[22] != "Office Manager" && $users[22] != "Call Center Supervisor" && $users[22] != "Call Center Agent" && $users[22] != "Basic User"){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid scope");
          }
          if($users[23] != "phone" && $users[23] != "mobile" && $users[23] != "none"){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid phone extension");
          }
          /*
          $users[25] = str_replace(':', '', $users[25]);
          if(strlen($users[25]) != 12 && ($users[23] == "phone")){
            array_push($errors, "Row " . $index . " on the Users tab has an invalid MAC address");
          }
          $domainCreate->users[$index-1][25] = $users[25];
          if(($users[23] == "none" || $users[23] == "mobile") && ($users[24] != "" || $users[25] != "")){
            $domainCreate->users[$index-1][24] = "";
            $domainCreate->users[$index-1][25] = "";
          }
          */
        }

        if($_POST['option'] == "Call Flow" || $_POST['option'] == "Everything"){
          //Process Mailbox list
          $mailboxList = $xlsx->rows(2);
          foreach($mailboxList as $index => $mailboxes){
            if($index === 0){
              continue;
            }
            $domainCreate->mailboxes[$index-1] = $mailboxes;
            if(in_array($mailboxes[0], $exclude) || in_array($mailboxes[0], $userArray) || strlen($mailboxes[0]) < 3 || strlen($mailboxes[0]) > 4){
              array_push($errors, "Row " . $index . " on the Mailbox tab has an invalid extension number or one in use already");
            } else {
              array_push($exclude, $mailboxes[0]);
            }
            if($mailboxes[2] != "MB"){
              array_push($warnings, "Row " . $index . " on the Mailbox tab doesn't have MB as the last name.");
            }
            if (!filter_var($mailboxes[5], FILTER_VALIDATE_EMAIL)) {
              array_push($errors, "Row " . $index . " on the Maiblox tab has an invalid email");
            }
            if($mailboxes[9] != "attnew" && $mailboxes[9] != "atttrash"){
              $domainCreate->mailboxes[$index-1][9] = "no";
            }
            if($mailboxes[11] != "yes"){
              $domainCreate->mailboxes[$index-1][11] = "no";
            }
            if($mailboxes[12] != "yes"){
              $domainCreate->mailboxes[$index-1][12] = "no";
            }
          }

          //Process Auto Attendants
          $AAList = $xlsx->rows(3);
          $number = -1;
          foreach($AAList as $index => $AAs){
            if($index === 0){
              continue;
            }
            foreach($AAs as $key => $AA){
              if($key === 0 && $AA != ""){
                $number++;
                if(in_array($AA, $exclude) || in_array($AA, $userArray) || strlen($AA) < 3 || strlen($AA) > 4){
                  array_push($errors, "Row " . $index . " on the Auto Attendant tab has an invalid extension number or one in use already");
                } else {
                  array_push($exclude, $AA);
                }
                $domainCreate->AAs[$number]['ext'] = $AA;
                $domainCreate->AAs[$number]['name'] = $AAs[1];
                if(!in_array($AAs[2], $options)){
                  array_push($errors, "Row " . $index . " on the Auto Attendant tab has an invalid option");
                }
                if(!in_array($AAs[3], $treatments)){
                  array_push($errors, "Row " . $index . " on the Auto Attendant tab has an invalid treatment");
                }
                if(($AAs[3] == "User" || $AAs[3] == "Conference" || $AAs[3] == "Voicemail" || $AAs[3] == "External Number") && $AAs[4] == ""){
                  array_push($errors, "Row " . $index . " on the Auto Attendant tab has an invalid destination");
                }
                $domainCreate->AAs[$number]['options'][$AAs[2]] = $AAs[3] . " - " . $AAs[4];
                continue;
              }
              if($key === 0 && $AA == "" && $AAs[2] != ""){
                if(!in_array($AAs[2], $options)){
                  array_push($errors, "Row " . $index . " on the Auto Attendant tab has an invalid option");
                }
                if(!in_array($AAs[3], $treatments)){
                  array_push($errors, "Row " . $index . " on the Auto Attendant tab has an invalid treatment");
                }
                $domainCreate->AAs[$number]['options'][$AAs[2]] = $AAs[3] . " - " . $AAs[4];
                continue;
              }
            }
          }

          //Process Time frames
          $timeFramesList = $xlsx->rows(4);
          $number = -1;
          foreach($timeFramesList as $index => $timeFrames){
            if($index === 0){
              continue;
            }
            foreach($timeFrames as $key => $timeFrame){
              if($key === 0 && $timeFrame != ""){
                $number++;
                $domainCreate->timeFrames[$number]['name'] = $timeFrame;
                array_push($timeFramesArray, $timeFrame);
                $domainCreate->timeFrames[$number]['type'] = $timeFrames[1];
                $type = $timeFrames[1];
                if($type != "range" && $type != "dates" && $type != "always"){
                  array_push($errors, "Row " . $index . " on the Time Frames tab has an invalid type");
                }
                if($timeFrames[1] == "dates"){
                  if(strtotime($timeFrames[3]) == "" || strtotime($timeFrames[4]) == ""){
                    array_push($errors, "Row " . $index . " on the Time Frames tab has an invalid time");
                  }
                  $timeFrames[2] = date("Y-m-d", strtotime($timeFrames[3]));
                  $timeFrames[3] = $timeFrames[2];
                  $timeFrames[4] = date("Y-m-d", strtotime($timeFrames[4]));
                } else {
                  if(strtotime($timeFrames[3]) == "" xor strtotime($timeFrames[4]) == ""){
                    array_push($errors, "Row " . $index . " on the Time Frames tab has an invalid time");
                  }
                }
                if(strtotime($timeFrames[2]) == ""){
                  array_push($errors, "Row " . $index . " on the Time Frames tab has an invalid time");
                }
                $domainCreate->timeFrames[$number]['times'][$timeFrames[2]] = $timeFrames[3] . " - " . $timeFrames[4];
                continue;
              }
              if($key === 0 && $timeFrame == "" && ($timeFrames[2] != "" || $timeFrames[3] != "")){
                if($type == "dates"){
                  $timeFrames[2] = date("Y-m-d", strtotime($timeFrames[3]));
                  $timeFrames[3] = $timeFrames[2];
                  $timeFrames[4] = date("Y-m-d", strtotime($timeFrames[4]));
                } else {
                  if($timeFrames[3] != ""){
                    $timeFrames[3] = date("H:i:s", strtotime($timeFrames[3]));
                    $timeFrames[4] = date("H:i:s", strtotime($timeFrames[4]));
                  } else {
                    $timeFrames[3] = "";
                    $timeFrames[4] = "";
                  }
                }
                $domainCreate->timeFrames[$number]['times'][$timeFrames[2]] = $timeFrames[3] . " - " . $timeFrames[4];
              }
            }
          }

          //Process Ring Groups and Forwards
          $utilityList = $xlsx->rows(5);
          $number = -1;
          foreach($utilityList as $index => $utilities){
            if($index === 0){
              continue;
            }
            foreach($utilities as $key => $utility){
              if($key === 0 && $utility != ""){
                $number++;
                if(in_array($utility, $exclude) || in_array($utility, $userArray) || strlen($utility) < 3 || strlen($utility) > 4){
                  array_push($errors, "Row " . $index . " on the Ring Groups tab has an invalid extension number or one in use already");
                } else {
                  array_push($exclude, $utility);
                }
                if(strlen($utilities[4]) != 3 && strlen($utilities[4]) != 4 && strlen($utilities[4]) != 10){
                  array_push($errors, "Row " . $index . " on the Ring Groups tab has an invalid destination");
                }
                if(!in_array($utilities[5], $treatments)){
                  array_push($errors, "Row " . $index . " on the Ring Groups tab has an invalid treatment");
                }
                if(strlen($utilities[6]) != 3 && strlen($utilities[6]) != 4 && $utilities[6] != ""){
                  array_push($errors, "Row " . $index . " on the Ring Groups tab has an invalid destination");
                }
                if(!in_array($utilities[7], $treatments)){
                  array_push($errors, "Row " . $index . " on the Ring Groups tab has an invalid treatment");
                }
                $domainCreate->utilities[$number]['ext'] = $utility;
                $domainCreate->utilities[$number]['firstName'] = $utilities[1];
                $domainCreate->utilities[$number]['lastName'] = $utilities[2];
                $domainCreate->utilities[$number]['ringTime'] = $utilities[3];
                $domainCreate->utilities[$number]['ring'][$utilities[4]] = $utilities[4];
                $domainCreate->utilities[$number]['unansweredTreatment'] = $utilities[5];
                $domainCreate->utilities[$number]['unansweredDestination'] = $utilities[6];
                $domainCreate->utilities[$number]['offlineTreatment'] = $utilities[7];
                $domainCreate->utilities[$number]['offlineDestination'] = $utilities[8];
                continue;
              }
              if($key === 0 && $utility == "" && $utilities[2] == "" && $utilities[4] != ""){
                if(strlen($utilities[4]) != 3 && strlen($utilities[4]) != 4 && strlen($utilities[4]) != 10){
                  array_push($errors, "Row " . $index . " on the Ring Groups tab has an invalid destination");
                }
                $domainCreate->utilities[$number]['ring'][$utilities[4]] = $utilities[4];
                continue;
              }
            }
          }

          //Process ToDs
          $ToDList = $xlsx->rows(6);
          $number = -1;
          foreach($ToDList as $index => $ToDs){
            if($index === 0){
              continue;
            }
            foreach($ToDs as $key => $ToD){
              if($key === 0 && $ToD != ""){
                $number++;
                if(in_array($ToD, $exclude) || in_array($ToD, $userArray) || strlen($ToD) < 3 || strlen($ToD) > 4){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid extension number or one in use already");
                } else {
                  array_push($exclude, $ToD);
                }
                if(!in_array($ToDs[3], $timeFramesArray) && $ToDs[3] != "Default"){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid time frame");
                }
                if(!in_array($ToDs[4], $treatments) || $ToDs[4] == ""){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid treatment");
                }
                if(strlen($ToDs[5]) != 3 && strlen($ToDs[5]) != 4 && strlen($ToDs[5]) != 10){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid destination");
                }
                $domainCreate->ToDs[$number]['ext'] = $ToD;
                $domainCreate->ToDs[$number]['firstName'] = $ToDs[1];
                $domainCreate->ToDs[$number]['lastName'] = $ToDs[2];
                $domainCreate->ToDs[$number]['timeFrame'][$ToDs[3]] = $ToDs[4] . " - " . $ToDs[5];
                continue;
              }
              if($key === 0 && $ToD == "" && $ToDs[2] == ""){
                if(!in_array($ToDs[3], $timeFramesArray) && $ToDs[3] != "Default"){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid time frame");
                }
                if(!in_array($ToDs[4], $treatments) || $ToDs[4] == ""){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid treatment");
                }
                if(strlen($ToDs[5]) != 3 && strlen($ToDs[5]) != 4 && strlen($ToDs[5]) != 10){
                  array_push($errors, "Row " . $index . " on the ToDs tab has an invalid destination");
                }
                $domainCreate->ToDs[$number]['timeFrame'][$ToDs[3]] = $ToDs[4] . " - " . $ToDs[5];
                continue;
              }
            }
          }

          //Process Queues
          $queueList = $xlsx->rows(7);
          foreach($queueList as $index => $queues){
            if($index === 0){
              continue;
            }
            if($queues[0] == ""){
              continue;
            }
            $domainCreate->queues[$index-1] = $queues;
            if(in_array($queues[0], $exclude) || in_array($queues[0], $userArray) || strlen($queues[0]) < 3 || strlen($queues[0]) > 4){
              array_push($errors, "Row " . $index . " on the Queues tab has an invalid extension number or one in use already");
            } else {
              array_push($exclude, $queues[0]);
            }
            if(!in_array($queues[2], $queueTypes)){
              array_push($errors, "Row " . $index . " on the Queues tab has an invalid type");
            }
            if($queues[2] == "Call Park" && (intval($queues[0]) > 709 || intval($queues[0] < 700))){
              array_push($warnings, "You have a Call Park greater than outside the 700-709 range");
            }
            if($queues[2] == "Call Park"){
              continue;
            } else {
              array_push($queueArray, $queues[0]);
              if($queues[5] != "yes"){
                $domainCreate->queues[$index-1][5] = "no";
              }
              if((!is_numeric($queues[6]) xor intval($queues[6]) % 10 != 0) && ($queues[7] != "unlimited" && $queues[7] != "")){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid max expected wait time.  It should either be 0/unlimited or number divisible by 10");
              }
              if((!is_numeric($queues[7]) || intval($queues[7]) < 0 || intval($queues[7]) > 99) && ($queues[7] != "unlimited" && $queues[7] != "")){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid max queue length.  It should either be a number between 0 and 99 or unlimited");
              }
              if($queues[8] != "yes"){
                $domainCreate->queues[$index-1][8] = "no";
              }
              if($queues[9] != "yes"){
                $domainCreate->queues[$index-1][9] = "no";
              }
              if(!in_array($queues[10], $treatments) && $queues[10] != ""){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid unavailable treatment");
              }
              if(strlen($queues[11]) != 3 && strlen($queues[11]) != 4 && strlen($queues[11]) != 10  && $queues[11] != ""){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid unavailable destination");
              }
              if($queues[12] == ""){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid ring time");
              }
              if(!in_array($queues[13], $treatments) || $queues[13] == ""){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid unanswered treatment");
              }
              if(strlen($queues[14]) != 3 && strlen($queues[14]) != 4 && strlen($queues[14]) != 10){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid unanswered destination");
              }
              if($queues[15] == ""){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid agent timeout");
              }
              if($queues[2] == "Ring All"){
                continue;
              }
              if($queues[2] == "Round-robin"){
                continue;
              }
              if(!is_numeric($queues[16]) || intval($queues[16]) < 1 || intval($queues[16]) > 99){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid agent group to add after timeout.  It should be a number between 0 and 99 ");
              }
              if(!is_numeric($queues[17]) || intval($queues[17]) < 1 || intval($queues[17]) > 99){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid initial agent timeout.  It should be a number between 0 and 99 ");
              }
            }

          }

          //Process Agents
          $agentList = $xlsx->rows(8);
          foreach($agentList as $index => $agents){
            if($index === 0){
              continue;
            }
            if($agents[0] != "" || $agents[1] != ""){
              $domainCreate->agents[$index-1] = $agents;
              if(in_array($agents[0], $exclude) || !in_array($agents[0], $userArray)){
                array_push($errors, "Row " . $index . " on the Agents tab has an invalid extension number");
              } else {
                array_push($exclude, $queues[0]);
              }
              if(!in_array($agents[1], $queueArray)){
                array_push($errors, "Row " . $index . " on the Agents tab has an invalid queue number");
              } else {
                array_push($exclude, $queues[0]);
              }
              if((!is_numeric($agents[2]) xor intval($agents[7]) % 5 != 0)){
                array_push($errors, "Row " . $index . " on the Agents tab has an invalid wrap up time.  It should either be 0 or number divisible by 10");
              }
              if(!is_numeric($agents[3]) || intval($agents[3]) < 1 || intval($agents[3]) > 6){
                array_push($errors, "Row " . $index . " on the Queues tab has an invalid initial agent timeout.  It should be a number between 1 and 6");
              }
              if($agents[4] == 0){
                $domainCreate->agents[$index-1][4] = "0";
                $agents[4] = "0";
              }
              if(!is_numeric($agents[4]) || intval($agents[4]) < 0 || intval($agents[4]) > 99){
                array_push($errors, "Row " . $index . " on the Agents tab has an invalid order.  It should be a number between 1 and 99");
              }
            }
          }

          //Process Conference Bridges
          $conferenceList = $xlsx->rows(9);
          foreach($conferenceList as $index => $conferences){
            if($index === 0){
              continue;
            }
            if($conferences[0] == ""){
              break;
            }
            $domainCreate->conferences[$index-1] = $conferences;
            if(in_array($conferences[1], $exclude) || in_array($conferences[1], $userArray) || strlen($conferences[1]) < 3 || strlen($conferences[1]) > 4){
              array_push($errors, "Row " . $index . " on the Conferences tab has an invalid extension number or one in use already");
            }else {
              array_push($exclude, $conferences[1]);
            }
            if($conferences[2] != "" && $conferences[3] == ""){
              array_push($errors, "Row " . $index . " on the Conferences tab has a Leader PIN but not Participant PIN.  There has to be a Participant PIN if there is a Leader PIN");
            }
            if($conferences[2] == "" && $conferences[3] == ""){
              array_push($warnings, "For security there should be at least a Participant PIN");
            }
            if((!is_numeric($conferences[4]) || intval($conferences[4]) < 0 || intval($conferences[4]) > 99) && ($conferences[4] != "unlimited" && $conferences[4] != "")){
              array_push($errors, "Row " . $index . " on the Conferences tab has an invalid max participants.  It should either be a number between 0 and 99 or unlimited");
            }
            if(!is_numeric($conferences[5]) || intval($conferences[5]) < 1 || intval($conferences[5]) > 5){
              array_push($errors, "Row " . $index . " on the Conferences tab has an invalid min to start.  It should be a number between 1 and 5");
            }
            if($conferences[6] != "yes"){
              $domainCreate->conferences[$index-1][6] = "no";
            }
            if($conferences[7] != "yes"){
              $domainCreate->conferences[$index-1][7] = "no";
            }
            if($conferences[8] != "yes"){
              $domainCreate->conferences[$index-1][8] = "no";
            }

          }
          //Process Phone Numbers
          $numberList = $xlsx->rows(10);
          foreach($numberList as $index => $numbers){
            if($index === 0){
              continue;
            }
            $domainCreate->numbers[$index-1] = $numbers;
            if(strlen($numbers[0]) != 10){
              array_push($errors, "Row " . $index . " on the Numbers tab has is invalid");
            }
            if(!in_array($numbers[1], $treatments) || $numbers[1] == ""){
              array_push($errors, "Row " . $index . " on the Numbers tab has an invalid treatment");
            }
            if($numbers[1] != "Available Number" && (strlen($numbers[2]) != 3 && strlen($numbers[2]) != 4 && strlen($numbers[2]) != 10)){
              array_push($errors, "Row " . $index . " on the Numbers tab has an invalid destination");
            }
            if($numbers[4] == "yes"){
              if(!in_array($numbers[5], $userArray)){
                array_push($errors, "Row " . $index . " on the Numbers tab has you have SMS as yes but the destination doesn't match an extension in the Users tab");
              }
            }
            if($numbers[4] != "yes"){
              $domainCreate->numbers[$index-1][4] = "no";
            }
          }
        }

        $domainCreate->errors = $errors;
        $domainCreate->warnings = $warnings;
      } else {
      	echo SimpleXLSX::parseError();
      }

      return $domainCreate;
    }

    public function buildDomain($info, $list){
      $domainCreate = unserialize($_POST['value']);
      //Set up
      $responder['Available Number'] = "AvailableDID";
      $responder['External Number'] = "To-Conn-For-DNIS-Add-Header";
      $responder['AA'] = "sip:start@to-user";
      $responder['Voicemail'] = "Residential VMail";
      $responder['Queue'] = "sip:start@call-queuing";
      $responder['Conference'] = "sip:start@to-owned-device";
      $responder['User'] = "sip:start@to-user-resi";
      $responder['Directory'] = "sip:start@directory";
      $responder['Voicemail Management'] = "sip:vmail-unreg@nms.netsapiens.com";
      $responder['Repeat'] = "Prompt";
      $server['PT1'] = "core1-nj";
      $server['PT2'] = "core2-nj";
      $server['TX1'] = "core1-tx";
      $server['TX2'] = "core2-tx";
      $mobileLicenses = [];
      $userList = [];
      $dialRules = [];
      $treatments['User'] = 'user_';
      $treatments['Voicemail'] = 'vmail_';
      $treatments['AA'] = 'aa_';

      $domainKey = array_keys(array_column($list->domain, "domain"), $description[0][1]);
      //if(empty($domainKey)){
  //Create domain
        $query = array(
          'object' => 'domain',
          'action' => 'create',
          'domain' => $domainCreate->name,
          'territory' => $domainCreate->reseller,
          'plan_description' => $domainCreate->description,
          'email_sender' => $domainCreate->emailSender,
          'dial_policy' => $domainCreate->dialPolicy,
          'time_zone' => $domainCreate->timeZone,
          'call_limit_ext' => $domainCreate->callPaths,
          'sso' => 'yes',
          'format' => 'json',
        );
      //    echo "<br>Create Domain - ";
      //    var_export($query);
      $domainCreateResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
      //echo "<br><br>";
  //Create dial plan
        $query = array(
              'object' => 'dialplan',
              'action' => "create",
              'domain' => $domainCreate->name,
              'dialplan' => $domainCreate->name,
              'plan_description' => "Dial Plan for " . $domainCreate->name
        );
        //    echo "<br>Create Dial Plan - ";
        //    var_export($query);
        $dialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        //    echo "<br><br>";
      //Add dial plan to default table
        $query = array(
          'object' => 'dialrule',
          'action' => "create",
          'domain' => $domainCreate->name,
          'dialplan' => $domainCreate->name,
          'matchrule' => "*",
          'responder' => "<Cloud PBX Features>",
          'to_scheme' => "[*]",
          'to_user' => "[*]",
          'to_host' => "[*]",
          'plan_description' => "Chain to Default Table"
        );
        //    echo "<br>Add Dial Plan to defalut table - ";
        //    var_export($query);
        $dialPlanTableResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        //    echo "<br><br>";
        //Create domain user
        //Random password
        $PASSWORD = mt_rand() % 1000000;
        $query = array(
          'object' => 'subscriber',
          'action' => "create",
          'domain' => $domainCreate->name,
          'first_name' => "Domain",
          'last_name' => "User",
          'dial_plan' => $domainCreate->name,
          'dial_policy' => $domainCreate->dialPolicy,
          'user' => 'domain',
          'dir_list' => "no",
          'dir_anc' => "no",
          'srv_code' => 'system-user',
          'area_code' => $domainCreate->areaCode,
          'callid_name' => $domainCreate->callerIDName,
          'callid_nmbr' => $domainCreate->callerIDNumber,
          'callid_emgr' => $domainCreate->emergencyNumber,
          'subscriber_pin' => $PASSWORD
        );

        //    echo "<br>Domain User - ";
        //    var_export($query);
        $domainUserResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        //    echo "<br><br>";
  //EmailtoNotify
        $array = explode("@", $domainCreate->emergencyEmail);
        $add = array('object' => 'dialrule', 'action' => "create", 'domain' => $domainCreate->name, 'dialplan' => $domainCreate->name, 'matchrule' => 'sip:911@*', 'match_from' => '*', 'responder' => 'Emergency w/Notify', 'parameter' => 'EmailToNotify', 'to_scheme' => 'sip:', 'to_user' => '[*]', 'to_host' => '<OwnDomain>', 'from_name' => '<CallerCidName>', 'from_scheme' => 'sip:', 'from_user' => '<CallerCidEmgr>', 'from_host' => '<OwnDomain>', 'format' => 'json', 'plan_description' => '911 to Email Alert - DFR');
        doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $add, null, $http_response);
        $add2 = array('object' => 'dialrule', 'action' => "create", 'domain' => $domainCreate->name, 'dialplan' => $domainCreate->name, 'matchrule' => 'EmailToNotify', 'match_from' => '*', 'responder' => 'EMail-Account-Info', 'parameter' => '', 'to_scheme' => 'sip:', 'to_user' => $array[0], 'to_host' => $array[1], 'from_name' => '<OwnName>', 'from_scheme' => 'sip:', 'from_user' => '<OwnUser>', 'from_host' => '<OwnDomain>', 'plan_description' => '911 to Email Alert - DFR');
        doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $add2, null, $http_response);

  //API Manager
        $query = array('object' => 'subscriber', 'action' => "create", 'uid' => "api@" . $domainCreate->name, 'user' => "api", 'domain' => $domainCreate->name, 'subscriber_login' => "api@" . $domainCreate->name, 'first_name' => "API", 'last_name' => "Manager", 'pwd_hash' => $domainCreate->name . "T3l3@Pi", 'dir_anc' => "no", 'dir_list' => "no", 'vmail_provisioned' => "no", 'data_limit' => 10000, 'call_limit' => 4, 'dial_plan' => $domainCreate->name, 'dial_policy' => "US and Canada", 'scope' => "Office Manager", 'srv_code' => "system-trunk", 'format' => "json");
        doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
      //}
      if($domainCreate->option == "User List" || $domainCreate->option == "Everything"){
  //Create Users
        foreach($domainCreate->users as $user){
          $uid = $user[0] . "@" . $domainCreate->name;
          $query = array(
            'object' => 'subscriber',
            'action' => "create",
            'format' => "json",
            'user' => $user[0],
            'domain' => $domainCreate->name,
            'first_name' => $user[1],
            'last_name' => $user[2],
            'subscriber_login' => $user[3],
            'uid' => $uid,
            'pwd_hash' => $user[4],
            'email' => $user[5],
            'vmail_provisioned' => "yes",
            'vmail_enabled' => $user[9],
            'subscriber_pin' => $user[6],
            'vmail_notify' => $user[20],
            'vmail_transcribe' => $user[19],
            'group' => $user[7],
            'site' => $user[8],
            'data_limit' => 10000,
            'time_zone' => $user[11],
            'area_code' => $user[12],
            'callid_nmbr' => $user[13],
            'callid_name' => $user[14],
            'callid_emgr' => $user[15],
            'dial_plan' => $domainCreate->name,
            'dial_policy' => $user[16],
            'dir_list' => $user[17],
            'dir_anc' => $user[18],
            'no_answer_timeout' => $user[10],
            'vmail_annc_time' => "no",
            'vmail_annc_cid' => "no",
            'scope' => $user[22]
          );
          //    echo "<br>User - ";
          //    var_export($query);
          $userResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

          if($user[21] == "yes"){
            $recording = "Record";
            $query = array('object' => 'recording', 'action' => "create", 'aor' => $uid, 'format' => "json");
            //    echo "<br>Recording - ";
            //    var_export($query);
            $userRecordingResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          } else {
            $recording = "";
          }
          $userList[$user[0]] = $user[1] . " " . $user[2];
    //Create phone hardware
          if($user[23] != "phone"){
            $query = array(
               'object' => 'device',
               'action' => "create",
               'format' => "json",
               'domain' => $domainCreate->name,
               'user' => $user[0],
               'call_processing_rule' => $recording,
               'device' => "sip:" . $user[0] . "@" . $domainCreate->name,
    //           'mac' => $user[25],
               'mac' => '',
               'server' => $server[$domainCreate->preferredServer],
    //           'model' => $user[24]
               'model' => ''
             );
             //    echo "<br>User Hardware - ";
             //    var_export($query);
             $userHardwareResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          }
    //Create mobile & web phone extension
          if($user[23] == "mobile"){
            array_push($mobileLicenses, $user[0]);
            $query = array(
               'object' => 'device',
               'action' => "create",
               'format' => "json",
               'domain' => $domainCreate->name,
               'user' => $user[0],
               'call_processing_rule' => $recording,
               'device' => "sip:" . $user[0] . "m@" . $domainCreate->name,
               'callid_emgr' => "[*]"
             );
             //    echo "<br>User Mobile - ";
             //    var_export($query);
             $userHardwareResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
            $query = array(
               'object' => 'device',
               'action' => "create",
               'format' => "json",
               'domain' => $domainCreate->name,
               'user' => $user[0],
               'call_processing_rule' => $recording,
               'device' => "sip:" . $user[0] . "wp@" . $domainCreate->name,
               'callid_emgr' => "[*]"
             );
             //    echo "<br>User Web Phone - ";
             //    var_export($query);
            $userHardwareResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          }

    //Create default answering rule
          $query = array(
             'object' => 'answerrule',
             'action' => 'create',
             'format' => "json",
             'uid' => $uid,
             'time_frame' => '*',
             'priority' => "0",
             'dnd_enable' => "0",
             'enable' => "yes",
             'order' => "99",
             'sim_control' => "e",
             'sim_parameters' => $user[0] . " <OwnDevices>"
           );
           //    echo "<br>User Answering Rules - ";
           //    var_export($query);
           $userAnsweringRulesResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
           //    echo "<br>";
        }
        //    echo "<br><br>";
      }
      if($domainCreate->option == "Call Flow" || $domainCreate->option == "Everything"){
  //Phone Numbers
        $sms = "no";
        foreach($domainCreate->numbers as $number){
          $treatment['Available Number'] = "AvailableDID";
          if($number[1] == "Available Number"){
            $to_user = "[*]";
            $to_host = $domainCreate->name;
          } else if ($number[1] == "External Number"){
            $to_user = $number[2];
            $to_host = $domainCreate->name;
          } else if ($number[1] == "AA"){
            $to_user = $number[2];
            $to_host = $domainCreate->name;
          } else if ($number[1] == "Voicemail"){
            $to_user = $number[2];
            $to_host = $domainCreate->name;
          } else if ($number[1] == "Queue"){
            $to_user = $number[2];
            $to_host = $domainCreate->name;
          } else if ($number[1] == "Conference"){
            $to_user = $number[2] . "." . $domainCreate->name;
            $to_host = "conference-bridge";
          } else if ($number[1] == "User"){
            $to_user = $number[2];
            $to_host = $domainCreate->name;
          }
          $query = array(
            'object' => 'phonenumber',
            'action' => "create",
            'dest_domain' => $domainCreate->name,
            'valid_from' => $domainCreate->portDate,
            'dialplan' => 'DID Table',
            'matchrule' => "sip:1" . $number[0] . "@*",
            'responder' => $responder[$number[1]],
            'to_user' => $to_user,
            'to_host' => $to_host,
            'enable' => "yes"
          );
          //    echo "<br>Create Numbers - ";
          //    var_export($query);
          $phoneNumbersResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br><br>";
          if($number[4] == "yes"){
            $sms = "yes";
            $query = array(
              'object' => 'smsnumber',
              'action' => "create",
              'domain' => $domainCreate->name,
              'number' => $number[0],
              'application' => "user",
              'dest' => $number[5]
            );
            //    echo "<br>Create SMS Numbers - ";
            //    var_export($query);
            $smsNumbersResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          }
        }
        if($sms == "yes"){
          $query = array(
            'object' => 'uiconfig',
            'action' => "create",
            'domain' => $domainCreate->name,
            'config_name' => "PORTAL_CHAT_SMS",
            'config_value' => "yes"
          );
          //    echo "<br>Create SMS Config - ";
          //    var_export($query);
          $smsNumbersResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        }
        //    echo "<br><br>";
    //Create UI Config PORTAL_DEVICE_NDP_SERVER
        $query = array(
          'object' => 'uiconfig',
          'action' => "create",
          'domain' => $domainCreate->name,
          'config_name' => "PORTAL_DEVICE_NDP_SERVER",
          'config_value' => $server[$domainCreate->preferredServer]
        );
        //    echo "<br>Create Device Config - ";
        //    var_export($query);
        $deviceResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        //    echo "<br><br>";


        //Create Mailboxes
        foreach($domainCreate->mailboxes as $mailbox){
          $uid = $mailbox[0] . "@" . $domainCreate->name;
          $query = array(
            'object' => 'subscriber',
            'action' => "create",
            'format' => "json",
            'user' => $mailbox[0],
            'domain' => $domainCreate->name,
            'first_name' => $mailbox[1],
            'last_name' => $mailbox[2],
            'subscriber_login' => $mailbox[3],
            'uid' => $uid,
            'pwd_hash' => $mailbox[4],
            'email' => $mailbox[5],
            'vmail_provisioned' => "yes",
            'vmail_enabled' => "yes",
            'subscriber_pin' => $mailbox[6],
            'vmail_notify' => $mailbox[9],
            'vmail_transcribe' => $mailbox[10],
            'group' => $mailbox[7],
            'site' => $mailbox[8],
            'data_limit' => 10000,
            'time_zone' => $domainCreate->timeZone,
            'area_code' => $domainCreate->areaCode,
            'callid_nmbr' => $domainCreate->callerIDNumber,
            'callid_name' => $domainCreate->callerIDName,
            'callid_emgr' => $domainCreate->emergencyNumber,
            'dial_plan' => $domainCreate->name,
            'dial_policy' => $domainCreate->dialPolicy,
            'dir_list' => "no",
            'dir_anc' => "no",
            'no_answer_timeout' => "25",
            'vmail_annc_time' => "no",
            'vmail_annc_cid' => "no",
            'scope' => "Basic User"
          );
          $userList[$mailbox[0]] = $mailbox[1] . " " . $mailbox[2];
          //    echo "<br>Mailbox - ";
          //    var_export($query);
          $mailboxResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

          //Create mailbox hardware
          if($mailbox[11] != ""){
            $query = array(
               'object' => 'device',
               'action' => "create",
               'format' => "json",
               'domain' => $domainCreate->name,
               'user' => $mailbox[0],
               'device' => "sip:" . $mailbox[0] . "@" . $domainCreate->name
             );
             //    echo "<br>User Hardware - ";
             //    var_export($query);
             $mailboxHardwareResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          }

          //Create default answering rule
          $query = array(
             'object' => 'answerrule',
             'action' => 'create',
             'format' => "json",
             'uid' => $uid,
             'time_frame' => '*',
             'priority' => "0",
             'dnd_control' => "d",
             'enable' => "yes",
             'order' => "99",
             'for_control' => "e",
             'for_parameters' => "vmail_" . $mailbox[0],
             "scr_control" => "d",
             "foa_control" => "d",
             "foa_parameters" => "",
             "fnr_control" => "d",
             "fnr_paramters" => "",
             "fna_control" => "d",
             "fna_paramters" => "",
             "sim_control" => "d",
             "sim_parameters" => ""
           );
           //    echo "<br>Mailbox Answering Rule - ";
           //    var_export($query);
           $mailboxAnsweringRulesResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
           //    echo "<br>";
        }

        foreach($domainCreate->AAs as $AA){
          $aaName = explode(' ', $AA['name'], 2);
          if($aaName[1] == ""){
            $aaName[1] = "Auto Attendant";
          }
          $query = array(
            'object' => 'subscriber',
            'action' => "create",
            'format' => "json",
            'user' => $AA['ext'],
            'domain' => $domainCreate->name,
            'first_name' => $aaName[0],
            'last_name' => $aaName[1],
            'subscriber_login' => $AA['ext'] . "@" . $domainCreate->name,
            'uid' => $AA['ext'] . "@" . $domainCreate->name,
            'vmail_provisioned' => "yes",
            'vmail_enabled' => "yes",
            'vmail_notify' => 'no',
            'data_limit' => 10000,
            'call_limit' => 0,
            'time_zone' => $domainCreate->timeZone,
            'area_code' => $domainCreate->areaCode,
            'callid_nmbr' => $domainCreate->callerIDNumber,
            'callid_name' => $domainCreate->callerIDName,
            'callid_emgr' => $domainCreate->emergencyNumber,
            'dial_plan' => $domainCreate->name . "_" . $AA['ext'],
            'dial_policy' => $domainCreate->dialPolicy,
            'dir_list' => "no",
            'dir_anc' => "no",
            'srv_code' => 'system-aa',
            'no_answer_timeout' => "25"
          );
          $userList[$AA['ext']] = $aaName[0] . " " . $aaName[1];
          //    echo "<br>AA user - ";
          //    var_export($query);
          $aaCreateResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br>";

          //Create default AA dial plan
          $query = array(
                'object' => 'dialplan',
                'action' => "create",
                'domain' => $domainCreate->name,
                'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                'plan_description' => "dial plan for " . $AA['ext'] . "@" . $domainCreate->name . " for AA"
          );
          //    echo "<br>Create AA Dial Plan - ";
          //    var_export($query);
          $aaDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br><br>";
          //Add dial plan to default table
          $query = array(
            'object' => 'dialrule',
            'action' => "create",
            'domain' => $domainCreate->name,
            'dialplan' => $domainCreate->name . "_" . $AA['ext'],
            'matchrule' => "*",
            'responder' => "<" . $domainCreate->name . ">",
            'to_scheme' => "[*]",
            'to_user' => "[*]",
            'to_host' => "[*]",
            'plan_description' => "Chain to Default for domain"
          );
          //    echo "<br>Add Dial Plan to defalut table - ";
          //    var_export($query);
          $dialPlanTableResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br><br>";

          //AA Dial plan for 4 digit extension calling
          $query = array(
                'object' => 'dialrule',
                'action' => "create",
                'matchrule' => "Prompt_" . $AA['ext'] . "001.Case_[0-9][0-9][0-9][0-9]",
                'domain' => $domainCreate->name,
                'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                'responder' => "sip:start@to-user",
                'to_user' => '[!!!!!!!!!!!!!!!!!!!*]',
                'to_host' => $domainCreate->name,
                "to_scheme" => "[*]",
                'plan_description' => "AA designer: Dial Extension"
          );
          //    echo "<br>Create AA 4 digit Dial Plan - ";
          //    var_export($query);
          $aa4DialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br><br><br>";

          //AA Dial plan for 3 digit extension calling
          $query = array(
                'object' => 'dialrule',
                'action' => "create",
                'matchrule' => "Prompt_" . $AA['ext'] . "001.Case_[0-9][0-9][0-9]",
                'domain' => $domainCreate->name,
                'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                'responder' => "sip:start@to-user",
                'to_user' => '[!!!!!!!!!!!!!!!!!!!*]',
                'to_host' => $domainCreate->name,
                "to_scheme" => "[*]",
                'plan_description' => "AA designer: Dial Extension"
          );
          //    echo "<br>Create AA 3 digit Dial Plan - ";
          //    var_export($query);
          $aa3DialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br><br><br>";

          //AA Dial plan for AA
          $query = array(
                'object' => 'dialrule',
                'action' => "create",
                'matchrule' => "Prompt_" . $AA['ext'] . "001",
                'domain' => $domainCreate->name,
                'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                'responder' => "Prompt",
                "to_scheme" => "[*]",
                'to_user' => $AA['ext'] . '001',
                'to_host' => $domainCreate->name,
                'plan_description' => "AA designer: " . $aaName[0] . " " . $aaName[1]
          );
          //    echo "<br>Create AA 3 digit Dial Plan - ";
          //    var_export($query);
          $aaDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          //    echo "<br><br><br>";

          //Default AA Answering Rule
          $query = array(
             'object' => 'answerrule',
             'action' => 'create',
             'format' => "json",
             'domain' => $domainCreate->name,
             'user' => $AA['ext'],
             'time_frame' => '*',
             'priority' => "0",
             'enable' => "yes",
             'order' => "99",
             'acp_control' => "d",
             'acp_parameters' => "",
             'rej_control' => "d",
             'rej_parameters' => "",
             'dnd_control' => "d",
             'scr_control' => "d",
             'for_control' => "e",
             'for_parameters' => "Prompt_" . $AA['ext'] . "001",
             'fbu_control' => "d",
             'fbu_parameters' => "",
             'fna_control' => "d",
             'fna_parameters' => "",
             'sim_control' => "",
             'sim_parameters' => ""
           );
           //    echo "<br>AA Answering Rule - ";
           //    var_export($query);
           $aaAnsweringRulesResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
           //    echo "<br>";

           //AA Not Found Dial plan for AA
           $query = array(
                 'object' => 'dialrule',
                 'action' => "create",
                 'matchrule' => "ToUserNotFound",
                 'domain' => $domainCreate->name,
                 'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                 'responder' => "sip:start@to-user",
                 'to_user' => $AA['ext'],
                 'to_host' => $domainCreate->name,
                 'plan_description' => "AA designer: No User Found"
           );
           //    echo "<br>Create AA No User Found Dial Plan - ";
           //    var_export($query);
           $aaNoUserFoundDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
           //    echo "<br><br>";

           //AA Root Dial plan for AA
           $query = array(
                 'object' => 'dialrule',
                 'action' => "create",
                 'matchrule' => "AAMain",
                 'domain' => $domainCreate->name,
                 'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                 'responder' => "sip:start@to-user",
                 'to_user' => $AA['ext'],
                 'to_host' => $domainCreate->name,
                 'plan_description' => "AA designer: Root"
           );
           //    echo "<br>Create AA Root digit Dial Plan - ";
           //    var_export($query);
           $aaRootDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

           $responder['External Number'] = "sip:no-rna@to-connection";
           foreach($AA['options'] as $key => $option){
             $optionNumber = explode(' - ', $option);
             if($optionNumber[0] == ""){
               continue;
             } else {
               $description = "AA designer: " . $optionNumber[0] . " for " . $optionNumber[1];
               if($optionNumber[0] == "Voicemail Management"){
                 $to_user = "[*]";
                 $to_host = $domainCreate->name;
               } else if ($optionNumber[0] == "External Number"){

                 if(substr($optionNumber[1], 0, 1) != "1"){
                   $to_user = "1" . $optionNumber[1];
                 } else {
                   $to_user = $optionNumber[1];
                 }
                 $to_host = $domainCreate->name;
               } else if ($optionNumber[0] == "Directory"){
                 if($key == "0"){
                   $step = "011";
                 } else if($key == "*"){
                   $step = "012";
                 } else {
                   $num = $key+1;
                   $step = "00" . $num;
                 }
                 $to_user = $step;
                 $to_host = $domainCreate->name;
               } else if ($optionNumber[0] == "Voicemail"){
                 $to_user = $optionNumber[1];
                 $to_host = $domainCreate->name;
               } else if ($optionNumber[0] == "Queue"){
                 $to_user = $optionNumber[1];
                 $to_host = $domainCreate->name;
               } else if ($optionNumber[0] == "Conference"){
                 $to_user = $optionNumber[1] . "." . $domainCreate->name;
                 $to_host = "conference-bridge";
               } else if ($optionNumber[0] == "User"){
                 $to_user = $optionNumber[1];
                 $to_host = $domainCreate->name;
               } else if ($optionNumber[0] == "Repeat"){
                 $to_user = $AA['ext'] . "001";
                 $to_host = $domainCreate->name;
               }

               if($key !== "invalid" && $key !== "nothing"){
                 //AA Not Found Dial plan for AA
                 ${"query" . $key} = array(
                   'object' => 'dialrule',
                   'action' => "create",
                   'matchrule' => "Prompt_" . $AA['ext'] . "001.Case_" . $key,
                   'domain' => $domainCreate->name,
                   'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                   'responder' => $responder[$optionNumber[0]],
                   'to_user' => $to_user,
                   'to_host' => $to_host,
                   'plan_description' => "AA designer: press " . $optionNumber[1]
                 );
                 //    echo "<br>Create AA Press " . $key . " Found Dial Plan - ";
                 //    var_export(${"query" . $key});
                 $aaPressDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, ${"query" . $key}, null, $http_response);
                 //    echo "<br><br>";
               } else if ($key == "nothing"){
                 if($optionNumber[0] == "Repeat"){
                   //No Press Repeat option
                   $query = array(
                         'object' => 'dialrule',
                         'action' => "create",
                         'matchrule' => "Prompt_" . $AA['ext'] . "001.Default",
                         'domain' => $domainCreate->name,
                         'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                         'responder' => "sip:start@to-user",
                         'to_user' => $AA['ext'] . "001",
                         'to_host' => $domainCreate->name,
                         'plan_description' => "AA designer: No Press Repeat"
                   );
                   //    echo "<br>Create AA No Press Repeat - ";
                   //    var_export($query);
                   $aaNoPressRepeatDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
                 } else {
                   $follow = explode(' ', $optionNumber[0]);
                   ${"query" . $follow[1]}['matchrule'] = "Prompt_" . $AA['ext'] . "001.Default";
                   ${"query" . $follow[1]}['plan_description'] = "AA designer:  No key press";
                   //    echo "<br>Create AA Press Nothing - ";
                   //    var_export(${"query" . $key});
                   $aaNoPressPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, ${"query" . $follow[1]}, null, $http_response);
                 }
               } else if ($key == "invalid"){
                 if($optionNumber[0] == "Repeat"){
                   //Invalid Repeat option
                   $query = array(
                         'object' => 'dialrule',
                         'action' => "create",
                         'matchrule' => "Prompt_" . $AA['ext'] . "001.*",
                         'domain' => $domainCreate->name,
                         'dialplan' => $domainCreate->name . "_" . $AA['ext'],
                         'responder' => "sip:start@to-user",
                         'to_user' => $AA['ext'] . "001",
                         'to_host' => $domainCreate->name,
                         'plan_description' => "AA designer: Invalid Repeat"
                   );
                   //    echo "<br>Create AA Invalid Repeat - ";
                   //    var_export($query);
                   $aaInvalidRepeatDialPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
                 } else {
                   $follow = explode(' ', $optionNumber[0]);
                   ${"query" . $follow[1]}['matchrule'] = "Prompt_" . $AA['ext'] . "001.*";
                   ${"query" . $follow[1]}['plan_description'] = "AA designer:  No key press";
                   //    echo "<br>Create AA Press Invalid - ";
                   //    var_export(${"query" . $follow[1]});
                   $aaInvalidPressPlanResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, ${"query" . $follow[1]}, null, $http_response);
                 }
               }
             }
           }
        }

        //    echo "<br><br>";
        //Create Time Frames
        foreach($domainCreate->timeFrames as $timeFrame){
          $i = 0;
            $created = "no";
            foreach ($timeFrame['times'] as $key => $time){
              $hour = explode(' - ', $time);
              if($timeFrame['type'] == "range"){
                $date_from = "now";
                $date_to = "never";
                $tod_from = date("H:i", strtotime($hour[0]));
                $tod_to = date("H:i", strtotime($hour[1]) - 60);
                $days = $i;
              } else {
                $date_from = date("Y-m-d", strtotime($hour[0]));
                $date_to = date("Y-m-d", strtotime($hour[0]));
                $tod_from = "00:00";
                $tod_to = "23:59";
                $days = "*";
              }
              if($i < 1){
                $created = "no";
              }
              if($time == " - "){
                $i=$i+1;
              } else {
                if($created == "no"){
                  $action = "create";
                } else {
                  $action = "update";
                }
                $from = date("H:i", strtotime($hour[0]));
                $to = date("H:i", strtotime($hour[1]) - 60);
                $query = array(
                      'object' => 'timerange',
                      'action' => $action,
                      'format' => "json",
                      'date_from' => $date_from,
                      'date_to' => $date_to,
                      'tod_from' => $tod_from,
                      'tod_to' => $tod_to,
                      'owner_domain' => $domainCreate->name,
                      'owner' => "*",
                      'time_frame' => $timeFrame['name'],
                      'days' => $days,
                      'order' => $i
                );
                //    echo "<br>Create Time Frame Range - ";
                //    var_export($query);
                $timeFrameCreateResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
                $created = "yes";
                $i=$i+1;
              }
            }

          //    echo "<br><br>";
        }

        foreach($domainCreate->utilities as $utility){
          //Create Utility Extension
          $uid = $utility['ext'] . "@" . $domainCreate->name;
          $query = array(
            'object' => 'subscriber',
            'action' => "create",
            'format' => "json",
            'user' => $utility['ext'],
            'domain' => $domainCreate->name,
            'first_name' => $utility['firstName'],
            'last_name' => $utility['lastName'],
            'subscriber_login' => $uid,
            'uid' => $uid,
            'pwd_hash' => "tempAA11!!",
            'vmail_provisioned' => "yes",
            'vmail_enabled' => "no",
            'subscriber_pin' => "9876",
            'vmail_notify' => "no",
            'vmail_transcribe' => "no",
            'data_limit' => 10000,
            'time_zone' => $domainCreate->timeZone,
            'area_code' => $domainCreate->areaCode,
            'callid_nmbr' => $domainCreate->callerIDNumber,
            'callid_name' => $domainCreate->callerIDName,
            'callid_emgr' => $domainCreate->emergencyNumber,
            'dial_plan' => $domainCreate->name,
            'dial_policy' => $domainCreate->dialPolicy,
            'dir_list' => "no",
            'dir_anc' => "no",
            'no_answer_timeout' => $utility['ringTime'],
            'vmail_annc_time' => "no",
            'vmail_annc_cid' => "no",
            'scope' => "Basic User"
          );
          $userList[$utility['ext']] = $utility['firstName'] . " " . $utility['lastName'];
          //    echo "<br>Utility - ";
          //    var_export($query);
          $utilityResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

          //Create utility answering rule
          $query = array(
             'object' => 'answerrule',
             'action' => 'create',
             'format' => "json",
             'uid' => $uid,
             'time_frame' => '*',
             'priority' => "0",
             'dnd_control' => "d",
             'enable' => "yes",
             'order' => "99",
             "scr_control" => "d",
             'for_control' => "d",
             'for_parameters' => "",
             "foa_control" => "d",
             "foa_parameters" => "",
             "fnr_control" => "d",
             "fnr_paramters" => "",
             "fna_control" => "d",
             "fna_paramters" => "",
             "sim_control" => "d",
             "sim_parameters" => ""
           );

           $list = "";
           foreach($utility['ring'] as $ring){
             $list .= $ring . " ";
           }
           $list = substr($list, 0, -1);
          if($utility['lastName'] == "RG"){
            $query['sim_control'] = "e";
            $query['sim_parameters'] = $list;
            if($utility['unansweredTreatment'] != ""){
              $query['fna_control'] = "e";
              if($utility['unansweredTreatment'] == "User"){
                $query['fna_parameters'] = "user_" . $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "Voicemail"){
                $query['fna_parameters'] = "vmail_" . $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "Queue"){
                $query['fna_parameters'] = "queue_" . $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "External Number"){
                $query['fna_parameters'] = $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "AA"){
                $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$utility['unansweredDestination']]));
                $query['fna_parameters'] = "aa_" . $name;
              }
            }
            if($utility['offlineTreatment'] != ""){
              $query['foa_control'] = "e";
              if($utility['offlineTreatment'] == "User"){
                $query['foa_parameters'] = "user_" . $utility['offlineDestination'];
              } else if($utility['offlineTreatment'] == "Voicemail"){
                $query['foa_parameters'] = "vmail_" . $utility['offlineDestination'];
              } else if($utility['offlineTreatment'] == "Queue"){
                $query['foa_parameters'] = "queue_" . $utility['offlineDestination'];
              } else if($utility['offlineTreatment'] == "External Number"){
                $query['foa_parameters'] = $utility['offlineDestination'];
              } else if($utility['offlineTreatment'] == "AA"){
                $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$utility['offlineDestination']]));
                $query['foa_parameters'] = "aa_" . $name;
              }
            }
          } else if($utility['lastName'] == "FWD"){
            $query['for_control'] = "e";
            $query['for_parameters'] = $list;
            if($utility['unansweredTreatment'] != ""){
              $query['fna_control'] = "e";
              if($utility['unansweredTreatment'] == "User"){
                $query['fna_parameters'] = "user_" . $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "Voicemail"){
                $query['fna_parameters'] = "vmail_" . $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "Queue"){
                $query['fna_parameters'] = "queue_" . $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "External Number"){
                $query['fna_parameters'] = $utility['unansweredDestination'];
              } else if($utility['unansweredTreatment'] == "AA"){
                $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$utility['unansweredDestination']]));
                $query['fna_parameters'] = "aa_" . $name;
              }
            }
          }
          if($utility['unansweredTreatment'] == "User" || $utility['unansweredTreatment'] == "Voicemail" || $utility['unansweredTreatment'] == "AA"){
            $push = $treatments[$utility['unansweredTreatment']] . $utility['unansweredDestination'];
            if(!in_array($push, $dialRules)){
              array_push($dialRules, $push);
            }
          }
          if($utility['offlineTreatment'] == "User" || $utility['offlineTreatment'] == "Voicemail" || $utility['offlineTreatment'] == "AA"){
            $push = $treatments[$utility['offlineTreatment']] . $utility['offlineDestination'];
            if(!in_array($push, $dialRules)){
              array_push($dialRules, $push);
            }
          }

           //    echo "<br>Utility Answering Rule - ";
           //    var_export($query);
           $utilityAnsweringRulesResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
           //    echo "<br><br><br>";
        }

        foreach($domainCreate->ToDs as $ToD){
          //Create ToDs
          $uid = $ToD['ext'] . "@" . $domainCreate->name;
          $query = array(
            'object' => 'subscriber',
            'action' => "create",
            'format' => "json",
            'user' => $ToD['ext'],
            'domain' => $domainCreate->name,
            'first_name' => $ToD['firstName'],
            'last_name' => $ToD['lastName'],
            'subscriber_login' => $uid,
            'uid' => $uid,
            'pwd_hash' => "tempAA11!!",
            'vmail_provisioned' => "yes",
            'vmail_enabled' => "no",
            'subscriber_pin' => "9876",
            'vmail_notify' => "no",
            'vmail_transcribe' => "no",
            'data_limit' => 10000,
            'time_zone' => $domainCreate->timeZone,
            'area_code' => $domainCreate->areaCode,
            'callid_nmbr' => $domainCreate->callerIDNumber,
            'callid_name' => $domainCreate->callerIDName,
            'callid_emgr' => $domainCreate->emergencyNumber,
            'dial_plan' => $domainCreate->name,
            'dial_policy' => $domainCreate->dialPolicy,
            'dir_list' => "no",
            'dir_anc' => "no",
            'no_answer_timeout' => "25",
            'vmail_annc_time' => "no",
            'vmail_annc_cid' => "no",
            'scope' => "Basic User"
          );
          $userList[$ToD['ext']] = $ToD['firstName'] . " " . $ToD['lastName'];
          //    echo "<br>ToD - ";
          //    var_export($query);
          $todResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
          $i = 1;
          foreach($ToD['timeFrame'] as $key => $timeframe){
            //Create tod answering rule
            $option = explode(' - ', $timeframe);
            if($key == "Default"){
              $i = 99;
              $time = "*";
            } else {
              $time = $key;
            }
            $query = array(
               'object' => 'answerrule',
               'action' => 'create',
               'format' => "json",
               'uid' => $uid,
               'time_frame' => $time,
               'priority' => $i,
               'dnd_control' => "d",
               'enable' => "yes",
               'order' => $i,
               "fna_control" => "d",
               'fna_parameters' => "",
               "scr_control" => "d",
               'for_control' => "e",
               "foa_control" => "d",
               "foa_parameters" => "",
               "fnr_control" => "d",
               "fnr_paramters" => "",
               "fna_control" => "d",
               "fna_paramters" => "",
               "sim_control" => "d",
               "sim_parameters" => ""
             );
             if($option[0] == "User"){
               $query['for_parameters'] = "user_" . $option[1];
             } else if($option[0] == "Voicemail"){
               $query['for_parameters'] = "vmail_" . $option[1];
             } else if($option[0] == "Queue"){
               $query['for_parameters'] = "queue_" . $option[1];
             } else if($option[0] == "External Number"){
               $query['for_parameters'] = $option[1];
             } else if($option[0] == "AA"){
               $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$option[1]]));
               $query['for_parameters'] = "aa_" . $name;
             }
             $i++;
             if($option[0] == "User" || $option[0] == "Voicemail" || $option[0] == "AA"){
               $push = $treatments[$option[0]] . $option[1];
               if(!in_array($push, $dialRules)){
                 array_push($dialRules, $push);
               }
             }
             //    echo "<br>";
             //    var_export($query);
             $todTimeframResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

          }
       }

       foreach($domainCreate->queues as $queue){
         //Create Queues
         $queueName = explode(' ', $queue[1], 2);
         if($queueName[1] == ""){
           $queueName[1] = "Queue";
         }
         $uid = $queue[0] . "@" . $domainCreate->name;
         $query = array(
           'object' => 'subscriber',
           'action' => "create",
           'format' => "json",
           'user' => $queue[0],
           'domain' => $domainCreate->name,
           'first_name' => $queueName[0],
           'last_name' => $queueName[1],
           'subscriber_login' => $uid,
           'pwd_hash' => "tempAA11!!",
           'vmail_provisioned' => "yes",
           'vmail_enabled' => $queue[9],
           'subscriber_pin' => "9876",
           'vmail_notify' => "no",
           'vmail_transcribe' => "no",
           'data_limit' => 10000,
           'time_zone' => $domainCreate->timeZone,
           'area_code' => $domainCreate->areaCode,
           'callid_nmbr' => $domainCreate->callerIDNumber,
           'callid_name' => $domainCreate->callerIDName,
           'callid_emgr' => $domainCreate->emergencyNumber,
           'dial_plan' => $domainCreate->name,
           'dial_policy' => $domainCreate->dialPolicy,
           'no_answer_timeout' => $queue[12],
           'dir_list' => "no",
           'dir_anc' => "no",
           'vmail_annc_time' => "no",
           'vmail_annc_cid' => "no",
           'srv_code' => 'system-queue',
           'scope' => "Basic User"
         );
         //    echo "<br>Queue - ";
         //    var_export($query);
         $queueResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

         //Create Call Queue object
         $query = array(
            'object' => 'callqueue',
            'action' => "create",
            'domain' => $domainCreate->name,
            'queue' => $queue[0],
            'description' => $queue[1],
            'max_time' => "",
            'auto_logout' => "no"
        );
        if($queue[6] == ""){
          $query['wait_limit'] = "0";
        } else {
          $query['wait_limit'] = $queue[6];
        }
        if($queue[7] == ""){
          $query['length_limit'] = "0";
        } else {
          $query['length_limit'] = $queue[7];
        }
        if($queue[5] == ""){
          $query['agent_required'] = "no";
        } else {
          $query['agent_required'] = $queue[5];
        }
        if($queue[9] == "" || $queue[9] == "no"){
          $query['callback_max_hours'] = "0";
        } else {
          $query['callback_max_hours'] = "2";
        }
        if($queue[15] == ""){
          if($queue[2] == "Call Park"){
            $query['connect_to'] = "0";
          } else {
            $query['connect_to'] = "15";
          }
        } else {
          $query['connect_to'] = $queue[15];
        }
        if($queue[2] == "Call Park"){
          $query['run_stats'] = "no";
          $query['huntgroup_option'] = "Call Park";
        } else {
          $query['run_stats'] = "yes";
          $query['enable_sms'] = "0";
          $query['initiation_keyword'] = "HELP";
          $query['initiation_message'] = "You have now entered the queue.  An agent will be with your shortly.";
          $query['initiation_needed_message'] = "Reply HELP to enter queue.";
          $query['termination_keyword'] = "DONE";
          $query['termination_message'] = "You have now exited the conversation. Thank you.";
          $query['no_agents_message'] = "Sorry, there are no agents available right now. Please check at a later time";
          if($queue[17] == ""){
            $query['sring_inc'] = "1";
          } else {
            $query['sring_inc'] = $queue[17];
          }
          if($queue[2] == "Round-robin"){
            $query['huntgroup_option'] = "1stAvail";
            $query['sring_1st'] = "1";
          } else if($queue[2] == "Ring All"){
            $query['huntgroup_option'] = "SRing";
            $query['sring_1st'] = "0";
          } else if($queue[2] == "Linear Hunt"){
            $query['huntgroup_option'] = "Linear";
            $query['sring_1st'] = $queue[16];
          } else if($queue[2] == "Linear Cascade"){
            $query['huntgroup_option'] = "SRingOrdered";
            $query['sring_1st'] = $queue[16];
          }
        }

        //    echo "<br>Queue Object - ";
        //    var_export($query);
        $queueObjectResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        //    echo "<br>";

        //Queue Dialrule
        $query = array(
           'object' => 'dialrule',
           'action' => "create",
           'domain' => $domainCreate->name,
           'dialplan' => $domainCreate->name,
           'matchrule' => "*",
           'responder' => "sip:start@call-queuing",
           'matchrule' => "queue_" . $queue[0],
           'to_scheme' => "sip:",
           'to_user' => $queue[0],
           'to_host' => $domainCreate->name,
           'plan_description' => "Portal Created: Call Queue - " . $queue[0] . " (" . $queueName[0] . " " . $queueName[1] . ")"
       );
       //    echo "<br>Queue Dial Rule - ";
       //    var_export($query);
       $queueDialRuleResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);


        //Queue Answering Rule
        $query = array(
           'object' => 'answerrule',
           'action' => 'create',
           'format' => "json",
           'user' => $queue[0],
           'domain' => $domainCreate->name,
           'time_frame' => "*",
           'priority' => "99",
           'dnd_control' => "d",
           'enable' => "yes",
           'order' => "99",
           "fna_control" => "d",
           'fna_parameters' => "e",
           "scr_control" => "d",
           'for_control' => "e",
           "for_parameters" => "queue_" . $queue[0],
           "fbu_control" => "d",
           "fbu_parameters" => "",
           "fnr_control" => "d",
           "fnr_paramters" => "",
           "fna_control" => "d",
           "fna_paramters" => "",
           "foa_control" => "d",
           "foa_paramters" => "",
           "sim_control" => "d",
           "sim_parameters" => ""
         );
         if($queue[10] != ""){
           $query['fbu_control'] = "e";
           if($queue[10] == "User"){
             $query['fbu_parameters'] = "user_" . $queue[11];
           } else if($queue[10] == "Voicemail"){
             $query['fbu_parameters'] = "vmail_" . $queue[11];
           } else if($queue[10] == "Queue"){
             $query['fbu_parameters'] = "queue_" . $queue[11];
           } else if($queue[10] == "External Number"){
             $query['fbu_parameters'] = $queue[11];
           } else if($queue[10] == "AA"){
             $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$queue[11]]));
             $query['fbu_parameters'] = "aa_" . $name;
           }
         }
         if($queue[13] != ""){
           $query['fna_control'] = "e";
           if($queue[13] == "User" || $queue[13] == "AA"){
             $query['fna_parameters'] = "user_" . $queue[14];
           } else if($queue[13] == "Voicemail"){
             $query['fna_parameters'] = "vmail_" . $queue[14];
           } else if($queue[13] == "Queue"){
             $query['fna_parameters'] = "queue_" . $queue[14];
           } else if($queue[13] == "External Number"){
             $query['fna_parameters'] = $queue[14];
           } else if($queue[13] == "AA"){
             $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$queue[14]]));
             $query['fna_parameters'] = "aa_" . $name;
           }
         }
         if($queue[10] == "User" || $queue[10] == "Voicemail" || $queue[10] == "AA"){
           $push = $treatments[$queue[10]] . $queue[11];
           if(!in_array($push, $dialRules)){
             array_push($dialRules, $push);
           }
         }
         if($queue[13] == "User" || $queue[13] == "Voicemail" || $queue[13] == "AA"){
           $push = $treatments[$queue[13]] . $queue[14];
           if(!in_array($push, $dialRules)){
             array_push($dialRules, $push);
           }
         }
         //    echo "<br>Queue Answering Rule - ";
         //    var_export($query);
         $queueAnsweringRuleResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
         //    echo "<br>";
        if($queue[2] != "Call Park"){
          //    $query = array('object' => 'recording', 'action' => "create", 'aor' => $uid, 'format' => "json");
          //    echo "<br>Queue Recording - ";
          //    var_export($query);
          $queueRecordingResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        }
       }
       //    echo "<br><br><br>";
       //Create Agents
       foreach($domainCreate->agents as $agent){
         //Create Agent
        if(in_array($agent[0], $mobileLicenses)){
          $query = array(
             'object' => 'agent',
             'action' => "create",
             'format' => "json",
             'domain' => $domainCreate->name,
             'queue' => $agent[1],
             'device' => "sip:" . $agent[0] . "m@" . $domainCreate->name,
             'wrap_up_sec' => $agent[2],
             'call_limit' => $agent[3],
             'entry_order' => $agent[4],
             'entry_priority' => "1"
         );
         //    echo "<br>Create Mobile App Agents - ";
         //    var_export($query);
         $agentResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
         $query = array(
            'object' => 'agent',
            'action' => "create",
            'format' => "json",
            'domain' => $domainCreate->name,
            'queue' => $agent[1],
            'device' => "sip:" . $agent[0] . "wp@" . $domainCreate->name,
            'wrap_up_sec' => $agent[2],
            'call_limit' => $agent[3],
            'entry_order' => $agent[4],
            'entry_priority' => "1"
        );
        //    echo "<br>Create Web Phone Agents - ";
        //    var_export($query);
        $agentResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        } else {
          $query = array(
             'object' => 'agent',
             'action' => "create",
             'format' => "json",
             'domain' => $domainCreate->name,
             'queue' => $agent[1],
             'device' => "sip:" . $agent[0] . "@" . $domainCreate->name,
             'wrap_up_sec' => $agent[2],
             'call_limit' => $agent[3],
             'entry_order' => $agent[4],
             'entry_priority' => "1"
         );
         //    echo "<br>Create Agents - ";
         //    var_export($query);
         $agentResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
        }
       }
       //    echo "<br><br><br>";
      //Create Conference Bridge Subscriber
      foreach($domainCreate->conferences as $conference){
        $conferenceName = explode(' ', $conference[0], 2);
        if($conferenceName[1] == ""){
          $conferenceName[1] = "Bridge";
        }
        $uid = $conference[1] . "@" . $domainCreate->name;
        $query = array(
          'object' => 'subscriber',
          'action' => "create",
          'format' => "json",
          'user' => $conference[1],
          'domain' => $domainCreate->name,
          'first_name' => $conferenceName[0],
          'last_name' => $conferenceName[1],
          'subscriber_login' => $uid,
          'pwd_hash' => "tempAA11!!",
          'vmail_provisioned' => "yes",
          'vmail_enabled' => "no",
          'subscriber_pin' => "9876",
          'vmail_notify' => "no",
          'vmail_transcribe' => "no",
          'data_limit' => 10000,
          'call_limit' => 0,
          'time_zone' => $domainCreate->timeZone,
          'area_code' => $domainCreate->areaCode,
          'callid_nmbr' => $domainCreate->callerIDNumber,
          'callid_name' => $domainCreate->callerIDName,
          'callid_emgr' => $domainCreate->emergencyNumber,
          'dial_plan' => $domainCreate->name,
          'dial_policy' => $domainCreate->dialPolicy,
          'no_answer_timeout' => 25,
          'dir_list' => "no",
          'dir_anc' => "no",
          'vmail_annc_time' => "no",
          'vmail_annc_cid' => "no",
          'srv_code' => 'system-conf',
          'scope' => "Basic User"
        );
      //    echo "<br>Create Conference - ";
      //    var_export($query);
      $conferenceResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
      //Create Conference Object
      $query = array(
        'object' => 'conference',
        'action' => "create",
        'format' => "json",
        'aor' => "sip:" . $conference[1] . "." . $domainCreate->name . "@conference-bridge",
        'owner_uid' => $uid,
        'domain' => $domainCreate->name,
        'name' => $conference[0],
        'SipSipAuthUser' => $conference[1],
        'leader_pin' => $conference[2],
        'participants_pin' => $conference[3],
        'max_participants' => $conference[4],
        'quorum' => $conference[5],
        'leader_required' => $conference[6],
        'annc_part' => $conference[7],
        'request_name' => $conference[8]
      );
      //    echo "<br>Create Conference Object - ";
      //    var_export($query);
      $conferenceObjectResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

      //Create Conference Dial Rule
      $query = array(
        'object' => 'dialrule',
        'action' => "create",
        'domain' => $domainCreate->name,
        'dialplan' => $domainCreate->name,
        'matchrule' => "conf_" . $conference[1],
        'responder' => "sip:start@to-owned-device",
        'to_scheme' => "sip:",
        'to_user' => $conference[1] . "." . $domainCreate->name,
        'to_host' => "conference-bridge",
        'from_scheme' => "sip:",
        'plan_description' => "Conference Bridge - " . $conference[1] . "(" . $conference[0] . ")"
      );
      //    echo "<br>Add Conference Dial Rule - ";
      //    var_export($query);
      $dialPlanTableResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);

      //Create Conference Answering Rule
      $query = array(
         'object' => 'answerrule',
         'action' => 'create',
         'format' => "json",
         'user' => $conference[1],
         'domain' => $domainCreate->name,
         'time_frame' => "*",
         'priority' => "99",
         'dnd_control' => "d",
         'enable' => "yes",
         'order' => "99",
         "fna_control" => "d",
         'fna_parameters' => "e",
         "scr_control" => "d",
         'for_control' => "e",
         "for_parameters" => "conf_" . $conference[1],
         "fbu_control" => "d",
         "fbu_parameters" => "",
         "fnr_control" => "d",
         "fnr_paramters" => "",
         "fna_control" => "d",
         "fna_paramters" => "",
         "foa_control" => "d",
         "foa_paramters" => "",
         "sim_control" => "d",
         "sim_parameters" => ""
       );
       //    echo "<br>Add Conference Answering Rule - ";
       //    var_export($query);
       $dialPlanTableResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
       //    echo "<br><br><br>";
      }

      //Create Dial Rules for users, voicemails, and AAs
      foreach($dialRules as $dialRule){
        $ext = explode("_", $dialRule);
        if($ext[0] == "vmail"){
          $respond = "Residential VMail";
          $description = "Portal Created: Voicemail - " . $ext[1] . " (" . $userList[$ext[1]] . ")";
        } else if($ext[0] == "user"){
          $respond = "sip:start@to-user";
          $description = "Portal Created: User - " . $ext[1] . " (" . $userList[$ext[1]] . ")";
        }
        if($ext[0] == "aa"){
          $respond = "sip:start@to-user";
          $name = str_replace(' ', '', str_replace('Auto Attendant', '', $userList[$ext[1]]));
          $dialRule = "aa_" . $name;
          $description = "Portal Created: Auto Attendant- " . $userList[$ext[1]] . " (" . $ext[1] . ")";
        }
        $query = array(
           'object' => 'dialrule',
           'action' => "create",
           'domain' => $domainCreate->name,
           'dialplan' => $domainCreate->name,
           'responder' => $respond,
           'matchrule' => $dialRule,
           'to_scheme' => "sip:",
           'to_user' => $ext[1],
           'to_host' => "<OwnDomain>",
           'plan_description' => $description
       );
       //    echo "<br>";
       //    var_export($query);
       $dialPlanUserResult = doCurl(APIROOT, CURLOPT_POST, "Authorization: Bearer " . $info->accessToken, $query, null, $http_response);
      }
    }
    //Think about Sites and Departments
  }
}
