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

//mirem que user tingui bustia al curs
$err=0;
if($course->id!=1){

    $subj="CUR26::".$USER->id."::".$course->id;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
    $sql="SELECT p.* FROM {$CFG->prefix}internalmail_posts p WHERE p.subject = '$subj'";
    $havemailbox = get_record_sql($sql);
    if(internalmail_count_user_course_mailboxes()==0) {
	if(!internalmail_add_user_mailbox($USER->id, $course->id))  {	
	    error("Could not add mailbox for user id $USER->id!", "../../course/view.php?id=$course->id");
	}
    }
} else { //curs general
    $disc = $discussion = internalmail_get_user_discussion($USER->id);
    if(empty($disc)){
	//$err=1;
	if(!internalmail_add_user_mailbox($USER->id, 1)) {
	    error("Could not add mailbox for user id $USER->id!", "../../course/view.php?id=$course->id");
	}
    }
}
		       
if ( $err ) {
    error(get_string('no_mailbox','internalmail'), "../../course/view.php?id=$course->id");
}

add_to_log($course->id, "internalmail", "view", "view.php?id=$cm->id", "$internalmail->id");

//AQUESTA ESTRUCTURA ÉS LA QUE ES FA SERVIR PER FER SERVIR ELS BLOCKS

$PAGE = internalmail_page_create_object(INTERNALMAIL_PAGE_MOD_VIEW, $cm->instance, $course->id);

//AQUESTA FUNCIÓ ÉS IMPRESCINDIBLE PER ALS BLOCKS
$pageblocks = internalmail_blocks_setup($PAGE);

/*la informació sobre cada bloc està a la BD dins de mdl_block_instance i és:
 [id] => clau primària del moodel
 [blockid] => tipus de bloc
 [pageid] => id de la pàgina (per get)
 [pagetype] => demoment son totes course-view
 [position] => 'l' left o 'r' right
 [weight] => pes, és la posició que ocupa (1r, 2n... començant des de d'alt)
 [visible] => si és visible (1 o 0)
 [configdata] => ¿? de moment tots estan buits.*/

//POSEM TOT LO DE EDICIÓ
//$USER->editing és la variable que defineix si està o no actiavada l'edició
if (!isset($USER->editing)) {
    $USER->editing = false;
}

//aquest if mira si es pot posar en editar o no
//$USER->editing és el que habilita les opcions d'edició o no.
if ($PAGE->user_allowed_editing()) {
    $edit = optional_param('edit', -1, PARAM_BOOL);
    //mirem si està activat o desactivat l'edit (passat per GET)
    if ($edit == 'on') {
	//posem a true l'edició
	$USER->editing = true;
    } else if ($edit == 'off') {
	//posem a false l'edició
	$USER->editing = false;
	if(!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
	    $USER->activitycopy       = false;
	    $USER->activitycopycourse = NULL;
	}
    }
} else {
    $USER->editing = false;
}

// Print the page header

$navigation = "";
if ($course->category) {
    $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}

$strinternalmails = get_string("modulenameplural", "internalmail");
$strinternalmail  = get_string("modulename", "internalmail");

// Lets make sure that any person trying to compose only use the compose.php
if ( $option == 1 ) {
    $query = $_SERVER["QUERY_STRING"];
    $aux = split('&',$query);
    $link = 'compose.php?id=' . $id; 
    $values_set = array('id');
    foreach ( $aux as $aa ) {
	$values = explode('=',$aa);
	if (count($values) == 2 ) {
	    if (!in_array($values[0], $values_set)) {
		$link .= "&". $values[0] .'=' . $values[1];
		array_push($values_set, $values[0]);
	    }
	}
    }

    redirect($link);
}

print_header("$course->shortname: $strinternalmail", "$course->fullname",
	     "$navigation <a href=index.php?id=$course->id>$strinternalmails</a> -> $internalmail->name", 
	     "", "", true, block_module_modification_buttons($cm->id, $course->id, $strinternalmail), 
	     navmenu($course, $cm));

// Print the main part of the page
	
echo '<div class="course-content">';  // course wrapper start
	
require("$CFG->dirroot/mod/internalmail/format/format.php");  // Include the actual course format

echo '</div>';  // content wrapper end

// Finish the page
print_footer($course);

?>
