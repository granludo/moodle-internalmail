<?php

//necessitem les funcions de l'internalmail original
require_once ($CFG->dirroot.'/mod/internalmail/lib.php');

class block_internalmail_notify extends block_list {

	//funció que es crida al arrancar una instància del mòdul
    function init() {
        $this->title = get_string('courses_notify','internalmail');
        $this->version = 2004081200;
    }
	
	function get_content() {
		global $USER,$CFG,$course;
		
		if($this->content !== NULL) {
        	return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = get_string('courses_notify','internalmail');
		
		$act_course=$this->instance->pageid;
		
		$icon  = "<img src=\"$CFG->pixpath/i/course.gif\"".
                 " height=\"16\" width=\"16\" alt=\"".get_string("course")."\" />";
	
		//agafem la discussion de l'usuari
		$discussion=internalmail_get_user_discussion($USER->id);
		
		//agafem els cursos on hi ha mails pendents
		$query = "SELECT id,course FROM {$CFG->prefix}internalmail_posts 
				WHERE discussion={$discussion->id} AND mailed=0 AND format=1";
		$cursos = get_records_sql($query);
		
		//la URL a la cosa
		$interurl = $CFG->wwwroot.'/mod/internalmail/';
		
		if (!$cursos) return $this->content;
		
		//per cada curs
		$used_curs = array();
		foreach ($cursos as $curs){
			//agafem el nom del curs
			if (!in_array($curs->course,$used_curs)){
				//agafem les dades del curs
				$name=get_record_sql("SELECT id, fullname
	                            FROM {$CFG->prefix}course
	                           WHERE id = {$curs->course}");
	            //agafem el course-mmodule del curs.
	            $id = get_record('internalmail','course',$curs->course);
				//el posem a la llista
				if ($course->id == $curs->course) {
					$this->content->items[]="<a href=\"{$interurl}view.php?a={$id->id}\"><font color='#FF0000'><b>{$name->fullname}</b></font>" .
							"&nbsp;<img src=\"{$CFG->wwwroot}/mod/internalmail/images/newmail.gif\" /></a>";
				} else {
					$this->content->items[]="<a href=\"{$interurl}view.php?a={$id->id}\"><font color='#AAAAAA'><b>{$name->fullname}</b></font>" .
							"&nbsp;<img src=\"{$CFG->wwwroot}/mod/internalmail/images/newmail.gif\" /></a>";
				}
				$this->content->icons[]=$icon;
				$used_curs[] = $curs->course;
			}
		}
   	    
   	    //si no hi ha cursos ho diem
   	    if (count($used_curs) === 0)  $this->content->footer = get_string('nonewmails','internalmail');
		
		return $this->content;
	}
	
	/**
     * This function is called on your subclass right after an instance is loaded
     * Use this function to act on instance data just after it's loaded and before anything else is done
     * For instance: if your block will have different title's depending on location (site, course, blog, etc)
     */
	 //SERVEIX PER ADAPTAR EL MÒDUL A UNA INSTÀNCIA CONCRETA (POTSER ÉS PRESCINDIBLE)
    function specialization() {
        // Just to make sure that this method exists.
    }

    /**
     * Are you going to allow multiple instances of each block?
     * If yes, then it is assumed that the block WILL USE per-instance configuration
     * @return boolean
     * @todo finish documenting this function by explaining per-instance configuration further
     */
	 //SI NO VOLS PERMETRES QUE HI HAGI MÉS D'UNA INSTÀNCIA DEL BLOC
	 //ESBORRA AQUESTA FUNCIÓ
   

}

?>
