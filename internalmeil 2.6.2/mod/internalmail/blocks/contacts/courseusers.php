<?PHP

require_once("../../../../config.php");
require_once("../../lib.php");

//require_variable($course);
//require_variable($option);
//require_variable($idc);

$course = required_param('course', PARAM_INT);   // course
$option = required_param('option', PARAM_INT);   // course
$idc    = required_param('idc', PARAM_INT);   // course
$currentgroup = optional_param('grpid', 0, PARAM_INT);   // Group to show

global $CFG;

//echo "<html>";

print_header(get_string('block_Contacts','internalmail'));

echo	"\n <script language=\"JavaScript\" type=\"text/javascript\" >";

echo "\nfunction addContact(email) {";

$groupinfo = "";
if ( $currentgroup ) {
    $groupinfo = "&grpid=$currentgroup ";
}
if ($option == 1) {
    echo "\n field=window.opener.document.theform.destiny;";
    echo "\n if (field.value==\"\"){";
    echo "\n   field.value= email;";
    echo "\n } else {";
    echo "\n  var bool=0;";
    echo "\n  var comprova=field.value.split(\",\");";
    echo "\n  var n=comprova.length;";
    echo "\n  var i=0;";

    echo "\n  while(i<=n && bool==0){";
    echo "\n   if( email==comprova[i] ) {";
    echo "\n     bool=1;";
    echo "\n   }";
    echo "\n   i++;";
    echo "\n  }";
  
    echo "\n  if (bool==0) {";
    echo "\n   field.value= field.value + \",\" + email;";
    echo "\n  }";
    echo "\n }";
} else {
    //echo "\n window.opener.location.href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$idc&option=1\";";
    echo "\n window.opener.location.href=\"$CFG->wwwroot/mod/internalmail/compose.php?id=$idc&option=1\";";
    echo "\n location.href=\"courseusers.php?course=$course&option=1&idc=$idc$groupinfo\";";
}

echo "\n }";

echo "\n</script>\n";

$curs = get_record("course", "id", $course);	
//echo "<center> <font color='#330066'><h2> <b>".$curs->teachers."</b></h2> </font> </center>";

// Course context
$context = get_context_instance(CONTEXT_COURSE, $course);
 
$isseparategroups = ($curs->groupmode == SEPARATEGROUPS and $curs->groupmodeforce and
		     !has_capability('moodle/site:accessallgroups', $context));

// we are looking for all users with this role assigned in this context or higher
if ($usercontexts = get_parent_contexts($context)) {
    $listofcontexts = '('.implode(',', $usercontexts).')';
} else {
    $listofcontexts = '()'; // must be site
}

$select = 'SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.city, u.country, r.roleid, 
                  u.picture, u.lang, u.timezone, u.emailstop, u.maildisplay, ul.timeaccess AS lastaccess '; // s.lastaccess

$from   = "FROM {$CFG->prefix}user u INNER JOIN
                {$CFG->prefix}role_assignments r on u.id=r.userid LEFT OUTER JOIN
                {$CFG->prefix}user_lastaccess ul on (r.userid=ul.userid and ul.courseid = $curs->id)"; 

// otherwise we run into the problem of having records in ul table, but not relevant course
// and user record is not pulled out
$where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
             AND u.deleted = 0 
             AND (ul.courseid = $curs->id OR ul.courseid IS NULL)
             AND u.username <> 'guest' ";
//$where .= get_lastaccess_sql($accesssince);

$wheresearch = '';


if ($currentgroup) {    // Displaying a group by choice
    // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"
    $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';
    $where .= ' AND gm.groupid = '.$currentgroup;
}

//$totalcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where);   // Each user could have > 1 role

/*if ($table->get_sql_where()) {
 $where .= ' AND '.$table->get_sql_where();
 }*/

 $sort = ' ORDER BY r.roleid';
/*if ($table->get_sql_sort()) {
 $sort = ' ORDER BY '.$table->get_sql_sort();
 } else {
 $sort = '';
 }*/

//$matchcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where.$wheresearch);

/*if ( $totalcount < $perpage ) {
 $perpage = 5000;
 }
 $table->initialbars($totalcount > $perpage);
 $table->pagesize($perpage, $matchcount);
 
 $userlist = get_records_sql($select.$from.$where.$wheresearch.$sort,
 $table->get_page_start(), $table->get_page_size());*/

//echo $select.$from.$where.$wheresearch.$sort;
$userlist = get_records_sql($select.$from.$where.$wheresearch.$sort );

$viewfullnames = (has_capability('moodle/site:viewfullnames', $context));

$count = 0;
$rolenames = array();
if ($roles = get_roles_used_in_context($context)) {
        
    // We should exclude "admin" users (those with "doanything" at site level) because 
    // Otherwise they appear in every participant list

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

    foreach ($roles as $role) {
	$rolenames[$role->id] = strip_tags(format_string($role->name));   // Used in menus etc later on
	if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)
	    unset($roles[$role->id]);
	    continue;
	}
    }
}

$newroleid = -1;
$enddisplay = 0;
foreach ($userlist as $user) {
    $subj = "CUR26::".$user->id."::".$curs->id;  //busquem el fantasma del curs per a veure si té bustia en aquell curs

    $havemailbox = get_record_sql("SELECT p.*
	                             FROM {$CFG->prefix}internalmail_posts p
                                    WHERE p.subject = '$subj'");
	    			
    if(!empty($havemailbox)) {

	if ($newroleid != $user->roleid ) {
	    $newroleid = $user->roleid;
	    if ( $enddisplay ) {
		echo "\n </ul>";
	    }
	    echo  "\n<div class=\"internalmail-block-title\">". $rolenames[$newroleid] ."</div>";
	    echo "\n <ul class='list'>";
	    $enddisplay = 1;
	}

	$picture = print_user_picture($user->id, $curs->id, $user->picture, 25, true,false);
	//$fullname = fullname($student, $isteacher);
	$fullname = fullname($user, $viewfullnames);
	$nom = $user->username;

	$classn = ($count % 2) ? "r0" :"r1";
	$text ="\n  <li class=\"".$classn.'"><span class="icon c0">' .$picture. '</span>';
	$text .='<span class="c1">' . "<a href=\"javascript:addContact('".$nom."')\">".$fullname.'</a> '. '</span></li>';
	$count++;
	echo $text;
    }
    unset($havemailbox);
}
echo "\n <ul>\n";

/*
if ($teachers = get_course_teachers($course)) {

  if(empty($teachers)){				
    $teachers=array();
  }

  echo '<center><table width="70%" border="1">';
  echo '<thead>';
  echo '<tr>';
  echo '<th bgcolor="#C6CED6" >'.get_string('image', 'internalmail') .' | '.get_string('name').' | '.get_string('surname', 'internalmail').'</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tr><td>';
  echo '<center><table>';
  
  foreach ($teachers as $teacher)  {
    $subj="CUR26::".$teacher->id."::".$course;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
    $havemailbox=get_record_sql("SELECT p.*
	                           FROM {$CFG->prefix}internalmail_posts p
	                          WHERE p.subject = '$subj'");
						
    if(!empty($havemailbox)) {
      if ($teacher->editall or ismember($currentgroup, $teacher->id))  	{
	$picture = print_user_picture($teacher->id, $course, $teacher->picture, 51, true,false); //pinta foto user
	$fullname = fullname($teacher, $isteacher); //obté nom
	$nom=$teacher->username;
	echo '<tr><td>';
	echo "<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<a href=\"javascript:addContact('".$nom."')\">"."<font size='4'><i>$fullname</i></font>"."</a><br>"; //imprimeix profes
	echo "</td></tr>";										            
      }
      unset($havemailbox);
    }
  }  
  echo"</td></tr>";
  echo '</table></center>';	              
  echo "</table></center>";	               	
}
	                 	
echo ""; //'<img src="users2.gif">';
echo "<center><font color='#330066'><h2> <b>".$curs->students."</b></h2> </font>  </center>";

*/


	              	
/*
$guest = get_guest();
$exceptions .= $guest->id;
if ($sort == "lastaccess") {
  $dsort = "s.timeaccess";
 } else  {
  $dsort = "u.$sort";
 }

$students = get_course_students($course);
$c=get_record("course","id",$course);
$totalcount=count_course_students($c, "", "", "", $currentgroup);
if ($firstinitial or $lastinitial)  {
  $matchcount = count_course_students($c, "", $firstinitial, $lastinitial, $currentgroup);
 } else  {
  $matchcount = $totalcount;
 }
		    	    
if(empty($students)) {							
  $students=array();					
}

$posvar = 0;

foreach ($students as $student)  {
  if (!$posvar) { //imprimim la llista nomes si hi ha estudiants
    echo '<center><table width="70%" border="1">';
    echo '<thead>';
    echo '<tr>';
    echo '<th bgcolor="#C6CED6" >'.get_string('image', 'internalmail') .' | '.get_string('name').' | '.get_string('surname', 'internalmail').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tr><td>';
    echo '<center><table>';
	
    $posvar=1;
  }

  $subj="CUR26::".$student->id."::".$course;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
  $havemailbox=get_record_sql("SELECT p.*
	                         FROM {$CFG->prefix}internalmail_posts p
	                        WHERE p.subject = '$subj'");
						
  if(!empty($havemailbox)) {
    $picture = print_user_picture($student->id, $course, $student->picture, 51, true,false);
    $fullname = fullname($student, $isteacher);
    $nom= $student->username;
    echo '<tr><td>';
    echo "<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>'; //'<img src="users2.gif">';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<a href=\"javascript:addContact('".$nom."')\">".$fullname."</a><br>";
    echo "</td></tr>";										            
  }	
  unset($havemailbox);	            
}

echo"</td></tr>";
echo '</table></center>';
echo "</table></center>";	               		
*/

print_footer();
//include($CFG->footer);

//include ($CFG->themedir.current_theme().'/footer.html');
//echo "</html>";
?>
