<?php //$Id: pagelib.php,v 1.32 2005/03/02 03:43:41 defacer Exp $

/**
 * This file contains the parent class for moodle pages, page_base, 
 * as well as the page_course subclass.
 * A page is defined by its page type (ie. course, blog, activity) and its page id
 * (courseid, blogid, activity id, etc).
 *
 * @authors David Castro & Ferran Recio
 * @version  $Id: pagelib.php,v 1.32 2005/03/02 03:43:41 defacer Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package pages
 */

/**
 * Factory function page_create_object(). Called with a pagetype identifier and possibly with
 * its numeric ID. Returns a fully constructed page_base subclass you can work with.
 */

//Crec que el paràmetre type no caldria passar-lo
function internalmail_page_create_object($type, $id = NULL, $courseid) {
    global $CFG;

    $data = new stdClass;
    $data->pagetype = $type;
    $data->pageid   = $id;
    $data->courseid = $courseid;

    $classname = internalmail_page_map_class($type);
	
    $object = &new $classname;
    // TODO: subclassing check here
	
    if ($object->get_type() !== $type) {
        // s'ha comés un error
        if ($CFG->debug > 7) {
            error('Page object\'s type ('. $object->get_type() .') does not match requested type ('. $type .')');
        }
    }

    $object->init_quick($data);
    return $object;
}

/**
 * Function page_map_class() is the way for your code to define its own page subclasses and let Moodle recognize them.
 * Use it to associate the textual identifier of your Page with the actual class name that has to be instantiated.
 */

 //AQUESTA FUNCIÓ NOMÉS RETORNA 'PAGE_MOD' I ES PODRIA TREURE EN EL FUTUR
function internalmail_page_map_class($type, $classname = NULL) {
    global $CFG;

    static $mappings = NULL;
    if ($mappings === NULL) {
        $mappings = array(
            INTERNALMAIL_PAGE_MOD_VIEW => 'internalmail_page_mod'
        );	
    }

    if (!empty($type) && !empty($classname)) {
        $mappings[$type] = $classname;
    }

    if (!isset($mappings[$type])) {
        if ($CFG->debug > 7) {
            error('Page class mapping requested for unknown type: '.$type);
        }
    }

    if (!class_exists($mappings[$type])) {
        if ($CFG->debug > 7) {
            error('Page class mapping for id "'.$type.'" exists but class "'.$mappings[$type].'" is not defined');
        }
    }

    return $mappings[$type];
}

/**
 * Parent class from which all Moodle page classes derive
 *
 * @authors David Castro & Ferran Recio
 * @package pages
 */

class internalmail_page_base {

    /**
     * The string identifier for the type of page being described.
     * @var string $type
     */
    var $type           = NULL;

    /**
     * The numeric identifier of the page being described.
     * @var int $id
     */
    var $id             = NULL;

    /**
     * Class bool to determine if the instance's full initialization has been completed.
     * @var boolean $full_init_done
     */
    var $full_init_done = false;

}

/**
 * Class that models the behavior of a moodle course
 *
 * @authors Ferran Recio & David Castro
 * @package pages
 */

class internalmail_page_mod extends internalmail_page_base {

	//retorna el tipus de classe que és (de futura eliminació)
    function get_type() {
        return INTERNALMAIL_PAGE_MOD_VIEW;
    }
	
    // Here you should load up all heavy-duty data for your page. Basically everything that
    // does not NEED to be loaded for the class to make basic decisions should NOT be loaded
    // in init_quick() and instead deferred here. Of course this function had better recognize
    // $this->full_init_done to prevent wasteful multiple-time data retrieval.
    function init_full() {
        if($this->full_init_done) {
            return;
        }
        if (empty($this->id)) {
            $this->id = 0; // avoid db errors
        }
	//error($this->courseid);

	/*if (! $cm = get_record("course_modules", "id", $this->id)) {
	    error("Course Module ID was incorrect");
	}*/

        $this->courserecord = get_record('course', 'id', $this->courseid);
        if(empty($this->courserecord) && !defined('ADMIN_STICKYBLOCKS')) {
            error('Cannot fully initialize page: invalid course id '. $this->id);
        }
        $this->full_init_done = true;
    }

	// Do any validation of the officially recognized bits of the data and forward to parent.
    // Do NOT load up "expensive" resouces (e.g. SQL data) here!
    function init_quick($data) {
        if(empty($data->pageid)) {
            error('Cannot quickly initialize page: empty course id');
        }
		//copíem les dades que ha passat el mòdul
        $this->type = $data->pagetype;
        $this->id   = $data->pageid;
        $this->courseid   = $data->courseid;
    }
	
	// Simple stuff, do not override this. (original de page_base)
    function get_id() {
        return $this->id;
    }
	
    // Which are the positions in this page which support blocks? Return an array containing their identifiers.
    // BE CAREFUL, ORDER DOES MATTER! In textual representations, lists of blocks in a page use the ':' character
    // to delimit different positions in the page. The part before the first ':' in such a representation will map
    // directly to the first item of the array you return here, the second to the next one and so on. This way,
    // you can add more positions in the future without interfering with legacy textual representations.
    function blocks_get_positions() {
        return array(BLOCK_POS_LEFT, BLOCK_POS_RIGHT);
    }
	
    // Given an instance of a block in this page and the direction in which we want to move it, where is
    // it going to go? Return the identifier of the instance's new position. This allows us to tell blocklib
    // how we want the blocks to move around in this page in an arbitrarily complex way. If the move as given
    // does not make sense, make sure to return the instance's original position.
    //
    // Since this is going to get called a LOT, pass the instance by reference purely for speed. Do **NOT**
    // modify its data in any way, this will actually confuse blocklib!!!
    function blocks_move_position(&$instance, $move) {
        if($instance->position == BLOCK_POS_LEFT && $move == BLOCK_MOVE_RIGHT) {
            return BLOCK_POS_RIGHT;
        } else if ($instance->position == BLOCK_POS_RIGHT && $move == BLOCK_MOVE_LEFT) {
            return BLOCK_POS_LEFT;
        }
        return $instance->position;
    }
	
	// When a new block is created in this page, which position should it go to?
    function blocks_default_position() {
        return BLOCK_POS_RIGHT;
    }
	
	// USER-RELATED THINGS

    // When is a user said to have "editing rights" in this page? This would have something
    // to do with roles, in the future.
    function user_allowed_editing() {
	$this->init_full();
        return isteacheredit($this->courserecord->id);
    }
	
	// Is the user actually editing this page right now?
    function user_is_editing() {
	$this->init_full();
        return isediting($this->courserecord->id);
    }
	
	//FUNCIONS QUE NO S'HAURIEN DE TOCAR
	
    // This should actually NEVER be overridden unless you have GOOD reason. Works fine as it is.
    function url_get_full($extraparams = array()) {
        $path = $this->url_get_path();
        if(empty($path)) {
            return NULL;
        }

        $params = $this->url_get_parameters();
	
		//montem els paràmetres de la URL
        $params = array_merge($params, $extraparams);
	
		//si no te paràmetres retornem el path
        if(empty($params)) {
            return $path;
        }
	
        
        $first = true;

		//montem la url amb el get
        foreach($params as $var => $value) {
            $path .= $first? '?' : '&amp;';
            $path .= $var .'='. urlencode($value);
            $first = false;
        }
	
        return $path;
    }
	
	// This should return a fully qualified path to the URL which is responsible for displaying us.
    function url_get_path() {
        global $CFG;
        
            return $CFG->wwwroot .'/mod/internalmail/view.php';
    }

    // This should return an associative array of any GET/POST parameters that are needed by the URL
    // which displays us to make it work. If none are needed, return an empty array.
    function url_get_parameters() {
    		
	$id     = optional_param('id', 0, PARAM_INT);    // Course Module ID, or`
	/*$query=$_SERVER[QUERY_STRING];
	 $aux=split('=',$query);
	 $aux2=$aux[1];
	 $aux=split('&',$aux2);
	 $id=$aux[0];*/
       
	return array('id' => $id);// potser la necessitem mes endavant 'a' => $this->id
    }

	
	
}
	


?>
