<?php
require_once('../../globals.php');
require_once('../../../library/calendar.inc');
require_once('../../../library/patient.inc');

// session_start();
// 
// if(isset($_POST['pc_username'])) { 
//   $_SESSION['pc_username'] = $_POST['pc_username']; 
// }
// if(isset($_POST['pc_facility'])) { 
//   $_SESSION['pc_facility'] = $_POST['pc_facility']; 
//   
  // // if facility is changed, we set it to all the providers
  // $_SESSION['pc_username'] = array();
  // array_push($_SESSION['pc_username'], '__PC_ALL__');
// }
// 
// if(!isset($_SESSION['pc_username'])) { // if we haven't selected a provider, select all by default
//   $_SESSION['pc_username'] = array();
//   array_push($_SESSION['pc_username'], '__PC_ALL__');
// }

?>
<html>
<head>  
  <link href='full_calendar/fullcalendar.min.css' rel='stylesheet' />
  <link href='full_calendar/fullcalendar.print.min.css' rel='stylesheet' media='print' />
  <link href='full_calendar_scheduler/scheduler.min.css' rel='stylesheet' />
  <script src='full_calendar/lib/moment.min.js'></script>
  <script src='full_calendar/lib/jquery.min.js'></script>
  <script src='full_calendar/fullcalendar.min.js'></script>
  <script src='full_calendar_scheduler/scheduler.min.js'></script>
</head>
<body>
  <div style="float:left; width:15%; min-width:140px; clear:left; display:block;">
    <form name='theform' id='theform' method='post' onsubmit='return top.restoreSession()'>
    <?php
      // FACILITIES
      // From Michael Brinson 2006-09-19:
      if (isset($_POST['pc_username'])) $_SESSION['pc_username'] = $_POST['pc_username'];

      //(CHEMED) Facility filter
      if (isset($_POST['all_users'])) $_SESSION['pc_username'] = $_POST['all_users'];

      // bug fix to allow default selection of a provider
      // added 'if..POST' check -- JRM
      if (isset($_REQUEST['pc_username']) && $_REQUEST['pc_username']) $_SESSION['pc_username'] = $_REQUEST['pc_username'];

      // (CHEMED) Get the width of vieport
      if (isset($_GET['framewidth'])) $_SESSION['pc_framewidth'] = $_GET['framewidth'];

      // FACILITY FILTERING (lemonsoftware) (CHEMED)
      $_SESSION['pc_facility'] = 0;

      /*********************************************************************
      if ($_POST['pc_facility'])  $_SESSION['pc_facility'] = $_POST['pc_facility'];
      *********************************************************************/
      if (isset($_COOKIE['pc_facility']) && $GLOBALS['set_facility_cookie']) $_SESSION['pc_facility'] = $_COOKIE['pc_facility'];
      // override the cookie if the user doesn't have access to that facility any more
      if ($_SESSION['userauthorized'] != 1 && $GLOBALS['restrict_user_facility']) { 
        $facilities = getUserFacilities($_SESSION['authId']);
        // use the first facility the user has access to, unless...
        $_SESSION['pc_facility'] = $facilities[0]['id']; 
        // if the cookie is in the users' facilities, use that.
        foreach ($facilities as $facrow) {
          if (($facrow['id'] == $_COOKIE['pc_facility']) && $GLOBALS['set_facility_cookie'])
            $_SESSION['pc_facility'] = $_COOKIE['pc_facility'];
        }
      }
      if (isset($_POST['pc_facility']))  {
        $_SESSION['pc_facility'] = $_POST['pc_facility'];
      }
      /********************************************************************/

      if (isset($_GET['pc_facility']))  $_SESSION['pc_facility'] = $_GET['pc_facility'];
      if ($GLOBALS['set_facility_cookie'] && ($_SESSION['pc_facility'] > 0)) setcookie("pc_facility", $_SESSION['pc_facility'], time() + (3600 * 365));

      // Simplifying by just using request variable instead of checking for both post and get - KHY
      if (isset($_REQUEST['viewtype'])) $_SESSION['viewtype'] = $_REQUEST['viewtype'];

      // CHEMED
      $facilities = getUserFacilities($_SESSION['authId']); // from users_facility
      if ( $_SESSION['pc_facility'] ) {
         $provinfo = getProviderInfo('%', true, $_SESSION['pc_facility']);
      } else {
         $provinfo = getProviderInfo();
      }
      
      // lemonsoftware
      if ($_SESSION['authorizeduser'] == 1) {
        $facilities = getFacilities();
      } else {
        $facilities = getUserFacilities($_SESSION['authId']); // from users_facility
        if (count($facilities) == 1)
          $_SESSION['pc_facility'] = key($facilities);
      }
      
      if (count($facilities) > 1) {
        echo "   <select name='pc_facility' id='pc_facility' >\n";
        if ( !$_SESSION['pc_facility'] ) $selected = "selected='selected'";
        echo "    <option value='0' $selected>"  .xl('All Facilities'). "</option>\n";

        foreach ($facilities as $fa) {
            $selected = ( $_SESSION['pc_facility'] == $fa['id']) ? "selected" : "" ;
            echo "    <option style=background-color:".htmlspecialchars($fa['color'],ENT_QUOTES)." value='" .htmlspecialchars($fa['id'],ENT_QUOTES). "' $selected>"  .htmlspecialchars($fa['name'],ENT_QUOTES). "</option>\n";
        }
        echo "   </select>\n";
      }

      // PROVIDERS
      foreach($_SESSION['pc_username'] as $provider) {   //if __PC_ALL__ is one of selected, we set session as all the providers
        if($provider == "__PC_ALL__") {
          $_SESSION['pc_username'] = array();
          foreach($provinfo as $doc) {
            array_push($_SESSION['pc_username'], $doc['username']);
          }
        }
      }
      
      
      // remove those providers which aren't in provinfo from session
      $provinfo_users = array();
      foreach($provinfo as $doc) {
        array_push($provinfo_users, $doc['username']);
      }
      $_SESSION['pc_username'] = array_intersect($_SESSION['pc_username'], $provinfo_users);
      
      echo "   <select multiple size='15' name='pc_username[]' id='pc_username'>\n";
      echo "    <option value='__PC_ALL__'>"  .xl ("All Users"). "</option>\n";
      foreach ($provinfo as $doc) {
        $username = $doc['username'];
        echo "    <option value='$username'";
        foreach ($_SESSION['pc_username'] as $provider) {          
          if ($provider == $username) {
            echo " selected";
          }
        }
        echo ">" . htmlspecialchars($doc['lname'],ENT_QUOTES) . ", " . htmlspecialchars($doc['fname'],ENT_QUOTES) . "</option>\n";
      }
      echo "   </select>\n";
    ?>
  </form>
    <?php 
    if($_SESSION['pc_facility'] == 0){
      echo '<div id="facilityColor">';
      echo '<table>';
      foreach ($facilities as $f){
        echo "   <tr><td><div class='view1' style=background-color:".$f['color'].";font-weight:bold>".htmlspecialchars($f['name'],ENT_QUOTES)."</div></td></tr>";
      }
      echo '</table>';
      echo '</div>';
    }
    ?>
  </div>
  
  <div style="height: 99%;">
    <div id='calendar' style="overflow-x:auto; display:block;"></div>
  </div>
  
  <script>
    $(document).ready(function() {

      $('#calendar').fullCalendar({
        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
        height: 'parent',
        header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,agendaDay'
        },
        defaultView: 'agendaDay',
        defaultTimedEventDuration: '00:15:00',
        minTime: '08:00:00',  // TODO: set according to globals
        maxTime: '18:00:00',
        slotDuration: '00:15:00',
        views: {
          week: {
            // options apply to basicWeek and agendaWeek views
            groupByResource: true
          },
          day: {
            // options apply to basicDay and agendaDay views
            groupByDateAndResource: true
          }
        },
        resources: {
          url: 'api/get_providers.php',
          type: 'POST',
          error: function() {
              alert('There was an error while fetching providers.');
          }
        },
        events: {
          url: 'api/get_provider_events.php',
          type: 'POST',
          error: function() {
              alert('There was an error while fetching events.');
          }
        }
      })
      
    });
    
    
    
    $("#pc_username").change(function() { $('#theform').submit(); });
    $("#pc_facility").change(function() { $('#theform').submit(); });
  </script>
</body>
</html>
