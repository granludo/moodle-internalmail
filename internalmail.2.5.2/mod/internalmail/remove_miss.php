<?php
require_once("../../config.php");
require_once("lib.php");


global $USER;
$error=false;

if(!$frm=data_submitted())
{
	$error=true;
}
//print_r($frm);

if($frm->Operation==REM && !$error)
{
	
	if(!isset($frm->ch))
	{
		$frm->ch=array();
	}
	foreach($frm->ch as $msg)
	{ 
		//print_r($frm->ch);
		$aux=split(':',$msg);
		
		if($aux[1]==1)
		{
			if(!delete_records("message","id",$aux[0]))
			{
				$error=true;
			}
			
			
		}
		else if($aux[1]==2)
		{
			if(!delete_records("message_read","id",$aux[0]))
			{
				$error=true;
			}
			
		}
		else
		{
			$error=true;
		}
	}
}

if(!$error)
	{
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=7",get_string('mensaje borrado','internalmail'),2);
	}
	else
	{
		redirect("$CFG->wwwroot/mod/internalmail/view.php?id=$frm->id&option=7",get_string('error','internalmail')." ".$err,2);	
	}