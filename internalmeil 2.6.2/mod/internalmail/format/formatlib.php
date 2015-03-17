<?php
/**
 * Auxiliar functions to work with BLocks into modules
 *
 * @authors Ferran Recio & David Castro
 * @package pages
 */
 
//funció que retorna una taula amb els dos botons d'edició
function block_module_modification_buttons ($modid,$courseid, $modulename){
	//montem els botons d'edició
	$button1 = block_edition_button($modid,$courseid);
	$button2 = update_module_button($modid,$courseid, $modulename);
	
	//montem la taula
	$res = "<TABLE border=0 cellpadding=0 cellspacing=0>
			<TR>
				<TD>$button1</TD>
				<TD>$button2</TD>
			</TR>
		</TABLE>";
	
	//retornem el resultat
	return $res;
}

/**
 * Returns a turn edit on/off button for modules in a self contained form.
 *
 * @uses $CFG
 * @uses $USER
 * @param int $modid The mod  to update by id as found in internalmail table
 * @return string
 */
function block_edition_button($modid,$courseid) {

    global $CFG, $USER;

	//mirem si és un professor que pot editar
    if (isteacheredit($courseid)) {
		//mirem si s'està editant o ja s'ha editat
        if (!empty($USER->editing)) {
            $string = get_string('turneditingoff');
            $edit = 'off';
        } else {
            $string = get_string('turneditingon');
            $edit = 'on';
        }
        return "<form target=\"$CFG->framename\" method=\"get\" action=\"$CFG->wwwroot/mod/internalmail/view.php\">".
               "<input type=\"hidden\" name=\"id\" value=\"$modid\" />".
               "<input type=\"hidden\" name=\"edit\" value=\"$edit\" />".
               "<input type=\"submit\" value=\"$string\" /></form>";
    }
}
?>