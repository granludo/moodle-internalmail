<?PHP // $Id: mysql.php,v 1.19 2004/07/07 17:42:53 moodler Exp $

require_once("$CFG->dirroot/mod/internalmail/lib.php");

function internalmail_upgrade($oldversion) {
// This function does anything necessary to upgrade
// older versions to match current functionality

    global $CFG;
  
    /*if($oldversion < 2005061700) {
     if(!execute_sql("DROP TABLE ".$CFG->prefix."internalmail_subscriptions")){
     return false;
     }
     if(!execute_sql("DROP TABLE ".$CFG->prefix."internalmail_groups")){
     return false;
     }
     if(!execute_sql("DROP TABLE ".$CFG->prefix."internalmail_aliases")){
     return false;	
     }
     if(!execute_sql("INSERT INTO ".$CFG->prefix."internalmail_block (name, version, cron, lastcron, visible, multiple) VALUES ('search',200504200,0,0,1,0)")){
     return false;
     }
     $internalmails=get_records_sql("SELECT * FROM {$CFG->prefix}internalmail");
     if(empty($internalmails)){
     $internalmails=array();
     }
     foreach($internalmails as $internalmail){
     internalmail_update_instance($internalmail);
     }
     }*/
	
    if($oldversion < 2006032800) {
		
	if(!table_column('internalmail_posts', '', 'course', 'integer', '10','unsigned', '0','', 'totalscore')) { 
	    return false;
	}
	$principals = get_records('internalmail_posts', 'parent', '0'); 
	if($principals){
						
	    foreach($principals as $principal) {	
		//Totes les busties dels usuaris al curs 1
		$principal->course = 1;
		//update_record('internalmail_posts', $principal);
		set_field('internalmail_posts', 'course', $principal->course , 'id', $principal->id);
		if ( $principal_boxes = get_records('internalmail_posts', 'parent', $principal->id)) {
				
		    foreach ($principal_boxes as $principal_box) 	{	//les pestanyes de inbox, deleted, send i courses.
			$principal_box->course = 1;
			
			//update_record('internalmail_posts',$principal_box);
			set_field('internalmail_posts', 'course', $principal_box->course , 'id', $principal_box->id);
			switch ($principal_box->subject) {
			case 'Inbox':
			case 'Deleted':
			case 'Sent';
			//$query = "UPDATE {$CFG->prefix}internalmail_posts SET course = 1 WHERE parent = $principal_box->id";
			//execute_sql($query);
			set_field('internalmail_posts', 'course', $principal_box->course , 'parent', $principal_box->id);
			break;
			case 'Courses':
			    $courses = get_records('internalmail_posts', 'parent', $principal_box->id);
			    if($courses){
				foreach ($courses as $course) 	{
				    $cur_course = explode ('::', $course->subject);
				    $course->course = (int)$cur_course[2];
				    //update_record('internalmail_posts',$course);
				    set_field('internalmail_posts', 'course', $course->course , 'id', $course->id);
				    //$courses_boxes = get_records('internalmail_posts', 'parent', $cur_course[2]);
				    $courses_boxes = get_records('internalmail_posts', 'parent', $course->id);
				    if ( $courses_boxes ) {
					foreach ($courses_boxes as $courses_box)  {
					    $courses_box->course = $course->course;
					    //update_record('internalmail_posts',$courses_box);
					    set_field('internalmail_posts', 'course', $courses_box->course , 'id', $courses_box->id);
					    //$query = "UPDATE {$CFG->prefix}internalmail_posts SET course = $cur_course[2] WHERE parent = $courses_box->id";
					    //execute_sql($query);
					    set_field('internalmail_posts', 'course', $course->course, 'parent', $courses_box->id);
					}
				    }
				}
			    }
			    
			default:
			    break;
			}
		    }
		}	
	    }
	}
		
	//$internalmail = get_record('modules', 'name', 'internalmail');
				
	$query = "SELECT * FROM {$CFG->prefix}internalmail_posts WHERE attachment <> ''";
	$attachments = get_records_sql($query);
	foreach ($attachments as $attachment) 	{	
	    $course = $attachment->course;	
	    $cur_inter=get_record('internalmail', 'course', $course);
			
	    //D'ON BE
	    //"$course/$CFG->moddata/internalmail/$cur_inter/$attachment->id";
	    //ON ANIRA
	    //"$course/$CFG->moddata/internalmail/$attachment->id";
	    $origen = make_upload_directory("$course/$CFG->moddata/internalmail/$cur_inter->id/$attachment->id");
	    $desti = make_upload_directory("$course/$CFG->moddata/internalmail");	
									
	    $newhash = md5_file ("$origen/$attachment->attachment");
	    if (file_exists("$desti/$attachment->attachment")) {
	  	
		$oldhash = md5_file ("$desti/$attachment->attachment");
		echo 'arxiu ja existent<br>';
		//mirem si són iguals
		echo $newhash.'------'.$oldhash.'<br>';
		if ($newhash !== $oldhash) {
		    echo 'NO és el mateix<br>';//!!!!!!!!!!
		    //haurem de crear un nou nom
		    //número de arxiu
		    $i = 0;
		    $namesplit = explode ('.',$attachment->attachment);
		    //el nom original sense extensió
		    $firstsplit = $namesplit[0];
		    //si continuem buscant-li un nom
		    $finded = false;
		    //mentre existeix algun arxiu amb el nom i no l'haguem trobat'
		    while (file_exists("$desti/$attachment->attachment") && !$finded) {
			echo 'buscant nom<br>';
			//recalculem el hash per veure si he trobat el que busquem
			$oldhash = md5_file ("$desti/$attachment->attachment");
			if ($newhash === $oldhash) {
			    echo "trobat $attachment->attachment!!!!";//!!!!!!!
			    $finded = true;
			} else {
			    echo "seguim buscant $i, ";//!!!!
			    //si encara no l'hem trobat posem el sufix
			    $namesplit[0] = $firstsplit.$i; 
			    //tornem a montar el nom d'arxiu'
			    $attachment->attachment = implode ('.',$namesplit);
			}
			echo "bucle $i - $attachment->attachment<br>";//!!!!!!
			$i++;
		    }
		  		
		    //si no l'hem trobat, ja tenim el nom final i podem copiar-lo.
		    if (!$finded){
			echo "és nou $attachment->attachment.<br>";//!!!!!!!!!!
			if (copy("$origen/$attachment->attachment","$desti/$attachment->attachment")) {
			    chmod("$desti/$attachment->attachment", $CFG->directorypermissions);
			    unlink("$origen/$attachment->attachment");
			    rmdir($origen);
			} else {
			    echo 'error 81<br>';
			    notify("An error happened while saving the file on the server");
			    //$attach_name = $aux;
			}
		    } else {
			echo "Ja el teniem $attachment->attachment<br>";//!!!!!!!!!!
		    }
	  				
		} else {
		    echo "és el mateix!!!!! $attachment->attachment<br>";//!!!!!!!!!!!!
		}
	    } else {
		echo 'arxiu no existent<br>';
		if (copy("$origen/$attachment->attachment","$desti/$attachment->attachment")) {
		    echo 'posant persmisos<br>';
		    chmod("$desti/$attachment->attachment", $CFG->directorypermissions);
		    unlink("$origen/$attachment->attachment");
		    rmdir($origen);
		} else {
		    echo 'error al copiar<br>';
		    notify("An error happened while saving the file on the server");
		    //$attach_name = $aux;
		}
	    }
					
	    //Esborren l'arxiu i el directori.
	    //unlink("$origen/$attachment->attachment");
	    //rmdir($origen);			
			
			
	}
		
		
    }
	
	
    return true;

}
?>