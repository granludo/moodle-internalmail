<?php
  //this file shows a course contacts list.

  //params:
  // cid: the specific course
  // uid: the user contacts
  // mid: the user mesage contacts
  // stext: search text

  //si no se li passa res, mostrarà tots els contactes accessibles pel curs
  //per la resta és una mòdul normal de Moodle

require_once("../../../config.php");
require_once("../lib.php");

//optional_variable($id);    // Course Module ID, or
//optional_variable($a);     // internalmail ID
////optional_variable($reply); //when we reply a message

$id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);     // internalmail ID
$reply  = optional_param('reply', 0, PARAM_INT); //when we reply a message

if ($id) {
    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $internalmail = get_record("internalmail", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

} else {
    if (! $internalmail = get_record("internalmail", "id", $a)) {
        error("Course module is incorrect");
    }
    if (! $course = get_record("course", "id", $internalmail->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("internalmail", $internalmail->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}
// Course context
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);

//print("a");
require_login($course->id);
//print("a");

//------------------------- INTERFACE

/// Print the page header

if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strnames = get_string("modulenameplural", "internalmail");
$strname  = get_string("modulename", "internalmail");

/*print_header('', "$course->fullname",
 $strname.'->'.get_string ('block_Contacts','internalmail')."</A>", 
 "", "", true, '&nbsp;', 
 '');*/

print_header (get_string('contacts','internalmail'));

/*print_header ($title='', $heading='', $navigation='', $focus='', $meta='',
 $cache=true, $button='&nbsp;', $menu='', $usexml=false, $bodytags='')*/

//----printem els contactes especials
/*echo '<div onclick="switchMenu(\'specials\')">' .
 '<img id="specials_icon" src="'.$CFG->wwwroot.'/mod/internalmail/images/minus.gif"/>'
 .get_string('specialcontacts','internalmail').'</div>';

 echo "\n<div id=\"specials\" style=\"display:none;\">\n <ul>";
 if (has_capability('moodle/legacy:admin', $sitecontext, $USER->id, false)) {
 echo "\n  ". '<li><a href="#" onclick="window.opener.setContact(\'[allsite]\')">'.get_string('allsite','internalmail').'</a></li>';
 }
 if (isteacher($course->id)) {
 echo "\n  ".'<li><a href="#" onclick="window.opener.setContact(\'[allcourse]\')">'.get_string('allcourse','internalmail').'</a></li>';
 echo "\n  ".'<li><a href="#" onclick="window.opener.setContact(\'[allstudents]\')">'.get_string('allstudents','internalmail').'</a></li>';
 }
 echo "\n  ".'<li><a href="#" onclick="window.opener.setContact(\'[allteachers]\')">'.get_string('allteachers','internalmail').'</a></li>';
 echo "\n </ul>\n</div>";
*/

//-----només l'alfabet
//if (has_capability('moodle/legacy:admin', $sitecontext, $USER->id, false)) {
echo '<div onclick="switchMenu(\'abcd\')">' .
'<img id="abcd_icon" src="'.$CFG->wwwroot.'/mod/internalmail/images/minus.gif"/>'
.get_string('alphabetical','internalmail').'</div>';
 
echo '<div id="abcd" style="display:none;"><ul>';
//array amb l'abecedari
$alpha  = explode(',', get_string('alphabet'));
 
//alfabet per nom
echo '<li>'.get_string('firstname').': ';
$nch = 0;
$courseid = $course->id;
foreach ($alpha as $ch) {
    if ($nch != 0) {
	echo ', ';
    }
    echo "<a href=\"javascript:reloadiframe('&eid=search_res&pop=si&name=$ch')\">$ch</a>";
    $nch++;
}
echo '</li>';
//alfabet per cognom
echo '<li>'.get_string('lastname').': ';
$nch = 0;
foreach ($alpha as $ch) {
    if ($nch!=0) {
	echo ', ';
    }
    echo "<a href=\"javascript:reloadiframe('&eid=search_res&pop=si&srname=$ch')\">$ch</a>";
    $nch++;
}
//}

//només els del curs
echo "<li><a href=\"javascript:reloadiframe('&eid=search_res&pop=si&cid={$course->id}')\">".get_string('justcourse','internalmail')."</a></li>";
echo '</li></ul></div>';

//---posem el formulari de cerca
if (has_capability('moodle/legacy:admin', $sitecontext, $USER->id, false)) {
    echo '<div onclick="switchMenu(\'srch\')">' .
	'<img id="srch_icon" src="'.$CFG->wwwroot.'/mod/internalmail/images/minus.gif"/>'
	.get_string('searchcontact','internalmail').'</div>';
    
    echo '<div id="srch" style="display:none;"><ul>';
    echo '<form method="post" action="search.php?id='.$cm->id.'&pop=si" target="bssearch">' .
	'<input id="sfield" type="text" name="search" value="" />' .
	'<input type="submit" name="doit" value="'.get_string('search').'"/>' .
	'</form>';
    echo '</ul></div>';
}


//--------- l'frame
echo '<hr />';
echo '<div id="search_res"></div>' .
'<iframe id="idsearch" name="bssearch" src="search.php?id='.$cm->id.'&cid='.$course->id.'&pop=si" style="display:none;"></iframe>' . "\n\n";


// '<iframe id="idsearch" name="isearch" src="search.php?id='.$cm->id.'&cid='.$course->id.'" style="display:none;"></iframe>';

?>
<script type="text/javascript" language="JavaScript" >
 <!-- // Non-Static Javascript functions

 //funció per posar el resultat
function changeme (cid,txt) {
    document.getElementById(cid).innerHTML = txt;
}

//recarrega la cosa amb els paràmetres
function reloadiframe (params) {
    var url = "search.php?id=<?php echo $cm->id;?>"+params;
    document.getElementById("idsearch").src = url;
    // document.write( "Somthing" + url);
    //document.getElementById("search_res").innerHTML = url;
}
	
//mostra/oculta un div
function switchMenu(obj) {
    var el = document.getElementById(obj);
    if ( el.style.display != 'none' ) {
	el.style.display = 'none';
    } else {
	el.style.display = '';
    }
    //això és extra
    var im = document.getElementById(obj+"_icon");
    var srcb = "<?php echo $CFG->wwwroot.'/mod/internalmail/images/'; ?>";
    if ( im.src == srcb+"plus.gif" ) {
	im.src = srcb+"minus.gif";
    }  else {
	im.src = srcb+"plus.gif";
    }
}

// done hiding -->	
</script>
<?php

  /// Finish the page
  //print_footer();
include ($CFG->footer);
//include ($CFG->themedir.current_theme().'/footer.html');
//echo '</body></html>';

?>