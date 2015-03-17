<?PHP //$Id: block_internalmail_information.php,v 1.8.2.5 2004/10/02 23:09:36 stronk7 Exp $

//include_once($CFG->dirroot.'/mod/internalmail/blocks/moodleblock.class.php');

class block_internalmail_information extends block_base { //extends block_base {
   
  	/*function CourseBlock_internalmail_information($course){
  		$this->title = get_string('blockname','internalmail');
  		$this->content_type = BLOCK_TYPE_TEXT;
  		$this->course = $course;//get_record('course','id',$this->instance->pageid);
              $this->version = 2004112412;
  	}*/
  	
  	function init() {
        $this->title = get_string('blockname','internalmail');
        $this->course = get_record('course','id',$this->instance->pageid);
        $this->version = 2004112412;
    }
  	
  	
    function has_config() {return false;}

    function print_config() {
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
    }

	function get_content() {

        global $USER, $CFG;

		$this->init();
        if ($this->content !== NULL) {
            return $this->content;
        }
        if (empty($this->course)) {
            $this->content = '';
            return $this->content;
        }
        $this->content = New object;
        //$this->content->text = '';
        $this->content->footer = '';
        
        global $CFG, $USER, $THEME;
        
        
        
        if (file_exists("$CFG->dirroot/mod/internalmail/lib.php")){
					require_once("$CFG->dirroot/mod/internalmail/lib.php");
					$this->content->text = '';
					$unread=0;
					
					$internalmail= get_record("modules","name","internalmail");
					
					$id_post = get_record_sql("SELECT p.*, u.firstname, u.lastname, u.email, u.picture
		       							FROM {$CFG->prefix}internalmail_posts p,
                            {$CFG->prefix}user u
                       WHERE p.subject='$USER->id'
                       AND   p.userid= u.id");
          
          $cid=$this->course->id;
          
          
                       
          $id = get_record_sql("SELECT c.id
																			FROM {$CFG->prefix}course_modules c
																			WHERE c.module=$internalmail->id
																			AND c.course=$cid");
					
					if ($this->course->id==1){
						
						if ($id_post->id){
								$post[0] = get_record("internalmail_posts","parent",$id_post->id,"subject","Inbox");
								$post[1] = get_record("internalmail_posts","parent",$id_post->id,"subject","Sent");
								$post[2] = get_record("internalmail_posts","parent",$id_post->id,"subject","Deleted");
								$post[3] = get_record("internalmail_posts","parent",$id_post->id,"subject","Courses");
								$unread = internalmail_get_folder_unread($post[0]) + internalmail_get_folder_unread($post[1]) + internalmail_get_folder_unread($post[2]);
								
								if ($unread != 0){
									$this->content->text = "<a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=".$id->id."&option=2\">".$this->course->shortname." <B>(".$unread.")</B>aaa<img src=$CFG->wwwroot/blocks/internalmail_information/images/new.gif width=\"12\" height=\"12\" align=\"absmiddle\"></a>";
								}
								else{
									$this->content->text = "<a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=".$id->id."&option=2\">".$this->course->shortname."</a>";
								}
								$link  = false;
								//$ratingsmenuused = false;
								if ($posts = internalmail_get_child_posts($post[3]->id)) {
									foreach ($posts as $post) {
										$aux=preg_split('/::/',$post->subject,3);
										
										$subjbo=$aux[2];
										if (get_record('internalmail','course',$subjbo)){
												$coursea=get_record("course","id",$subjbo);
												$i=internalmail_get_folder_unread($post);		                
												$this->content->text = $this->content->text. "<BR>";
												
												
												$id = get_record_sql("SELECT cm.id
																			FROM {$CFG->prefix}course_modules cm
																			WHERE cm.module=$internalmail->id
																			AND cm.course=$subjbo");
												
												
												if ($i>0) {
													$this->content->text = $this->content->text. 	"<img src=\"".$CFG->wwwroot."/pix/i/course.gif\" width=\"24\" height=\"24\" align=\"absmiddle\"><a name=\"$post->id\"></a><font size=-1><a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=$id->id&option=2\">$coursea->shortname <B>(".$i.")</B><img src=$CFG->wwwroot/blocks/internalmail_information/images/new.gif width=\"12\" height=\"12\" align=\"absmiddle\"></a> ";
												}else{
													$this->content->text = $this->content->text. 	"<img src=\"".$CFG->wwwroot."/pix/i/course.gif\" width=\"24\" height=\"24\" align=\"absmiddle\"><a name=\"$post->id\"></a><font size=-1><a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=$id->id&option=2\">$coursea->shortname </a> ";
												}
												$this->content->text = $this->content->text.	"</font>";
										}
									}
								}
						}else{
							$this->content->text = '';
						}
					}else{

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
												
												                
												if ($i>0){
													$this->content->text = $this->content->text."<img src=\"".$CFG->wwwroot."/pix/i/course.gif\" width=\"24\" height=\"24\" align=\"absmiddle\"><a name=\"$post->id\"></a><font size=-1><a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=$id->id&option=2\">$coursea->shortname <B>(".$i.")</B></a> ";
												}else{
													$this->content->text = $this->content->text."<img src=\"".$CFG->wwwroot."/pix/i/course.gif\" width=\"24\" height=\"24\" align=\"absmiddle\"><a name=\"$post->id\"></a><font size=-1><a href=\"".$CFG->wwwroot."/mod/internalmail/view.php?id=$id->id&option=2\">$coursea->shortname </a> ";
												}
												$this->content->text = $this->content->text."</font>";
											}
									}
								}
							
							}
						}else{$this->content->text = '';}
					}
	        return $this->content;
	     }else{
	    	}
	     
    }
    
}

?>
