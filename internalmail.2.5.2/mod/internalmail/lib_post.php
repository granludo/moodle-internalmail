<?PHP
//This file contains internalmail's file management functions

require_once("$CFG->dirroot/mod/internalmail/lib.php");
require_once("$CFG->dirroot/lib/filelib.php");

function internalmail_add_attachment($attach,$id,$miss_id,$opt=0)
{
	global $CFG,$course;
	
	
	$course_module=get_record("course_modules","id","$id");
	$course=get_record("course","id","$course_module->course");
	$internalmail=get_record("internalmail","course","$course->id");	
	$maxbytes = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes, $internalmail->maxbytes);
	$attach_name = clean_filename($attach['name']);
	$aux->error=1;
	
	//echo 'dins lib_post<br>';
	
	//if (!valid_uploaded_file($attach)) echo 'nom mal!!!<br>';
	//if (!$opt) echo 'per opt!!!<br>';
	
	//posem els attach a lloc
	if (valid_uploaded_file($attach) || $opt) {	
	  if ($maxbytes and $attach['size'] > $maxbytes)  {
	  	  //echo 'error de tamany<br>';
		  return $aux;
	  }
	  if (! $attach_name) {
	  	notify("This file had a wierd filename and couldn't be uploaded");
	  } else if (! $dir = make_upload_directory("$course->id/$CFG->moddata/internalmail" )) {
			//else if (! $dir = make_upload_directory("$course->id/$CFG->moddata/internalmail/$internalmail->id/$miss_id" ))
			//echo 'error de upload directory<br>';
		  	notify("Attachment could not be stored");
		    $attach_name = $aux;
	  } else {
	  	//echo 'de moment anem bé<br>';
	  	//si cal canviem el nom de l'arxiu'
	  	//agafem el has del temporal
	  	/*echo $attach_name;
	  	echo '+';
	  	echo $attach['tmp_name'];*/
	  	if(!@$newhash = md5_file ( $attach['tmp_name'])){
	  		$url_attach = $CFG->dataroot.'/'.$course->id.'/'.$CFG->moddata.'/internalmail/'.$attach_name;
	  		$reenvio = true;
	  		$newhash = md5_file ( $CFG->dataroot.'/'.$course->id.'/'.$CFG->moddata.'/internalmail/'.$attach_name);
	  	}
	  	//mirem si ja esixteix un arxiu amb aquest nom
	  	if (file_exists("$dir/$attach_name")) {
	  		
	  		//calculem el hash de l'arxiu existent
	  		$oldhash = md5_file ("$dir/$attach_name");
	  		//echo 'arxiu ja existent<br>';
	  		//mirem si són iguals
	  		//echo $newhash.'------'.$oldhash.'<br>';
	  		if ($newhash !== $oldhash) {
		  		//echo 'NO és el mateix<br>';//!!!!!!!!!!
		  		//haurem de crear un nou nom
		  		//número de arxiu
		  		$i = 0;
		  		$namesplit = explode ('.',$attach_name);
		  		//el nom original sense extensió
		  		$firstsplit = $namesplit[0];
		  		//si continuem buscant-li un nom
		  		$finded = false;
		  		//mentre existeix algun arxiu amb el nom i no l'haguem trobat'
		  		while (file_exists("$dir/$attach_name") && !$finded) {
		  			//echo 'buscant nom<br>';
		  			//recalculem el hash per veure si he trobat el que busquem
		  			$oldhash = md5_file ("$dir/$attach_name");
		  			if ($newhash === $oldhash) {
		  				//echo "trobat $attach_name!!!!";//!!!!!!!
		  				$finded = true;
		  			} else {
		  				//echo "seguim buscant $i, ";//!!!!
		  				//si encara no l'hem trobat posem el sufix
			  			$namesplit[0] = $firstsplit.$i; 
			  			//tornem a montar el nom d'arxiu'
			  			$attach_name = implode ('.',$namesplit);
		  			}
		  			//echo "bucle $i - $attach_name<br>";//!!!!!!
		  			$i++;
		  		}
		  		
		  		//si no l'hem trobat, ja tenim el nom final i podem copiar-lo.
		  		if (!$finded){
		  			//echo "és nou $attach_name.<br>";//!!!!!!!!!!
			  		if ($reenvio){
			  			if (copy($url_attach, "$dir/$attach_name")) {
					  	 	chmod("$dir/$attach_name", $CFG->directorypermissions);
					    } else {
					    	echo 'error 81<br>';
							echo $url_attach;
							echo $dir.'/'.$attach_name;
							notify("An error happened while saving the file on the server");
					     	$attach_name = $aux;
					    }
			  		}if (!$reenvio){
			  			if (copy($attach['tmp_name'], "$dir/$attach_name")) {
					  	 	chmod("$dir/$attach_name", $CFG->directorypermissions);
					    } else {
					    	//echo 'error 81<br>';
								notify("An error happened while saving the file on the server");
					     	$attach_name = $aux;
					    }
			  		}
		  		} else {
		  			//echo "Ja el teniem $attach_name<br>";//!!!!!!!!!!
		  		}
	  		} else {
	  			//echo "és el mateix!!!!! $attach_name<br>";//!!!!!!!!!!!!
		  	}
	  	} else {
	  		//echo 'arxiu no existent<br>';
			if (copy($attach['tmp_name'], "$dir/$attach_name")) {
				//echo 'posant persmisos<br>';
		  	 	chmod("$dir/$attach_name", $CFG->directorypermissions);
		    } else {
		    	//echo 'error al copiar<br>';
					notify("An error happened while saving the file on the server");
		     	$attach_name = $aux;
		    }
	  	}
	  	//incrementem el comptador sobre l'arxiu
	  	//echo 'anem a incrementar'; //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	  	//internalmail_file_num_increase($attach_name,$internalmail->id);
	  	
	  }
	} else {
		//echo 'nom raro<br>';
		$attach_name = $aux;
	}
	//echo 'retornant'.$attach_name.'<br>';
	return $attach_name;

}


function internalmail_print_attachments($post) 
{

global $CFG;

/*      por si hubiera mas adjuntos
$files=get_directory_list("$CFG->dataroot/$post->course/$CFG->moddata/internalmail/$post->internalmail/$post->id");
print_r($files);
*/
$icon = mimeinfo("icon", $post->attachment);
$image = "<img border=\"0\" src=\"$CFG->pixpath/f/$icon\" height=\"16\" width=\"16\">";
echo $image;
echo "&nbsp;";
//echo "<a href=\"$CFG->wwwroot/file.php?file=/$post->course/$CFG->moddata/internalmail/$post->internalmail/$post->id/$post->attachment\" target=\"blank\">$post->attachment</a>";
echo "<a href=\"$CFG->wwwroot/file.php?file=/$post->course/$CFG->moddata/internalmail/$post->attachment\" target=\"blank\">$post->attachment</a>";
}


?>