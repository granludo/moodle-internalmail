<?PHP  // $Id: view.php,v 1.1 2003/09/30 02:45:19 moodler Exp $

  // This page prints a particular instance of internalmail
  // (Replace internalmail with the name of your module)

require_once("../../config.php");
require_once("lib.php");
//module libraries
require_once("format/formatlib.php");
require_once($CFG->dirroot.'/mod/internalmail/blocks/blocklib.php');

$id     = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$a      = optional_param('a', 0, PARAM_INT);     // internalmail ID
$reply  = optional_param('reply', 0, PARAM_INT); //when we reply a message

$option = optional_param('option', 0, PARAM_INT); // Option selected
$post   = optional_param('post', 0, PARAM_INT);    // e-mail to show
$page   = optional_param('page', 0, PARAM_INT);

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


$id = $cm->id;
$a = $internalmail->id;

require_login($course->id);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$canviewmail = has_capability('mod/internalmail:viewmail', $context);
if (!$canviewmail) {
    error(get_string('noviewpermission', 'internalmail'), "$CFG->wwwroot/course/view.php?id=$course->id");
}

global $im_error;
unset($im_error);

if ( $postfrm = data_submitted() ) {
    require_once("lib_post.php");
    require_once($CFG->dirroot.'/message/lib.php');

    $addfile = optional_param('attachfile', '', PARAM_CLEAN);
    $cancel = optional_param('cancel', NULL, PARAM_CLEAN);

    if ( $cancel ) {
	internalmail_attachment_delete_temp_dir();
	redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id", get_string('cancel','internalmail'), 1);
    }

    $destiny = optional_param('destiny', NULL, PARAM_CLEAN);
    $subject = optional_param('subject', NULL, PARAM_CLEAN);
    $message = optional_param('message', NULL, PARAM_CLEAN);
    $deletefile = optional_param('deletefile', '', PARAM_CLEAN);

    $destiny = str_replace(' ', '', $destiny);
    $destinataries = array();
    if ( $destiny ) {
	// make sure some users are specified
	$destinataries = explode(',', $destiny);
    }

    if ( $addfile ) {
	internalmail_save_attached_files($course, $internalmail->maxbytes);
    } else if ( $deletefile ) {
	internalmail_attachment_delete_temp_file($postfrm->tmpfilename);
    } else if ( !$destiny || !$subject ) {
	if ( !$destiny ) {
	    // Check for destination people, subject, and message
	    $im_error .= "\n <li class=\"internalmail-compose-warn\">" . get_string('no_destination_error', 'internalmail') . "</li>";
	}
	if ( !$subject  ) {
	    // Check for the subject of the message
	    $im_error .= "\n <li class=\"internalmail-compose-warn\">" . get_string('no_subject_error', 'internalmail') . "</li>";
	}
    } else {
	// Send the mail now.
	global $USER;

	$error=0; //tot correcte
	$not_sent_array = array();

	$msg_curt = substr ($message, 0, 20);
	$admin = get_admin(); //get_record("user","username","admin");

	//guardem el missatge a la carpeta d'enviats

	$discuss = internalmail_get_user_discussion($USER->id);
	$ghost = $discuss->firstpost;
	// enviar a cursos
	// $cm = get_record("course_modules","id",$id);
	if ( $cm->course !== 1) { 
	    $folder_course = $ghost+4;
	    $post_courses = array();
	    $post_courses = internalmail_get_child_posts($folder_course);

	    if(empty($post_courses)) {
		$post_courses = array();
	    }

	    foreach($post_courses as $post_course) {
		$aux = internalmail_get_subject($post_course);
		if( $aux[2] == $cm->course) {
		    $ghost = $post_course->id;
		}
	    }
	}

	$sent = $ghost+2; //el parent del missatge
	$subject_sql = "RIO26::".$USER->id."::".$subject;

	//creem el missatge
	$miss->discussion = $discuss->id;
	$miss->parent     = $sent;
	$miss->oldparent  = 0;
	$miss->userid     = $USER->id;
	$miss->created    = $miss->modified = time();
	$miss->mailed     = 1;
	$miss->subject    = $subject_sql;
	$miss->message    = $message;
	$miss->format     = 1;
	$miss->attachment = "";
	$miss->totalscore = 1;

	$miss->course = $course->id;

	$internalmail_files = internalmail_add_attachment($course, $internalmail->maxbytes);

	if ( $internalmail_files && isset($internalmail_files->error) && $internalmail_files->error == 1 ) {
	    // Delete the files and move to error
	    internalmail_attachment_delete_temp_dir();
	    redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id", get_string('error desconegut','internalmail'), 10);
	}

	// Add any file attachments
	if ( $internalmail_files ) {
	    $miss->attachment = $internalmail_files;
	}

	//es guarda el missatge
	if (! $miss->id = insert_record("internalmail_posts", $miss))  {
	    $error = 1; //no s'ha pogut guardar a la bustia d'enviats
	}

	// insertem en l'historic l'event ghost	
	if ( $error == 1 ) {
	    internalmail_attachment_delete_temp_dir();
	    redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('error desconegut','internalmail'), 10);
	}
		
	$hist->mailid = $miss->id;
	$hist->time   = time();
	$hist->userid = $USER->id;
	$hist->event  = "ghost";
	$hist->id     = insert_record("internalmail_history",$hist);
	$ghost_hist   = $hist->id;
	//insertem en l'historic l'event enviat
	$hist->mailid = $miss->id;
	$hist->time   = time();
	$hist->event  = "sent";
	$hist->userid = $USER->id;
	$hist->parent = $ghost_hist;
	$hist->id     = insert_record("internalmail_history",$hist);

	// enviem el missatge als diferents destinataris
	// mirem is és algun cas especial (conevrsió de destinataris)

	// print_object($destinataries);
	foreach ($destinataries as $destinatari ) {
	    if ( empty($destinatari) ) {
		continue;
	    }
	    $destinatary = get_record("user","username","$destinatari");
	    if (!$destinatary) {
		$error = 2;
		$not_sent_array[] = $destinatari;
		continue;
	    }
	    $discuss = internalmail_get_user_discussion($destinatary->id);
	    if ( !$discuss ) {
		$error = 2;
		$not_sent_array[] = $destinatari;
		continue;
	    }

	    $ghost = $discuss->firstpost;
	
	    //enviar a cursos
	    if($cm->course !== 1) {
		$folder_course = $ghost+4;
		$post_courses  = array();
		$post_courses  = internalmail_get_child_posts($folder_course);
		if(empty($post_courses)) {
		    $post_courses = array();
		}
		foreach($post_courses as $post_course)  {
		    $aux=internalmail_get_subject($post_course);
		    if ($aux[2] == $cm->course) {
			$ghost = $post_course->id;
		    }
		}
	    }
	
	    $inbox = $ghost+1; //el parent del missatge
	    $subject_sql = "RIO26::".$USER->id."::".$subject;
	
	    //creem el missatge
	    $miss->discussion = $discuss->id;
	    $miss->parent     = $inbox;
	    $miss->oldparent  = 0;
	    $miss->userid     = $destinatary->id;
	    $miss->created    = $miss->modified= time();
	    $miss->mailed     = 0;
	    $miss->subject    = $subject_sql;
	    $miss->message    = $message;
	    $miss->format     = 1;
	    if ( $internalmail_files ) {
		$miss->attachment = $internalmail_files;
	    }

	    $miss->totalscore = 1;
	    $miss->course = $course->id;

	    if ( !$miss->id = insert_record("internalmail_posts", $miss)) { //echo $miss->userid."<br>";
		// no s'ha pogut enviar a determinat destinatari a la bustia de curs
		if ($cm->course !== 1) {
		    //echo'course!=1<br>';
		    $miss->parent = ($discuss->firstpost)+1;
		    $miss->course = 1;
		    if (! $miss->id = insert_record("internalmail_posts", $miss)) {
			//echo 'error<br>';
			$error = 2; //no s'ha pogut enviar a determinat destinatari a la bustia principal
			$not_sent_array[] = $destinatari;
		    }
		} 
	    } else {
		if ( $admin ) {
		    $sms_curt="<br><h5>You have new Internalmail messages:</h5><br>".$msg_curt."..."."<br><br><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$id\">Go to Inbox</a>";
		    message_post_message($admin, $destinatary, addslashes($sms_curt),1, 'direct');
		}
		//insertem en l'historic l'event rebut
		$hist->mailid = $miss->id;
		$hist->parent = $ghost_hist;
		$hist->time   = time();
		$hist->event  = "received";
		$hist->userid = $destinatary->id;
		$hist->id     = insert_record("internalmail_history",$hist);
	    }
	}

	// mirem el course copies....
	//if(!isteacher($cm->course, $USER->id))  {
	if ( !has_capability('mod/internalmail:activatemessagecopies', $context)) {

	    $teachers = get_records("internalmail_copiesenabled","courseid",$cm->course);
	    if (empty($teachers)) {
		$teachers= array();
	    }
	    foreach($teachers as $teacher) {

		$destinatary = get_record("user","id","$teacher->userid");
		if (!$destinatary) {
		    $error = 2;
		    $not_sent_array[] = $destinatari;
		    continue;
		}
		$discuss = internalmail_get_user_discussion($destinatary->id);
		if ( !$discuss ) {
		    $error = 2;
		    $not_sent_array[] = $destinatari;
		    continue;
		}
		$ghost = $discuss->firstpost;
		//enviar a cursos
		if ($cm->course !== 1) {
		    $folder_course = $ghost+4;
		    $post_courses = array();
		    $post_courses = internalmail_get_child_posts($folder_course);
		    if (empty($post_courses)) {
			$post_courses = array();
		    }
		    foreach($post_courses as $post_course) {
			$aux = internalmail_get_subject($post_course);
			if ( $aux[2] == $cm->course ){
			    $ghost = $post_course->id;
			}
		    }
		}
	
		$inbox = $ghost+4; //copies
		$subject_sql = "RIO26::".$USER->id."::".$subject;
	    
		//creem el missatge
		$miss->discussion = $discuss->id;
		$miss->parent     = $inbox;
		$miss->oldparent  = 0;
		$miss->userid     = $destinatary->id;
		$miss->created    = $miss->modified= time();
		$miss->mailed     = 0;
		$miss->subject    = $subject_sql;
		$miss->message    = $message;
		$miss->format     = 1;
		if ( $internalmail_files ) {
		    $miss->attachment = $internalmail_files;
		}
		$miss->totalscore = 1;

		$miss->course     = $course->id;
			
		if (! $miss->id = insert_record("internalmail_posts", $miss)) {
		    //$error=2; //no s'ha pogut enviar a determinat destinatari///////////////////////CONTROL ERRORRRRRRRRRRR
		    //$not_sent_array[]=$destinatary->username; 
		} else {
		    //insertem en l'historic l'event rebut
		    $hist->mailid = $miss->id;
		    $hist->parent = $ghost_hist;
		    $hist->time   = time();
		    $hist->event  = "copies";
		    $hist->userid = $destinatary->id;
		    $hist->id     = insert_record("internalmail_history",$hist);
		}	
	    }	
	}

	switch($error) {
	case 0:
	    redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('enviat correctament','internalmail'),2);
	    break;
	case 1:
	    redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('error desconegut','internalmail'),2);
	    break;
	case 2:
	    $error_message = notify(get_string('error destinataris','internalmail'), $style='notifyproblem', $align='center', $return=true);
	    $postinfo = '';
	    foreach ($not_sent_array as $post) {
		if ( $postinfo ) {
		    $postinfo .= ", ";
		}
		$postinfo .= $post;
	    }
	    if ( $postinfo ) {
		$error_message .= notify($postinfo, $style='notifyproblem', $align='center', $return=true);
	    }
	    redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id", $error_message, 10);
	    break;
	case 3:
	    redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('nopermission','internalmail'),2);
	    break;
	}
    }
}

$err=0;

add_to_log($course->id, "internalmail", "view", "view.php?id=$cm->id", "$internalmail->id");

//AQUESTA ESTRUCTURA ÉS LA QUE ES FA SERVIR PER FER SERVIR ELS BLOCKS
$PAGE = internalmail_page_create_object(INTERNALMAIL_PAGE_MOD_VIEW, $cm->instance, $course->id);

//AQUESTA FUNCIÓ ÉS IMPRESCINDIBLE PER ALS BLOCKS
$pageblocks = internalmail_blocks_setup($PAGE);

// Print the page header
$navigation = "";
if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strinternalmails = get_string("modulenameplural", "internalmail");
$strinternalmail  = get_string("modulename", "internalmail");

print_header("$course->shortname: $strinternalmail", "$course->fullname",
	     "$navigation <a href=index.php?id=$course->id>$strinternalmails</a> -> $internalmail->name", 
	     "", "", true, '' ,  navmenu($course, $cm));

// Print the main part of the page
echo '<div class="course-content">';  // course wrapper start
	
//require("$CFG->dirroot/mod/internalmail/format/format.php");  // Include the actual course format
//echo "STEP 2: $destiny, $subject, $message, $id, $reply, $cancel, $option";

// definim el tamany dels blocks
define('BLOCK_L_MIN_WIDTH', 100);
define('BLOCK_L_MAX_WIDTH', 210);
define('BLOCK_R_MIN_WIDTH', 100);
define('BLOCK_R_MAX_WIDTH', 210);

//per calcular les amplades preferibles, hem de mirar tots els blocks.
optional_variable($preferred_width_left,  internalmail_blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]));
optional_variable($preferred_width_right, internalmail_blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]));
//les variables preferred_width_left i preferred_width_right haurien
//d'estar entre BLOCK_x_MAX_WIDTH i BLOCK_x_MIN_WIDTH.
$preferred_width_left = min($preferred_width_left, BLOCK_L_MAX_WIDTH);
$preferred_width_left = max($preferred_width_left, BLOCK_L_MIN_WIDTH);
$preferred_width_right = min($preferred_width_right, BLOCK_R_MAX_WIDTH);
$preferred_width_right = max($preferred_width_right, BLOCK_R_MIN_WIDTH);


//echo "STEP 3: $destiny, $subject, $message, $id, $reply, $cancel, $option";	

//crec que mostrar un tòpic concret (NO TE PERQUÊ SER NECESSARI)
if (isteacher($course->id) and isset($marker) and confirm_sesskey()) {
    $course->marker = $marker;
    if (! set_field("course", "marker", $marker, "id", $course->id)) {
	error("Could not mark that topic for this course");
    }
}

//echo "STEP 4: $destiny, $subject, $message, $id, $reply, $cancel, $option";


//carregeum el textos
$streditsummary   = get_string('editsummary');
$stradd           = get_string('add');
$stractivities    = get_string('activities');
$strshowalltopics = get_string('showalltopics');
$strtopic         = get_string('topic');
$strgroups        = get_string('groups');
$strgroupmy       = get_string('groupmy');
//mirem si la pàgina s'està editant
$editing          = $PAGE->user_is_editing();
//carreguem els textos d'edició
if ($editing) {
    $strstudents = moodle_strtolower($course->students);
    $strtopichide = get_string('topichide', '', $strstudents);
    $strtopicshow = get_string('topicshow', '', $strstudents);
    $strmarkthistopic = get_string('markthistopic');
    $strmarkedthistopic = get_string('markedthistopic');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
}
	
//--------------------------------------------- AQUÍ COMENÇA LA INTERFICIE
//echo "STEP 5: $destiny, $subject, $message, $id, $reply, $cancel, $option";
/// Layout the whole page as three big columns.
echo '<table id="layout-table"><tr>';

/// The left column ...

//mirem si hi ha blocs per posar al costat esquerra
	
if (internalmail_blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
    echo '<td style="width: '.$preferred_width_left.'px;" id="left-column">';
    internalmail_blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
    echo '</td>';
}
//echo "STEP 6: $destiny, $subject, $message, $id, $reply, $cancel, $option";
/// Start main column
echo '<td id="middle-column">';

//titol del blocs central
//print_heading_block('internalmail', 'outline');
//print_heading_block($strinternalmail, 'outline');
print_heading_block($internalmail->name, 'outline');

//echo "STEP 7: $destiny, $subject, $message, $id, $reply, $cancel, $option";
//comencem la taula amb el contingut
echo '<table class="topics" width="100%"><tr><td>';

/// Print Section 0: EL NOSTRE MAIN

$section = 0;
//$thissection = $sections[$section];

//    echo "<h1>Option: $option, Reply: $reply</h1>";
//echo "STEP 8: $destiny, $subject, $message, $id, $reply, $cancel, $option";
//AQUÍ CRIDAREM LA FUNCIÓ PER MOSTRAR EL CONTINGUT DEL MÒDUL

//internalmail_print_content($id,$option,$post,$reply,$page, $context,$cm);

//incloïm la llibreria de javascript per als contactes
require_once($CFG->dirroot.'/mod/internalmail/contacts/dinamiclib.php');

if(empty($reply)){
    $reply=0;
}
$tabStrings = array ();
//Pestanyes sempre presents.
$tabStrings[] = get_string('compose','internalmail');
$tabStrings[] = get_string('inbox','internalmail');
$tabStrings[] = get_string('sent','internalmail');
$tabStrings[] = get_string('deleted','internalmail');
//Pestanyes condicionades
//Només per a profesors 
//TODO:està comentat el codi.  Encara s'ha de comprobar o ara es per tothom
$tabStrings[] = get_string('options','internalmail');
//Només per al curs general
if ($course->id == 1){
	$tabStrings[] = get_string('messages','internalmail');
}

$im = get_record("modules","name","internalmail");
$course_module = get_record_sql("SELECT cm.course
		              FROM {$CFG->prefix}course_modules cm
		             WHERE cm.id = '$id' AND cm.module='$im->id'");
		
//Només si el usuari té activat les copies de missatges.
$consult= get_record_sql("	SELECT c.*
                        	FROM {$CFG->prefix}internalmail_copiesenabled c
	               			WHERE c.userid = '$USER->id' AND c.courseid = '$course_module->course'");
if(!empty($consult)){
	$tabStrings[] = get_string('copies','internalmail');
}

//Pintem les pestanyes i seleccionem aquella de la que carregarem el contingut.
internalmail_print_tabs($id,$option,$tabStrings);

internalmail_make_post($id, $post, $reply);

///Finaltzar el document	
echo '</td></tr></table>';

echo '</td>';

/// The right column
if (internalmail_blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing) {
    echo '<td style="width: '.$preferred_width_right.'px;" id="right-column">';
    internalmail_blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
    echo '</td>';
}

echo '</tr></table>';


echo '</div>';  // content wrapper end

// Finish the page
print_footer($course);

?>
