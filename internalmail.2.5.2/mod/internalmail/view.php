<?PHP  // $Id: view.php,v 1.1 2003/09/30 02:45:19 moodler Exp $

/// This page prints a particular instance of internalmail
/// (Replace internalmail with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");
    //module libraries
  	require_once("format/formatlib.php");
	  require_once($CFG->dirroot.'/mod/internalmail/blocks/blocklib.php');

    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
   	$a  = optional_param('a', 0, PARAM_INT);     // internalmail ID
   	$reply  = optional_param('reply', 0, PARAM_INT);; //when we reply a message

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
    
    
    //mirem que user tingui bustia al curs
    $err=0;
    if($course->id!=1){

    	$subj="CUR26::".$USER->id."::".$course->id;  //busquem el fantasma del curs per a veure si t� bustia en aquell curs
    	$sql="SELECT p.* FROM {$CFG->prefix}internalmail_posts p WHERE p.subject = '$subj'";
    	$havemailbox=get_record_sql($sql);
		if(internalmail_count_user_course_mailboxes()==0) {
			if(!internalmail_add_user_mailbox($USER->id, $course->id))
			{	
				error("Could not add mailbox for user id $USER->id!");
			}
		}		
    }
    else{ //curs general
     	$disc=$discussion=internalmail_get_user_discussion($USER->id);
    	if(empty($disc)){
    		//$err=1;
    		if(!internalmail_add_user_mailbox($USER->id, 1))
			{
				error("Could not add mailbox for user id $USER->id!");
			}
		
    	}
  	}
		       
	if($err){
		error(get_string('no_mailbox','internalmail'));
				
	}
    add_to_log($course->id, "internalmail", "view", "view.php?id=$cm->id", "$internalmail->id");

//AQUESTA ESTRUCTURA �S LA QUE ES FA SERVIR PER FER SERVIR ELS BLOCKS
    $PAGE = page_create_object(PAGE_MOD_VIEW, $cm->instance);

	//AQUESTA FUNCI� �S IMPRESCINDIBLE PER ALS BLOCKS
	$pageblocks = blocks_setup($PAGE);
	/*la informaci� sobre cada bloc est� a la BD dins de mdl_block_instance i �s:
		[id] => clau prim�ria del moodel
		[blockid] => tipus de bloc
		[pageid] => id de la p�gina (per get)
		[pagetype] => demoment son totes course-view
		[position] => 'l' left o 'r' right
		[weight] => pes, �s la posici� que ocupa (1r, 2n... comen�ant des de d'alt)
		[visible] => si �s visible (1 o 0)
		[configdata] => �? de moment tots estan buits.*/
	

	//POSEM TOT LO DE EDICI�
	//$USER->editing �s la variable que defineix si est� o no actiavada l'edici�
    if (!isset($USER->editing)) {
        $USER->editing = false;
    }

	//aquest if mira si es pot posar en editar o no
	//$USER->editing �s el que habilita les opcions d'edici� o no.
    if ($PAGE->user_allowed_editing()) {
		//mirem si est� activat o desactivat l'edit (passat per GET)
        if ($edit == 'on') {
			//posem a true l'edici�
            $USER->editing = true;
        } else if ($edit == 'off') {
			//posem a false l'edici�
            $USER->editing = false;
            if(!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
                $USER->activitycopy       = false;
                $USER->activitycopycourse = NULL;
            }
        }
    } else {
        $USER->editing = false;
    }



/// Print the page header

    if ($course->category) {
        $navigation = "<A HREF=\"../../course/view.php?id=$course->id\">$course->shortname</A> ->";
    }

    $strinternalmails = get_string("modulenameplural", "internalmail");
    $strinternalmail  = get_string("modulename", "internalmail");


    print_header("$course->shortname: $strinternalmail", "$course->fullname",
                 "$navigation <A HREF=index.php?id=$course->id>$strinternalmails</A> -> $internalmail->name", 
                  "", "", true, block_module_modification_buttons($cm->id, $course->id, $strBLOCKMODULE), 
                  navmenu($course, $cm));


/// Print the main part of the page

	
	echo '<div class="course-content">';  // course wrapper start
	
	require("$CFG->dirroot/mod/internalmail/format/format.php");  // Include the actual course format

  	echo '</div>';  // content wrapper end




/// Finish the page
    print_footer($course);

?>
