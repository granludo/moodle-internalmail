<?PHP

global $CFG;

class block_contacts extends block_list {

  //funció que es crida al arrancar una instància del mòdul
  function init() {
    $this->title = get_string('block_Contacts', 'internalmail');
    $this->version = 2004081200;
    //$this->course = get_record('course','id',$this->instance->pageid);
  }
	
  function get_content() {
	
      global $USER, $CFG;
	
      $group  = optional_param('group', -1, PARAM_INT); 

      if (empty($this->instance->pageid)) {
		return '';  
      }

      if (empty($this->instance)) {
		  $this->content = '';
		  return $this->content;
      }

      //	$course=get_record('course','id',$this->instance->pageid);
      $this->content = new stdClass;
      $this->content->items = array();
      $this->content->icons = array();
    
      $this->content->footer = "<br />"; //get_string('block_Contacts','internalmail');

      $internalmail= get_record("internalmail","id",$this->instance->pageid);
      if ( !$internalmail) {
		  $this->content = '';
		  return $this->content;
      }
      $course = get_record("course","id",$internalmail->course);
      if ( !$course ) {
		  $this->content = '';
		  return $this->content;
      }
      $course_module = get_coursemodule_from_instance("internalmail", $internalmail->id, $internalmail->course);
	  //get_record("course_modules","course",$internalmail->course,"instance",$this->instance->pageid);
      if ( !$course_module ) {
		  $this->content = '';
		  return $this->content;  
      }

      // Course context
      $context = get_context_instance(CONTEXT_COURSE, SITEID);
      $viewfullnames = (has_capability('moodle/site:viewfullnames', $context));

      if( $this->instance->pageid == SITEID ) {
		  //$this->content->footer = get_string('block_Contacts','internalmail');
	      
		  $timetoshowusers = 300; //Seconds default
		  if (isset($CFG->block_online_users_timetosee)) {
		      $timetoshowusers = $CFG->block_online_users_timetosee * 60;
		  }
		  $timefrom = time() - $timetoshowusers;
	
		  // get lists of contacts and unread messages
		  $onlinecontacts = get_records_sql("SELECT u.id, u.firstname, u.lastname, u.picture, mc.blocked
	                                           FROM {$CFG->prefix}user u, {$CFG->prefix}message_contacts mc
	                                          WHERE mc.userid=$USER->id
	                                            AND u.id=mc.contactid AND u.lastaccess>=$timefrom AND mc.blocked=0 
	                                       ORDER BY u.firstname ASC");
	
		  if(empty($onlinecontacts)) {
		      $onlinecontacts=array();
		  }
	
		  $contacts = get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, u.picture, mc.blocked
	                                     FROM {$CFG->prefix}user u, {$CFG->prefix}message_contacts mc
	                                    WHERE mc.userid=$USER->id AND u.id=mc.contactid  
	                                      AND mc.blocked=0 
	                                 ORDER BY u.firstname ASC");
	                            
		  $numCount = 0;
		  $this->content->icons[] = ""; //'<img src="users2.gif">';
		  $this->content->items[] = "\n<div class=\"internalmail-block-title\">".get_string('block_Contacts_contacts','internalmail')."</div>";	
	
		  if(empty($contacts)) {
		      $contacts = array();
		  } else {
	
		      $user_offline = get_string('user_offline', 'internalmail');
		      $user_online = get_string('user_online', 'internalmail');
		      
		      foreach ($contacts as $contact) {
				  $disc=$discussion=internalmail_get_user_discussion($contact->id);
				  if(!empty($disc)) {
				      
				      $picture = print_user_picture($contact->id, $course->id, $contact->picture, 25, true,false); //pinta foto user
				      $fullname = fullname($contact, $viewfullnames); //obté nom
				      $nom = $contact->username;
				      $online = $user_offline;
				      $onlineuser = "";
				      
				      foreach ($onlinecontacts as $onlinecontact) {
						  if ( $onlinecontact->id == $contact->id ) {
						      $onlineuser = ' class="internalmail-user-online"';
						      $online = $user_online;
						  }
				      }
				      $numCount++;
				      $this->content->icons[]= "<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>';
				      $this->content->items[]= "<a$onlineuser href=\"javascript:addContact('".$nom."')\">".$fullname." (".$online.")</a>"; 
				  }
		      }
		  }
		  
		  if ( $numCount == 0) {
		      $this->content = '';
		  }
      } else { //course !=1 	
		  
		  //$query=$_SERVER[QUERY_STRING];
		  //$aux=split('&',$query);
		  //$aux2=split('=',$aux[0]);
		  //$aux2[1];
		  //$aux2=split('=',$aux[1]);
		  //$aux2[1];	  
	
		  $idc = optional_param('id', 0, PARAM_INT); 
		  $option = optional_param('option', 0, PARAM_INT); 
	
		  if(empty($option)) {
		      $option = 2;						
		  }
	
		  /*$this->content->icons[]= ""; //'<img src="users2.gif">';
		   $this->content->items[]="<center> <font color='#330066'> <b>".$course->teachers."</b> </font> </center>";*/
	      
		  // Course context
		  $context = get_context_instance(CONTEXT_COURSE, $course->id);
	
		  $groupmode = groupmode($course);   // Groups are being used
		  //$currentgroup = get_and_set_current_group($course, $groupmode, $group);
		  if ( !$group ) {
		      $currentgroup = 0;
		  } else {
		      $currentgroup = get_and_set_current_group($course, $groupmode, $group);
		  }
		  
		  $isseparategroups = ($course->groupmode == SEPARATEGROUPS and $course->groupmodeforce and
				       !has_capability('moodle/site:accessallgroups', $context));
	
		  // we are looking for all users with this role assigned in this context or higher
		  if ($usercontexts = get_parent_contexts($context)) {
		      $listofcontexts = '('.implode(',', $usercontexts).')';
		  } else {
		      $listofcontexts = '()'; // must be site
		  }
		  /*if ($roleid) {
		   $selectrole = " AND r.roleid = $roleid ";
		   } else {
		   $selectrole = " ";
		   }*/
	
		  $select = 'SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.city, u.country, r.roleid, 
	                            u.picture, u.lang, u.timezone, u.emailstop, u.maildisplay, ul.timeaccess AS lastaccess '; // s.lastaccess
	
		  $from   = "FROM {$CFG->prefix}user u INNER JOIN
	                          {$CFG->prefix}role_assignments r on u.id=r.userid LEFT OUTER JOIN
	                          {$CFG->prefix}user_lastaccess ul on (r.userid=ul.userid and ul.courseid = $course->id)"; 
	
		  // otherwise we run into the problem of having records in ul table, but not relevant course
		  // and user record is not pulled out
		  $where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
	                       AND u.deleted = 0 
	                       AND (ul.courseid = $course->id OR ul.courseid IS NULL)
	                       AND u.username <> 'guest' ";
		  //$where .= get_lastaccess_sql($accesssince);
	
		  $wheresearch = '';
	
		  $groupinfo = '';
		  $groupinfopopup = '';
		  if ($currentgroup) {    // Displaying a group by choice
		      // FIX: TODO: This will not work if $currentgroup == 0, i.e. "those not in a group"
		      $from  .= 'LEFT JOIN '.$CFG->prefix.'groups_members gm ON u.id = gm.userid ';
		      $where .= ' AND gm.groupid = '.$currentgroup;
		      $groupinfo = "&amp;grpid=$currentgroup";
		      $groupinfopopup = "&grpid=$currentgroup";
		  }
	
		  //$totalcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where);   // Each user could have > 1 role
	
		  /*if ($table->get_sql_where()) {
		   $where .= ' AND '.$table->get_sql_where();
		  }*/
		  $sort = ' ORDER BY r.roleid';
	
		  /*if ($table->get_sql_sort()) {
		   $sort = ' ORDER BY '.$table->get_sql_sort();
		   } else {
		   $sort = '';
		  }*/
	
		  //$matchcount = count_records_sql('SELECT COUNT(distinct u.id) '.$from.$where.$wheresearch);
	
		  /*if ( $totalcount < $perpage ) {
		   $perpage = 5000;
		   }
		   $table->initialbars($totalcount > $perpage);
		   $table->pagesize($perpage, $matchcount);
	
		   $userlist = get_records_sql($select.$from.$where.$wheresearch.$sort,
		   $table->get_page_start(), $table->get_page_size());*/
	
		  $userlist = get_records_sql($select.$from.$where.$wheresearch.$sort );
	
		  $count = 0;
		  $countNum = 6;
	
		  $viewfullnames = (has_capability('moodle/site:viewfullnames', $context));
		  
		  unset ($todos);
		
		  foreach ($userlist as $user) {
	
		      if ($count != $countNum) {
				  $subj = "CUR26::".$user->id."::".$course->id;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
		
				  $havemailbox = get_record_sql("SELECT p.*
			                                           FROM {$CFG->prefix}internalmail_posts p
		                                                  WHERE p.subject = '$subj'");
			    			
				  if(!empty($havemailbox)) {
				      $picture = print_user_picture($user->id, $course->id, $user->picture, 25, true,false);            
				      //$fullname = fullname($student, $isteacher);
				      $fullname = fullname($user, $viewfullnames);
				      $nom = $user->username;
			  		  $todos="".$todos.",".$nom;
				      $count++;
				      $this->content->icons[]="<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>'; //'<img src="users2.gif">';
				      $this->content->items[]="<a href=\"javascript:addContact('".$nom."')\">".$fullname.'</a>';
				  }
				  unset($havemailbox);
		      }
		  }
//		  print_object ($todos);
//		  echo ("<a href = \"javascript:addAllContacts(".$todos.")\">Todos</a>");
//		  this->content->footer = "\n <a href = añlsdfj>allkj</a>";
//		  this->content->footer = "\n <a href=prova.html\"javascript:addContacts('".$todos."')\">Todos"'</a>';
//		  $this->content->icons[]="<a href=\"javascript:addContact('Todos')\">".$picture.'</a>';
		  $this->content->items[]="<a href=\"javascript:addContact('Todos')\"></a>";
		  $todos=substr($todos,1);
  		  $this->content->items[]="<a href=\"javascript:addAllContacts('".$todos."')\">Todos</a>";
		  
		  if ( $count == $countNum ) {
		      // Display link for more contact if there are more
		      $strmorecontact = get_string('more contacts','internalmail');
		      $this->content->footer = "\n <a target=\"popup2\" title=\"" .$strmorecontact ."\" href=\"/mod/internalmail/blocks/contacts/courseusers.php?course=$course->id&amp;option=$option&amp;idc=$idc$groupinfo\" onClick=\"return openpopup('/mod/internalmail/blocks/contacts/courseusers.php?course=$course->id&option=$option&idc=$idc$groupinfopopup', 'popup2', 'menubar=1,location=0,scrollbars,resizable,width=400,height=500', 0);\"><span\">".$strmorecontact."</span></a>\n";
		  }	     	
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
}

?>