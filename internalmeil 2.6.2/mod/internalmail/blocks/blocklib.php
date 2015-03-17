<?php //$Id$

//This library includes all the necessary stuff to use blocks in course pages

if (!defined('BLOCK_MOVE_LEFT')) {
    define('BLOCK_MOVE_LEFT',   0x01);
}
if (!defined('BLOCK_MOVE_RIGHT')) {
    define('BLOCK_MOVE_RIGHT',  0x02);
}
if (!defined('BLOCK_MOVE_UP')) {
    define('BLOCK_MOVE_UP',     0x04);
}
if (!defined('BLOCK_MOVE_DOWN')) {
    define('BLOCK_MOVE_DOWN',   0x08);
}
if (!defined('BLOCK_CONFIGURE')) {
    define('BLOCK_CONFIGURE',   0x10);
}

if (!defined('BLOCK_POS_LEFT')) {
    define('BLOCK_POS_LEFT',  'l');
}
if (!defined('BLOCK_POS_RIGHT')) {
    define('BLOCK_POS_RIGHT', 'r');
}

//if (!defined('PAGE_MOD_VIEW')) {
define('INTERNALMAIL_PAGE_MOD_VIEW',  'mod_view');
//}

require_once($CFG->dirroot.'/mod/internalmail/blocks/pagelib.php');


// Simple entry point for anyone that wants to use blocks
function internalmail_blocks_setup(&$PAGE) {
    $pageblocks = internalmail_blocks_get_by_page($PAGE);
    //aquesta funció no sembla fer res, ara quan ho fagi fliparem
    internalmail_blocks_execute_url_action($PAGE, $pageblocks);
    return $pageblocks;
}

//retorna tots els blocs d'una pàgina
function internalmail_blocks_get_by_page($page) {
	//agafa tots els blocs que són del page->id ordenats per position i weight
    $blocks = get_records_select('internalmail_block_instance', 'pageid = '. $page->get_id(), 'position, weight');

    //això retorna un array amb dues posicions [0] i [1]
    $positions = $page->blocks_get_positions();
	//de fet, arr no caldria declarar-lo perquè el montem més avall
    $arr = array();
	
	//posa les posicions com a índexos de arr
    foreach($positions as $key => $position) {
        $arr[$position] = array();
    }

	//si no tenim blocks sortim
    if(empty($blocks)) {
        return $arr;
    }

	//aquí el que fem es posar cada bloc en el lateral. Concretament recorre cadascun
	//dels blocks i el va posant a l'array "arr". Les posicions són [l] esquerra i
	//[r] dreta.
    foreach($blocks as $block) {
        $arr[$block->position][$block->weight] = $block;
    }
    return $arr;
}

// You can use this to get the blocks to respond to URL actions without much hassle
//aquesta és la funció responsable de relaitzar les accions sobre els mòduls
//moure, esborrar, ocultar... la acció ve per get.
function internalmail_blocks_execute_url_action(&$PAGE, &$pageblocks) {
	//blockaction és l'acció que s eli ha de realitzar a un bloc (moure'l, esborrar-lo...)
    $blockaction = optional_param('blockaction');
	//echo 'blocklib 72|'.$blockaction.'|<br>';
	
	//si no hi ha cap acció a realitzar o l'usuari no pot realitzar accions, surt.
    if (empty($blockaction) || !$PAGE->user_allowed_editing() || !confirm_sesskey()) {
        return;
    }
	
	//agafem els paràmetres del get
    $instanceid  = optional_param('instanceid', 0, PARAM_INT);
    $blockid     = optional_param('blockid',    0, PARAM_INT);
    
    if (!empty($blockid)) {
        internalmail_blocks_execute_action($PAGE, $pageblocks, strtolower($blockaction), $blockid);

    }
    else if (!empty($instanceid)) {
        $instance = internalmail_blocks_find_instance($instanceid, $pageblocks);
        internalmail_blocks_execute_action($PAGE, $pageblocks, strtolower($blockaction), $instance);
    }
}

//funció que realitza la acció passada per get ($blockaction) sobre les blocs.
function internalmail_blocks_execute_action($page, &$pageblocks, $blockaction, $instanceorid) {
    global $CFG;

	//mirem si ens passen l'id de la instància o l'estructura d'aquesta.
    if (is_int($instanceorid)) {
        $blockid = $instanceorid;
    } else if (is_object($instanceorid)) {
        $instance = $instanceorid;
    }

    switch($blockaction) {
        case 'config':
			//todo: implements bloc configure
        break;
        case 'toggle':
		//canviar entre fer visible un bloc i fer-lo invisible
			if(empty($instance))  {
                error('Invalid block instance for '.$blockaction);
            }
            $instance->visible = ($instance->visible) ? 0 : 1;
            update_record('internalmail_block_instance', $instance);
        break;
        case 'delete':
			//esborrar una instància d'un bloc
            if(empty($instance))  {
                error('Invalid block instance for '. $blockaction);
            }
            internalmail_blocks_delete_instance($instance);
        break;
        case 'moveup':
			//movedown està comentat
            if(empty($instance))  {
                error('Invalid block instance for '. $blockaction);
            }

            if($instance->weight == 0) {
                // The block is the first one, so a move "up" probably means it changes position
                // Where is the instance going to be moved?
                $newpos = $page->blocks_move_position($instance, BLOCK_MOVE_UP);
                $newweight = (empty($pageblocks[$newpos]) ? 0 : max(array_keys($pageblocks[$newpos])) + 1);

                internalmail_blocks_execute_repositioning($instance, $newpos, $newweight);
            }
            else {
                // The block is just moving upwards in the same position.
                // This configuration will make sure that even if somehow the weights
                // become not continuous, block move operations will eventually bring
                // the situation back to normal without printing any warnings.
                if(!empty($pageblocks[$instance->position][$instance->weight - 1])) {
                    $other = $pageblocks[$instance->position][$instance->weight - 1];
                }
                if(!empty($other)) {
                    ++$other->weight;
                    update_record('internalmail_block_instance', $other);
                }
                --$instance->weight;
                update_record('internalmail_block_instance', $instance);
            }
        break;
        case 'movedown':
            if(empty($instance))  {
                error('Invalid block instance for '. $blockaction);
            }

			//mirem a quina posició es troba el bloc ara.
            if($instance->weight == max(array_keys($pageblocks[$instance->position]))) {
                // The block is the last one, so a move "down" probably means it changes position
                // Where is the instance going to be moved?
                $newpos = $page->blocks_move_position($instance, BLOCK_MOVE_DOWN);
                $newweight = (empty($pageblocks[$newpos]) ? 0 : max(array_keys($pageblocks[$newpos])) + 1);

                internalmail_blocks_execute_repositioning($instance, $newpos, $newweight);
            }
            else {
                // The block is just moving downwards in the same position.
                // This configuration will make sure that even if somehow the weights
                // become not continuous, block move operations will eventually bring
                // the situation back to normal without printing any warnings.
				
				//mirem si existeix alguna instància a la posició destí
                if(!empty($pageblocks[$instance->position][$instance->weight + 1])) {
                    $other = $pageblocks[$instance->position][$instance->weight + 1];
                }
				//si hi ha un altre bloc el posem a d'alt.
                if(!empty($other)) {
                    $other->weight--;
                    update_record('internalmail_block_instance', $other);
                }
				//baixem el nostre
                ++$instance->weight;
                update_record('internalmail_block_instance', $instance);
            }
        break;
        case 'moveleft':
            if(empty($instance))  {
                error('Invalid block instance for '. $blockaction);
            }

            // Where is the instance going to be moved?
			//mirem si va a la dreta o a l'esquerra
            $newpos = $page->blocks_move_position($instance, BLOCK_MOVE_LEFT);
			//definim l'altura on anirà (weight).
            $newweight = (empty($pageblocks[$newpos]) ? 0 : max(array_keys($pageblocks[$newpos])) + 1);

            internalmail_blocks_execute_repositioning($instance, $newpos, $newweight);
        break;
		
        case 'moveright':
            if(empty($instance))  {
                error('Invalid block instance for '. $blockaction);
            }

            // Where is the instance going to be moved?
            $newpos    = $page->blocks_move_position($instance, BLOCK_MOVE_RIGHT);
            $newweight = (empty($pageblocks[$newpos]) ? 0 : max(array_keys($pageblocks[$newpos])) + 1);

            internalmail_blocks_execute_repositioning($instance, $newpos, $newweight);
        break;
        case 'add':
            // Add a new instance of this block, if allowed
            $block = internalmail_blocks_get_record($blockid);

			//mirem si el block existeix i si és visible
            if(empty($block) || !$block->visible) {
                // Only allow adding if the block exists and is enabled
                return false;
            }

			//mirem si exiteix ja alguna instància del bloc i si se'n pot crear més d'una
            if(!$block->multiple && internalmail_blocks_find_block($blockid, $pageblocks) !== false) {
                // If no multiples are allowed and we already have one, return now
                return false;
            }

			//mirem en quin costat s'ha de posar per defecte
            $newpos = $page->blocks_default_position();
			//agafem la posició en la qual es pot posar el bloc dins de la pàgina (el weight)
            $weight = get_record_sql('SELECT 1, max(weight) + 1 AS nextfree FROM '.$CFG->prefix.'internalmail_block_instance WHERE pageid = '.$page->get_id().' AND position = \''. $newpos .'\''); 

			//preparem les dades per posar-les a la taula de blocs.
            $newinstance = new stdClass;
            $newinstance->blockid    = $blockid;
            $newinstance->pageid     = $page->get_id();
            $newinstance->pagetype   = $page->get_type();
            $newinstance->position   = $newpos;
            $newinstance->weight     = empty($weight->nextfree) ? 0 : $weight->nextfree;
            $newinstance->visible    = 1;
            $newinstance->configdata = '';
			//insertem una nova instància a la taula de blockinstances.
            insert_record('internalmail_block_instance', $newinstance);
        break;
    }

    // In order to prevent accidental duplicate actions, redirect to a page with a clean url
      
    redirect($page->url_get_full());
}

//funció per eliminar una instància concreta dins de blocinstances
function internalmail_blocks_delete_instance($instance) {
    global $CFG;

    delete_records('internalmail_block_instance', 'id', $instance->id);
    // And now, decrement the weight of all blocks after this one
    execute_sql('UPDATE '.$CFG->prefix.'internalmail_block_instance SET weight = weight - 1 WHERE
				pageid = '.$instance->pageid.' AND position = \''.$instance->position.
                '\' AND weight > '.$instance->weight, false);
}

//funció que recoloca un bloc en una posició concreta
// This shouldn't be used externally at all, it's here for use by internalmail_blocks_execute_action()
// in order to reduce code repetition.
function internalmail_blocks_execute_repositioning(&$instance, $newpos, $newweight) {
    global $CFG;

    // If it's staying where it is, don't do anything
    if($newpos == $instance->position) {
        return;
    }

    // Close the weight gap we 'll leave behind
    execute_sql('UPDATE '. $CFG->prefix .'internalmail_block_instance SET weight = weight - 1 WHERE 
                      pageid = '. $instance->pageid .' AND position = \'' .$instance->position.
                      '\' AND weight > '. $instance->weight,
                false);

    $instance->position = $newpos;
    $instance->weight   = $newweight;

    update_record('internalmail_block_instance', $instance);
}

//funció que retorna la instància d'un bloc a partir de la matriu
//de blocs de la pàgina
function internalmail_blocks_find_instance($instanceid, $blocksarray) {
    foreach($blocksarray as $subarray) {
        foreach($subarray as $instance) {
            if($instance->id == $instanceid) {
                return $instance;
            }
        }
    }
    return false;
}

// This iterates over an array of blocks and calculates the preferred width
// Parameter passed by reference for speed; it's not modified.
//$instances és un array amb la informació dels blocks que es posen en un dels laterals
//de la pàgina. Això vols dir un dels dos components que genera blocks_setup($PAGE)
//sobre la pàgina actual.
function internalmail_blocks_preferred_width(&$instances) {
	//li suposem 0 a width.
    $width = 0;

	//si no hi ha blocs en aquest costat retornem el zero
    if(empty($instances) || !is_array($instances)) {
        return 0;
    }
	
	//recorrem cadascun dels blocs
    foreach($instances as $instance) {
		//només tenim en compte els blocs visibles per l'usuari
        if(!$instance->visible) {
            continue;
        }
		
        $block = internalmail_blocks_get_record($instance->blockid);
		
		//executem la funció preferred_width de la classe del block
        $pref = internalmail_block_method_result($block->name, 'preferred_width');
		
		//si no en te definit cap simplement no es te en compte
        if($pref === NULL) {
            continue;
        }
		//agafem el màxim
        if($pref > $width) {
            $width = $pref;
        }
    }
    return $width;
}

//retorna la informació d'un block a partir de la seva ID (de la taula internalmail_block)
//invalidate fa tornar a consultar el blocks disponibles per comptes de fer-ho dos cops.
function internalmail_blocks_get_record($blockid = NULL, $invalidate = false) {
    static $cache = NULL;

    if($invalidate || empty($cache)) {
		//agafem tots els blocks
        $cache = get_records('internalmail_block');
    }

	//si la id és incorrecte retorna null.
    if($blockid === NULL) {
        return $cache;
    }
    return (isset($cache[$blockid])? $cache[$blockid] : false);
}

//This function retrieves a method-defined property of a class WITHOUT instantiating an object
//It seems that the only way to use the :: operator with variable class names is eval() :(
//For caveats with this technique, see the PHP docs on operator ::
function internalmail_block_method_result($blockname, $method) {
    if(!internalmail_block_load_class($blockname)) {
        return NULL;
    }
	//echo 'blocklib 160:return block_'.$blockname.'::'.$method.'();<br>';
    return eval('return block_'.$blockname.'::'.$method.'();');
}

//This function loads the necessary class files for a block
//Whenever you want to load a block, use this first
function internalmail_block_load_class($blockname) {
    global $CFG;

    if (empty($blockname)) {
        return false;
    }

    include_once($CFG->dirroot.'/blocks/moodleblock.class.php');
    $classname = 'block_'.$blockname;
    include_once($CFG->dirroot.'/mod/internalmail/blocks/'.$blockname.'/block_'.$blockname.'.php');
	
    // After all this, return value indicating success or failure
    return class_exists($classname);
}

// Accepts an array of block instances and checks to see if any of them have content to display
// (causing them to calculate their content in the process). Returns true or false. Parameter passed
// by reference for speed; the array is actually not modified.
//@param pageblocks és la matriu dels blocks que crea blocks_setup.
//@param position és el costat (dret i esquerra)
function internalmail_blocks_have_content(&$pageblocks, $position) {
	//mirem els blocks del costat que volem mirar
    foreach($pageblocks[$position] as $instance) {
		//mirem si es visible
        if(!$instance->visible) {
            continue;
        }
		//mirem si el tipus de bloc de la instància existeix
        if(!$record = internalmail_blocks_get_record($instance->blockid)) {
            continue;
        }
        if(!$obj = internalmail_block_instance($record->name, $instance)) {
            continue;
        }
		//mirem si la inst'ancia té contingut
        if(!$obj->is_empty()) {
            return true;
        }
    }

    return false;
}

//This function creates a new object of the specified block class
function internalmail_block_instance($blockname, $instance = NULL) {
	//inicialitzem la classe si no ho estava ja.
    if(!internalmail_block_load_class($blockname)) {
        return false;
    }
	//carreguem el nom de la classe
    $classname = 'block_'.$blockname;
	//inicialitzem l'objecte de la classe
    $retval = new $classname;
	//adapta a la instància concreta
    if($instance !== NULL) {
        $retval->_load_instance($instance);
    }
    return $retval;
}

// This function prints one group of blocks in a page
// Parameters passed by reference for speed; they are not modified.
function internalmail_blocks_print_group(&$page, &$pageblocks, $position) {

	//definim el númdero de blocs que hi ha
    if(empty($pageblocks[$position])) {
		//si no hi ha blocs per posar inicialitzem el vector
		//i indiquem que se n'han de posar 0
        $pageblocks[$position] = array();
        $maxweight = 0;
    }
    else {
		//si n'existeixien n'agafem el màxim
        $maxweight = max(array_keys($pageblocks[$position]));
    }

	//mirem si està en editar
    $isediting = $page->user_is_editing();
    //echo $page->courserecord->id;

	//reccòrrem tots el blocs
    foreach($pageblocks[$position] as $instance) {
	
		//agafem les dades del tipus de bloc
        $block = internalmail_blocks_get_record($instance->blockid);
		
		//si es invisible no la treiem
        if(!$block->visible) {
            // Disabled by the admin
            continue;
        }

		//creem l'objecte del bloc
        if (!$obj = internalmail_block_instance($block->name, $instance)) {
            // Invalid block
            continue;
        }

        if ($isediting) {
			//afegim els controls d'edició
            $options = 0;
            // The block can be moved up if it's NOT the first one in its position. If it is, we look at the OR clause:
            // the first block might still be able to move up if the page says so (i.e., it will change position)
            $options |= BLOCK_MOVE_UP    * ($instance->weight != 0          || ($page->blocks_move_position($instance, BLOCK_MOVE_UP)   != $instance->position));


            // Same thing for downward movement
            $options |= BLOCK_MOVE_DOWN  * ($instance->weight != $maxweight || ($page->blocks_move_position($instance, BLOCK_MOVE_DOWN) != $instance->position));
            // For left and right movements, it's up to the page to tell us whether they are allowed
            $options |= BLOCK_MOVE_RIGHT * ($page->blocks_move_position($instance, BLOCK_MOVE_RIGHT) != $instance->position);
            $options |= BLOCK_MOVE_LEFT  * ($page->blocks_move_position($instance, BLOCK_MOVE_LEFT ) != $instance->position);
            // Finally, the block can be configured if the block class either allows multiple instances, or if it specifically
            // allows instance configuration (multiple instances override that one). It doesn't have anything to do with what the
            // administrator has allowed for this block in the site admin options.
			//LA PART DE CONFIGURACIÓ D'UN BLOC ENCARA NO ESTÀ HABILITADA
            //$options |= BLOCK_CONFIGURE * (
            //$obj->instance_allow_multiple() ||
            //$obj->instance_allow_config() );

            //$obj->_add_edit_controls($options);
	    // Remove this function on Wed Feb 14 2007 and added a new
	    // function to this library. - MK
	    internalmail_add_edit_controls($options, $obj);
        }

        if(!$instance->visible) {
            if($isediting) {
		//si la aplicació no es visible pe`ro estem editant, s'ha de
		//mostrar la barra d'eines (shadow)
                $obj->_print_shadow();
            }
        }
        else {
            $obj->_print_block();
        }
    }

	//aquí treiem per pantalla el bloc per afegir un nou bloc a la pàgina
	//només el treiem si estem al bloc de la dreta al final de tot.
    if($page->blocks_default_position() == $position && $page->user_is_editing()) {
       internalmail_blocks_print_adminblock($page, $pageblocks);
    }
}


/**
 * Sets class $edit_controls var with correct block manipulation links.
 *
 * @uses $CFG
 * @uses $USER
 * @param stdObject $options ?
 * @todo complete documenting this function. Define $options.
 */
function internalmail_add_edit_controls($options, &$obj) {
      
    global $CFG, $USER, $PAGE;

    // this is the context relevant to this particular block
    // instance
    //$blockcontext = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
        
    // context for site or course, i.e. participant list etc
    // check to see if user can edit site or course blocks.
    // blocks can appear on other pages such as mod and blog pages...
        
    switch ($obj->instance->pagetype) {
    case 'course-view':
	if ($obj->instance->pageid == SITEID) {
	    $context = get_context_instance(CONTEXT_SYSTEM, $obj->instance->pageid);
	} else {
	    $context = get_context_instance(CONTEXT_COURSE, $obj->instance->pageid);
	}
                
	if (!has_capability('moodle/site:manageblocks', $context)) {
	    return null;
	}
	break;
    default:

	break;  
    }
                
    if (!isset($obj->str)) {
	$obj->str->delete    = get_string('delete');
	$obj->str->moveup    = get_string('moveup');
	$obj->str->movedown  = get_string('movedown');
	$obj->str->moveright = get_string('moveright');
	$obj->str->moveleft  = get_string('moveleft');
	$obj->str->hide      = get_string('hide');
	$obj->str->show      = get_string('show');
	$obj->str->configure = get_string('configuration');
	$obj->str->assignroles = get_string('assignroles', 'role');
    }

    $movebuttons = '<div class="commands">';

    if ($obj->instance->visible) {
	$icon = '/t/hide.gif';
	$title = $obj->str->hide;
    } else {
	$icon = '/t/show.gif';
	$title = $obj->str->show;
    }

    if (empty($obj->instance->pageid)) {
	$obj->instance->pageid = 0;
    }
    if (!empty($PAGE->type) and ($obj->instance->pagetype == $PAGE->type) and $obj->instance->pageid == $PAGE->id) {
	$page = $PAGE;
    } else {
	$page = page_create_object($this->instance->pagetype, $obj->instance->pageid);
    }
    $script = $page->url_get_full(array('instanceid' => $obj->instance->id, 'sesskey' => $USER->sesskey));

    // place holder for roles button
    /*   if ( $blockcontext ) {
     $movebuttons .= '<a class="icon roles" title="'. $obj->str->assignroles .'" href="'.$CFG->wwwroot.'/'.$CFG->admin.'/roles/assign.php?contextid='.$blockcontext->id.'">' .
     '<img src="'.$CFG->pixpath.'/i/roles.gif" alt="'.$obj->str->assignroles.'" height="11" width="11" border="0"/></a>';
     }*/

    $movebuttons .= '<a class="icon hide" title="'. $title .'" href="'.$script.'&amp;blockaction=toggle">' .
	'<img src="'. $CFG->pixpath.$icon .'" alt="'.$title.'" /></a>';

    if ($options & BLOCK_CONFIGURE && $obj->user_can_edit()) {
	$movebuttons .= '<a class="icon edit" title="'. $obj->str->configure .'" href="'.$script.'&amp;blockaction=config">' .
	    '<img src="'. $CFG->pixpath .'/t/edit.gif" alt="'. $obj->str->configure .'" /></a>';
    }

    $movebuttons .= '<a class="icon delete" title="'. $obj->str->delete .'" href="'.$script.'&amp;blockaction=delete">' .
	'<img src="'. $CFG->pixpath .'/t/delete.gif" alt="'. $obj->str->delete .'" /></a>';

    if ($options & BLOCK_MOVE_LEFT) {
	$movebuttons .= '<a class="icon left" title="'. $obj->str->moveleft .'" href="'.$script.'&amp;blockaction=moveleft">' .
	    '<img src="'. $CFG->pixpath .'/t/left.gif" alt="'. $obj->str->moveleft .'" /></a>';
    }
    if ($options & BLOCK_MOVE_UP) {
	$movebuttons .= '<a class="icon up" title="'. $obj->str->moveup .'" href="'.$script.'&amp;blockaction=moveup">' .
	    '<img src="'. $CFG->pixpath .'/t/up.gif" alt="'. $obj->str->moveup .'" /></a>';
    }
    if ($options & BLOCK_MOVE_DOWN) {
	$movebuttons .= '<a class="icon down" title="'. $obj->str->movedown .'" href="'.$script.'&amp;blockaction=movedown">' .
	    '<img src="'. $CFG->pixpath .'/t/down.gif" alt="'. $obj->str->movedown .'" /></a>';
    }
    if ($options & BLOCK_MOVE_RIGHT) {
	$movebuttons .= '<a class="icon right" title="'. $obj->str->moveright .'" href="'.$script.'&amp;blockaction=moveright">' .
	    '<img src="'. $CFG->pixpath .'/t/right.gif" alt="'. $obj->str->moveright .'" /></a>';
    }

    $movebuttons .= '</div>';
    $obj->edit_controls = $movebuttons;
}


//This function prints the block to admin blocks as necessary
//concretament treu el bloc per afegir nous blocs.
function internalmail_blocks_print_adminblock(&$page, &$pageblocks) {
    global $USER;

    $missingblocks = internalmail_blocks_get_missing($page, $pageblocks);
    if (!empty($missingblocks)) {
        $strblocks = get_string('blocks');
        $stradd    = get_string('add');
        foreach ($missingblocks as $blockid) {
            $block = internalmail_blocks_get_record($blockid);
            $blockobject = internalmail_block_instance($block->name);
            if ($blockobject === false) {
				//si no existeix l'objecte no el posem a la llista
                continue;
            }
            $menu[$block->id] = $blockobject->get_title();
        }
		
		//ordenem la llista per nom de bloc
        asort($menu);

        $target = $page->url_get_full(array('sesskey' => $USER->sesskey, 'blockaction' => 'add'));
        $content = popup_form($target.'&amp;blockid=', $menu, 'add_block', '', $stradd .'...', '', '', true);

        print_side_block($strblocks, $content, NULL, NULL, NULL, array('class' => 'block_adminblock'));
    }
}

// This function returns an array with the IDs of any blocks that you can add to your page.
// Parameters are passed by reference for speed; they are not modified at all.
function internalmail_blocks_get_missing(&$page, &$pageblocks) {

    $missingblocks = array();
    $allblocks = internalmail_blocks_get_record();
    //$pageformat = $page->get_format_name();

    if(!empty($allblocks)) {
        foreach($allblocks as $block) {
            if($block->visible && (!internalmail_blocks_find_block($block->id, $pageblocks) || $block->multiple)) {
                // And if it's applicable for display in this format...
				//AQUESTA COMPROBACIÓ ENS LA SALTEM PQ TOTS ELS BLOCS SÓN DEL MÒDUL
                //if(blocks_name_allowed_in_format($block->name, $pageformat)) {
                    // ...add it to the missing blocks
                $missingblocks[] = $block->id;
                //}
            }
        }
    }
    return $missingblocks;
}

//mira si un bloc existeix ja a la pàgina o no
function internalmail_blocks_find_block($blockid, $blocksarray) {
    foreach($blocksarray as $blockgroup) {
        foreach($blockgroup as $instance) {
            if($instance->blockid == $blockid) {
                return $instance;
            }
        }
    }
    return false;
}
?>
