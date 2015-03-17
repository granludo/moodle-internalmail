<?PHP

require_once("../../../../config.php");
require_once("../../lib.php");

//require_variable($course);
//require_variable($option);
//require_variable($idc);

$course = required_param('course', PARAM_INT);   // course
$option = required_param('option', PARAM_INT);   // course
$idc = required_param('idc', PARAM_INT);   // course

global $CFG;

//echo "<html>";

print_header(get_string('block_Contacts','internalmail'));

echo	"	<script language=\"JavaScript\" >";


echo "function addContact(email) {";

if($option==1)
{
echo "field=window.opener.document.theform.destiny;";
echo "if(field.value==\"\"){";
echo " field.value= email;";
echo "}";
echo "else{";
echo "var bool=0;";
echo "var comprova=field.value.split(\",\");";
echo "var n=comprova.length;";
echo "var i=0;";

echo "while(i<=n && bool==0){";
echo "if(email==comprova[i]){";
echo "bool=1;";
echo "}";
echo "i++;";
echo "}";

echo "if(bool==0){";
echo "field.value= field.value + \",\" + email;";
echo "}";
echo "}";
}
else
{
	echo "window.opener.location.href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$idc&option=1\";";
	echo "location.href=\"courseusers.php?course=$course&option=1&idc=$idc\";";
}

echo " }";


echo "</SCRIPT>";



$curs=get_record("course","id",$course);					
echo "<center> <font color='#330066'><h2> <b>".$curs->teachers."</b></h2> </font> </center>";



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
  
  foreach ($teachers as $teacher) 
  {
  	$subj="CUR26::".$teacher->id."::".$course;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
		$havemailbox=get_record_sql("SELECT p.*
	                             FROM {$CFG->prefix}internalmail_posts p
	                             WHERE p.subject = '$subj'");
						
		if(!empty($havemailbox))
		{
			if ($teacher->editall or ismember($currentgroup, $teacher->id)) 
	  	{
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

//STUDENTS	                 	
	              	
	
$guest = get_guest();
$exceptions .= $guest->id;
if ($sort == "lastaccess")
{
  $dsort = "s.timeaccess";
} else 
{
  $dsort = "u.$sort";
}

$students = get_course_students($course);
$c=get_record("course","id",$course);
$totalcount=count_course_students($c, "", "", "", $currentgroup);
if ($firstinitial or $lastinitial) 
{
   $matchcount = count_course_students($c, "", $firstinitial, $lastinitial, $currentgroup);
} else 
{
   $matchcount = $totalcount;
}
		    
		    
if(empty($students))
{
								
	$students=array();
								
}
$posvar = 0;


foreach ($students as $student) 
{
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
						
	if(!empty($havemailbox))
	{
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


include ($CFG->themedir.current_theme().'/footer.html');
//echo "</html>";
?>
