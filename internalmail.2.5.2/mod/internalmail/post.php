<?PHP
/*Aquest arxiu tracta la informació del formulari de cerca*/

require_once("../../config.php");
require_once("lib.php");
require_once("lib_post.php");
require_once($CFG->dirroot.'/message/lib.php');

//get required parameters
/*$destiny = optional_param('destiny', 0, PARAM_INT);
$subject = optional_param('subject', 0, PARAM_INT);
$message = optional_param('message', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$att = optional_param('att', 0, PARAM_INT);
$attpath = optional_param('attpath', 0, PARAM_INT);*/

$destiny = optional_param('destiny', NULL, PARAM_CLEAN);
$subject = optional_param('subject', NULL, PARAM_CLEAN);
$message = optional_param('message', NULL, PARAM_CLEAN);
$id = optional_param('id', NULL, PARAM_CLEAN);
$att = optional_param('att', NULL, PARAM_CLEAN);
$attpath = optional_param('attpath', NULL, PARAM_CLEAN);

global $USER;
global $THEME;
global $CFG;
global $course;

//mirem si tenim el curs
if (!isset($course->id)) {
	//calculem l'id del curs a partir del de cm
	$cm = get_record('course_modules','id',$id);
	//print_object($cm);
	if (isset($cm->id)){
		$course = get_record('course','id',$cm->course);
	}
}

$error=0; //tot correcte
$not_sent_array=array();

//agafem l'attach
$attach=$_FILES['attachment'];
//agafem els 20 primers caràcters del missatge per fer el resum.
$msg_curt=substr ($message, 0, 20);
$admin=get_record("user","username","admin");
$opt=0;

if(empty($attach["name"]))
{
	$attach=NULL;
}
if($att!="")
{
	$attach["name"]=$att;
	$attach["size"]=1;
	$attach["tmp_name"]=$attpath;
	$opt=1;
}

//si no hi ha destí retornem l'error
if($destiny=="")
{
	
	redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id&option=1",get_string('error','internalmail'),2);	
	
}
if($subject=="")
{
	$subject="?";
}


//////guardem el missatge a la carpeta d'enviats

$discuss=internalmail_get_user_discussion($USER->id);
$ghost=$discuss->firstpost;
/////enviar a cursos
$cm=get_record("course_modules","id",$id);
if($cm->course!==1)
{

	$folder_course=$ghost+4;
	$post_courses=array();
	$post_courses=internalmail_get_child_posts($folder_course);

	if(empty($post_courses))
	{
	 $post_courses=array();
	}

	foreach($post_courses as $post_course)
	{
		$aux=internalmail_get_subject($post_course);
		if($aux[2]==$cm->course)
		{
				$ghost=$post_course->id;
		}
	}
}
/////////////////////////////////////////
$sent=$ghost+2; //el parent del missatge
$subject_sql="RIO26::".$USER->id."::".$subject;

//creem el missatge
$miss->discussion=$discuss->id;
$miss->parent=$sent;
$miss->oldparent=0;
$miss->userid=$USER->id;
$miss->created=$miss->modified= time();
$miss->mailed=1;
$miss->subject=$subject_sql;
$miss->message=$message;
$miss->format=1;
$miss->attachment="";
$miss->totalscore=1;

$miss->course = $course->id;

//es guarda el missatge
if (! $miss->id = insert_record("internalmail_posts", $miss)) 
{
   $error=1; //no s'ha pogut guardar a la bustia d'enviats
}
//////afegim els attachments
else
{
	if($attach!=NULL)
	{
		$miss->attachment=internalmail_add_attachment($attach,$id,$miss->id,$opt);	
		if($miss->attachment->error==1)
		{
			delete_records("internalmail_posts","id",$miss->id);
			$error=1; //no s'ha afegit l'attachment, el missatge s'ha esborrat, com que es una continuacio de l'error 1 te el mateix codi
			
		}
		else
		{
	  	set_field("internalmail_posts", "attachment", $miss->attachment, "id", $miss->id);
		}
		
	}
	
}
//insertem en l'historic l'event ghost
			
			$hist->mailid = $miss->id;
	  		$hist->time=time();
	  		$hist->userid = $USER->id;
	  		$hist->event="ghost";
	  		$hist->id = insert_record("internalmail_history",$hist);
	  		$ghost_hist = $hist->id;
//insertem en l'historic l'event enviat
				$hist->mailid=$miss->id;
	  		$hist->time=time();
	  		$hist->event="sent";
	  		$hist->userid=$USER->id;
	  		$hist->parent = $ghost_hist;
	  		$hist->id = insert_record("internalmail_history",$hist);
	  		
	  		
////enviem el missatge als diferents destinataris
//mirem is és algun cas especial (conevrsió de destinataris)
switch ($destiny) {
	case '[all]':
	case '[allcourse]':
		//volem enviar a tot el curs (només per a professors)
		//NOTA: all és una sinònim de allcourse
		//mirem si es professor
		if (isteacher($course->id,$USER->id)){
			//echo 'allcourse'.$course->id.'<br>';
			//carreguem la llista
			$dest_temps = get_course_users($course->id);
			$destinataries = array();
			foreach ($dest_temps as $dest_temp){
					$destinataries[] = $dest_temp->username;
			}
			//print_object($destinataries);
		} else {
			$error = 3;
		}
		break;
	case '[allstudents]':
		//echo 'allstudents<br>';
		//només als estudiants del curs (només per a professors)
				//mirem si es professor
		if (isteacher($course->id,$USER->id)){
			//carreguem la llista
			$dest_temps = get_course_students($course->id);
			$destinataries = array();
			foreach ($dest_temps as $dest_temp){
					$destinataries[] = $dest_temp->username;
			}
		} else {
			$error = 3;
		}
		break;
	case '[allteachers]':
		//echo 'allteachers<br>';
		//a tots els porfessors del curs
		//carreguem la llista
		$dest_temps = get_course_teachers($course->id);
		$destinataries = array();
		foreach ($dest_temps as $dest_temp){
				$destinataries[] = $dest_temp->username;
		}
		break;
	case '[allsite]':
		//echo 'allsite<br>';
		//enviar a tots els usuaris d ela plataforma (només per a administradors)
		//mirem si es administrador
		if (isadmin($USER->id)){
			//carreguem la llista
			$dest_temps = get_users();
			$destinataries = array();
			foreach ($dest_temps as $dest_temp){
					$destinataries[] = $dest_temp->username;
			}
		} else {
			$error = 3;
		}
		break;
	default:
		//echo 'normal<br>';
		//una direcció normal
		$destinataries=explode(',',$destiny);
}

//print_object($destinataries);

foreach($destinataries as $destinatari)
{
	$destinatary=get_record("user","username","$destinatari");
	$discuss=internalmail_get_user_discussion($destinatary->id);
	$ghost=$discuss->firstpost;
	
	/////////////enviar a cursos
	if($cm->course!==1)
	{
		$folder_course=$ghost+4;
		$post_courses=array();
		$post_courses=internalmail_get_child_posts($folder_course);
		if(empty($post_courses))
		{$post_courses=array();}
		foreach($post_courses as $post_course)
		{
			$aux=internalmail_get_subject($post_course);
			if($aux[2]==$cm->course)
			{$ghost=$post_course->id;}
		}
	}
	/////////////////////////////
	$inbox=$ghost+1; //el parent del missatge
	$subject_sql="RIO26::".$USER->id."::".$subject;
	
	//creem el missatge
	$miss->discussion=$discuss->id;
	$miss->parent=$inbox;
	$miss->oldparent=0;
	$miss->userid=$destinatary->id;
	$miss->created=$miss->modified= time();
	$miss->mailed=0;
	$miss->subject=$subject_sql;
	$miss->message=$message;
	$miss->format=1;
//	$miss->attachment="";
	$miss->totalscore=1;
	
	$miss->course = $course->id;

	if (! $miss->id = insert_record("internalmail_posts", $miss)) 
	{ //echo $miss->userid."<br>";
		//no s'ha pogut enviar a determinat destinatari a la bustia de curs
		if ($cm->course !== 1)
		{
			//echo'course!=1<br>';
	  		$miss->parent = ($discuss->firstpost)+1;
	  		$miss->course = 1;
	  		if (! $miss->id = insert_record("internalmail_posts", $miss))
	  		{
	  			//echo 'error<br>';
		  		$error=2; //no s'ha pogut enviar a determinat destinatari a la bustia principal
		  		$not_sent_array[]=$destinatari;
	  		}
		} 
	}
	else
	{
		if($attach!=NULL)
		{
			$miss->attachment=internalmail_add_attachment($attach,$id, $miss->id, $opt);	
			if($miss->attachment->error==1)
			{
				delete_records("internalmail_posts","id",$miss->id);
				$error=2; //no s'ha pogut enviar a determinat destinatari
  			$not_sent_array[]=$destinatary->username; 
			}
			else
			{
		  	set_field("internalmail_posts", "attachment", $miss->attachment, "id", $miss->id);
		  	$sms_curt="<br><H5>You have new Internalmail messages:<H5><br>".$msg_curt."..."."<br><br><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$id\">Go to Inbox</a>";
	  		message_post_message($admin, $destinatary, addslashes($sms_curt),1, 'direct');
	  		//insertem en l'historic l'event rebut
	  		$hist->mailid=$miss->id;
	  		$hist->parent = $ghost_hist;
	  		$hist->time=time();
	  		$hist->event="received";
	  		$hist->userid=$destinatary->id;
	  		$hist->id = insert_record("internalmail_history",$hist);
				
			}
		}
		else
		{
			$sms_curt="<br><H5>You have new Internalmail messages:<H5><br>".$msg_curt."..."."<br><br><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$id\">Go to Inbox</a>";
	  	message_post_message($admin, $destinatary, addslashes($sms_curt),1, 'direct');
	  	//insertem en l'historic l'event rebut
	  	$hist->mailid=$miss->id;
  		$hist->parent = $ghost_hist;
  		$hist->time=time();
  		$hist->event="received";
  		$hist->userid=$destinatary->id;
  		$hist->id = insert_record("internalmail_history",$hist);
		}
	}
}

//mirem el course copies....
if(!isteacher($cm->course,$USER->id))
{
	
	$teachers=get_records("internalmail_copiesenabled","courseid",$cm->course);
	if(empty($teachers))
	{
		$teachers= array();
	}
	foreach($teachers as $teacher)
	{

			$destinatary=get_record("user","id","$teacher->userid");
			$discuss=internalmail_get_user_discussion($destinatary->id);
			$ghost=$discuss->firstpost;
	/////////////enviar a cursos
			if($cm->course!==1)
			{
				$folder_course=$ghost+4;
				$post_courses=array();
				$post_courses=internalmail_get_child_posts($folder_course);
				if(empty($post_courses))
				{$post_courses=array();}
				foreach($post_courses as $post_course)
				{
					$aux=internalmail_get_subject($post_course);
					if($aux[2]==$cm->course)
						{$ghost=$post_course->id;}
				}
			}
	/////////////////////////////
			$inbox=$ghost+4; //copies
			$subject_sql="RIO26::".$USER->id."::".$subject;

	//creem el missatge
			$miss->discussion=$discuss->id;
			$miss->parent=$inbox;
			$miss->oldparent=0;
			$miss->userid=$destinatary->id;
			$miss->created=$miss->modified= time();
			$miss->mailed=0;
			$miss->subject=$subject_sql;
			$miss->message=$message;
			$miss->format=1;
//	$miss->attachment="";
			$miss->totalscore=1;

			$miss->course = $course->id;
			
			if (! $miss->id = insert_record("internalmail_posts", $miss)) 
			{
  			//$error=2; //no s'ha pogut enviar a determinat destinatari///////////////////////CONTROL ERRORRRRRRRRRRR
  			//$not_sent_array[]=$destinatary->username; 
			}
			else
			{
				if($attach!=NULL)
				{
					$miss->attachment=internalmail_add_attachment($attach,$id, $miss->id, $opt);	
					if($miss->attachment->error==1)
					{
						delete_records("internalmail_posts","id",$miss->id);
						$error=2; //no s'ha pogut enviar a determinat destinatari
  					$not_sent_array[]=$destinatary->username; 
					}
					else
					{
		  			set_field("internalmail_posts", "attachment", $miss->attachment, "id", $miss->id);
		  	//		$sms_curt="<br><H5>You have new Internalmail messages:<H5><br>".$msg_curt."..."."<br><br><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$id\">Go to Inbox</a>";
	  		//		message_post_message($admin, $destinatary, addslashes($sms_curt),1, 'direct');
	  		//insertem en l'historic l'event rebut
	  		$hist->mailid=$miss->id;
	  		$hist->parent = $ghost_hist;
	  		$hist->time=time();
	  		$hist->event="copies";
	  		$hist->userid=$destinatary->id;
	  		$hist->id = insert_record("internalmail_history",$hist);
				
					}
				}
				else
				{
			/*$sms_curt="<br><H5>You have new Internalmail messages:<H5><br>".$msg_curt."..."."<br><br><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$id\">Go to Inbox</a>";
	  	message_post_message($admin, $destinatary, addslashes($sms_curt),1, 'direct');
	  	*///insertem en l'historic l'event rebut
	  	$hist->mailid=$miss->id;
  		$hist->parent = $ghost_hist;
  		$hist->time=time();
  		$hist->event="copies";
  		$hist->userid=$destinatary->id;
  		$hist->id = insert_record("internalmail_history",$hist);
  		
				}
		}		
	}	
}
////// GORKA


switch($error)
{
	case 0:
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('enviat correctament','internalmail'),2);
		break;
	case 1:
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('error desconegut','internalmail'),2);
		break;
	case 2:
		notify(get_string('error destinataris','internalmail'));
		foreach($not_sent_array as $post)
		{
			notify("$post");
		}
		echo "<center><a href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$id\">Continue</a></center>";
		break;
	case 3:
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id",get_string('nopermission','internalmail'),2);
		break;
}



?>
