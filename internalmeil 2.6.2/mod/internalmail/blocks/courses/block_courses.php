<?php

class block_courses extends block_list {

  //funci� que es crida al arrancar una inst�ncia del m�dul
  function init() {
    $this->title = get_string('courses');
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
    $this->content->footer = "<br />"; // get_string('courses');
		
    $act_course = $this->instance->pageid;
		
    $icon  = "<img src=\"$CFG->pixpath/i/course.gif\"".
      " height=\"16\" width=\"16\" alt=\"".get_string("course")."\" />";
	
    //agafem la discussion de l'usuari
    $discussion=internalmail_get_user_discussion($USER->id);
		
    //agafem tots els cursos
    $query = "SELECT id,course 
                FROM {$CFG->prefix}internalmail_posts 
	       WHERE discussion={$discussion->id} AND subject='Inbox'";

    $cursos = get_records_sql($query);
		
    //agafem els cursos on hi ha mails pendents
    /*$query = "SELECT id,course FROM {$CFG->prefix}internalmail_posts 
     WHERE discussion={$discussion->id} AND mailed=0 AND format=1";
     $cursos = get_records_sql($query);*/
		
    //per cada curs
    $used_curs = array();

    if ( count($cursos) < 1 ) {
      return $this->content;
    }

    foreach ($cursos as $curs) {
      //agafem el nom del curs
      if (!in_array($curs->course,$used_curs)) {
	//agafem les dades del curs
	$name=get_record_sql("SELECT id, fullname
	                        FROM {$CFG->prefix}course
	                       WHERE id = '{$curs->course}'");

	//agafem l'id de l'internalmail del curs.
	$id = get_record('internalmail','course',$curs->course);
	//mirem si t� mails nous
	/*$query = "SELECT COUNT(*) AS num FROM {$CFG->prefix}internalmail_posts 
	 WHERE discussion={$discussion->id} AND mailed=0 AND format=1";*/
	$nous = count_records_select('internalmail_posts',"discussion='{$discussion->id}'" .
				     "AND mailed='0' AND format='1' AND course='{$curs->course}'");
	//$nous = get_records_sql($query);
	if ($nous != 0) {
	  $newimg = "&nbsp;<img src=\"{$CFG->wwwroot}/mod/internalmail/images/newmail.gif\" />";
	  if ($nous > 1 ) { 
	    $newimg .= "x$nous";
	  }
	} else {
	  $newimg = '';
	}
	            
	//el posem a la llista
	if ($course->id == $curs->course) {
	  $this->content->items[]="<a href=\"view.php?a={$id->id}\"><font color='#FF0000'><b>{$name->fullname}</b>" .
	    "$newimg</font></a>";
	} else {
	  $this->content->items[]="<a href=\"view.php?a={$id->id}\"><font color='#AAAAAA'><b>{$name->fullname}</b>" .
	    "$newimg</font></a>";
	}
	$this->content->icons[]=$icon;
	$used_curs[] = $curs->course;
      }
    }
		
    return $this->content;
  }
	
  /**
   * This function is called on your subclass right after an instance is loaded
   * Use this function to act on instance data just after it's loaded and before anything else is done
   * For instance: if your block will have different title's depending on location (site, course, blog, etc)
   */
  //SERVEIX PER ADAPTAR EL M�DUL A UNA INST�NCIA CONCRETA (POTSER �S PRESCINDIBLE)
  function specialization() {
    // Just to make sure that this method exists.
  }

  /**
   * Are you going to allow multiple instances of each block?
   * If yes, then it is assumed that the block WILL USE per-instance configuration
   * @return boolean
   * @todo finish documenting this function by explaining per-instance configuration further
   */
  //SI NO VOLS PERMETRES QUE HI HAGI M�S D'UNA INST�NCIA DEL BLOC
  //ESBORRA AQUESTA FUNCI�
  function instance_allow_multiple() {
    // Are you going to allow multiple instances of each block?
    // If yes, then it is assumed that the block WILL USE per-instance configuration
    return true;
  }

}

?>