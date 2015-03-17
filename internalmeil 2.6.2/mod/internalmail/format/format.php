<?php
  /**
   * Module Blocked Format for Moodle
   *
   * @authors David Castro & Ferran Recio
   */
    
  /*optional_variable($id);      // Course Module ID
   optional_variable($option);  // Option selected
   optional_variable($post);    // e-mail to show
   optional_variable($reply);
   optional_variable($page);*/
	
$id = optional_param('id', 0, PARAM_INT);    // Course Module ID
$option = optional_param('option', 0, PARAM_INT); // Option selected
$post = optional_param('post', 0, PARAM_INT);    // e-mail to show
$reply = optional_param('reply', 0, PARAM_INT);    
$page = optional_param('page', 0, PARAM_INT);

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
	
//crec que mostrar un tòpic concret (NO TE PERQUÊ SER NECESSARI)
if (isteacher($course->id) and isset($marker) and confirm_sesskey()) {
    $course->marker = $marker;
    if (! set_field("course", "marker", $marker, "id", $course->id)) {
	error("Could not mark that topic for this course");
    }
}

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

/// Layout the whole page as three big columns.
echo '<table id="layout-table"><tr>';

/// The left column ...

//mirem si hi ha blocs per posar al costat esquerra
/*foreach ($pageblocks as $t => $s ) {
    echo "$t <br>";
    foreach ( $s as $f => $e ) {
	echo "$f => " . print_r($e) . "<br>";
    }
}*/
	
if (internalmail_blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
    echo '<td style="width: '.$preferred_width_left.'px;" id="left-column">';
    internalmail_blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
    echo '</td>';
}

/// Start main column
echo '<td id="middle-column">';

//titol del blocs central
//print_heading_block('internalmail', 'outline');
print_heading_block($internalmail->name, 'outline');

//comencem la taula amb el contingut
echo '<table class="topics" width="100%"><tr><td>';

/// Print Section 0: EL NOSTRE MAIN

$section = 0;
//$thissection = $sections[$section];

//AQUÍ CRIDAREM LA FUNCIÓ PER MOSTRAR EL CONTINGUT DEL MÒDUL
//global $cm;
//echo "HERE: $cm->id, $cm->course<br />";
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

internalmail_print_content($id,$option,$post,$reply,$page, $context, $cm);

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

?>