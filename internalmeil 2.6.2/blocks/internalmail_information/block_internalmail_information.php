<?PHP //$Id: block_internalmail_information.php,v 1.8.2.5 2004/10/02 23:09:36 stronk7 Exp $

  //include_once($CFG->dirroot.'/mod/internalmail/blocks/moodleblock.class.php');

class block_internalmail_information extends block_base { //extends block_base {
   
    function init() {
        $this->title = get_string('blockname','internalmail');
        if ( isset($this->instance->pageid) ) {
	    $this->course = get_record('course','id',$this->instance->pageid);
	}
        $this->version = 2004112412;
    }  	

    function has_config() {
	return false;
    }

    /*function print_config() {
     global $CFG, $USER, $THEME;
     print_simple_box_start('center', '', $THEME->cellheading);
     include($CFG->dirroot.'/blocks/'.$this->name().'/config.html');
     print_simple_box_end();
     return true;
     }

     function handle_config($config) {
     foreach ($config as $name => $value) {
     set_config($name, $value);
     }
     return true;
    }*/

    function get_content() {

        global $USER, $CFG, $THEME;

	$this->init();

        if ($this->content !== NULL) {
            return $this->content;
        }
        if (empty($this->course)) {
            $this->content = '';
            return $this->content;
        }
        $this->content = New object;
        $this->content->footer = '';        
        
        if (!file_exists("$CFG->dirroot/mod/internalmail/lib.php")){
	    $this->content = '';
	    return $this->content;
	}

	require_once("$CFG->dirroot/mod/internalmail/lib.php");
	$this->content->text = '';
	$unread=0;
					
	$internalmail= get_record("modules","name","internalmail");
	
	$id_post = get_record_sql("SELECT p.*, u.firstname, u.lastname, u.email, u.picture
		       		     FROM {$CFG->prefix}internalmail_posts p,
                                          {$CFG->prefix}user u
                                    WHERE p.subject='$USER->id'
                                      AND p.userid= u.id");
	    
	$cid=$this->course->id;
                       
	$id = get_record_sql("SELECT c.id
		  	        FROM {$CFG->prefix}course_modules c
			       WHERE c.module=$internalmail->id
				 AND c.course=$cid");
					
	if ($this->course->id == SITEID ) {
						
	    if (isset($id_post->id)){
		$post[0] = get_record("internalmail_posts","parent",$id_post->id,"subject","Inbox");
		$post[1] = get_record("internalmail_posts","parent",$id_post->id,"subject","Sent");
		$post[2] = get_record("internalmail_posts","parent",$id_post->id,"subject","Deleted");
		$post[3] = get_record("internalmail_posts","parent",$id_post->id,"subject","Courses");
		$unread = internalmail_get_folder_unread($post[0]) + internalmail_get_folder_unread($post[1]) + internalmail_get_folder_unread($post[2]);

		$unreadinfo = "";
		if ($unread != 0) {
		    $unreadinfo = " <strong>(".$unread.")</strong>";
		    $unreadinfo.= " <img src=\"$CFG->wwwroot/blocks/internalmail_information/images/new.gif\"";
		    $unreadinfo.= " width=\"12\" height=\"12\" alt=\"\" align=\"absmiddle\">";
		}

		$this->content->text .= "<a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=".$id->id."&option=2\">";
		$this->content->text .=	$this->course->shortname ."$unreadinfo</a>\n <br />"; 

		$link  = false;
		//$ratingsmenuused = false;
		if ($posts = internalmail_get_child_posts($post[3]->id)) {
		    foreach ($posts as $post) {
			$aux=preg_split('/::/',$post->subject,3);
										
			$subjbo=$aux[2];
			if (get_record('internalmail','course',$subjbo)){
			    $coursea=get_record("course","id",$subjbo);
			    $i=internalmail_get_folder_unread($post);
			
			    $id = get_record_sql("SELECT cm.id
						    FROM {$CFG->prefix}course_modules cm
						   WHERE cm.module=$internalmail->id
						     AND cm.course=$subjbo");												
					
			    $newdatainfo = "";
			    if ( $i > 0) {
				$newdatainfo = "<strong>(".$i.")</strong><img src=\"$CFG->wwwroot/blocks/internalmail_information/images/new.gif\"";
				$newdatainfo .= " alt=\"\" width=\"12\" height=\"12\" align=\"absmiddle\">";
			    }

			    $this->content->text .= "<img src=\"".$CFG->wwwroot."/pix/i/course.gif\" width=\"24\" height=\"24\" align=\"absmiddle\">";
			    $this->content->text .= "<a name=\"$post->id\"></a>";
			    $this->content->text .= "<span style=\"font-size:0.8em\"><a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=$id->id";
			    $this->content->text .= "&option=2\">$coursea->shortname $newdatainfo</a></span><br /> \n";
			}
		    }
		}
	    }
	} else{

	    if ($id_post->id){
		$id_post = internalmail_get_user_parent_id($USER->id);
		$link  = false;
		//$ratingsmenuused = false;
		$post[3] = get_record("internalmail_posts","parent",$id_post->id,"subject","Courses");
		if ($posts = internalmail_get_child_posts($post[3]->id)) {
		    foreach ($posts as $post) {
			$aux=preg_split('/::/',$post->subject,3);
			$subjbo=$aux[2];
			if (get_record('internalmail','course',$subjbo)){
			    if(($this->course->id)==$subjbo){
				$coursea=get_record("course","id",$this->course->id);
				$i=internalmail_get_folder_unread($post);
												
				$id = get_record_sql("SELECT cm.id
							FROM {$CFG->prefix}course_modules cm
						       WHERE cm.module=$internalmail->id
							 AND cm.course=$subjbo");

				$newdatainfo = "";
				if ($i>0){
				    $newdatainfo = "<strong>(".$i.")</strong>";
				}
				$this->content->text.="<img src=\"".$CFG->wwwroot."/pix/i/course.gif\" width=\"24\" height=\"24\"";
				$this->content->text.=" align=\"absmiddle\"><a name=\"$post->id\"></a>";
				$this->content->text.="<span style=\"font-size:0.8em\"><a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=$id->id";
				$this->content->text.="&option=2\">$coursea->shortname $newdatainfo</a></span> <br />\n";
			    }
			}
		    }
		}
	    }
	}
	return $this->content;
	
    }
}

?>
