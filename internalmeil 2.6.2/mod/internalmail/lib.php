<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $

  /// Library of functions and constants for module internalmail
  /// (replace internalmail with the name of your module and delete this line)

require_once("lib_post.php");

$internalmail_CONSTANT = 7;     /// for example
//----- GENERAL FUNCIOTNS ---------


function internalmail_add_instance($internalmail) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.
    global $CFG, $USER, $PAGE;

    $internalmail->timemodified = time();
    $internalmail->assessed = 0;
    $internalmail->assesstimestart  = 0;
    $internalmail->assesstimefinish = 0;
    $count=count_records("internalmail");

    $messages = get_record("block","name","messages");
    $message_instance = get_records("block_instance","blockid",$messages->id);
	
    if(empty($message_instance)){  //no tenim messages		
	//redirect("$CFG->wwwroot","Sorry, you must install block MESSAGES",2);	
		
	$message_instance = false;
	$messages = false;	
	//return 0;
    }

    $courseid = '';
    if ( $internalmail->course == SITEID ) {
	$courseid = $internalmail->course;
     	if ($count==0)	{
	    if (! $internalmail->id=insert_record("internalmail", $internalmail)) {
		error("Failed to enable internalmail ".$internalmail->timemodified);
	    }
	} else {
	    error("internalmail was already enabled");
	}
    } else {
	$courseid = $internalmail->course;
	if($count==0) 	{
	    error("internalmail has to be enabled in the whole site. Please contact your administrator.");
	} else {
			
	    $course->id=$internalmail->course;
	    if(count_records("internalmail","course",$course->id) > 0)  {
		error("internalmail is already enabled for this course.");
	    }
			
	    $internalmail->id=insert_record("internalmail",$internalmail);
	    if(count_records("internalmail","course",$course->id)==0)	{
		print $course->id."   ";
		print count_records("internalmail_courses","id",$course->id);
		error("Failed to enable internalmail for this course.");
	    }
	}
    }

    $user_withbox = array();
    if ($teachers = get_course_teachers($courseid)) {
	if(!empty($teachers))  {  
	    foreach ($teachers as $teacher)    {
		if (in_array($teacher->id, $user_withbox)) {
		    continue;
		} else {
		    array_push($user_withbox,$teacher->id);
		}
		internalmail_add_user_mailbox($teacher->id, $courseid);		    
	    }
	}
    }
         	
    if ( $students = get_course_students($courseid) ) {		
	if(!empty($students)) {
	    foreach ($students as $student)    {
		if (in_array($student->id, $user_withbox)) {
		    continue;
		} else {
		    array_push($user_withbox,$student->id);
		}
		internalmail_add_user_mailbox($student->id, $courseid);		    
	    }
	}
    }

    $internalmailblocks = array (
	'contacts' => array (
	    'position' => 'r',
	    'weight' => 0,
	    'visible' => 1
	    ),
	'courses_notify' => array (
	    'position' => 'l',
	    'weight' => 0,
	    'visible' => 1
	    ),
	'search' => array (
	    'position' => 'l',
	    'weight' => 1,
	    'visible' => 1
	    ),
	'search_contacts' => array (
	    'position' => 'r',
	    'weight' => 1,
	    'visible' => 1
	    )
	);

    foreach ($internalmailblocks as $blockname => $addblock ) {

	$cur_block = get_record("internalmail_block","name",$blockname );
	$block_instance->blockid  = $cur_block->id;
	$block_instance->pageid   = $internalmail->id;
	$block_instance->pagetype = "mod_view";
	$block_instance->position = $addblock['position'];
	$block_instance->weight   = $addblock['weight'];
	$block_instance->visible  = $addblock['visible'];
   		 		
	if (! $block_instance->id = insert_record("internalmail_block_instance", $block_instance) ) {
	    error("Failed to create block instance" . $blockname);
	    return 0;
	}
	unset($block_instance);
    }

    return $internalmail->id;
}


function internalmail_update_instance($internalmail) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.

    global $CFG;
		
    $internalmail->timemodified = time();
    if(isset($internalmail->instance)){
    	$internalmail->id = $internalmail->instance;
    }

    return update_record("internalmail", $internalmail);
}


function internalmail_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  
    global $CFG;

    if (! ($module=get_record("modules","name","internalmail"))) {
	return false;
    }
	
    if(!$internalmail=get_record("internalmail","id",$id)){
	return false;
    }

    $result = true;
    if ($internalmail->course==1) {

	$internalmails = get_records("course_modules","module",$module->id);                         
	   
	//borrem posts i attachs dels cursos		
	foreach($internalmails as $internalmaild)   {
	    if($internalmaild->course!=1){								
		$mailboxes = internalmail_get_mailboxes($internalmaild->course);				
		foreach ($mailboxes as $removemailb) {
		    if (! internalmail_remove_mailbox($removemailb->id,$internalmaild->course)) {
			error("Could not remove mailboxes");
		    }
		}
	    }
	}		
		
	$mailboxes = internalmail_get_mailboxes(1);
	//borrem posts i attachs dels curs principal
	foreach ($mailboxes as $removemailb) {		
	    if (! internalmail_remove_mailbox($removemailb->id,1)) {
	    	error("Could not remove mailboxes");
	    }
	} 		
		
	if ($modules = get_records("course_modules", "module", $module->id))  {
	    foreach ($modules as $mod) {
		if (! delete_course_module($mod->id)) {
		    $result=false;
		}
		/*if (! delete_mod_from_section($mod->id, "$mod->section")) 
		 {
		 $result=false;
		 }*/
	    }
	}
			
	if (! delete_records("internalmail")) {
	    $result = false;
	}
		
	if (! delete_records("internalmail_discussions")) {
	    $result = false;
	}
		
	if (! delete_records("internalmail_posts")) {
	    $result = false;
	}
		
	if (! delete_records("internalmail_history")) {
	    $result = false;
	}
		
	if (! delete_records("internalmail_aliases")) {
	    $result = false;
	}
		
	if (! delete_records("internalmail_copiesenabled")) {
	    $result = false;
	}

	if (! delete_records("internalmail_subscriptions")) {
	    $result = false;
	}
		
	if (! delete_records("internalmail_groups")) {
	    $result = false;
	}
	
	if (! delete_records("internalmail_copiesenabled")) {
	    $result = false;
	}	
		
	if (! delete_records("internalmail_block_instance")) {
	    $result = false;
	}
	
    } else {
	//print $internalmail->course;
		
	$mailboxes = internalmail_get_mailboxes($internalmail->course);
		
	$modules[] = get_records_sql("SELECT *
	                                FROM {$CFG->prefix}course_modules cm
	                                WHERE cm.id = '$module->id' AND cm.course='$internalmail->course'");
		
	foreach ($mailboxes as $removemailb) {
	    if (! internalmail_remove_mailbox($removemailb->id, $internalmail->course)) {
		error("Could not remove mailboxes");
	    }
	}
		
	foreach ($modules as $mod) {
			
	    $course_mod = get_record_sql("SELECT *
	                                    FROM {$CFG->prefix}course_modules cm
	                                   WHERE cm.module = '$mod->id' AND cm.course='$internalmail->course'");
	    
	    if (! delete_records("course_modules","id",$course_mod->id)) {
		$result = false;
	    }
			
	    /*if (! delete_mod_from_section($mod->id, "$mod->section")) {
	     $result=false;
	     }*/
	}
		
	if (! delete_records("internalmail","id",$id)){
	    $result=false;
	}
	if (! delete_records("internalmail_block_instance","pageid",$id)){
	    $result=false;
	}
	if(! delete_records("internalmail_copiesenabled","courseid",$internalmail->course)) {
	    $result=false;
	}
	//error ("Nothing");
    }
    return $result;       
}

function internalmail_user_outline($course, $user, $mod, $internalmail) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    return $return;
}

function internalmail_user_complete($course, $user, $mod, $internalmail) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

    return true;
}

function internalmail_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in internalmail activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function internalmail_cron () {

/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc... 

/*Recieved mail format
 modulename: sitename
 ·course name
 Subject | From	| Date
 Subject | From	| Date
 ...
*/

    global $CFG;
    global $USER;
    global $THEME;

//new 2.5, translate some harcoded strings
    $strsendingmails  = get_string("sendingmails", "internalmail");//before harcoded as "Enviant mails..."
    $inboxunknown  = get_string("inboxunknown", "internalmail");//before harcoded as "Could not find user's Inbox"
    $courseunknown  = get_string("courseunknown", "internalmail");//before harcoded as "Could not find course ID_COURSE"
    $userunknown  = get_string("userunknown", "internalmail");//before harcoded as "Could not find user ID_USER"
    $couldntemail  = get_string("couldntemail", "internalmail");//before harcoded as "Error: dialogue cron: Could not send out mail user $userto->id ($userto->email)\n"
    $strmails = get_string("modulenameplural", "internalmail");
    $strmail  = get_string("modulename", "internalmail");
    $posttext=get_string('postsubject','internalmail',$site->fullname).":\n";
    $borderstyle="style=\"border-width:1px;border-style:solid;border-color:#CCCCCC\"";
//end new 2.5

    $internalmail=get_record("internalmail","course",SITEID);

    $site=get_site();

    if($internalmail->assesstimestart=1 || $internalmail->assesstimestart<mktime(0,0,0,date("m")  ,date("d"),date("Y"))) {

	$lastsent=$internalmail->assesstimestart;
	if (!$lastsent)	{
	    $lastsent=0;
	}
	
	//update assesstimestart field with the current time
	$internalmail->assesstimestart=time();
	set_field("internalmail","assesstimestart",$internalmail->assesstimestart,"id",$internalmail->id);
	
	//check if there are subscribbed users
	if (($subscribed_users = get_records("internalmail_subscriptions")) || count_records("internalmail","id",SITEID,"assessed",1)) 	{
	
	    print("$strsendingmails");
		
	    //unset limit, sending mails can take a lot of time
	    @set_time_limit(0);

	    foreach ($subscribed_users as $user) { //send the mails foreach user
			
		if (!$userto = get_record("user", "id", $user->userid)) {
		    continue;
		}
		if (!$inboxes=get_records_select("internalmail_posts", "userid='$user->userid' AND subject='Inbox'")) {
		    print ($inboxunknown);
		    continue;
		}
		if ($userto->mailformat == 1) 	{
		    //print the 'modulename: sitename' link
		    $posthtml = "<p><font face=\"sans-serif\">";
		    $posthtml=$posthtml."<table width=\"100%\"><tr><td bgcolor=$THEME->cellheading2><img src=\"".$CFG->wwwroot."/mod/internalmail/icon.gif\" align=\"absmiddle\"> <a href=\"$CFG->wwwroot/course/view.php?id=".SITEID."\">".get_string('modulename','internalmail').": $site->fullname</a></td></tr><tr><td bgColor=$THEME->cellheading>";
		} else {
		    $posthtml = "";
		    $posttext  = SITEID."\n\n";
		}
			
		unset ($mails);
		$numberofmails=0;
			
		foreach($inboxes as $inbox)  { //check the unread mails of the inboxes of the user				
		    $USER->lang = $userto->lang;
		    $parent=get_record("internalmail_posts","id",$inbox->parent);
		    $course=SITEID;
		    if(substr(($parent->subject),0,5)=="CUR26") {
			$aux=internalmail_treure_dades($parent);
			$course=$aux[2];
		    }
		    if (! $course = get_record("course", "id", $course))  {
			print("$courseunknown $mail->course");
			continue;
		    }

		    //new 2.5: added mailed=0 to the query in order to send only the unread messages
		    if ($mails = get_records_select("internalmail_posts", "parent='$inbox->id' AND modified>'$lastsent' AND mailed='0' AND modified<'$internalmail->assesstimestart'", "$entry->mailid")) 	{
			if($userto->mailformat==1) {
			    //name of the course
			    $posthtml=$posthtml."<br /><ul><img src=\"".$CFG->wwwroot."/pix/i/course.gif\" align=\"absmiddle\"> <a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->fullname</a></ul><br />";
			    //mail list table					
			    $posthtml=$posthtml."<table align=\"center\" cellpadding=\"5\" cellspacing=\"0\" width=\"90%\" border=\"0\">";
			    //mail list header
			    $posthtml=$posthtml."<tr border=\"0\" bgColor=$THEME->cellheading2><td $borderstyle background=\"".$CFG->wwwroot."/theme/standard/gradient.jpg\" width=\"50%\"><strong>".get_string('subject','internalmail')."</strong></td><td $borderstyle background=\"".$CFG->wwwroot."/theme/standard/gradient.jpg\" width=\"30%\"><strong>".get_string('from','internalmail')."</strong></td><td $borderstyle background=\"".$CFG->wwwroot."/theme/standard/gradient.jpg\" width=\"20%\"><strong>".get_string('date','internalmail')."</strong></td></tr>";

			} else	{
			    $posttext=$course->fullname." :\n\n";
			}
			
			foreach ($mails as $mail)  {//send each internalmail						
			    //new 2.5
			    $iddelmodul = get_record("modules", "name", "internalmail");
			    $course_modules = get_record_select("course_modules", "module=$iddelmodul->id AND course=$mail->course ");
			    $numberofmails+=1; //count the mails on all inboxes
			    //end new 2.5
					
			    unset($mailinfo);
			    if (!$userfrom = get_record("user", "id", "$mail->userid"))  {
				print("$userunknown $mail->userid");
				continue;
			    }
			    $info=get_user_info_from_db('id',$mail->userid);
			    //user's picture
			    $picture = print_user_picture($info->id, $course->id, $info->picture, false, true, false);
			    //extract the subject
			    $subj=internalmail_treure_dades($mail);

			    if ($userto->mailformat == 1) {  //list of the subjects of the unread mails
				//new 2.5: the link now is like: view.php?id=COURSE_MODULE_ID&option=6&post=POST_ID (option 6 is view post)
				$posthtml=$posthtml."<tr bgColor=$THEME->body><td $borderstyle width=\"50%\"><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$course_modules->id
&option=6&post=$mail->id\">".$subj[2]."</a></td><td $borderstyle width=\"30%\">".$picture." ".fullname($userfrom)."</td><td $borderstyle width=\"20%\"><font size=\"-2\">".userdate($mail->modified)."</font></td></tr>";
			    } else  {
				$posthtml = "";
				$posttext  = $posttext.get_string('date','internalmail')." : ".userdate($mail->modified)."\n";
				$posttext  = $posttext.get_string('subject','internalmail')." : ".$subj[2]."\n";
				$posttext  = $posttext.get_string('from','internalmail')." : ".fullname($userfrom)."\n\n";
			    } 
			}
			if($userto->mailformat==1) {
			    $posthtml=$posthtml."</table>";
			} else 	{
			    $posttext  = $posttext."\n\n\n";
			}
		    }

		}
		if($userto->mailformat==1) {
		    $posthtml=$posthtml."<br /><br /></tr></table>";
		} else  {
		    $posttext  = $posttext."\n";
		}

		if($numberofmails) {		
		    //email the user		
		    //prepare the subject
		    $postsubject=get_string('postsubject','internalmail',$site->fullname)." ($numberofmails)";
		    //email to user
		    //print("To: $userto->email<br>Subject: $postsubject<br>Text: $posttext<br>HTML: $posthtml<br><br><br>");
		    if (!$mailresult =  email_to_user($userto, $site->shortname, $postsubject, $posttext, $posthtml, '', '', $CFG->forum_replytouser))	{
			print ("$couldntemail $userto->id ($userto->email)");
		    }
		}
	    }	
	}
    }

    // Also delete all file attachments that are more than 2 days old.
    $tempfiles_location = $CFG->dataroot . '/temp/internalmail/';

    if ( is_dir($tempfiles_location) ) {
	if ($im_dirs = get_directory_list($temp_location, '', false, true, false)) {
	    // Get current time
	    $curtime = time();

	    foreach ($im_dirs as $key => $dirname) {

		// Get the date on it.
		$dirparts = explode('-', $dirname);

		if ( count($dirparts) !== 2 ) {
		    continue;	    
		}

		$yy =  (int)substr($dirparts[1], 0, 4);
		$mm =  (int)substr($dirparts[1], 4, 2);
		$dd =  (int)substr($dirparts[1], 6, 2);
		$hh =  (int)substr($dirparts[1], 8, 2);
		$MM =  (int)substr($dirparts[1], 10, 2);
		$ss =  (int)substr($dirparts[1], 12, 2);
		$filetime = mktime($hh, $MM, $ss, $mm, $dd, $yy);

		$offset = $curtime - $filetime;

		$days = floor($offset/(60*60*24));
		if ( $days >= 2 ) {
		    // Delete the directory after two days
		    $directory = $temp_location . '/' . $dirname;
		    if  ($files = get_directory_list($directory)) {
			foreach ($files as $key => $file ) {
			    $tmpfilename = $directory . '/'. $file;
			    unlink($tmpfilename);
			}
		    }
		    rmdir($directory);
		}
	    }
	}
    }

    return true;
}

function internalmail_treure_dades($post) {
    //return the subject from the post
    $aux=preg_split('/::/',$post->subject,3);
    return $aux;
}


function internalmail_grades($internalmailid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

    return NULL;
}

function internalmail_get_participants($internalmailid) {
//Must return an array of user records (all data) who are participants
//for a given instance of internalmail. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    return false;
}

function internalmail_scale_used ($internalmailid,$scaleid) {
//This function returns if a scale is being used by one internalmail
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
   
    $return = false;

    //$rec = get_record("internalmail","id","$internalmailid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

// ------ PRIVATE FUNCTIONS ---------

//////////////////////////////////////////////////////////////////////////////////////
/// Any other internalmail functions go here.  Each of them must have a name that 
/// starts with internalmail_
function internalmail_print_content($id,$option,$post,$reply,$page, $context, $cm){
	
    global $CFG, $USER;

    //incloïm la llibreria de javascript per als contactes
    require_once($CFG->dirroot.'/mod/internalmail/contacts/dinamiclib.php');
	
    $internalmail=get_record("modules","name","internalmail");
	
    $course=get_record_sql("SELECT cm.course
		              FROM {$CFG->prefix}course_modules cm
		             WHERE cm.id = '$id' AND cm.module='$internalmail->id'");
		
    //this function is the responsable of printing the content of the module.
	
    //tabs style
    /*echo "<TABLE class=\"headermenu\" WIDTH=\"100%\">";
     echo "<td width=\"10%\">";
     echo "</td>";
     echo "<td width=\"30%\">";*/

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
	if ($course->course == 1){
		$tabStrings[] = get_string('messages','internalmail');
	}
	//Només si el usuari té activat les copies de missatges.
    $consult = get_record_sql("SELECT c.*
	                           FROM {$CFG->prefix}internalmail_copiesenabled c
		               		   WHERE c.userid = '$USER->id' AND c.courseid = '$course->course'");
	if(!empty($consult)){
		$tabStrings[] = get_string('copies','internalmail');
	}
	
	//Pintem les pestanyes i seleccionem aquella de la que carregarem el contingut.
	internalmail_print_tabs($id,$option,$tabStrings);

    if(empty($reply)){
	$reply=0;
    }

    //MENU PRINCIPAL
    switch($option) {
    case 1: //redacta (compose)
	internalmail_make_post($id,$post,$reply);
	break;
    case 2: //entrants (inbox)
	internalmail_print_folder_header($id,$reply,2,$page); //header
	internalmail_print_folder($id,"inbox",$page,$reply);  //messages list
	break;
    case 3: //enviats (sent)
	internalmail_print_folder_header($id,$reply,3,$page);
	internalmail_print_folder($id,"sent",$page,$reply);
	break;
    case 4: //esborrats (deleted)
	internalmail_print_folder_header($id,$reply,4,$page);
	internalmail_print_folder($id,"deleted",$page,$reply);
	break;
    case 5: //opcions (options)
	internalmail_options($id,$page, $context, $cm);
	break;
    case 6: //post detail
	internalmail_print_post($post,$id);
	break;
    case 7: //missatges (messages summary)
	echo "\n<h4 class=\"internalmail-unreadmsg-title\">Unread messages</h4>";
	//internalmail_print_message_header($id);
	internalmail_print_messages($id,"unread",$page);
	echo "\n<h4 class=\"internalmail-readmsg-title\">Read messages</h4>";
	//internalmail_print_message_header($id);
	internalmail_print_messages($id,"read",$page);
	break;
    case 8: //¿?
	internalmail_print_message($post,$option);
	break;
    case 9: //¿?
	internalmail_print_message($post,$option);
	break;
    case 10: //¿?
	internalmail_print_folder_header($id,$reply,10,$page);
	internalmail_print_search($id,$search,$page,$reply);
	break;
    case 11: //històric (history)
	internalmail_print_history($id,$post);
	break;
    case 12: // copies
	internalmail_print_folder_header($id,$reply,12,$page);
	internalmail_print_folder($id,"copies",$page,$reply);          
	break;
    case 13: //¿?
	break;
    case 14:
	//used to execute internalmail cron manually
	internalmail_cron();
	break;
    default: //if there is no option defined, go to inbox by default
	internalmail_print_folder_header($id,$reply,2,$page);
	internalmail_print_folder($id,"inbox",$reply);          
	break;
    }
}



//FUNCIONS PROPIES

function internalmail_options($id, $page=0, $context, $course_modules) {
    /*
     Allows the user to specify the options:
     -admin accounts
     -send a copy of internalmails to the teachers
     -specify if the user is subscribbed or not to he internalmail	
    */

    global $USER;
    //$course_modules=get_record("course_modules","id",$id);
	
    if ($page==2) {
	if ($frm=data_submitted()) {
			
	    if(count_records("internalmail_copiesenabled","userid",$USER->id,"courseid",$course_modules->course)) {
		
		if($frm->Operation == "YES") {
		    //no fer res
		
		} else {	
		    if(!delete_records("internalmail_copiesenabled","userid",$USER->id,"courseid",$course_modules->course)) {
			echo "error";
		    }
		}
	    } else {
		if($frm->Operation == "NOT")  {
		    //no fer res
		} else 	{
		    $copiese->userid=$USER->id;
		    //error('userid = ' . $USER->id . ' ' . $copiese->userid);
		    $copiese->courseid=$course_modules->course;
		    if(!insert_record("internalmail_copiesenabled",$copiese)) {
			echo "error";
		    }
		}
	    }
	}
	redirect("view.php?id=$id&option=5&page=0");
    }
    echo "\n<table border=\"0\" width=\"70%\" class=\"internalmail-center-table\">";
	
    if($page==0) {
	//if($USER->id==2)//s'ha de modificar per consultar qui es el administrador
	//if(isteacher($course_modules->course,$USER->id)) {
	if ( has_capability('mod/internalmail:createaccount', $context)) {

	    echo "\n <tr>\n  <td>";
	    echo "<a href=\"view.php?id=$id&amp;option=5&amp;page=1\">".get_string("administrar","internalmail")."</a>";
	    helpbutton('admin', get_string('to'),'internalmail');
	    echo "</td>\n </tr>";
	}
		
	if ($course_modules->course!=1)	{
	    //if(isteacher($course_modules->course,$USER->id)) {
	    if ( has_capability('mod/internalmail:activatemessagecopies', $context)) {
		echo "\n <tr>\n  <td><br /><br />";
		echo "\n   <form method=\"post\" action=\"view.php?id=$id&option=5&page=2\">";

		echo  get_string('copies_missatges?','internalmail');
		echo "\n    <select name=\"Operation\">";
		echo "\n     <option value=\"NOT\">".get_string('no') . "</option>";
		if(count_records("internalmail_copiesenabled","userid",$USER->id,"courseid",$course_modules->course)) {
		    echo "\n     <option value=\"YES\" selected>".get_string('yes') . "</option";
		} else	{
		    echo "\n     <option value=\"YES\">".get_string('yes')."</option>";
		}
		echo "\n    </select>\n    ";
		helpbutton('copies', get_string('to'),'internalmail');
		echo "<br />";
		echo "\n    <input type=\"submit\" name=\"do\" value=\"" . get_string("savechanges"). "\">\n   ";
		echo "</td>\n </tr>";
	    }
	    //echo "\n <tr>\n  <td>";

	    echo "\n  </form>";
	    echo "\n </td>\n </tr>";
	}
    }
	
    echo "\n </table>";

    //New in IM 2.5
    if ( $page==0) {
	if ( has_capability('mod/internalmail:allowsummaryemail', $context)) {
	    internalmail_subscription_options();
	}
    }
    //End new in IM 2.5
	
    if ($page==1) {
	//if($USER->id==2)
	if ( has_capability('mod/internalmail:createaccount', $context)) {
	    //if(isteacher($course_modules->course,$USER->id)) {
	    internalmail_admin_accounts($id);
	}
    }
}

function internalmail_subscription_options() {
    //New in Internalmail 2.5. Allows the user to specify if the mail with the summary
    //of unread internalmails is sent with the cron task or not

    global $USER;
    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID
	
    //---add or delete subscriptions
	
    //check if the user was subscribbed
    $subscribbedyet = count_records("internalmail_subscriptions","userid",$USER->id);
	
    //check if there is data subbmitted in the form
    $display_message = "";
    if ($frm=data_submitted()) {
	if ($frm->subscribed==1 && !$subscribbedyet) {
	    //the user wants to recieve the e-mail
	    $record->userid=$USER->id;
	    insert_record("internalmail_subscriptions",$record);
	    //inform the user
	   $display_message = get_string('subscriptionadded','internalmail');
	} else if ($frm->subscribed==0 && $subscribbedyet) {
		
	    //the user doesn't want to recieve the e-mail
	    delete_records("internalmail_subscriptions","userid",$USER->id);
	    //inform the user
	    $display_message = get_string('subscriptiondeleted','internalmail');
	}  else if ($subscribbedyet){
	    //The user was subscribbed, inform the user
	    $display_message = get_string('subscriptionyes','internalmail');
	}  else if (!$subscribbedyet) {
	    //The user was not subscribbed, inform the user
	    $display_message = get_string('subscriptionno','internalmail');
	}
    }
	
    //---display the form
	
    //options title
    //echo  "<strong>".get_string('options','internalmail')."</strong><br /><br />";
	
    //form header
    echo "\n <table border=\"0\" width=\"70%\" class=\"internalmail-center-table\">";
    echo "\n <tr>\n  <td><br /><br />";
    if ( $display_message ) {
	echo "\n   <span class=\"internalmail-subscribe-display\">" . $display_message . "</span>";
    }
    echo "<form method=\"post\" action=\"view.php?id=$id&option=5\" name=\"form\">";
	
    //subscription question
    echo get_string('wantsummaryemail','internalmail')." ";
	
    //yes / no dropdown list
    unset($options);

    $options[0] = get_string("no");
    $options[1] = get_string("yes");
	
    //check if the user is subscribbed or not, in order to display the default option
    $user_subscription = count_records("internalmail_subscriptions","userid",$USER->id);
    $option = "0";
    if ($user_subscription) {
	$option = "1";
    }
	
    //print the dropdown list
    choose_from_menu ($options, "subscribed", $option, "", "", "");

    //echo "<br />";
    echo "   <br />";
	
    //submit button	
    echo "<input type=\"submit\" value=\"".get_string("savechanges")."\" />";
    echo "</form>";
    echo "\n </td>\n </tr>";
    echo "\n </table>";
}


function internalmail_print_history($id,$post) {
    global $CFG;
	
    $mail_hist = get_record("internalmail_history","mailid",$post);
    $consulta = "SELECT h.* FROM {$CFG->prefix}internalmail_history h WHERE h.parent = '$mail_hist->parent' AND h.event <> 'copies' ORDER BY id ASC";
    $consulta2 = "SELECT h.* FROM {$CFG->prefix}internalmail_history h WHERE h.parent = '$mail_hist->id' AND h.event <> 'copies' ORDER BY id ASC";
    $hist = get_records_sql($consulta);
    if (!$hist) {
	$hist=get_records_sql($consulta2);
    }
    echo  "\n".'<table border="0" width="70%" class="internalmail-center-table">';

    if(empty($hist)){
	$hist=array();
    }
	
    foreach ($hist as $hist_event)  {
	$hist_user=get_record("user","id",$hist_event->userid);
	echo "\n <tr>\n  <td>";
	echo fullname($hist_user);
	echo "\n </tr>\n <tr>\n  <td>";
	echo userdate($hist_event->time);
	echo "</td>\n  <td width=\"20%\">";
	switch ($hist_event->event ) {
	case sent:
	    echo get_string("sent","internalmail");
	    break;
	case read:
	    echo get_string("read","internalmail");
	    break;
	case deleted:
	    echo get_string("deleted","internalmail");
	    break;
	case received:
	    echo get_string("received","internalmail");
	    break;
	    
	}
	echo "</td>\n </tr>";
    }
    echo "\n</table>\n\n";
}

function internalmail_search($posts,$search) {
    $result=array();
	
    foreach($posts as $post) {
	$match=false;
	$aux=internalmail_get_subject($post);
	$subject = split(" ",$aux[2]);
	foreach($subject as $subject_part) {
	    if(strcasecmp($subject_part,$search)==0) {
		$match=true;
	    }
	}
	if(!$match) {
	    $sender=get_record("user","id",$aux[1]);
	    $fsender=fullname($sender);
	    $full_sender=split(" ",$fsender);
	    foreach($full_sender as $full_sender_part) {
		if(strcasecmp($full_sender_part,$search)==0)  {
		    $match=true;
		}
	    }
	}
	if(!$match) {
	    $message=split(" ",$post->message);
	    foreach($message as $msge)	{
		if(strcasecmp($msge,$search)==0) {
		    $match=true;
		}
	    }
	}
	if($match) {
	    $result[]=$post;
	}	
    }
	
    return $result;
}


function internalmail_print_search($id,$page=0,$reply) {
    global $USER, $THEME;

    $mode = optional_param('mode', '', PARAM_CLEAN); // Option selected

    if(!$frm=data_submitted())	{

    } else {
	$discussion=internalmail_get_user_discussion($USER->id);
	$ghost=$discussion->firstpost;
	
	$cm=get_record("course_modules","id",$id);
	$course=get_record("course","id",$cm->course); //per lo que ho utilitzo ara amb la consulta de sobre n'hi ha prou
	
	if($cm->course!==1) {
		
	    $folder_course=$ghost+4;
	    $post_courses=array();
	    $post_courses=internalmail_get_child_posts($folder_course,$reply);
	    if(empty($post_courses)){
		$post_courses=array();
	    }
	    foreach($post_courses as $post_course) {
		$aux=internalmail_get_subject($post_course);
		if($aux[2]==$cm->course) {
		    $ghost=$post_course->id;
		}
	    }
	}
	
	$folder=$ghost+1;
	$posts_inbox = internalmail_get_child_posts($folder,$reply);
	
	$folder=$ghost+2; 
	$posts_sent = internalmail_get_child_posts($folder,$reply);
	
	$folder=$ghost+3; 
	$posts_deleted = internalmail_get_child_posts($folder,$reply);
	
	if(!empty($posts_inbox)) {
	    $posts=$posts_inbox;
	} else	{
	    $posts=array();
	}

	if(!empty($posts_sent))	{
	    $posts=array_merge($posts,$posts_sent);
	}
	if(!empty($posts_deleted)) {
	    $posts=array_merge($posts,$posts_deleted);
	}
	
	if(!$search=internalmail_search($posts,$frm->inform["field"])) {
	    $search=array();
	}
		
	$n_x_page=10;
	$i=0;
	$j=0;
	$min=$page*$n_x_page;
	$max=$min+$n_x_page;
		
	//aqui d'aquests posts agafare aquells que m'interessin
	echo' <form method="post" action="remove.php">';
	
	foreach ($search as $post) {
		
	  if(($i>=$min) && ($i<$max))    {
			
	    //echo "\n <tr bgColor=\"#00FF00\">";
	    $classinfo = ($j % 2 == 0) ? 'r0' : 'r1';
	    echo "\n <tr class=\"$classinfo\">";
	    echo "\n  <td width=\"7%\" class=\"cell c1\">";
	    if($post->mailed)	{
	      echo "<img src=images/mailopen.gif width=\"24\" height=\"24\" align=\"absmiddle\"><input type=checkbox value=".$post->id." name=ch[]>";
	    }	else {
	      echo "<img src=images/mailclose.gif width=\"24\" height=\"24\" align=\"absmiddle\"><input type=checkbox value=".$post->id." name=ch[]>";
	    }
	    echo "</td>";
	    $aux=internalmail_get_subject($post);
	    $subjbo=$aux[2];
			
	    echo "\n  <td class=\"cell c2\">";
	    if ($post->attachment!="") {
	      echo "<img src=images/clip.gif width=\"16\" height=\"16\" align=\"absmiddle\"><a name=\"$post->id\"></a><font size=\"-1\"><strong><a href=\"view.php?id=$id&option=6&post=$post->id\">$subjbo</a></strong> ";
	    } else  {
	      echo "<a name=\"$post->id\"></a><font size=\"-1\"><strong><a href=\"view.php?id=$id&option=6&post=$post->id\">$subjbo</a></strong> ";
	    }
	    echo "</font>";
	    echo "</td>";
	    echo "\n  <td width=\"15%\" class=\"cell c3\">";
	    $sender=get_record("user","id",$aux[1]);
	    echo fullname($sender);
	    echo "</td>";
	    echo "\n  <td width=\"17%\"><font size=\"-2\">";
	    echo userdate($post->modified);
	    echo "</font></td>";
	    echo "\n  <td width=\"9%\" align=\"center\" class=\"cell c4\">";
	    echo "<a name=\"$post->id\"></a><font size=\"-1\"><strong><a href=\"view.php?id=$id&option=11&post=$post->id\"><img src=images/history.gif width=\"24\" height=\"24\" align=\"absmiddle\"></a></strong> ";     
	    echo "</td>";
	    echo "\n  <td width=\"7%\" align=\"center\" class=\"cell c5\">";
	    echo print_user_picture($sender->id, $course->id, $post->picture);
	    echo "</td>";
	    echo "\n </tr>";
	    $j++;
	  }
	  $i++;
	}
	    
	echo "</table>";
	//////////////calcul de les pagines/////////////
	$pagelast=(($i / $n_x_page)-(($i % $n_x_page) / $n_x_page));
	if(($i % $n_x_page)==0 && $i != 0) {
	    $pagelast=$pagelast-1;
	}
	$pagelast=round($pagelast);
	$pagemes=$page+1;
	$pagemenos=$page-1;
	//////////////fi calcul de les pagines/////////////
	if (!isset($cellcontent2)) {
	    $cellcontent2 = "";
	}
	echo	"\n<table border=\"1\" width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"5\" bordercolor=\"".$cellcontent2."\" class=\"generalbox\" >";
	echo "\n <tr>";
	echo "\n  <td width=\"60%\">";
	echo get_string('mostrando','internalmail');
	if ($i != 0) {
	    echo ($n_x_page*$page)+1;
	} else {
	    echo($n_x_page*$page);
	}
	echo get_string('al','internalmail'); 
	echo ($n_x_page*$page)+$j;
	echo get_string('de','internalmail'); 
	echo $i;
	echo get_string('mensajes','internalmail');
	echo "</td>";
	echo "\n  <td>";
	if($page!=0) {
	    echo " <a href=\"view.php?id=$id&option=$option&page=0\">&lt;&lt;&lt;</a> ";
	    echo " <a href=\"view.php?id=$id&option=$option&page=$pagemenos\">;&lt;&lt;</a> ";
	}
	if($page!=$pagelast){
	    echo " <a href=\"view.php?id=$id&option=$option&page=$pagemes\">&gt;&gt;</a> ";
	    echo " <a href=\"view.php?id=$id&option=$option&page=$pagelast\">&gt;&gt;&gt;</a> ";
	}
	echo "</td>";
	echo "\n </tr>";
	echo "\n</table>\n";
	
	
	echo" <p>".get_string('wanadoo','internalmail')."";
	echo" <select name=\"Operation\">";
	echo"    <option value=\"NOT\">".get_string('nada','internalmail')."";
	echo"    <option value=\"REM\">".get_string('borrar','internalmail')."";
	echo"    <option value=\"RED\">".get_string('marcar leido','internalmail')."";
	echo"    <option value=\"NRE\">".get_string('marcar no leido','internalmail')."";
	echo"    <option value=\"RES\">".get_string('restaurar','internalmail')."";
	echo" </select>";
	echo"  <input type=\"submit\" name=\"do\" value=\"Go!!!\" />";
	echo " <input type=\"hidden\" name=id value=\"$id\" />";
	echo " <input type=\"hidden\" name=mode value=\"$mode\" />";
	echo"</p>";
	echo"</form>";
	return ($i>$max);
    }
}


function internalmail_print_message_header($id) {

    if (!isset($cellcontent2)) {
	$cellcontent2 = "";
    }
    echo "\n<table border=\"1\" width=\"94%\" align=\"center\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"".$cellcontent2."\" class=\"generalbox\">";
    echo "\n <tr>";
    echo "\n  <th width=\"6%\"align=\"center\" class=\"header c0\" >";
    echo "<strong>".get_string('check','internalmail')."</strong>";
    echo "</th>";
    echo "\n  <th width=\"20%\" class=\"header c1\" >";
    echo "<strong><a href=\"view.php?id=$id&option=2\">&raquo;".get_string('from','internalmail')."</a></strong>";
    echo "</th>";
    echo "\n  <th width=\"30%\" class=\"header c2\" >";
    echo "<strong><a href=\"view.php?id=$id&option=2\">&raquo;".get_string('date','internalmail')."</a></strong>";
    echo "</th>";
    //echo "<td width=\"7%\"align=\"center\">";
    //echo "<B>".get_string('picture','internalmail')."</B>";
    //echo "</td>";
    echo "\n </tr>";	
}


function internalmail_print_messages($id,$mode,$page=0) {
    global $USER;
    //$course->id=1; //ctr!
    if($mode=="unread") {
	$msages=get_records("message","useridto",$USER->id);
    } else if($mode=="read") {
	$msages=get_records("message_read","useridto",$USER->id);	
    }
    if(empty($msages))	{
	$msages=array();
    }

    $n_x_page=5;
    $i=0;
    $j=0;
    $min=$page*$n_x_page;
    $max=$min+$n_x_page;

    if($mode=="unread"){
	echo "\n". ' <form method="post" action="remove_miss.php">';
    }

    internalmail_print_message_header($id);

    $classinfo = "";
    foreach($msages as $msage) {
	if(($i>=$min) && ($i<$max)) {

	    $classinfo = ($j % 2 == 0) ? 'r0' : 'r1';
	    echo "\n <tr class=\"$classinfo\">";
	    echo "\n  <td width=\"6%\" align=\"center\" class=\"cell c1\">";
	    if($mode=="unread")	{
		echo "<width=\"24\" height=\"24\" align=\"absmiddle\"><input type=\"checkbox\" value=".$msage->id.":1"." name=ch[]>";
	    } else {
		echo "<width=\"24\" height=\"24\" align=\"absmiddle\"><input type=\"checkbox\" value=".$msage->id.":2"." name=ch[]>";
	    }	
	    echo "</td>";
	    echo "\n  <td width=\"20%\" class=\"cell c2\">";
	    $from=get_record("user","id",$msage->useridfrom);
	    if($mode=="unread") {
		echo "<a href=\"view.php?id=$id&option=8&post=$msage->id\">".fullname($from)."</a>";
	    }
	    if($mode=="read") {
		echo "<a href=\"view.php?id=$id&option=9&post=$msage->id\">".fullname($from)."</a>";
	    }
	    echo "</td>";
	    echo "\n  <td width=\"30%\" class=\"cell c3\">";
	    echo userdate($msage->timecreated);
	    echo "</td>";
	    //echo "<TD WIDTH=\"7%\"align=\"center\">";
	    //echo print_user_picture($post->userid, $course->id, $post->picture);
	    //echo "</TD>";
	    echo "\n </tr>";
	    $j++;
	}
	$i++;	
    }
    echo "\n</table>\n";	
    //////////////calcul de les pagines/////////////

    $pagelast=(($i / $n_x_page)-(($i % $n_x_page) / $n_x_page));
    if(($i % $n_x_page)==0 && $i != 0) 	{
	$pagelast=$pagelast-1;
    }
    $pagelast=round($pagelast);
    $pagemes=$page+1;
    $pagemenos=$page-1;
    //////////////fi calcul de les pagines/////////////
    if (!isset($cellcontent2)) {
	$cellcontent2 = "";
    }
    if($page <= $pagelast) {
	echo	"<table border=\"1\" width=\"94%\" align=\"center\" cellspacing=\"0\" cellpadding=\"3\" bordercolor=\"".$cellcontent2."\" class=\"generalbox\">";
	echo "\n <tr>";
	echo "\n  <td width=\"60%\">";
	echo get_string('mostrando','internalmail');
	if($i != 0) { 
	    echo ($n_x_page*$page)+1;
	} else { 
	    echo($n_x_page*$page);
	}
	echo get_string('al','internalmail');
	echo ($n_x_page*$page)+$j;
	echo get_string('de','internalmail'); 
	echo $i; echo get_string('mensajes','internalmail');
	echo "</td>";
	echo "\n  <td>";
	if($page!=0) {
	    echo "<a href=\"view.php?id=$id&option=7&page=0\"> &lt;&lt;&lt; </a> &nbsp;";
	    echo "<a href=\"view.php?id=$id&option=7&page=$pagemenos\"> &lt;&lt; </a> &nbsp;";
	}
	if($page!=$pagelast) {		
	    echo "<a href=\"view.php?id=$id&option=7&page=$pagemes\"> &gt;&gt; </a> &nbsp;";
	    echo "<a href=\"view.php?id=$id&option=7&page=$pagelast\"> &gt;&gt;&gt; </a>";
	}
	echo "</td>";
	echo "\n </tr>";
	echo "\n</table>\n";
	if ($mode=="read")	{	
	    echo "\n <p>".get_string('wanadoo','internalmail')."";
	    echo "\n <select name=\"Operation\">";
	    echo "\n    <option value=\"NOT\">".get_string('nada','internalmail')."";
	    echo "\n    <option value=\"REM\">".get_string('borrar','internalmail')."";
	    echo "\n </select>";
	    echo "\n <input type=\"submit\" name=\"do\" value=\"Go!!!\">";
	    echo "\n <input type=\"hidden\" name=\"id\" value=\"$id\">";
	    echo "\n</p>";
	    echo "\n</form>\n";
	}
    }
}


function internalmail_print_message($post,$option)  {
	
    global $CFG;
    $course->id=1;//ctr
    if($option==8) {
	$msage=get_record("message","id",$post);
    }  else if($option==9) {
	$msage=get_record("message_read","id",$post);
    }
    $sender=get_record("user","id",$msage->useridfrom);

    echo "<table border=\"0\" width=\"100%\" cellpadding=\"5\"	>";
    echo "<tr>";
    echo "<td width=\"7%\"align=\"left\">";
    echo print_user_picture($sender->id, $course->id, $sender->picture);
    echo "</td>";
    echo "<td>";
    echo "<h4>".fullname($sender).":</h4>";
    echo "</td>";
    echo "<tr>";
    echo "<td>";
    echo "</td>";
    echo "<td>";
    print_r($msage->message);
    echo "</td>";
    echo "</tr>";
    echo "</table>";

    //marcar com a mailed
    if($option==8) {
	delete_records("message","id",$msage->id);
	insert_record("message_read",$msage);
    }
}


function internalmail_admin_accounts($id) {
    
    $cm = get_record("course_modules","id",$id);

    $courad=$cm->course;
    //TRACTAR EL FORMULARI
    if(!$frm=data_submitted()) {

    } else {
	if (!empty($frm->add) and !empty($frm->addselect)) {
	    foreach ($frm->addselect as $addmailb) {
		
		if (! internalmail_add_user_mailbox($addmailb,$courad)) {
		    error("Could not add mailbox for user id $addmailb!");
		}
	    }
	} else if (!empty($frm->remove) and !empty($frm->removeselect)) {
	    $mailboxes = internalmail_get_mailboxes($courad);
	    if (count($mailboxes) >= count($frm->removeselect)) {
		foreach ($frm->removeselect as $removemailb) {
		    if($USER->id<>$removemailb)	{
			if (! internalmail_remove_mailbox($removemailb,$courad)) {
			    error("Could not remove admin with user id $removeadmin!");
			}
		    } else {
			$errorusr=get_string('errorusr','internalmail',fullname($USER));
		    }
		}
	    }
	} else if (empty($frm->removeselect) or empty($frm->addselect)) {
	    //fer algo, el que no es pot fer es ficar error pq surt del modul, i no te cap sentit
	} 
    }

    //EMPLENAR FORMULARI

    $admins = internalmail_get_mailboxes($courad);//hay que pasarle el curso
    if($admins) { //eliminem de la llista de users els que tenen bustia
	$auxarray = array();
	foreach ($admins as $admin) {
	    $auxarray[] = $admin->id;
	}
	$adminlist = implode(',', $auxarray);
	unset($auxarray);
    }
    $users = get_users(true, '', true, $adminlist, 'firstname ASC, lastname ASC', '', '',0, 99999, 'id, firstname, lastname, email');
    $usercount=count($users);

    //FORMULARI	
    echo "\n<form name=\"adminform\" id=\"adminform\" method=\"post\" action=\"view.php?id=$id&option=5&page=1\">";//aqui que hago??
    //<input type="hidden" name="previoussearch" value="$previoussearch">
    echo "\n<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">";
    echo "\n <tr>";
    echo "\n  <td valign=\"top\">";
     
    if(!$admins) {
	echo "0 ". get_string("str_users_account","internalmail");
    } else {
	echo count($admins) . " ". get_string("str_users_account","internalmail");
    }
     
    echo "</td>";
    echo "\n  <td>&nbsp;</td>";
    echo "\n  <td valign=\"top\">";
    echo $usercount . " " . get_string("str_users_noaccount","internalmail");
    echo "</td>";
    echo "\n </tr>";
    echo "\n <tr>";
    echo "\n  <td valign=\"top\">";
    echo "\n   <select name=\"removeselect[]\" size=\"20\" id=\"removeselect\" multiple";
    echo " onFocus=\"document.adminform.add.disabled=true;";
    echo " document.adminform.remove.disabled=false;";
    echo " document.adminform.addselect.selectedIndex=-1;\">";

    //PHP          
    if (!$admins) {
	$disabled = 'disabled';
	$removebuttontype = 'hidden';
    } else {
	$disabled = '';
	$removebuttontype = 'submit';
    }

    foreach ($admins as $admin) {
	$fullname = fullname($admin, true);
	echo "\n    <option value=\"$admin->id\" $disabled>".$fullname.", ".$admin->email."</option>";
    }

    //</PHP>          
          
    echo "\n   </select></td>";
    echo "\n  <td valign=\"top\">";
    echo "<br />";
    echo "\n   <input name=\"add\" type=\"submit\" id=\"add\" value=\"&larr;\" />";
    echo "<br />";
    echo "\n   <input name=\"remove\" type=\"$removebuttontype\" id=\"remove\" value=\"&rarr;\" />";
    echo "<br />";
    echo "</td>";
    echo "\n  <td valign=\"top\">";
    echo "\n   <select name=\"addselect[]\" size=\"20\" id=\"addselect\" multiple";
    echo " onFocus=\"document.adminform.add.disabled=false;";
    echo " document.adminform.remove.disabled=true;";
    echo " document.adminform.removeselect.selectedIndex=-1;\">";

    //<PHP>
    if (!empty($searchusers)) {
	echo "\n     <optgroup label=\"$strsearchresults (" . count($searchusers) . ")\">";
	foreach ($searchusers as $user) {
	    $fullname = fullname($user, true);
	    echo "\n     <option value=\"$user->id\">".$fullname.", ".$user->email."</option>";
	}
	echo "\n    </optgroup>\n";
    }

    if (!empty($users)) {
	foreach ($users as $user) {
	    $fullname = fullname($user, true);
	    echo "\n    <option value=\"$user->id\">".$fullname.", ".$user->email."</option>";
	}
    }

    //</PHP>
          
    echo "\n   </select>";
    echo "<br />";
    echo "\n  <input type=\"text\" name=\"searchtext\" size=\"30\" value=\"$searchtext\" ";
    echo " onFocus =\"document.adminform.add.disabled=true;";
    echo " document.adminform.remove.disabled=true;";
    echo " document.adminform.removeselect.selectedIndex=-1;";
    echo " document.adminform.addselect.selectedIndex=-1;\"";
    echo " onkeydown = \"var keyCode = event.which ? event.which : event.keyCode;";
    echo " if (keyCode == 13) {";
    echo "  document.adminform.previoussearch.value=1;";
    echo "  document.adminform.submit();";
    echo "} \" />";

    echo "\n  <input name=\"search\" id=\"search\" type=\"submit\" value=\"";p(get_string('find','internalmail'));echo" \" />";
    if (!empty($searchusers)) 	{
	echo "\n  <input name=\"showall\" id=\"showall\" type=\"submit\" value=\"".$strshowall.'" />'."\n";
    }
/*echo"<input type=\"hidden\" name=id     value=\"p($id)\">";
 echo"<input type=\"hidden\" name=f value=\"p($f)\">";
 echo"<input type=\"hidden\" name=courad value=\"p($courad)\">";
 echo"<input type=\"hidden\" name=course value=\"p($courad)\">";
 echo"<input type=\"hidden\" name=parent value=\"p($parent)\">";
 echo"<input type=\"hidden\" name=d     value=\"p($discussion)\">";
 echo"<input type=\"hidden\" name=screen     value=\"p($screen)\">";*/
    echo"</td>";
    echo"\n </tr>";
    echo"\n</table>";
    echo"\n</form>";
}

//retorna el número de bústies de l'usuari en el curs i 0 si no en te
// uid=false: l'id de l'usuari
// cid=false: l'id del course
//si els dos són fals els agafarà de $USER i de $couse
function internalmail_count_user_course_mailboxes ($uid=false,$cid=false) {
    global $USER,$course,$CFG;
    if ($uid===false) $uid = $USER->id;
	
    if ($cid===false) {
	if (isset($course->id)){
	    $cid = $course->id;
	} else {
	    echo 'error a user_got_course_mailbox<br>';
	    return false;
	}
    }
    //agafem la discussion del tio
    $disc = internalmail_get_user_discussion($uid);
    //agafem el parent
    $fantasma = get_record('internalmail_posts','id',$disc->firstpost);
    //fem la query
    $query = "SELECT COUNT(*) AS num 
                FROM {$CFG->prefix}internalmail_posts
	       WHERE discussion='{$disc->id}' AND course='$cid' AND subject='Inbox'";
    //echo $query.'<br>';
    $num = get_record_sql($query);
    return $num->num;
}


function internalmail_get_mailboxes($course){

    global $CFG;
    if($course==1) {
	return get_records_sql("SELECT u.*, d.id as adminid
	                          FROM {$CFG->prefix}user u,
	                               {$CFG->prefix}internalmail_discussions d
	                         WHERE u.id = d.name
	                         ORDER BY d.id ASC");
    } else {
	if($CFG->dbtype=="postgres7") {
	    return get_records_sql("SELECT us.*, s.id as adminid
				      FROM {$CFG->prefix}user us, {$CFG->prefix}internalmail_posts s
				     WHERE s.subject='CUR26::'||us.id||'::".$course."'");
	} else {
	    return get_records_sql("SELECT us.*, s.id as adminid
				      FROM {$CFG->prefix}user us, {$CFG->prefix}internalmail_posts s
				     WHERE s.subject=CONCAT(\"CUR26::\",us.id,\"::".$course."\")");
	}	
    }	
}

function internalmail_remove_mailbox($removemailb, $course) { //no s'utilitza course de moment

    if($course==1) {
	$old_discussion=get_record("internalmail_discussions","name",$removemailb);
	internalmail_delete_discussion($old_discussion);
    } else  {

	//Migration 1.5.3
	if(!$parent=get_record("internalmail_posts","subject","CUR26::".$removemailb."::".$course)) {
	    //error("Failed to find mailbox");
	    return false;
	}
	if (!internalmail_remove_all_child($parent->id)) {
	    return false;
	}

	if (! internalmail_delete_post($parent)) {
	    return false;
	}
    }
    return true;		
}


function internalmail_delete_discussion($discussion) {
    // $discussion is a discussion record object

    $result = true;
    if ($posts = get_records("internalmail_posts", "discussion", $discussion->id)) {
	
	foreach ($posts as $post) {
	    if (! delete_records("internalmail_posts", "id", $post->id))  {
		$result = false;
	    } else if ($post->attachment) {	
		/*$aux=internalmail_get_subject($post);
		 $post->course=1;*/

		$internalmail = get_record("internalmail","course",$post->course);
		$post->internalmail= $internalmail->id;
		
		internalmail_delete_old_attachments($post);
	    }
	}
    }

    if (! delete_records("internalmail_discussions", "id", "$discussion->id")) {
	$result = false;
    }

    return $result;
}


function internalmail_get_reply_info ($course_module, $course, $internalmail, $maxmbytes, $reply, $id, $post_id, &$post ) {
    global $CFG, $USER, $cm;

    $c_id = $course->id;

    if (! $parent = get_record_sql("SELECT p.*
                                      FROM {$CFG->prefix}internalmail_posts p
                                     WHERE p.id = '$post_id'")) {
	error("Parent post ID was incorrect");
    }
    if (! $discussion = get_record("internalmail_discussions", "id", $parent->discussion)) {
	error("This post is not part of a discussion!");
    }
  
    $simplemail=get_record("modules","name","internalmail");

    $post=get_record("internalmail_posts","id",$post_id);

    $course= get_record("internalmail_discussions","id",$post->discussion);

    /*if (! internalmail_user_can_post($internalmail)) {
     error("Sorry, but you can not post in this internalmail.");
     }*/

    if ($cm = get_coursemodule_from_instance("internalmail", $simplemail->id, $course->id)) {
	if (groupmode($course, $cm) and !isteacheredit($course->id)) {   // Make sure user can post here
	    if (mygroupid($course->id) != $discussion->groupid) {
		error("Sorry, but you can not post in this discussion.");
	    }
	}
	if (!$cm->visible and !isteacher($course->id)) {
	    error(get_string("activityiscurrentlyhidden"));
	}
    }

    // Load up the $post variable.
    $subject="";
    $post->message="";
    if ($parent->subject != "Sent"){
	$aux = preg_split('/::/', $parent->subject, 3);
	if ( $reply ==3 ) {  //fem forward
	    unset($post->id);
									
	    $subject = get_string("fw","internalmail") . ' ' . $aux[2];
	    $tnom = get_record('user', 'id', $post->userid);

	    $ctr = explode("<br />", $parent->message); //Separem el missatge segons els returns. We separate message with lines
	    //$post->message = $post->message."> ". $ctrAux . "<br> ";
	    $post->message .= "-------- ".get_string('forwarded_msg', 'internalmail')." -------- <br />";
	    $post->message .= "> ". get_string('from','internalmail').': '; 
	    $post->message .=  $tnom->firstname." ".$tnom->lastname."<br />";
	    $post->message .= "> ". get_string('subject','internalmail').': '; 
	    $post->message .= $aux[2] . "<br />";
	    $post->message .= "> ". get_string("date", "internalmail").': ';
	    $post->message .= userdate($post->modified). "<br /><br />";
	    //$post->message .= get_string("to", "internalmail");

	    //echo "hola";
	    foreach($ctr as $ctrAux){
		while (strlen($ctrAux)>90){
		    $aux_message = substr($ctrAux,0,90);
		    $subs_pos = strrpos($aux_message, ' ');
		    if ($subs_pos === false) {
			//si no es troba el carácter, retorna false, hem de comprobar que tingui mateix tipus i mateix valor (===)
			$subs_pos = 90;
		    }
		    $post->message .= "> ".substr($ctrAux,0,$subs_pos)."<br />";
		    $ctrAux = substr($ctrAux,$subs_pos);	
		}	
		$post->message .= "> ".$ctrAux."<br />";
	    }
	    //$post->message = $post->message ."------------- ".get_string('end_message', 'internalmail')." -------------<br>";
							
	} else { //reply
	    $subject = get_string("re","internalmail").' ' .$aux[2];

	    $ctr = explode("<br />", $parent->message); //Separem el
						       //missatge
						       //
						       //segons els returns. We separate message with lines
	    //$post->message = $post->message."------------- ".get_string('start_message', 'internalmail')." -------------<br>";

	    //$post->message=$post->message."------------- ".get_string('end_message', 'internalmail')." -------------<br>";
	    //$post->destiny="";
	    $aux = internalmail_get_subject($parent);
	    $userid = $aux[1];
	    $user = get_record("user","id",$userid);
	    $post->destiny = $user->username;
	    //$tnom = get_record('user', 'id', $post->userid);
	    $tnom = $user->firstname." ".$user->lastname;
	    $tdate = userdate($post->modified);
	    $aa->date = $tdate;
	    $aa->name = $tnom;
	    $post->message = get_string('replyto_msg','internalmail', $aa) . "<br /> <br />";

	    foreach($ctr as $ctrAux ){
		while (strlen($ctrAux) > 90){
		    $aux_message = substr($ctrAux,0,90);
		    $subs_pos = strrpos($aux_message, ' ');
		    if ($subs_pos === false) {
			//si no es troba el carácter, retorna false, hem de comprobar que tingui mateix tipus i mateix valor (===)
			$subs_pos = 90;
		    }
		    $post->message .= "> ".substr($ctrAux,0,$subs_pos) . "<br />";
		    $ctrAux = substr($ctrAux,$subs_pos);
		}	
		$post->message .= "> ".$ctrAux. "<br />";
	    }
			
	    if ($reply==2) {							
		$parent_hist=get_record_sql("SELECT h.parent
					       FROM {$CFG->prefix}internalmail_history h
					      WHERE h.mailid = '$post_id'");

		$consulta1 = "SELECT h.*	
                                FROM {$CFG->prefix}internalmail_history h 
                               WHERE h.parent = '$parent_hist->parent' 
                                 AND h.event= 'received'";

		$destinataries = get_records_sql($consulta1);
		if(empty($destinataries)) {
		    $destinataries = array();
		}
							
		foreach ($destinataries as $destinatary) {
		    if($destinatary->userid != $USER->id && $destinatary->userid != $userid) {
			$user = get_record("user","id",$destinatary->userid);
			$post->destiny = $post->destiny.",".$user->username;
		    }										
		}								
	    } else if ($reply==1) {
		/*	$aux=internalmail_get_subject($parent);
		 $userid=$aux[1];
		 $user=get_record("user","id",$userid);
		 $post->destiny=$post->destiny.$user->username;*/
	    }
	    //$post->message = get_string('send by','internalmail').": <br />".$tnom." <br />".$tdate." <br /><br />".$post->message;
	}
	$parent=get_record("internalmail_posts","id",$parent->parent);
	$parent=get_record("internalmail_posts","parent",$parent->parent,"subject","Sent");
    }
    $post->course  = $course->id;
    $post->simplemail  = $simplemail->id;
    $post->discussion  = $parent->discussion;
    $post->userid = $USER->id;
    $post->format = $defaultformat;
    $post->parent = $parent->id;
    $grandparent=get_record("internalmail_posts","id",$parent->parent);
    if(($parent->subject=="Sent") AND (substr($grandparent->subject,0,5)=="CUR26")){
	unset($parent);
	$post->subject=$subject;
	$post->action="send";
	$aux=preg_split('/::/',$grandparent->subject,3);
	$post->cursdest=$aux[2];
	$aux=preg_split('/::/',$grandparent->subject,3);
    } else if(($parent->subject=="Sent") AND (!(substr($grandparent->subject,0,5)=="CUR26"))){
	unset($parent);
	$post->subject=$subject;
	$post->action="send";
	$post->cursdest="1";
    } else {
	$post->subject = $parent->subject;
	$strre = get_string('re', 'internalmail');
	if (!(substr($post->subject, 0, strlen($strre)) == $strre)) {
	    $post->subject = $strre.' '.$post->subject;
	}
    }
}


function internalmail_make_post($id,$post_id,$reply=1) {
	
    global $CFG, $USER, $cm, $im_error;

    $course_module = get_record("course_modules","id","$id");
    $course = get_record("course","id","$course_module->course");
    $c_id = $course->id;
    $internalmail = get_record("internalmail","course","$course_module->course");	
    $tmpmaxbytes = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes, $internalmail->maxbytes);

    $maxmbytes = $tmpmaxbytes / 1024;

    $post->destiny = "";
    if ( $post = data_submitted() ) {
    } else {
	if ( $reply > 0 ) {   // User is writing a new reply
	    internalmail_get_reply_info($course_module, $course, $internalmail,
					$maxmbytes, $reply, $id, $post_id, $post);

	    //  unset($SESSION->fromdiscussion);
	}
    }


    if ( $im_error ) {
	echo "\n<ul>$im_error\n</ul>\n";
    }

    $option = optional_param('option', 0, PARAM_INT);
    echo "\n\n<form name=\"theform\" method=\"post\" action=\"compose.php\" enctype=\"multipart/form-data\">";
    echo "\n <input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "\n <input type=\"hidden\" name=\"option\" value=\"$option\" />";

    echo "\n<table border=\"0\" cellpadding=\"5\" class=\"internalmail-compose-table\">";
    echo "\n <tr valign=\"top\">";
    $strto = get_string("to", "internalmail");
    echo "\n  <td align=\"left\"><strong> ";
    echo  "<a target=\"popup2\" title=\"" .$strto ."\" href=\"/mod/internalmail/contacts/list.php?id=$cm->id\" onClick=\"return openpopup('/mod/internalmail/contacts/list.php?id=$cm->id', 'popup2', 'menubar=1,location=0,scrollbars,resizable,width=400,height=500', 0);\"><span\">".$strto."</span></a>";
    echo "</strong></td>";
    //determinem si posem el destí del post o ens l'inventem
    $tousr = optional_param('tousr','', PARAM_CLEAN);
    if ($tousr != ''){
	$destiny = $tousr;
    } else {
	if (isset($post->destiny)) {
	    $destiny = $post->destiny;
	} else {
	    $destiny = "";   
	}
    }

    echo "\n  <td><input type=\"text\" name=\"destiny\" size=\"60\" value=\"$destiny\" />";
    helpbutton('destiny', get_string('to'),'internalmail');
    echo "</td>";
    echo "\n </tr>";
    echo "\n <tr valign=\"top\">";
    echo "\n  <td align=\"left\"><strong>";
    print_string("subject", "internalmail"); 
    echo "</strong></td>";
    echo "\n  <td>";
    if ( !isset($post->subject)) {
	$post->subject = "";
    }
    echo "<input type=\"text\" name=\"subject\" size=\"60\" value=\"$post->subject\" />";
    echo "</td>";
    echo "\n </tr>";
    echo "\n <tr>";
    echo "\n  <td align=\"left\" valign=\"top\"><strong>";
    print_string("attachment","internalmail");
    echo "</strong> </td>";

    internalmail_compose_print_attachments($post, $reply, $c_id, $internalmail->id, $maxmbytes);

    echo "\n <tr valign=\"top\">";
    echo "\n  <td align=\"left\">&nbsp;</td>";
    echo "\n  <td align=\"right\">";
    echo '<br />';
    echo "\n   <input type=\"submit\" value=\"" . get_string('send','internalmail') . "\" />";
    echo "\n   <input type=\"submit\" name=\"cancel\" value=\"" . get_string('cancel') ."\" />";	

    echo '</td>';
    echo "\n </tr>";

    echo "\n <tr>\n  <td colspan=\"2\">\n  <span style=\"text-align:left; font-weight:bold;\">". get_string("message", "internalmail") . "</span><br />\n  ";

    $usehtmleditor = 0;
    if ( can_use_richtext_editor() ) {
	$usehtmleditor = true;
    }

    if (!isset($post->message)) {
	$post->message = "";
    }
    print_textarea($usehtmleditor,20,55,0,0, "message", $post->message);
    if ( $usehtmleditor ) {
	use_html_editor('message','lefttoright righttoleft insertorderedlist insertunorderedlist outdent indent forecolor hilitecolor inserthorizontalrule createanchor nolink inserttable subscript superscript justifyleft justifycenter justifyright justifyfull undo redo');
    }

    echo "\n  </td>";
    echo "\n </tr>";
    echo "\n <tr>";
    echo "\n  <td style=\"text-align:center;\" colspan=\"2\">";
    echo "\n   <input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "\n   <input type=\"submit\" value=\"" . get_string('send','internalmail') . "\" />";
    echo "\n   <input type=\"submit\" name=\"cancel\" value=\"" . get_string('cancel') . "\" />";
    echo "\n  </td>";	
    echo "\n </tr>";
    echo "\n </table>";
    echo "\n</form>\n\n";
}


function internalmail_compose_print_attachments($post, $reply, $course_id, $internalmail_id, $maxmbytes) {
    global $CFG, $USER;

    // Check to see if the attachment directory exist.
    $filelocation = optional_param('attachdir', NULL, PARAM_CLEAN);

    if ( !$filelocation ) {
	$curtime = date("Ymdhis"); //YYYYMMDDHHMMSS
	if ( $USER->username ) {
	    $filelocation = 'msg' . $USER->username .'-'. $curtime;
	} else {
	    $filelocation = 'msg-' . $curtime;
	}
    }

    // Display the file attachment info
    echo "\n  <td align=\"right\">";
    echo "\n   <input type=\"hidden\" name=\"attachdir\" value=\"$filelocation\" />";	
    echo "\n   <input type=\"file\" name=\"attachment\" size=\"30\" />";
    echo "\n   <input type=\"submit\" name=\"attachfile\" value=\"" .get_string('add')  ."\" />";
    echo '<br />';
    print_string("maxsize");
    echo $maxmbytes;
    echo " KB ";

    if ( $reply != 3 || $post->attachment == "") {
	internalmail_add_compose_attachment_temp_file(1, $filelocation);
    } else {
	if( $post->attachment != "") {
	    internalmail_forward_compose_attachment_temp_file(1, $filelocation, $post->attachment, $course_id);
	}
    }
    echo '</td>';
    echo "\n </tr>";
}


/**
 * Returns a string showing the file attachements in the current temp
 * location when forwarding a message
 *
 * @param int $print - indicator for either return the string or print
 * the string
 * @param string $filelocation - the location the tempt file are.
 * @return string or prints a string
 */
function internalmail_forward_compose_attachment_temp_file($print, $filelocation, $attachment, $courseid) {
    global $CFG;

    $tempfiles_location = $CFG->dataroot . '/temp/internalmail/' . $filelocation;
    
    $fromfiles_location = $CFG->dataroot .'/' . $courseid ."/$CFG->moddata/internalmail/" . $attachment;

    if ( is_dir($fromfiles_location) ) {
	// Copy the files to the temp location now for forward
	//$dest_dir_temp = $CFG->dataroot . '/temp';
	//$status = check_dir_exists($dest_dir_temp, true);
    
	//$dest_dir_internalmail =  $CFG->dataroot . '/temp/internalmail';
	//$status = check_dir_exists($dest_dir_internalmail,true, true);

	$status = check_dir_exists($tempfiles_location, true, true);
	if ( $status ) {
	    // copy files now
	    if ($files = get_directory_list($fromfiles_location)) {
		foreach ($files as $key => $file) {
		    $tmp_filename = $tempfiles_location . '/' . $file;
		    $old_filename_location = $fromfiles_location . '/' . $file;
		    copy($old_filename_location, $tmp_filename);
		}
	    }
	}
    }

    // display files
    internalmail_add_compose_attachment_temp_display($tempfiles_location, $print);
}



/**
 * Returns a string showing the file attachements in the current temp
 * location when composing a message
 *
 * @param int $print - indicator for either return the string or print
 * the string
 * @param string $filelocation - the location the tempt file are.
 * @return string or prints a string
 */
function internalmail_get_attachment_location($courseid) {
    global $CFG;

    $internalmail_dir = $CFG->dataroot .'/' . $courseid ."/$CFG->moddata/internalmail/";
    $newlocation = 'msg' . internalmail_random_string();
    $dirlocation =  $internalmail_dir . $newlocation;
    while ( is_dir($dirlocation) ) {
	$newlocation = 'msg' . internalmail_random_string();
	$dirlocation = $internalmail_dir . $newlocation;  
    }

    return $newlocation;
}


/**
 * Generate and return a random string of the specified length.
 *
 * @param int $length The length of the string to be created.
 * @return string
 */
function internalmail_random_string($length=15) {

    $pool = 'abcdefghijklmnopqrstuvwxyz';
    $pool .= '0123456789';
    $poollen = strlen($pool);
    
    mt_srand ((double) microtime() * 1000000 * getmypid());

    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= substr($pool, (mt_rand()%($poollen)), 1);
    }
    return $string;
}


/**
 * Returns a string showing the file attachements in the current temp
 * location when composing a message
 *
 * @param int $print - indicator for either return the string or print
 * the string
 * @param string $filelocation - the location the tempt file are.
 * @return string or prints a string
 */
function internalmail_add_compose_attachment_temp_file($print=0, $filelocation) {
    global $CFG;

    //$filelocation = optional_param('attachdir', NULL, PARAM_CLEAN);
    $tempfiles_location = $CFG->dataroot . '/temp/internalmail/' . $filelocation;

    // display files
    internalmail_add_compose_attachment_temp_display($tempfiles_location, $print);
}


/**
 * Returns a string showing the file attachements in the current temp location 
 *
 * @param int $print - indicator for either return the string or print
 * the string
 * @param string $tempfiles_location - the location the tempt file are.
 * @return string or prints a string
 */
function internalmail_add_compose_attachment_temp_display ($tempfiles_location, $print=0) {

    $output = "";
    if (is_dir($tempfiles_location)) {
	if ($files = get_directory_list($tempfiles_location)) {
	    $deletstr = get_string('deleteselected');
	    $count = 0;
	    $fileinfo = '';
	    $output .= "\n<table border=\"0\" class=\"internalmail-temp-table-left\">";
	    foreach ($files as $key => $file) {
		$tmp_filename = $tempfiles_location . '/' . $file;
		$output .= "\n  <tr>\n  <td class=\"internalmail-temp-files\">";
		$output .= "<input type=\"checkbox\" name=\"tmpfilename[]\" value=\"$file\" id=\"$file\" /><label for=\"$file\">$file (" . internalmail_show_readable_filesize(filesize($tmp_filename)) . ') </label>';
		$output .= "</td>\n  </tr>";
		$count++;
	    }
	    $output .= "\n  <tr>\n  <td>";
	    $output .= "<input type=\"submit\" name=\"deletefile\" value=\"$deletstr\" /> ";
	    $output .= "</td>\n  </tr>";
	    $output .= "\n</table>";
	}
    }
    if ( $print ) {
	echo $output;
    }

}

/**
 * Returns a string showing the size of the message/attachment.
 *
 * @param int bytes the filesize in bytes
 * @return string the filesize in human readable format
 */
function internalmail_show_readable_filesize($bytes) {
    $bytes /= 1024;
    $type = 'k';

    if ($bytes / 1024 > 1) {
        $bytes /= 1024;
        $type = 'M';
    }

    if ($bytes < 10) {
        $bytes *= 10;
        settype($bytes, 'integer');
        $bytes /= 10;
    } else {
        settype($bytes, 'integer');
    }

    return $bytes . '<small>&nbsp;' . $type . '</small>';
}


function internalmail_print_post($post_id,$id) {
		
    global $CFG;
    global $USER;
    global $cm;
    global $course;
    global $internalmail;
	
    //$course->id=1;//ctr
    //$internalmail->id=1;//ctr ctr
    //$numeromeu = count_records("internalmail_posts","id","$id");
    //$numeromeu = count_records("internalmail_posts","id",$post_id);
    //check if the logged user is the owner of the mail
    $owner = count_records_select("internalmail_posts","id=$post_id AND userid=$USER->id");
    //print ("<h1>$numeromeu - $numeromeu2 - $USER->id</h1>");
    if ($owner) {
	//get_record_select("course_modules", "module=$iddelmodul->id AND course=$mail->course ");
		
	//agafem algunes variables
	if (!isset($cm)){
	    $course_module=get_record("course_modules","id","$id");
	} else {
	    $course_module = $cm;
	}
	if (!isset($course)) {
	    $course=get_record("course","id","$course_module->course");
	}
	if (!isset($internalmail)) {
	    $internalmail=get_record("internalmail","course","$course->id");
	}
 
	//agafem el post a partir de l'id i el seu subject real
	$post = get_record("internalmail_posts","id",$post_id);
	$subject = internalmail_get_subject($post);
		
	//mirem qui ha rebut aquest mail
	$query = "SELECT * FROM {$CFG->prefix}internalmail_history WHERE mailid='$post_id'";

	$parent_hist = get_record_sql($query, 1);
								
	$consulta1 = "SELECT h.* 
                        FROM {$CFG->prefix}internalmail_history h 
                       WHERE h.parent = '$parent_hist->parent' AND h.event='received'";

	$consulta2 = "SELECT * 
                        FROM {$CFG->prefix}internalmail_history 
                       WHERE mailid='$post_id'";

	//echo $consulta1.'<br>';											
	$destinataries=get_records_sql($consulta1);
	if(!$destinataries){
	    $destinataries=get_records_sql($consulta2);
	}
	if(empty($destinataries)) {
	    $destinataries=array();
	}
		
	//recollim la llista de mails germans
	$mails= get_records_sql("SELECT p.*
		                   FROM {$CFG->prefix}internalmail_posts p
 		                  WHERE p.parent = '$post->parent'	
		                  ORDER BY p.created DESC");
		
	if(empty($mails)){
	    $mails=array();
	}
	
	//aquí carreguemm el next, prev (i nextnew i prevnew)
	$act = false;
	$prev = '';
	$next = '';
		
	$found=false;
	foreach( $mails as $mail) {
	    if (!$found) {
		if(empty($act)) {
		    //el prev va baixant tot el rato
		    if($mail->id==$post_id) {
			$act=$mail;
			$found=true;
			$next=$mail;
		    } else {
			$prev=$mail;	
		    }
		}		
	    } else {
		//aquí ja l'hem trobat, o sigui que posem el next
		if (!empty($next) && $next==$act) {
		    $next=$mail;
		}
	    }
	}
		
	//agafem la informació de l'usuari
	$user = get_record("user","id",$subject[1]);
		
	//el missatge parent
	$parent = get_record("internalmail_posts","id",$post->parent);
		
	//--- comença la presentació
		
	//--primera zero: controls (respondre, reenviar, respondre a tots... i anterior i següent)
	echo "\n<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n <tr valign=\"bottom\">\n  <td>";
		
	//els botons de control
	echo "\n   <a href=\"compose.php?id=$id&amp;option=1&amp;post=$post->id&amp;reply=1\"><strong>".get_string('reply','internalmail')."</strong></a>";
	echo " | ";
	echo "\n   <a href=\"compose.php?id=$id&amp;option=1&amp;post=$post->id&amp;reply=2\"><strong>".get_string('reply_all','internalmail')."</strong></a>";
	echo " | ";
	echo "\n   <a href=\"compose.php?id=$id&amp;option=1&amp;post=$post->id&amp;reply=3\"><strong>".get_string('forward','internalmail')."</strong></a>";
	echo " | ";
	echo "\n   <a href=\"view.php?id=$id&amp;option=11&amp;post=$post->id\"><strong>".get_string('history','internalmail')."</strong></a>";
	echo " | ";
	echo "\n   <a href=\"remove.php?id=$id&amp;Operation=REM&amp;post_id=$post->id&amp;mode=".strtolower($parent->subject)."\"><strong>".get_string('borrar','internalmail')."</strong></a>";
		
	echo "\n  </td>\n  <td width=\"70\">";
	//els botons de següent i anterior
	echo '&nbsp;';
	if(isset($prev) && isset($prev->id)) {
	    echo "\n   <a href=\"view.php?id=$id&option=6&amp;post=$prev->id\"><span class=\"internalmail_font_small\">" .
		"<img src=\"{$CFG->wwwroot}/mod/internalmail/images/up.gif\" alt=\"".get_string("previous","internalmail")." \" /></span></a>";
	}
	if($act!=$next) {
	    echo "\n   <a href=\"view.php?id=$id&amp;option=6&amp;post=$next->id\"><span class=\"internalmail_font_small\">" .
		"<img src=\"{$CFG->wwwroot}/mod/internalmail/images/down.gif\" alt=\"".get_string("next","internalmail")." \" /></span></a>";
	}
		
	echo "\n  </td>\n </tr>\n</table>\n\n";
		
		
	//--creem la taula principal (només una columna)
	echo '<div style="border-width:1px;border-style:solid;border-color:#dddddd">' . "\n";
	echo '<table border="0" width="100%" cellpadding="5">';
		
	//--fila 1: informació de la persona que ha enviat el mail i la data
	echo "\n <tr>\n  <td class=\"header c1\">";
		
	//serà una petita taula
	echo "\n  <table border=\"0\" width=\"100%\">\n  <tr>";
	echo "\n  <td width=\"60%\">";
	if ( !isset($user->picture) ) {
	    $user->picture = 0;
	}
	if ( !isset($user->id) ) {
	    echo "&nbsp;";
	} else {
	    print_user_picture($user->id, $course->id, $user->picture);
	}

	echo ' <span class="internalmail_subject_display">'.$subject[2].'</span>';
	echo '</td>';
	//data
	echo "\n  <td width=\"40%\">";
	echo "\n  <span class=\"internalmail_message_date\">".get_string("date",'internalmail').": </span><span class=\"internalmail_message_date_time\">".userdate($post->modified)."</span>\n  ";
	echo '</td>';
	echo "\n  </tr>\n  </table>\n";
		
	echo "\n  </td>\n </tr>\n";
		
	//--fila 2: el from i to del missatge (plegable)
	echo "\n <tr>\n  <td class=\"header c1\">\n  ";
		
	//aquesta part s'hauria de poder ocultar, així que tindrà tres divs
	echo '<a href="javascript:toggle(\'minitomsg\');toggle(\'tomsg\');">' .
	    '<div style="float:right;" id="togglesmsg">';
	echo get_string('info').'</div></a>';
	echo '<div id="tomsg" style="display:none;">';
	$print= "<span class=\"internalmail_font_small\">".get_string('from')." </span>";
	$print.= "<span class=\"internalmail_from_and_to_person\">".fullname($user)."</span>";
	$print.= "<span class=\"internalmail_font_small\"> ".get_string('to')." </span>";
	$miniprint = $print; //la versió resumida
	//montem la llista de TOs:
	//this array is used to delete repeated rows
	$used_dests = array();
	$countNum = 0;
	foreach($destinataries as $destinatary) {
	    $user_dest = get_record("user","id",$destinatary->userid);
	    if (!in_array($user_dest->username,$used_dests)) {
		if ($countNum > 0) {
		    $print .=", ";
		}
		$countNum++;
		$print.= "<span class=\"internalmail_from_and_to_person\">" . fullname($user_dest)."</span>";
		if (strlen($miniprint) <200) {
		    $miniprint = $print;
		}
		$used_dests[] = $user_dest->username;
	    }
	}
	if (strlen($miniprint) < strlen($print)) {
	    $miniprint .= '.....';
	}
	echo $print;
	echo '</div>';
	//aquest últim div és pel resum dels TOs
	echo '<div id="minitomsg">'.$miniprint.'</div>';
				
	echo "\n </td>\n </tr>\n";	
	
	//--fila 4: el cos del missatge
	echo "\n <tr>\n  <td>";
		
	echo $post->message;
		
	echo "\n  </td>\n </tr>\n";		

	//--fila 3: attachments
	if ($post->attachment!="") {
	    echo "\n <tr>\n <td class=\"internalmail-attachments c1\">";
	    echo get_string ('attachment','internalmail').': ';
	    $post->course = $course->id;
	    $post->internalmail = $internalmail->id;
	    internalmail_print_attachments($post);
	    echo "\n  </td>\n </tr>\n";
	}

	//--tanquem la taula
	echo "\n</table>\n</div>";
		
	//marcar com a mailed
	$post->message = str_replace("'","\'",$post->message);
	$post->subject = str_replace("'","\'",$post->subject);
	$post->mailed = 1;
	update_record("internalmail_posts",$post);
		
	//insertar com llegit a l'historic
	//mirem si ja ha estat insertat com a llegit
	$miss=get_record_sql("SELECT h.* 
				FROM {$CFG->prefix}internalmail_history h 
			       WHERE h.mailid = '$post_id'
				 AND (h.event= 'read' OR h.event= 'copies')");
	//si no ha estat insertat com a llegit l'insertem
		
	if(empty($miss->id))   {
	    //busquem el parent on penjar-lo
	    $parent_hist=get_record_sql("SELECT h.parent
				           FROM {$CFG->prefix}internalmail_history h
					  WHERE h.mailid = '$post_id'");

	    $hist->mailid=$post_id;
	    $hist->time=time();
	    $hist->event="read";
	    $hist->userid=$USER->id;
	    if ( isset($parent_hist->parent )) {
		$hist->parent = $parent_hist->parent;
	    }
	    $hist->id = insert_record("internalmail_history",$hist);
	}
    } else {
	//the logged user is not the owner of the mail
	print ("Mail not found");
    }
}

function internalmail_get_subject($post) {
    $aux=preg_split('/::/',$post->subject,3);
    return $aux;
}




function internalmail_print_folder_header($id,$reply,$option,$page) {

    if (!isset($cellcontent2)) {
	$cellcontent2 = "";
    }

    echo "\n<table border=\"1\" width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"5\" bordercolor=\"".$cellcontent2."\" class=\"generalbox\">";
    echo "\n <tr>";
    echo "\n  <th width=\"9%\"align=\"center\" class=\"header c0\">";
    echo "<a href=\"#\" onClick=\"invert_check('ch')\">".get_string('check','internalmail')."</a>";
    echo "</th>";
    echo "\n  <th width=\"20%\" class=\"header c1\"><strong>";
    if($reply==1) { //ordena per subj asc
	echo "<a href=\"view.php?id=$id&option=$option&page=$page&reply=2\">&raquo;".get_string('subject','internalmail')."</a>";
    } else if($reply==2) { //ordena per subj desc
	echo "<a href=\"view.php?id=$id&option=$option&page=$page&reply=1\">&raquo;".get_string('subject','internalmail')." </a>";
    } else {
	echo "<a href=\"view.php?id=$id&option=$option&page=$page&reply=1\">&raquo;".get_string('subject','internalmail')."</a>";	
    }
    echo "</th>";
    echo "\n  <th class=\"header c2\">";
    echo "<strong>".get_string('from','internalmail')."</strong></td>";

    echo "\n  <th class=\"header c3\"><strong>";
    if ($reply==5) { //ordena per data asc
	echo "<a href=\"view.php?id=$id&option=$option&page=$page&reply=6\">&raquo;".get_string('date','internalmail')." </a>";
    } else if ($reply==6) { //ordena per data desc
	echo "<a href=\"view.php?id=$id&option=$option&page=$page&reply=5\">&raquo;".get_string('date','internalmail')."</a>";
    } else {
	echo "<a href=\"view.php?id=$id&option=$option&page=$page&reply=5\">&raquo;".get_string('date','internalmail')."</a>";	
    }
    echo "</th>";
    echo "\n  <th width=\"9%\" class=\"header c4\">";
    echo "<strong>".get_string('history','internalmail')."</strong>";
    echo "</th>";
    echo "\n  <th width=\"7%\"align=\"center\" class=\"header c5\">";
    echo "<strong>".get_string('picture','internalmail')."</strong>";
    echo "</th>";
    echo "\n </tr>";
}


function internalmail_print_folder($id,$mode="inbox",$page=0,$reply=0)  {
  
    global $USER;
    global $THEME;

//javascript function used to invert checked messages
print("<script type=\"text/javascript\"><!--

 function invert_check(name) {
 var formblock;
 formblock= document.getElementById('form_id');
 for (i = 0; i < formblock.length; i++) {
	 if ((formblock[i].type == 'checkbox')) {
		 	formblock[i].checked = !formblock[i].checked ;
		}
	 }
 }
 //--></script>");
 
   
    $discussion=internalmail_get_user_discussion($USER->id);
    $ghost=$discussion->firstpost;

    $cm=get_record("course_modules","id",$id);
    $course=get_record("course","id",$cm->course); //per lo que ho utilitzo ara amb la consulta de sobre n'hi ha prou

    if($cm->course!==1) {
		$folder_course=$ghost+4;
		$post_courses=array();
		$post_courses=internalmail_get_child_posts($folder_course);
		if(empty($post_courses)){
		    $post_courses=array();
		}
		foreach($post_courses as $post_course)	{
		    $aux=internalmail_get_subject($post_course);
		    if($aux[2]==$cm->course)	{
			$ghost=$post_course->id;
		    }
		}
    }

    switch($mode){
	    case "inbox":
			$folder=$ghost+1;
			$option=2; 
			break;
	    case "sent":
			$folder=$ghost+2; 
			$option=3;
			break;
	    case "copies":
			$folder=$ghost+4; 
			$option=12;
			break;
	    case "deleted":
			$folder=$ghost+3; 
			$option=4;
			break;
    }

    $n_x_page=10;
    $i=0;
    $j=0;
    $min=$page*$n_x_page;
    $max=$min+$n_x_page;

    if ($posts = internalmail_get_child_posts($folder,$reply)) {

	echo "\n <form id=\"form_id\" method=\"post\" action=\"remove.php\">";
	
	foreach ($posts as $post) {
		
	    if(($i>=$min) && ($i<$max)) {
			
		//if(!$post->mailed)
		//{
		//echo "\n <tr bgColor=\"#CCCCCC\">";
		$classname = ($j % 2 == 0) ? 'r0' : 'r1';

		echo "\n <tr class=\"$classname\">";
		//}
			
		echo "\n  <td width=\"9%\" class=\"cell c1\">";
		$boldStart = "";
		$boldEnd = "";
		if($post->mailed){
		    echo "<img src=images/mailopen.gif width=\"24\" height=\"24\" align=\"absmiddle\"><input type=checkbox value=".$post->id." name=ch[]>";	  
		} else {
		    echo "<img src=images/mailclose.gif width=\"24\" height=\"24\" align=\"absmiddle\"><input type=checkbox value=".$post->id." name=ch[]>";
		    $boldStart = "<strong>";
		    $boldEnd = "</strong>";
		}
		echo "</td>";
		$aux=internalmail_get_subject($post);
		$subjbo=$aux[2];
		if(strlen($subjbo) > 20)	{
		    $subjbo=substr($subjbo,0,20);
		    $subjbo=$subjbo."...";
		}
			
		echo "\n  <td width=\"29%\" class=\"cell c2\">";
		if ($post->attachment!="") {
		    echo "<img src=images/clip.gif width=\"16\" height=\"16\" align=\"absmiddle\"><a name=\"$post->id\"></a><font size=\"-1\">$boldStart<a href=\"view.php?id=$id&option=6&post=$post->id\">$subjbo</a>$boldEnd ";
		} else	{
		    echo "<a name=\"$post->id\"></a><font size=\"-1\">$boldStart<a href=\"view.php?id=$id&option=6&post=$post->id\">$subjbo</a>$boldEnd ";
		}
		echo "</font>";
		echo "</td>";
		echo "\n  <td wudth=\"30%\" class=\"cell c3\">";
		$sender = get_record("user", "id", $aux[1]);
		echo fullname($sender);
		echo "</td>";
		echo "\n  <td width=\"16%\" class=\"cell c4\"><font size=\"-2\">";
		echo userdate($post->modified);
		echo "</font></td>";
		echo "\n  <td width=\"9%\" align=\"center\" class=\"cell c5\">";
		echo "<a name=\"$post->id\"></a><font size=\"-1\">$boldStart<a href=\"view.php?id=$id&option=11&post=$post->id\"><img src=images/history.gif width=\"24\" height=\"24\" align=\"absmiddle\"></a>$boldEnd ";     
		echo "</td>";
		echo "\n  <td width=\"7%\" align=\"center\">";

		if (!isset($sender->picture)) {
		    $sender->picture = 0;
		}
		if ( isset($sender->id)) {
		    echo print_user_picture($sender->id, $course->id, $sender->picture);
		} else {
		    echo "&nbsp;";
		}
		//echo "$sender->id, $course->id, $sender->picture";
		echo "</td>";
		echo "\n </tr>";
		$j++;
	    }
	    $i++;
	}
    }
    echo "\n</table>\n";
    //////////////calcul de les pagines/////////////

    $pagelast=(($i / $n_x_page)-(($i % $n_x_page) / $n_x_page));
    if(($i % $n_x_page)==0 && $i != 0){
	$pagelast=$pagelast-1;
    }
    $pagelast=round($pagelast);
    $pagemes=$page+1;
    $pagemenos=$page-1;
    //////////////fi calcul de les pagines/////////////
    if (!isset($cellcontent2)) {
	$cellcontent2 = "";
    }
    echo	"\n<table border=\"1\" width=\"99%\" align=\"center\" cellspacing=\"1\" cellpadding=\"5\" bordercolor=\"".$cellcontent2."\" class=\"generalbox\">";
    echo "\n <tr>";
    echo "\n  <td width=\"60%\">";
    echo get_string('mostrando','internalmail');
    if($i != 0) {
	echo ($n_x_page*$page)+1;
    } else{
	echo($n_x_page*$page);
    }
    echo get_string('al','internalmail');
    echo ($n_x_page*$page)+$j; 
    echo get_string('de','internalmail');
    echo $i;
    echo get_string('mensajes','internalmail');
    echo "</td>";
    echo "\n  <td> &nbsp; ";
    if($page!=0)    {
	echo " <a href=\"view.php?id=$id&option=$option&page=0\">&lt;&lt;&lt;</a> ";
	echo " <a href=\"view.php?id=$id&option=$option&page=$pagemenos\">&lt;&lt;</a> ";
    }
    if($page!=$pagelast){    
	echo " <a href=\"view.php?id=$id&option=$option&page=$pagemes\">&gt;&gt;</a> ";
	echo " <a href=\"view.php?id=$id&option=$option&page=$pagelast\">&gt;&gt;&gt;</a> ";
    }
    echo "\n  </td>";
    echo "\n </tr>";
    echo "\n</table>\n";

    echo "\n <p>".get_string('wanadoo','internalmail')."";
    echo "\n  <select name=\"Operation\">";
    echo "\n   <option value=\"NOT\">".get_string('nada','internalmail')."";
    echo "\n   <option value=\"REM\">".get_string('borrar','internalmail')."";
    if($mode!="deleted") {
	echo "\n   <option value=\"RED\">".get_string('marcar leido','internalmail')."";
	echo "\n   <option value=\"NRE\">".get_string('marcar no leido','internalmail')."";
    } else {
	echo "\n   <option value=\"RES\">".get_string('restaurar','internalmail')."";
    }
    echo "\n  </select>";
    echo "\n  <input type=\"submit\" name=\"do\" value=\"Go!!!\">";
    echo "\n  <input type=\"hidden\" name=\"id\" value=\"$id\">";
    echo "\n  <input type=\"hidden\" name=\"mode\" value=\"$mode\">";
    echo "\n </p>";
    echo "\n</form>\n";
    return ($i>$max);
}



function internalmail_get_user_discussion($userid) {
    global $CFG;
    return get_record("internalmail_discussions","userid",$userid);
}

function internalmail_get_post_full($postid) {
/// compatibilitat total amb postgresql?? comprovar-ho
    global $CFG;
    return get_record_sql("SELECT p.*, u.firstname, u.lastname, u.email, u.picture
                            FROM {$CFG->prefix}internalmail_posts p,
                                 {$CFG->prefix}user u
                           WHERE p.id = '$postid'
                             AND p.userid = u.id");
}


function internalmail_get_child_posts($parent,$reply=0) {
    // Gets posts with all info ready for internalmail_print_post
    global $CFG;
    //compatibilitat amb postgresql??

    if($reply==1){
	$ord="ORDER BY p.subject ASC";
    }else if($reply==2){
	$ord="ORDER BY p.subject DESC";
    }else if($reply==5){
	
	$ord="ORDER BY p.created ASC";
    } else {
	$ord="ORDER BY p.created DESC";
    }

    //GORKA
    return get_records_sql("SELECT p.*, u.firstname, u.lastname, u.email, u.picture
                              FROM {$CFG->prefix}internalmail_posts p,
                                   {$CFG->prefix}user u
                             WHERE p.parent = '$parent'
                               AND p.userid = u.id
                          $ord");
}


function internalmail_add_user_mailbox($addmailb, $course) {
	
    global $CFG;
	
    if($course==1) {	
		
	if(!$internalmail=get_record("internalmail","course",$course))	{
	    return false;
	}
	//discussion associat al nou internalmail
	$newdiscussion->course=$course;
	$newdiscussion->internalmail=$internalmail->id;
	$newdiscussion->name=$addmailb;
	//echo "<br>".$addmailb."<br>";CRICH Agramunt
	$newdiscussion->userid=$addmailb;
	$newdiscussion->intro="<br />";
	$newdiscussion->format=0;
	
	if(!$newdiscussion->id=internalmail_add_discussion($newdiscussion)) {
	    return false;
	}
	$newdiscussion=get_record("internalmail_discussions","id",$newdiscussion->id);				
	//post associat al discussion anterior
	$newpost->discussion=$newdiscussion->id;
	$newpost->parent=$newdiscussion->firstpost;
    }  else  {
	//echo 'curs';
	//agafem el pare de la llista de cursos
	if(!$parentp=get_record("internalmail_posts","userid",$addmailb,"subject","Courses")) {
	    //prova de posar-lo en el principal
	    if(! internalmail_add_user_mailbox($addmailb,1)) {
		return false;
	    }
	    if(!$parentp=get_record("internalmail_posts","userid",$addmailb,"subject","Courses")) {
		return false;
	    }
	}
	$newpost->discussion=$parentp->discussion;
	$newpost->parent=$parentp->id;
    }
    $newpost->message="<br />";
    $newpost->userid=$addmailb;
	
    $newpost->course = $course;
	
    if($course!=1)  {
	$newpost->subject="CUR26::".$addmailb."::".$course;
	if(!$newpost->id=internalmail_add_new_post($newpost)) {
	    if(!$newpost->id=internalmail_add_new_post($newpost)) {
		return false;
	    }
	}
	$newpost->parent=$newpost->id;
    }
    $newpost->subject="Inbox";
    if(!$newpost->id=internalmail_add_new_post($newpost)) {
	if(!$newpost->id=internalmail_add_new_post($newpost)) {
	    return false;
	}
    }

    $newpost->subject="Sent";
    if(!$newpost->id=internalmail_add_new_post($newpost)) {
	if(!$newpost->id=internalmail_add_new_post($newpost))  {
	    return false;
	}
    }
    $newpost->subject="Deleted";
    if(!$newpost->id=internalmail_add_new_post($newpost)) {
	    if(!$newpost->id=internalmail_add_new_post($newpost)) {
		return false;
	    }
    }		
    if($course==1) {
	$newpost->subject="Courses";
	if(!$newpost->id=internalmail_add_new_post($newpost)) {
	    if(!$newpost->id=internalmail_add_new_post($newpost)) {
		return false;
	    }
	}
    } else {
	$newpost->subject="Copies";
	if(!$newpost->id=internalmail_add_new_post($newpost)) {
	    if(!$newpost->id=internalmail_add_new_post($newpost)) {
		return false;
	    }
	}				
    }
    $newpost->parent=$newpost->id - 3;
    $newpost->subject="RIO26::".$addmailb."::".get_string('wtoi','internalmail');//"::Welcome to Internalmail 2.0";
    $newpost->format=1;
    $newpost->totalscore=1;
    internalmail_add_new_post($newpost);
    //print_object($newpost);
    return true;			
}


function internalmail_add_discussion($discussion)  {
    // Given an object containing all the necessary data,
    // create a new discussion and return the id
	
    GLOBAL $USER,$course;
	
    $timenow = time();
	
    // The first post is stored as a real post, and linked
    // to from the discuss entry.
	
    $post->discussion  = 0;
    $post->parent      = 0;
    $post->userid      = (isset($discussion->userid))? $discussion->userid : $USER->id;
    $post->created     = $timenow;
    $post->modified    = $timenow;
    $post->mailed      = 0;
    $post->subject     = $discussion->name;
    $post->message     = $discussion->intro;
    $post->attachment  = "";
    $post->internalmail= $discussion->internalmail;
    $post->course      = $discussion->course;
    $post->format      = $discussion->format;
	
    $post->course = 1;
	
    if (! $post->id = insert_record("internalmail_posts", $post) ) {
	return 0;
    }
	
    // Now do the main entry for the discussion,
    // linking to this first post
	
    $discussion->firstpost    = $post->id;
    $discussion->timemodified = $timenow;
    $discussion->usermodified = $post->userid;
	
    if (! $discussion->id = insert_record("internalmail_discussions", $discussion) )  {
	delete_records("internalmail_posts", "id", $post->id);
	return 0;
    }
	
    // Finally, set the pointer on the post.
    if (! set_field("internalmail_posts", "discussion", $discussion->id, "id", $post->id)) {
	delete_records("internalmail_posts", "id", $post->id);
	delete_records("internalmail_discussions", "id", $discussion->id);
	return 0;
    }
	
    return $discussion->id;
}


function internalmail_add_new_post($post) {
    global $course;
	
    $post->created = $post->modified = time();
    if(!isset($post->mailed)) {
	$post->mailed = "0";
    }
    if (!isset($post->course)) $post->course = $course->id;
	
    //$newfile = $post->attachment;
    $post->attachment = "";
    if (! $post->id = insert_record("internalmail_posts", $post))  {
	return false;
    }
	
    // Update discussion modified date
    set_field("internalmail_discussions", "timemodified", $post->modified, "id", $post->discussion);
    set_field("internalmail_discussions", "usermodified", $post->userid, "id", $post->discussion);
    return $post->id;
}

/*function internalmail_user_can_post($simplemail, $user=NULL) 
 {
// $simplemail, $user are objects

if ($user) 
{

$isteacher = isteacher($simplemail->course, $user->id);
} 
else 
{

$isteacher = isteacher($simplemail->course);
}


if ($simplemail->type == "teacher") 
{

return $isteacher;
} 
else if ($isteacher) 
{

return true;
} 
else 
{

return $simplemail->open;
}
}*/



function internalmail_get_folder_unread($parent) {

    if ($parent->subject=="Inbox" || $parent->subject=="Sent" || $parent->subject=="Deleted") {

	return count_records("internalmail_posts","mailed",0,"parent",$parent->id);
    } else  {
	$posts=internalmail_get_child_posts($parent->id);
	$result=0;
	if($posts) {
	    foreach ($posts as $post) {
		$result=$result+internalmail_get_folder_unread($post);
	    }
	}
	return $result;
    }
}

function internalmail_get_user_parent_id($uid)  {
    global $CFG;
	  
    return get_record_sql("SELECT p.*, u.firstname, u.lastname, u.email, u.picture
		       FROM {$CFG->prefix}internalmail_posts p,
                            {$CFG->prefix}user u
                       WHERE p.subject='$uid'
                       AND   p.userid= u.id");
}


function internalmail_remove_all_child($parent) {

    $result=true;
    if (!($parent >0))	{
	return false;
    }
    if($posts=internalmail_get_child_posts($parent)) {
	foreach ($posts as $post) {
	    if($result=internalmail_remove_all_child($post->id)) {
		if (! internalmail_delete_post($post)) {
		    $result=false;
		}
	    }
	}
    }
    return $result;
}


function internalmail_delete_post($post) {

    $hist=get_record("internalmail_history","mailid",$post->id);
    delete_records("internalmail_history","mailid",$post->id);
    $other= get_records("internalmail_history","parent",$hist->parent);
    if(empty($other)) {
	delete_records("internalmail_history","id",$hist->parent);
    }

    if (delete_records("internalmail_posts", "id", $post->id)) {

	if ($post->attachment) {

	    //$aux=internalmail_get_subject($post);
	    //$post->course=$aux[1];
	    $internalmail = get_record("internalmail","course",$post->course);
	    $post->internalmail = $internalmail->id;

	    $new_post = $post;
	    internalmail_delete_old_attachments($new_post);
	}
	return true;
    }

    return false;
}


// Get the course number and the internalmail id for the post given id
function internalmail_get_remove_post_info( $id ) {
    global $CFG;

    return get_record_sql("SELECT cm.course, i.id
                             FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course c, {$CFG->prefix}internalmail i 
                            WHERE cm.id='$id' 
                              AND c.id= cm.course 
                              AND i.course=c.id" );
}


//-------- ATTACHMENTS MANAGE ---------------

function internalmail_delete_old_attachments($post, $exception="") {
    global $CFG, $course;
    // Deletes all the user files in the attachments area for a post
    // EXCEPT for any file named $exception
	
    //per motius de poltergeist recarreguem el post
    //$post = get_record('internalmail_posts','id',$post->id);

    //  echo "Step 1:  $post->attachment<br>";
    //esborra els adjunts d'un post
    //print_object($post);
    //agafem el directori del arxius adjunts
    if ($basedir = internalmail_file_area($post)) {
	//echo "Step 1a : $basedir<br>";
	//mirem si té arxius adjunts
	if (isset($post->attachment) && $post->attachment != '') {
	    //hi ha algun adjunt per esborrar
	    $file = $post->attachment;
	    $discus = $post->discussion;
	    $id = $post->id;
	    // echo "Step 2<br>";
	    //mirem si tenim el curs
			
	    $curs = $post->course;		
	    //mirem si algú altre vol fer servir l'adjunt
	    $quer = "SELECT COUNT(*) AS num
                       FROM {$CFG->prefix}internalmail_posts
		      WHERE attachment='$file' AND course='$curs' AND id<>'$id'";

	    $other = get_record_sql($quer);
	    // echo "Step 3 : $other->num<br>";
	    if ( !$other->num ) {
		//no hi ha ningú més que el necessiti, o sigui que
		//podem esborrar-lo

		//if ( $basedir = internalmail_file_area($post) ) {
		$filedir = $basedir .'/'. $post->attachment;
		if ($files = get_directory_list($filedir)) {
		    //require_once($CFG->libdir.'/filelib.php');
		    //   echo "Step 4 : <br>";
		    foreach ($files as $key => $file) {
				$tmpfilename = $filedir . '/' . $file;
				//    echo "Step 5 : $tmpfilename <br>";
				if ( file_exists($tmpfilename)) {
				    // Remove the file now
				    unlink($tmpfilename);
				}
		    }
		    //  echo "Step 6 : $tmpfilename <br>";
		    // Remove the directory now
		    rmdir($filedir);
		}
	    }
	}
    }
}


/*
function internalmail_delete_old_attachments_removed($post, $exception="") {
    global $CFG, $course;
    // Deletes all the user files in the attachments area for a post
    // EXCEPT for any file named $exception
	
    //per motius de poltergeist recarreguem el post
    $post = get_record('internalmail_posts','id',$post->id);
	
    //esborra els adjunts d'un post
    //print_object($post);
    //agafem el directori del arxius adjunts
    if ($basedir = internalmail_file_area($post)) {
	//mirem si té arxius adjunts
	if (isset($post->attachment) && $post->attachment != '') {
	    //hi ha algun adjunt per esborrar
	    $file = $post->attachment;
	    $discus = $post->discussion;
	    $id = $post->id;
			
	    //mirem si tenim el curs
			
	    $curs = $post->course;
			
	    //mirem si algú altre vol fer servir l'adjunt
	    $quer = 'SELECT COUNT(*) AS num FROM '.$CFG->prefix."internalmail_posts " .
		"WHERE attachment='$file' AND course=$curs AND id<>$id";
	    $other = get_record_sql($quer);
	    if (!$other->num) {
		//no hi ha ningú més que el necessiti, o sigui que podem esborrar-lo
		if (file_exists("$basedir/$file")) {
		    unlink ("$basedir/$file");
		}
	    }
	}
    }
}
*/

function internalmail_file_area_name($post) {
    //  Creates a directory file name, suitable for make_upload_directory()
    global $CFG;

    return "$post->course/$CFG->moddata/internalmail";
    //return "$post->course/$CFG->moddata/internalmail/$post->internalmail/$post->id";
}


function internalmail_file_area($post) {
    return make_upload_directory( internalmail_file_area_name($post) );
}


//adds une to file count
// $file: the file name
// $id: internalmail id
/*function internalmail_file_num_increase ($file,$id) {
 global $CFG, $internalmail;
 //mirem si existeix
 echo 'increase: ';
 if (internalmail_file_num_exists ($file,$id)){
 echo 'existeix';
 //si exiteix agafem les dades
 $filerow = get_record('internalmail_files','internalmail',$id,'filename',$file);
 //incrementem num
 $filerow->num++;
 //actualitzem
 echo 'upd|'.update_record('internalmail_files', $filerow).'|';
 return $filerow->num;
 } else {
 echo 'no existeix ';
 //si no existeix montem les dades
 $filerow->internalmail = $id;
 $filerow->filename = $file;
 $filerow->num = 1;
 echo '|'.insert_record('internalmail_files', $filerow).'|';
 return $filerow->num;
 }
 }

//decrease the file num counter
// $file: the file name
// $id: internalmail id
function internalmail_file_num_descrease ($file,$id) {
global $CFG, $internalmail;
echo 'file:'.$file.'-id:'.$id.'<br>';
//mirem si existeix
if (internalmail_file_num_exists ($file,$id)){
//si exiteix agafem les dades
$filerow = get_record('internalmail_files','internalmail',$id,'filename',$file);
//decrementem num
$filerow->num = $filerow->num - 2;
if ($filerow->num < 0) $filerow->num = 0;
//actualitzem
update_record('internalmail_files', $filerow);
return $filerow->num;
} else {
return 0;
}
}

//returns true if the file is defined in the files table
// $file: the file name
function internalmail_file_num_exists ($file,$id) {
global $CFG, $internalmail;
$res = false;
//$res = record_exists('internalmail_files','internalmail',$internalmail->id,'filename',$file);
$res = count_records('internalmail_files','internalmail',$id,'filename',$file);
if ($res == 0) {
return false;
}
return true;
}

//deletes a file from the files table
// $file: the file name
function internalmail_file_num_delete ($file,$id){
global $CFG, $internalmail;
//mirem si existeix
if (internalmail_file_num_exists ($file,$id)){
delete_records('internalmail_files','internalmail',$id,'filename', $file);
}
}

//return the number of mails that attach an especified file
// $file: the file name
function internalmail_file_num_get ($file,$id) {
global $CFG, $internalmail;
//mirem si existeix
if (internalmail_file_num_exists ($file,$id)){
//si exiteix agafem les dades
$filerow = get_record('internalmail_files','internalmail',$id,'filename',$file);
return $filerow->num;
} else {
return 0;
}
}*/

/*	Functionallity:
		Pintar las pestañas que se le pasan en tabStrings, linkarlas 
		a donde corresponda y traer al frente la pestaña activa.
	Params:
		$id: se necesita para construir linkar correctamente las tabs
		$selected: Pestaña "activa".
		$tabStrings[]: Array con los nombres de las pestañas a mostrar.
		
	Creator:				Iván Rivero Alonso
	Creation date:			07/02/2007
	Last modify programmer: Iván Rivero Alonso
	Last modify date:		07/02/2007
*/
function internalmail_print_tabs ($id, $selected, $tabStrings){
	global $CFG;
	
	$redacta=get_string('compose','internalmail');
    $entrants=get_string('inbox','internalmail');
    $enviats=get_string('sent','internalmail');
    $esborrats=get_string('deleted','internalmail');
    $opcions=get_string('options','internalmail');
    $missatges=get_string('messages','internalmail');
    $copies=get_string('copies','internalmail');
	
	$tabrow = array();		 
	foreach	($tabStrings as $str){

		switch ($str){
			case $redacta:
				$tabrow[] = new tabobject('Redacta', $CFG->wwwroot.'/mod/internalmail/compose.php?id='.$id.'&option=1', $redacta);
				break;
			case $entrants:
			    $tabrow[] = new tabobject('Entrantes',$CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=2', $entrants);
				break;
			case $enviats:
				$tabrow[] = new tabobject('Enviados', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=3', $enviats);
				break;
			case $esborrats:
			    $tabrow[] = new tabobject('Borrados', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=4', $esborrats);
				break;
			case $opcions:
				$tabrow[] = new tabobject('Opciones', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=5', $opcions);			
				break;
			case $missatges:
				$tabrow[] = new tabobject('Mensajes', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=7', $missatges);
				break;
			case $copies:
				$tabrow[] = new tabobject('Copias', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=12', $copies);	
				break;
		}
	}

	$tabrows = array($tabrow);
	
    //Array con el identificador de las tabs
    $tablabelsId = array("Redacta","Entrantes","Enviados","Borrados","Opciones",'',
		       "Mensajes",'','','','',"Copias",'',"Entrantes");

	if ($selected==NULL){ 
	//Si option viene NULL significa que es la primera vez que se carga.
	//Como por defecto se carga el inbox, seleccionamos dicha pestaña ("Entrantes").
		print_tabs ($tabrows,$tablabelsId[1]);
	} else {
		print_tabs ($tabrows,$tablabelsId[$selected-1]);
	}
}

/*
function internalmail_link_tabs($tabStrings){
global $CFG;
	
	$redacta=get_string('compose','internalmail');
    $entrants=get_string('inbox','internalmail');
    $enviats=get_string('sent','internalmail');
    $esborrats=get_string('deleted','internalmail');
    $opcions=get_string('options','internalmail');
    $missatges=get_string('messages','internalmail');
    $copies=get_string('copies','internalmail');
	
	$tabrow = array();		 
	foreach	($tabStrings as $str){
		echo ($str);
		switch ($str){
			case $redacta:
				$tabrow[] = new tabobject('Redacta', $CFG->wwwroot.'/mod/internalmail/compose.php?id='.$id.'&option=1', $redacta);
				break;
			case $entrants:
			    $tabrow[] = new tabobject('Entrantes',$CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=2', $entrants);
				break;
			case $enviats:
				$tabrow[] = new tabobject('Enviados', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=3', $enviats);
				break;
			case $esborrats:
			    $tabrow[] = new tabobject('Borrados', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=4', $esborrats);
				break;
			case $opcions:
				$tabrow[] = new tabobject('Opciones', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=5', $opcions);			
				break;
			case $missatges:
				$tabrow[] = new tabobject('Mensajes', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=7', $missatges);
				break;
			case $copies:
				$tabrow[] = new tabobject('Copias', $CFG->wwwroot.'/mod/internalmail/view.php?id='.$id.'&option=12', $copies);	
				break;
		}
	}

	return array($tabrow);

}*/

?>
