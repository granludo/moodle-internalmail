<?PHP

global $CFG;

//echo "<html>";
/*echo	"	<script language=\"JavaScript\" >";


echo "function addContact(email) {";


$query=$_SERVER[QUERY_STRING];
$aux=split('&',$query);
$aux2=split('=',$aux[0]);
$idc=$aux2[1];
$aux2=split('=',$aux[1]);
$option=$aux2[1];


if($option==1)
{
echo "field=window.document.theform.destiny;";
echo "if(field.value==\"\"){";
echo " field.value= email;";
echo "}";
echo "else{";
echo "var bool=0;";
echo "var comprova=field.value.split(\",\");";
echo "var n=comprova.length;";
echo "var i=0;";

echo "while(i<=n && bool==0){";
echo "if(email==comprova[i]){";
echo "bool=1;";
echo "}";
echo "i++;";
echo "}";

echo "if(bool==0){";
echo "field.value= field.value + \",\" + email;";
echo "}";
echo "}";
}
else
{
echo "location.href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$idc&option=1\";";

}

echo " }";


echo "</SCRIPT>";
*/

//echo "</html>";


class block_contacts extends block_list {

	//funció que es crida al arrancar una instància del mòdul
    function init() {
        $this->title = get_string('block_Contacts', 'internalmail');
        $this->version = 2004081200;
        $this->course = get_record('course','id',$this->instance->pageid);

    }
	
	function get_content() {
		
		global $USER, $CFG;
		
				if($this->content !== NULL) 
				{
			
            return $this->content;
        }
		//		$course=get_record('course','id',$this->instance->pageid);
        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        
        $this->content->footer =get_string('block_Contacts','internalmail');

$internalmail= get_record("internalmail","id",$this->instance->pageid);		
$course = get_record("course","id",$internalmail->course);
$course_module = get_record("course_modules","course",$internalmail->course,"instance",$this->instance->pageid);



				if($course->id==1)
				{
	
		
					$this->content->footer =get_string('block_Contacts','internalmail');
			
					$timetoshowusers = 300; //Seconds default
    			if (isset($CFG->block_online_users_timetosee)) 
    			{
        		$timetoshowusers = $CFG->block_online_users_timetosee * 60;
    			}
    			$timefrom = time()-$timetoshowusers;

    
    /// get lists of contacts and unread messages
    			$onlinecontacts = get_records_sql("SELECT u.id, u.firstname, u.lastname, u.picture, mc.blocked
                                       FROM {$CFG->prefix}user u, {$CFG->prefix}message_contacts mc
                                       WHERE mc.userid=$USER->id AND u.id=mc.contactid AND u.lastaccess>=$timefrom 
                                         AND mc.blocked=0 
                                       ORDER BY u.firstname ASC");

					if(empty($onlinecontacts))
					{
			
						$onlinecontacts=array();
					}


    			$contacts = get_records_sql("SELECT u.id, u.username, u.firstname, u.lastname, u.picture, mc.blocked
                                       FROM {$CFG->prefix}user u, {$CFG->prefix}message_contacts mc
                                       WHERE mc.userid=$USER->id AND u.id=mc.contactid  
                                         AND mc.blocked=0 
                                       ORDER BY u.firstname ASC");
                    
                    
          $this->content->icons[]=""; //'<img src="users2.gif">';
					$this->content->items[]="<center><font color='#330066'> <b>".get_string('block_Contacts_contacts','internalmail')."</b> </font>  </center>";
										
										
					if(empty($contacts))
					{
						$contacts=array();
					}
					else
					{
					
						foreach ($contacts as $contact) 
						{
	            	$disc=$discussion=internalmail_get_user_discussion($contact->id);
  					  	if(!empty($disc)){
      
	             		$picture = print_user_picture($contact->id, $course->id, $contact->picture, 25, true,false); //pinta foto user
						   		$fullname = fullname($contact, $isteacher); //obté nom
						   		$nom=$contact->username;
						   		$online="offline";
							 		$marc="";
							 		$emarc="";
							 
							 		foreach ($onlinecontacts as $onlinecontact)
							 		{

										if($onlinecontact->id==$contact->id)
										{
															
											$marc="<b>";
											$emarc="</b>";
											$online="online";
										}
												
							 		}
							
							 	$this->content->icons[]="<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>';//$picture; //'<img src="users2.gif">';.'</td>'
							 	$this->content->items[]= "<a href=\"javascript:addContact('".$nom."')\">".$marc.$fullname." (".$online.")".$emarc.'</a>'; //imprimeix contactes
	          	}       		                 			                 
	          }  						
																				
					}
                   						
				}
				else
				{ //course !=1
					

$query=$_SERVER[QUERY_STRING];
$aux=split('&',$query);
$aux2=split('=',$aux[0]);
$idc=$aux2[1];
$aux2=split('=',$aux[1]);
$option=$aux2[1];

if(empty($option))
{
	$option=2;						
}
					$this->content->footer = "<a target=\"popup2\" title=\"more contacts\" href=\"courseusers.php?course=$course->id&option=$option&idc=$idc\" onClick=\"return openpopup('/mod/internalmail/blocks/contacts/courseusers.php?course=$course->id&option=$option&idc=$idc', 'popup2', 'menubar=1,location=0,scrollbars,resizable,width=400,height=500', 0);\"><span\">".get_string('more contacts','internalmail')."</span></a>";

					$this->content->icons[]=""; //'<img src="users2.gif">';
					$this->content->items[]="<center> <font color='#330066'> <b>".$course->teachers."</b> </font> </center>";

					if ($teachers = get_course_teachers($course->id)) 
					{

							if(empty($teachers))
							{
								
								$teachers=array();
								
							}


							$count=0;
  		        foreach ($teachers as $teacher) 
  		        {
	                 $subj="CUR26::".$teacher->id."::".$course->id;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
									$havemailbox=get_record_sql("SELECT p.*
	                             FROM {$CFG->prefix}internalmail_posts p
	                             WHERE p.subject = '$subj'");
									if(!empty($havemailbox))
									{
	                 if (($teacher->editall or ismember($currentgroup, $teacher->id) and $count!=4)) 
	                 {
	                 		$picture = print_user_picture($teacher->id, $course->id, $teacher->picture, 25, true,false); //pinta foto user
						            $fullname = fullname($teacher, $isteacher); //obté nom
						            $nom=$teacher->username;
												$count++;
										    $this->content->icons[]="<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>';
										    $this->content->items[]= "<a href=\"javascript:addContact('".$nom."')\">"."<b>$fullname</b>".'</a>';//imprimeix profes
										    
	                 }
	                 unset($havemailbox);
	            		}   	
					}
	                 	
	        $this->content->icons[]=""; //'<img src="users2.gif">';
					$this->content->items[]="<center><font color='#330066'> <b>".$course->students."</b> </font>  </center>";

//STUDENTS	                 	
	              	
	
					$guest = get_guest();
		    	$exceptions .= $guest->id;
		    	if ($sort == "lastaccess") 
		    	{
		        $dsort = "s.timeaccess";
		    	} else
		    	{
		        $dsort = "u.$sort";
		    	}

					$students = get_course_students($course->id);

		    
					if(empty($students))
					{								
						$students=array();								
					}

					$count=0;
					foreach ($students as $student) 
					{
						
						if($count!=4)
						{
							
	 						$subj="CUR26::".$student->id."::".$course->id;  //busquem el fantasma del curs per a veure si té bustia en aquell curs
							$havemailbox=get_record_sql("SELECT p.*
	                             FROM {$CFG->prefix}internalmail_posts p
	                             WHERE p.subject = '$subj'");
						
							if(!empty($havemailbox))
							{           
								$picture = print_user_picture($student->id, $course->id, $student->picture, 25, true,false);            
            		$fullname = fullname($student, $isteacher);
            		$nom= $student->username;
								$count++;
								$this->content->icons[]="<a href=\"javascript:addContact('".$nom."')\">".$picture.'</a>'; //'<img src="users2.gif">';
								$this->content->items[]="<a href=\"javascript:addContact('".$nom."')\">".$fullname.'</a>';
								unset($havemailbox);
							}
						}
					}	
				           
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