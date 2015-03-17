<?php

//XIBATO!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//Arxiu amb el resultat	
/*if (!$xinter = fopen("/usr/local/apache2/htdocs/moodlemgr/moodledata/admin/ice/inter.txt", 'a+', 1)) {
	error("No abre", "index.php");	
	exit;
}*/
/*$log = "\n-----------------------------\nincludes!!!!!\n";
if (fwrite($xinter, $log) === FALSE) {
	error(get_string("filenotwrite",$modul,$path_sortida."/".$nom_arxiu_log), "index.php");
	exit;
}*/


require_once("$CFG->dirroot/mod/internalmail/lib.php");

/*if (fwrite($xinter, "\ndesprés dels includes\n") === FALSE) {
	error(get_string("filenotwrite",$modul,$path_sortida."/".$nom_arxiu_log), "index.php");
	exit;
}*/

//------- API FUNCTIONS --------

/**Dona d'alta un correu al curs. Concretament necessita:
 * @param $nomcorreu=nom de la bústia!
 * @param $maxmida= mida màxima dels annexes!
 * @param $curs= id del curs al que donem d'alta!
 * @param $nommodul= nom del mòdul "simplemail"?
 * @param $admin_mail = id del usuari administrador del correu ?
 */
function alta_correu_curs($nomcorreu, $maxmida, $curs, $nommodul, $admin_mail ){
	
	/// Given an object containing all the necessary data, 
	/// (defined by the form in mod.html) this function 
	/// will create a new instance and return the id number 
	/// of the new instance.
	global $CFG, $USER, $PAGE;//, $xinter;
	
	//$internalmail = = new Object;
	
	
	//camps de la taula internalmail
	$internalmail->name = $nomcorreu;
	$internalmail->maxbytes = 512000;
	$internalmail->course = $curs;
	$internalmail->timemodified = time();
    $internalmail->intro = "''::text";
    $internalmail->open = 2;
    $internalmail->assessed = 1;
    $internalmail->assesspublic = 1;
    $internalmail->forcesubscribe = 0;
    $internalmail->rsstype = 0;
    $internalmail->rssarticles = 0;
    $internalmail->scale = 0;
    $internalmail->assesstimestart = 0;
    $internalmail->assesstimefinish = 0;
    
    //camps afegits a la nova versió de l'internalmail
    $internalmail->notext = 'general';
    
    //variables auxiliars
    $internalmail->type = "";
    //l'id del course-module
	$internalmail->coursemodule = "";
	//la secció on estarà
	$internalmail->section = "0";
	//l'id de internalmail dins la taula modules
	$internalmail->module = "";
	//el nom del mòdul a instal·lar
	$internalmail->modulename = 'internalmail';
	//l'id de la instància dins la taula internalmail	
	$internalmail->instance = "";
	//el mode, no sembla fer-se servir         
	$internalmail->mode = "add";
	
	
	//fwrite($xinter, "\nComencem la creació\n");//!!!!!!!!!!!!!!!
	//mirem si existeix l'internalmail
	if (!$modul = get_record("modules", "name", $internalmail->modulename)) {
		//fwrite($xinter, "error al agafar el module\n");//!!!!!!!!!!!!!!!
		$res = 1;
		return($res);	
    }
	
	//fwrite($xinter, "bé1\n");//!!!!!!!!!!!!!!!
	
	//es guarda el id del mòdul
    $internalmail->module = $modul->id;
	
	//es mira si existeix el curs
 	if (!$course = get_record("course", "id", $internalmail->course)) {
 		//fwrite($xinter, "error al agafar course\n");//!!!!!!!!!!!!!!!
        $res = 2;
        return($res);
    }
	
	//fwrite($xinter, "bé2\n");//!!!!!!!!!!!!!!!
	
	//agafem l'usuari administrador
	/*$user_mail = get_record("user", "username", $admin_mail);
	if (!$user_mail) {   
		//fwrite($xinter, "error al agafar user\n");//!!!!!!!!!!!!!!!    
        $res= 3;
        return($res);
    }*/
	
	//fwrite($xinter, "bé3\n");//!!!!!!!!!!!!!!!
	
	//creem la instància
	//$return = internalmail_add_instance($internalmail, $user_mail->id);
	$return = internalmail_add_instance($internalmail);
	if (!$return) {  
		//fwrite($xinter, "error al crear la instància\n");//!!!!!!!!!!!!!!!     
        $res= 4;
        return($res);
    }
	
	//fwrite($xinter, "bé4\n");//!!!!!!!!!!!!!!!
	
	//agafem el groupmode
	$internalmail->groupmode = $course->groupmode;  /// Default groupmode the same as course
    $internalmail->instance = $return;
    
    //fwrite($xinter, "bé5\n");//!!!!!!!!!!!!!!!
    
	//posem el coursemodule
	if (! $internalmail->coursemodule = add_course_module($internalmail) ) {  
		//fwrite($xinter, "error al crear course-module\n");//!!!!!!!!!!!!!!!
        $res= 5;	
        return($res);
    }
    
    //fwrite($xinter, "bé6\n");//!!!!!!!!!!!!!!!
    
    //afegim a la secció
    if (! $sectionid = add_mod_to_section($internalmail) ) {
    	//fwrite($xinter, "error al crear section\n");//!!!!!!!!!!!!!!!
        $res= 6;
        return($res);
    }

	//fwrite($xinter, "bé7\n");//!!!!!!!!!!!!!!!

    $visible = get_field("course_sections","visible","id",$sectionid);
	if (! set_field("course_modules", "visible", $visible, "id", $internalmail->coursemodule)) {
		//fwrite($xinter, "error al modificar course-module\n");//!!!!!!!!!!!!!!!
        $res= 7;
        return($res);        
	}   
	
	//fwrite($xinter, "bé8\n");//!!!!!!!!!!!!!!!
	
    if (! set_field("course_modules", "section", $sectionid, "id", $internalmail->coursemodule)) {
    	//fwrite($xinter, "error al modificar per segon cop course-module\n");//!!!!!!!!!!!!!!!
        $res= 8;    	
        return($res);
    }
    
    
	//fem un rebuild cache
	//fwrite($xinter, "abans del rebuilt cache\n");//!!!!!!!!!!!!!!!
	rebuild_course_cache($course->id);
	//fwrite($xinter, "Fet rebuilt cache\n");//!!!!!!!!!!!!!!!
	return(100);
}

/**
 * Baixa d'un "internalmail" en un curs
 * @param $idadmin=id del usuari administrador del correu
 * @param $id_course= id del curs al que es vol esborrrar l'instància del "internalmail"
 * @return true si ok, false si no ha pogut donar-lo de baixa
 */
/*function baixa_correu_curs($idadmin, $id_course){
	
	require_once("$CFG->dirroot/mod/internalmail/lib.php");
	
	//agafem l'id de l'activitat
	if (! ($module=get_record("modules","name","internalmail"))) {
		//echo 'in0';
		return false;
	}
	//agafem l'id del course module
	if(!$cm=get_record("course_modules","course",$id_course,"module",$module->id)){
		//echo 'in1';
		return false;
	}
	//agafem la secció on es troba (conte de la vella)
	//agafem totes les seccions
	if(!$sects = get_records('course_sections','course',$id_course)){
		$res = 6;
		return $res;
	}
	//busquem el cm
	foreach ($sects as $sect){
		$scms = explode(',',$sect->sequence);
		if (in_array($cm->id,$scms)){
			$section = $sect; 
		}
	}
	
	//eliminem l'internalmail
	if (!internalmail_delete_instance($cm->instance)){
		$res = 4;
		return($res);
	}
	
	//treiem el coursemodule
	if (!delete_course_module($cm->id) ) {        
        $res= 5;	
        return($res);
    }
    //el treiem de la secció
    if (!delete_mod_from_section($cm->id,$section->id) ) {        
        $res= 6;
        return($res);
    }
    
	//fem un rebuild cache
	rebuild_course_cache($course->id);
	return(100);
	
}*/


/**
 * dona d'alta un nou usuari al curs passat
 * 
 */
function alta_compte_usuari($curs, $usuari){

	//global $xinter;

	//fwrite($xinter, "\n------------------\nDins alta compte\n");//!!!!!!!!!!!!!!!

	global $USER;
	global $CFG;
	
	//si ja té una bústia retornem a saco un 100 (i feina feta)
	if (count_records('internalmail_posts','subject',"CUR26::$usuari::$curs")){
		//fwrite($xinter, "Ja en té\n");//!!!!!!!!!!!!!!!
		return(100);
	}
	
	//agafem la taula del curs (no és necessari)
	if (!$course = get_record("course", "id", $curs)) {
		//fwrite($xinter, "error get\n");//!!!!!!!!!!!!!!!		
		return(20);
				
    }
    
    //fwrite($xinter, "check 1\n");//!!!!!!!!!!!!!!!
    
    //agafem l'id de l'internalmail
	if (!$modul = get_record("modules", "name", 'internalmail')) {
		//fwrite($xinter, "error al agafar el module\n");//!!!!!!!!!!!!!!!
		$res = 45;
		return($res);	
    }
    
    //Si el curs no té internalmail, tampoc cal fer-ho
    if (!$cm = get_record("course_modules", "course", $curs,'module',$modul->id)) {
		//fwrite($xinter, "error get\n");//!!!!!!!!!!!!!!!		
		return(100);
				
    }
    
    //suposo que $usuari serà el nom de l'usuari i no l'id
	if (!internalmail_add_user_mailbox($usuari, $curs))	 {
		//fwrite($xinter, "error add\n");//!!!!!!!!!!!!!!!
		return(30);
	}
	
	//fwrite($xinter, "check 2 i tanco\n");//!!!!!!!!!!!!!!!
		
	return(100);
}

/**
 * el rollback de l'altra d'usuari. En realitat només necessite el userid, però
 * ens passen molta cosa
 * @param $gestor_log un arxiu on posar el log (algo hi hauries de posar)
 * @param gestor_no_proc: no en tinc ni idea
 * @param $line.... bé, ens ho passen.
 * @param $rowid: no sé pas què és.
 * @param $userid: aquest sí!!! l'id del user a esborrar
 * @param $resmail: el resultat que ha retornat el alta_compte_usuari erroni
 * @return cert sempre.
 */
function rollback_alta_compte_usuari($gestor_log, $gestor_no_proc, $line, $rowid,$userid, $resmail){
	//anem a esborrar totes les tuples
	delete_records('internalmail_contacts','userid',$userid);
	delete_records('internalmail_copiesenabled','userid',$userid);
	delete_records('internalmail_discussion','userid',$userid);
	delete_records('internalmail_groups','userid',$userid);
	delete_records('internalmail_history','userid',$userid);
	delete_records('internalmail_posts','userid',$userid);
	delete_records('internalmail_subscriptions','userid',$userid);
	return true;
}
/**
 * dona de baixa un usuari en un mail del curs.
 */
if (!function_exists('baixa_compte_usuari')) {
	function baixa_compte_usuari($id_course, $id_user){
	// Usada per esborrar la bustia d'un usuari en un curs.
	// Referencia de internalmail_remove_mailbox
	
		global $USER;
		global $CFG;
		//global $xinter;
	
		//fwrite($xinter, "\n\nDins baixa compte\n");//!!!!!!!!!!!!!!!
		
		
		//agafem les dades del curs
		if (!$course = get_record("course", "id", $id_course)) {
			//fwrite($xinter, "error curs\n");//!!!!!!!!!!!!!!!
			$res=1;
			return($res);
					
	    }
	    
	    //fwrite($xinter, "be_baixa1\n");//!!!!!!!!!!!!!!!
	    
	    //en aquest cas necessitem l'id de l'usuari.
		if (!internalmail_remove_mailbox($id_user, $id_course))	 {
			//fwrite($xinter, "errorremove\n");//!!!!!!!!!!!!!!!
			$res=2;
			return($res);
		}
		//fwrite($xinter, "be_baixa2\n");//!!!!!!!!!!!!!!!
		return(100);
	}
} else {
	//fwrite($xinter, "\nla baixa ja existeix, mola no?\n");//!!!!!!!!!!!!!!!
}

/*function baixa_compte_usuari($id_course, $id_user){
	return(100);
}*/

?>