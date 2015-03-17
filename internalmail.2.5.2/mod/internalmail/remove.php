<?php
/*File commented by Ferran Recio & David Castro
 * This file executes de especified option to the selected mails.
 * It can delete, set it readed, set it unread o do nothing.*/

require_once("../../config.php");
require_once("lib.php");

//optional_variable($post_id);
//optional_variable($mode);
$id = optional_param('id', 0, PARAM_INT);
$mode = optional_param('mode', 0, PARAM_INT);
$post_id = optional_param('post_id', 0, PARAM_INT);

global $USER;
$error=false;

//mirem si ns han passat algo pels formularis.
if(!$frm=data_submitted()) {
	if(empty($id)){
		$error=true;
	}
	else{
		$del=true;
		$msg=$post_id;
	}
} else {
 $mode=$frm->mode;
}

//mirem el mode d'eliminació
switch($mode) {
	case "inbox":
		$option='2';
	break;
	case "sent":
		$option='3';
	break;
	case "deleted":
		$option='4';
	break;
	case "copies":
		$option='12';
	break;
}

//Mirem queè volem fer amb els mails seleccionats
if($frm->Operation==REM && !$error) {
	//WE WANT TO REMOVE THEM ALL
	
	//get selected messages
	if(!isset($frm->ch)) {
		$frm->ch=array();
	}
	
	foreach($frm->ch as $msg) {
		//if the message doesn't exist
		if( ! $post=get_record("internalmail_posts","id",$msg)) {
				$error=true;
				$err="getrecord";
		}
		
		//deleted
		//if messages are already in trahs, it will be deleted permanently
		if($option!=4) {
			
			$oldpost=$post;
			$post->mailed=1;
			if($option==2) {
				$post->parent=$post->parent+2;
				//insertar com esborrat a l'historic
				//mirem si ja ha estat insertat com a esborrat
				$miss_hist=get_record_sql("SELECT h.* 
						FROM {$CFG->prefix}internalmail_history h 
						WHERE h.mailid = $post->id
						AND h.event= 'deleted'");
				//si no ha estat insertat com a llegit l'insertem
				if(empty($miss_hist->id)) {
					//busquem el parent on penjar-lo
					$parent_hist=get_record_sql("SELECT h.parent
									FROM {$CFG->prefix}internalmail_history h
									WHERE h.mailid = $post->id");
									
					$hist->mailid=$post->id;
					$hist->time=time();
					$hist->event="deleted";
					$hist->userid=$USER->id;
					$hist->parent = $parent_hist->parent;
					$hist->id = insert_record("internalmail_history",$hist);
				}
			} else if($option==3) {
				$post->parent=$post->parent+1;
			} else if($option==12) {
				$post->parent=$post->parent-1;
			}
			
			$post->message=str_replace("'","\'",$post->message);
			$post->subject=str_replace("'","\'",$post->subject);
			if(! update_record("internalmail_posts",$post)) {
				$error=true;
				$err="update";
			}
		} else {
			$aux=internalmail_get_subject($post);
			if($aux[0]!="RIO26") { //ctr //es missatge curs principal
				$post->course=$aux[1];
    		} else {
	    	$post->course=1;	
	    	}
	    	
		    $internalmail = get_record("internalmail","course",$post->course);
		    $post->internalmail= $internalmail->id;
		  	internalmail_delete_old_attachments($post);
		  	
			if(!delete_records("internalmail_posts","id",$msg)) {
				$error=true;
				$err="delete";
			}
			
			$hist=get_record("internalmail_history","mailid",$msg);
			delete_records("internalmail_history","mailid",$msg);
			$other= get_records("internalmail_history","parent",$hist->parent);
			if(empty($other)) {
				delete_records("internalmail_history","id",$hist->parent);
			}
		}
	}
		
	if(!$error) {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option",get_string('mensaje borrado','internalmail'),2);
	} else {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option",get_string('error','internalmail')." ".$err,2);	
	}
} else if($frm->Operation==NOT && !$error) {
	//WE WANT TO DO NOTHING
	redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option");	
} else if($frm->Operation==RED && !$error) {
	//WE WANT TO SET THEM AS READED
	if(!isset($frm->ch)) {
		$frm->ch=array();
	}
	
	foreach($frm->ch as $msg) {
		
		//deleted
		if($option!=4) {
			if( ! $post=get_record("internalmail_posts","id",$msg)) {
				$error=true;
				$err="get_Record";
			}
			
			if($option==2 || $option==3 || $option==12) {
				$post->message=str_replace("'","\'",$post->message);
				$post->subject=str_replace("'","\'",$post->subject);
				$post->mailed=1;				
				if( ! update_record("internalmail_posts",$post) ) {
					$error=true;
					$err="update";
				}
			}
		}
	}
		
	if(!$error) {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option","",0);
	} else {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option",get_string('error','internalmail')." ".$err,2);	
	}
	
} else if($frm->Operation==NRE && !$error) {
	//WE WANT TO SET THEM AS NOT READED
	
	if(!isset($frm->ch)) {
		$frm->ch=array();
	}
	
	foreach($frm->ch as $msg) {
		//deleted
		if($option!=4) {
			if( ! $post=get_record("internalmail_posts","id",$msg)) {
				$error=true;
				$err="get_Record";
			}
			
			if($option==2 || $option==3 || $option==12) {
				$post->message=str_replace("'","\'",$post->message);
				$post->subject=str_replace("'","\'",$post->subject);
				$post->mailed=0;
				if( ! update_record("internalmail_posts",$post) ) {
					$error=true;
					$err="update";
				}
			}
		}
		
	}
		
	if(!$error) {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option","",0);
	} else {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option",get_string('error','internalmail')." ".$err,2);	
	}
} else if($frm->Operation==RES && !$error) {
	
	if(!isset($frm->ch)) {
		$frm->ch=array();
	}
	
	foreach($frm->ch as $msg) { 
		//deleted
		if($option==4) {
			if( ! $post=get_record("internalmail_posts","id",$msg)) {
				$error=true;
				$err="get_record";
			}
			
			$post->message=str_replace("'","\'",$post->message);
			$post->subject=str_replace("'","\'",$post->subject);
			$post->parent=$post->parent-2;
			if( ! update_record("internalmail_posts",$post)) {
					$error=true;
					$err="update";
			}
		}
	}
		
	if(!$error) {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option","",0);
	} else {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=$option",get_string('error','internalmail')." ".$err,2);	
	}
} else if($del) {
	if( ! $post=get_record("internalmail_posts","id",$msg)) {
		$error=true;
		$err="getrecord";
	}
	
	//deleted
	if($option!=4) {
		$oldpost=$post;
		$post->mailed=1;
		if($option==2) {
			$post->parent=$post->parent+2;
			//insertar com esborrat a l'historic
			//mirem si ja ha estat insertat com a esborrat
			$miss_hist=get_record_sql("SELECT h.* 
						FROM {$CFG->prefix}internalmail_history h 
						WHERE h.mailid = $post->id
						AND h.event= 'deleted'");
			//si no ha estat insertat com a llegit l'insertem
			if(empty($miss_hist->id)) {
				//busquem el parent on penjar-lo
				$parent_hist=get_record_sql("SELECT h.parent
								FROM {$CFG->prefix}internalmail_history h
								WHERE h.mailid = $post->id
							");
				$hist->mailid=$post->id;
				$hist->time=time();
				$hist->event="deleted";
				$hist->userid=$USER->id;
				$hist->parent = $parent_hist->parent;
				$hist->id = insert_record("internalmail_history",$hist);
			}
		} else if($option==3) {
			$post->parent=$post->parent+1;
		} else if($option==12) {
			$post->parent=$post->parent-1;
		}
		$post->message=str_replace("'","\'",$post->message);
		$post->subject=str_replace("'","\'",$post->subject);
		if(! update_record("internalmail_posts",$post)) {
			$error=true;
			$err="update";
		}
			
	} else {
			
		$aux=internalmail_get_subject($post);
		if($aux[0]!="RIO26") {
      		$post->course=$aux[1];
    	} else {
    	$post->course=1;	
    	}
	    $internalmail = get_record("internalmail","course",$post->course);
	    $post->internalmail= $internalmail->id;
		internalmail_delete_old_attachments($post);
		if(!delete_records("internalmail_posts","id",$msg)) {
				$error=true;
				$err="delete";
		}
		$hist=get_record("internalmail_history","mailid",$msg);
		delete_records("internalmail_history","mailid",$msg);
		$other= get_records("internalmail_history","parent",$hist->parent);
		
		if(empty($other)) {
				delete_records("internalmail_history","id",$hist->parent);
		}
	}

	if(!$error) {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id&option=$option",get_string('mensaje borrado','internalmail'),2);
	} else {
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$id&option=$option",get_string('error','internalmail')." ".$err,2);	
	}
} else {
	redirect("$CFG->wwwroot",get_string('error','internalmail'),2);	
}

?>