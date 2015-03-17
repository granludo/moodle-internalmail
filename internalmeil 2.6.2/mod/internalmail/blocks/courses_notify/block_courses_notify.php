<?php

class block_courses_notify extends block_list {

  //funció que es crida al arrancar una instància del mòdul
  function init() {
    $this->title = get_string('courses_notify','internalmail');
    $this->version = 2004081200;
  }
	
  function get_content() {
    global $USER,$CFG,$COURSE;
    
    if($this->content !== NULL) {
		return $this->content;   
	}

    $this->content = new stdClass;
    $this->content->items = array();
    $this->content->icons = array();
    $this->content->footer = "<br />"; //get_string('block_Notify','internalmail');
		
    $act_course=$this->instance->pageid;
		
    $icon  = "<img src=\"$CFG->pixpath/i/course.gif\" height=\"16\" width=\"16\" alt=\"".get_string("course")."\" />";

    //agafem els cursos on hi ha mails pendents
    $query = "	
		SELECT
		  t.id cm_instance_id, z.id course_id, z.shortname im_instance_name
		FROM
		  {$CFG->prefix}internalmail_discussions m,
		  {$CFG->prefix}internalmail_posts n,
		  {$CFG->prefix}internalmail o,
		  {$CFG->prefix}course z,
		  {$CFG->prefix}course_modules t,
		  {$CFG->prefix}modules u
		WHERE
		  m.userid = {$USER->id}
		  and m.id = n.discussion
		  and n.mailed = '0'
		  and n.format = '1'
		  and n.course = o.course
		  and o.course = z.id
	      and u.name = 'internalmail'
	      and t.module = u.id
	      and t.course = z.id
	      and t.instance = o.id
	      and t.visible = '1'";
    $cursos = get_records_sql($query);
    if (!$cursos) {
	return $this->content;
    }

    //per cada curs
    $used_curs = array();
    foreach ($cursos as $curs) {
	//agafem el nom del curs
	if (!in_array($curs->course_id, $used_curs)){

	    //el posem a la llista			
	    if ($COURSE->id == $curs->course_id) {
		//$this->content->items[]="<a href=\"view.php?a={$id->id}\"><font color='#AAAAAA'><b>{$name->fullname}</b></font>" .
		$this->content->items[]="<a href=\"view.php?id={$curs->cm_instance_id}\"><font color='#FF0000'><b>{$curs->im_instance_name}</b></font>" .
		    "&nbsp;<img src=\"{$CFG->wwwroot}/mod/internalmail/images/newmail.gif\" /></a>";
	    } else {
		//$this->content->items[]="<a href=\"view.php?a={$id->id}\"><font color='#AAAAAA'><b>{$name->fullname}</b></font>" .
		$this->content->items[]="<a href=\"view.php?id={$curs->cm_instance_id}\"><font color='#AAAAAA'><b>{$curs->im_instance_name}</b></font>" .
		    "&nbsp;<img src=\"{$CFG->wwwroot}/mod/internalmail/images/newmail.gif\" /></a>";
	    }
	    $this->content->icons[]=$icon;
	    $used_curs[] = $curs->cm_instance_id;
	}
    }
		
    //si no hi ha cursos ho diem
    if (count($used_curs) === 0) {
	$this->content->footer = get_string('nonewmails','internalmail');
    }
		
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
  function instance_allow_multiple() {
    // Are you going to allow multiple instances of each block?
    // If yes, then it is assumed that the block WILL USE per-instance configuration
    return true;
  }

}

?>
