<?php
  //This file contains internalmail's file management functions

require_once("$CFG->dirroot/mod/internalmail/lib.php");
require_once("$CFG->dirroot/lib/filelib.php");
require_once($CFG->dirroot.'/lib/uploadlib.php');

define('INTERNALMAIL_POST', 'msg');

/* True if FAILURE */
function internalmail_save_attached_files($course,$maxbytes) {
    global $CFG, $_FILES;

    //echo "STEP 1";
    $filelocation = optional_param('attachdir', NULL, PARAM_CLEAN);

    $aux->error=1;

    /* get out of here if no file was attached at all */
    if (! is_uploaded_file($_FILES['attachment']['tmp_name']) ) {
	//error("EROR 1");
        return $aux;
    }

    //$dest_dir_temp = $CFG->dataroot . '/temp';
    //$status = check_dir_exists($dest_dir_temp,true, true);

    $dest_dir_internalmail =  $CFG->dataroot . '/temp/internalmail';
    $status = check_dir_exists($dest_dir_internalmail,true, true);

    $locationinfo = $filelocation;
    $AttachDir = "temp/internalmail/" .$locationinfo . '/';

    //echo "$AttachDir";
    $inputfilename = 'attachment';
    $handlecollision = true;
    $deleteothers = false;
    $recoverifmultiple = false;
    $um = new upload_manager($inputfilename,$deleteothers,$handlecollision,$course,$recoverifmultiple,$maxbytes);
    //notify("This is :$course->id/$CFG->moddata/internalmail/$internalmail->id/$miss_id");

    //echo "$locationinfo";
    //if (valid_uploaded_file($attach) || $opt) {
    if ($um->process_file_uploads($AttachDir)) {
	return true;
	//$um->get_new_filename();

	// Check to make sure the same file is not attached twice

    } else {
	return $aux;
    }
}


function internalmail_attachment_delete_temp_file($filenames) {
    global $CFG;

    //$filenames = optional_param('tmpfilename', NULL, PARAM_CLEAN);
    if ( count($filenames) <= 0 ) {
	echo "Files not found";
	return true;
    }

    $filelocation = optional_param('attachdir', NULL, PARAM_CLEAN);

    $tempfiles_location = $CFG->dataroot . '/temp/internalmail/' . $filelocation;
    foreach ( $filenames as  $filename) {
	$filepath = $tempfiles_location . '/' .  $filename;
	//echo "internalmail_attachment_delete_temp_file:$filepath";
	if (file_exists($filepath)) {
	    unlink($filepath);
	}
    }
    return true;
}


function internalmail_attachment_delete_temp_dir() {
    global $CFG;

    $filelocation = optional_param('attachdir', NULL, PARAM_CLEAN);
    $tempfiles_location = $CFG->dataroot . '/temp/internalmail/' . $filelocation;

    //echo "internalmail_attachment_delete_temp_dir: $tempfiles_location";
    if (is_dir($tempfiles_location)) {
	if ($files = get_directory_list($tempfiles_location)) {
	    foreach ($files as $key => $file) {
		$tmp_filename = $tempfiles_location . '/' . $file;
		unlink($tmp_filename);
	    }
	}
	rmdir($tempfiles_location);
    }
    return true;
}


function internalmail_add_attachment($course,$maxbytes) {
  global $CFG;
	
  $courseid = $course->id;
  //$course_module = get_record("course_modules","id","$id");
  //$course = get_record("course","id","$course_module->course");
  //$internalmail=get_record("internalmail","course","$course->id");

  //$maxbytes = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes, $internalmail->maxbytes);
  //$attach_name = clean_filename($attach['name']);

  //notify("This is attach_name: $attach_name");

  //$AttachDir = "$course->id/$CFG->moddata/internalmail/"
  //.$locationinfo . '/';

  if ( is_uploaded_file($_FILES['attachment']['tmp_name']) ) {
      $saveattach_file = internalmail_save_attached_files($course,$maxbytes);
  }

  $locationinfo = internalmail_get_attachment_location($courseid); // INTERNALMAIL_POST . time();
  $aux->error = 1;

  $filelocation = optional_param('attachdir', NULL, PARAM_CLEAN);
  
  $tempfiles_location = $CFG->dataroot . '/temp/internalmail/' . $filelocation;
  // echo $tempfiles_location . "<br>";
  // Check to make sure the file directory exist.
  if ( !is_dir($tempfiles_location) ) {
      // No files were added
      return NULL;
  }

  if (!$files = get_directory_list($tempfiles_location)) {
      //echo "NO Files found";
      internalmail_attachment_delete_temp_dir();
      return NULL;
  }

  $file_dir_tmp = "$courseid/$CFG->moddata/internalmail/" . $locationinfo;
  $final_file_location = $CFG->dataroot ."/". $file_dir_tmp;
  $status = check_dir_exists($final_file_location, true, true);

  //echo "Final: " . $final_file_location . "<br>";

  //error("DD");
  if (dirRename($tempfiles_location, $final_file_location) ) {
      return $locationinfo;
  } else {
      echo "internalmail_add_attachment: Rename <br />$tempfiles_location <br />$final_file_location<br /> <h1>FAILED</h1> <br />";
      return $aux;
  }
}


function internalmail_print_attachments($post) {

    global $CFG;

    if ( $post->attachment ) {
	//echo "Step 1";
	//$filearea = internalmail_file_area_name($post) . '/'. $post->attachment;
	$output = "";
	if ( $basedir = internalmail_file_area($post) ) {
	    $filedir = $basedir .'/'. $post->attachment;

	    if ($files = get_directory_list($filedir)) {
		require_once($CFG->libdir.'/filelib.php');
		foreach ($files as $key => $file) {
		    $icon = mimeinfo('icon', $file);
		    $output .= "\n <div class=\"internalmail_attachments\">\n  ";
		    $output .= '<img align="middle" src="'.$CFG->pixpath.'/f/'.$icon.'" height="16" width="16" alt="'.$icon.'" />';
		    $output .= "<a href=\"$CFG->wwwroot/file.php?file=/$post->course/$CFG->moddata/internalmail/$post->attachment/$file\" target=\"blank\">$file</a>";
		    $output .= "\n </div>";
		}
	    }
	}
	echo $output;
    }
}



// A function to copy files from one directory to another one, including subdirectories and
// nonexisting or newer files. Function returns number of files copied.
// This function is PHP implementation of Windows xcopy  A:\dir1\* B:\dir2 /D /E /F /H /R /Y
// Syntaxis: [$number =] dircopy($sourcedirectory, $destinationdirectory [, $verbose]);
// Example: $num = dircopy('A:\dir1', 'B:\dir2', 1);

function dircopy($srcdir, $dstdir, $verbose = false) {
    $num = 0;
    if(!is_dir($dstdir)) {
	mkdir($dstdir);
    }
    if($curdir = opendir($srcdir)) {
	while($file = readdir($curdir)) {
	    if($file != '.' && $file != '..') {
			$srcfile = $srcdir . '/' . $file;
			$dstfile = $dstdir . '/' . $file;
			if(is_file($srcfile)) {
			    if(is_file($dstfile)) {
				$ow = filemtime($srcfile) - filemtime($dstfile);
			    } else {
				$ow = 1;
			    }
			    if($ow > 0) {
				if($verbose) {
				    echo "Copying '$srcfile' to '$dstfile'...";
				}
				if(copy($srcfile, $dstfile)) {
				    touch($dstfile, filemtime($srcfile));
				    $num++;
				    if($verbose) {
					echo "OK\n";
				    }
				} else {
				    echo "Error: File '$srcfile' could not be copied!\n";
				}
			    }                 
			}
			else if(is_dir($srcfile)) {
			    $num += dircopy($srcfile, $dstfile, $verbose);
			}
	    }
	}
	closedir($curdir);
    }
    return $num;
}


// A function to move files from one directory to another one, including subdirectories and
// nonexisting or newer files. Function returns number of files moved.
// This function is PHP implementation of Windows xcopy  A:\dir1\* B:\dir2 /D /E /F /H /R /Y
// Syntaxis: [$number =] dirRename($sourcedirectory, $destinationdirectory [, $verbose]);
// Example: $num = dirRename('A:\dir1', 'B:\dir2', 1);

function dirRename($srcdir, $dstdir, $verbose = false) {
    $num = 0;
    if (!is_dir($dstdir)) {
		mkdir($dstdir);
	    }
	    if ($curdir = opendir($srcdir)) {
		while ($file = readdir($curdir)) {
		    if ($file != '.' && $file != '..') {
			$srcfile = $srcdir . '/' . $file;
			$dstfile = $dstdir . '/' . $file;
			if (is_file($srcfile)) {
			    if (is_file($dstfile)) {
				$ow = filemtime($srcfile) - filemtime($dstfile);
			    } else {
				$ow = 1;
			    }
			    if ($ow > 0) {
				if ($verbose) {
				    echo "Copying '$srcfile' to '$dstfile'...";
				}
				if (rename($srcfile, $dstfile)) {
				    touch($dstfile, filemtime($srcfile));
				    $num++;
				    if ($verbose) {
					echo "OK\n";
				    }
				} else { 
				    echo "Error: File '$srcfile' could not be copied!\n";
				}
			    }                 
			} else if (is_dir($srcfile)) {
			    $num += dirRename($srcfile, $dstfile, $verbose);
			}
		    }
		}
		closedir($curdir);
		rmdir($srcdir);
    }
    return $num;
}

?>
