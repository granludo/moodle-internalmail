<?php

//prova de fer que faci cerques d'usuaris

//si no se li passa res, mostrarà tots els contactes accessibles pel curs
//per la resta és una mòdul normal de Moodle

require_once("../../../config.php");
require_once("../lib.php");

    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
   	$a  = optional_param('a', 0, PARAM_INT);     // internalmail ID
   	$reply  = optional_param('reply', 0, PARAM_INT);; //when we reply a message
		
if ($id) {
    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (! $internalmail = get_record("internalmail", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

} else {
    if (! $internalmail = get_record("internalmail", "id", $a)) {
        error("Course module is incorrect");
    }
    if (! $course = get_record("course", "id", $internalmail->course)) {
        error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("internalmail", $internalmail->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}

require_login($course->id);

//------------------------- FINAL DE LA PART COMUNA
//agafem la cosa
$uid = optional_param('uid',false);
$mid = optional_param('mid',false);

//agafem els paràmetres
//el text a buscar
$search = optional_param('search',false);
//cid indica que només hem de mostrar els alumnes del curs (sense cerca)
$cid = optional_param('cid',false);
//name indica els noms a mostrar (una caràcter només) (sense cerca)
$name = optional_param('name',false);
//srname indica els cognoms a mostrar (una caràcter només) (sense cerca)
$srname = optional_param('srname',false);

//l'id del lloc on es guardarà
$eid = optional_param('eid','search_res');
//indica si el resultat es mostrarà de forma compacta o no
$compact = optional_param ('compact',false);
if ($compact!=false) $compact=true;
//ens indica si el pare està directament a internalmail o bé és un popup
$pop = optional_param('pop',false);
if ($pop) $pop = true;
//en cas que hi hagi més d'una pàgina de resultats, és el número de la pàgina mostrada
$page = optional_param('page',0);
//l'id del lloc on guardarà el número de pàgina
$pid = optional_param('pid','idpage');
//el màxim de resultats a mostrar
$max = optional_param('max',50);
//l'id del botó de cerca
$butid = optional_param('butid','search_but');

//aquest és el criteri que s'ha usat per seleccionar els usuaris
$criteria = '&eid='.$eid;
if ($pop) $criteria.= '&pop='.$pop;


//el resultat
$users = array();

if ($cid != false) {
	//get_users($get=true, $search='', $confirmed=false, $exceptions='', $sort='firstname ASC',
    //$firstinitial='', $lastinitial='', $page=0, $recordsperpage=99999, $fields='*')
    $users_r = get_course_users($course->id);
	
	
	$criteria.='&cid='.$cid;
} else {
	//si no ens passen un curs concret, els mostrem tots
	//mirem si tenim nom o cognom
    if ($name) {
    	$users_r = get_users($course->id,'',false,'','firstname ASC', $name,'');
    	$criteria.='&name='.$name;
    	echo 'nom';
    } elseif ($srname) {
    	$users_r = get_users($course->id,'',false,'','lastname ASC', '',$srname);
    	$criteria.='&srname='.$srname;
    	echo 'srnom';
    } elseif ($search) {
		$users_r = get_users(true,$search);//($course->id);
		$criteria.='&search='.$search;
		echo 'search';
	}
}

//echo $criteria;

//si és compacte només agafem 15 resultats
$aux_users = array('');
if ($compact) $max=10;
if (count($users_r)>$max){
	$aux_users = array_chunk($users_r,$max);
	if (!isset($aux_users[$page])) $page=0;
	$users_r = $aux_users[$page];
} else {
	$page = 0;
}
$criteria.='&max='.$max;

//montem l'array
foreach ($users_r as $user) {
	//posem el user i montem el nom maco
	$users[] = array ($user->username,$user->firstname.' '.$user->lastname,$user->id);
}

//----------------- MONTAR EL RESULTAT


//mirem l'script que posarem
if ($pop) {
	$script = 'window.opener.addContact';
	$script_base = 'window.opener.';
} else {
	$script = 'addContact';
	$script_base = '';
}

//montem l'html
if (count($users)!=0){
	
	$html = ($search)? get_string('resultsfrom','internalmail').' <b>'.$search.'</b>:'.($page+1).' '.get_string('of','internalmail').' '.count($aux_users).'<br>':
		get_string('total').': '.($page+1).' '.get_string('of','internalmail').' '.count($aux_users);

	//si no és compact posem les fletxes de dreta i esquerra
	if ($compact) {
		$html.= '<br>';
	} else {
		//mirem si posem la fletxa esquerra
		if (count($aux_users)!=1 && $page!=0){
			//montem els paràmetres per veure la següent pàgina
			$criteria.='&page='.($page-1);
			$html.='&nbsp;<a href="javascript:reloadiframe(\''.$criteria.'\')">';
			$html.='<img src="'.$CFG->wwwroot.'/mod/internalmail/images/left.gif" />'; 
			$html.='</a>';
		}
		if (count($aux_users)!=1 && $page!=(count($aux_users)-1)) {
			$criteria.='&page='.($page+1);
			$html.='&nbsp;<a href="javascript:reloadiframe(\''.$criteria.'\')">';
			$html.='<img src="'.$CFG->wwwroot.'/mod/internalmail/images/right.gif" />'; 
			$html.='</a>';
		}
		//ara ho fem per a la dreta
		$html.= '<br>';
	}

	//comencem la taula
	$html.= ($compact)? '' : '<table class="generaltable" border=1 width="100%"><tr><th class="header c1">'.get_string('user').'</th></tr>';
	
	//imprimim els usuaris
	foreach ($users as $user){
		$pic = print_user_picture($user[2], 1, false, 30, true,false);
		if ($compact) {
			$html.= '<a href="#" onClick="'.$script.'(\''.$user[0].'\')">'.$pic.$user[1].'</a><br>';
		} else {
			$html.= '<tr><td class="cell c1"><a href="#" onClick="'.$script.'(\''.$user[0].'\')">'.$pic.$user[1].'</a></td></tr>';
		}
	}
	
	//tanquem l'html
	$html.= ($compact)? '' : '</table>';
	
} else {
	$html = get_string('noresults','internalmail');
}

//------------------------- INTERFACE

//posem la llista al pare

echo '<html>
	<body>
	<script type="text/javascript">';

//posem els resultats		
echo 'parent.changeme("'.$eid.'","'.addslashes($html).'");';

//posem el número de pàgina
echo 'parent.setPage("'.$pid.'","'.($page+1).'");';

//resaturem el botó
if (count($aux_users)==1){
	echo 'parent.setPage("'.$butid.'","Cerca");';
} else {
	echo 'parent.setPage("'.$butid.'","Cerca+");';
}

echo '</script>';

//print_object($users);

echo '</body>
	</html>';


/*

<html>
<body>
	<script type="text/javascript">
		//document.write("Hello World!");
		parent.changeme("prova","hola a tots");
	</script>
</body>
</html>
*/

?>