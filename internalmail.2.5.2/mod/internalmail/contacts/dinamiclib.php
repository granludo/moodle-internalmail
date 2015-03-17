<?php
echo	"	<script language=\"JavaScript\" >";


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
echo "location.href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$idc&option=1&tousr=\"+email;";

}

echo " }";


echo "</SCRIPT>";

?>

<script type="text/javascript">

	//posa una direcció directament al per, eliminant-ne la resta
	function setContact(email) {
		window.document.theform.destiny.value = email;
	}

	//cid: id de l'element a canviar-li el contingut
	function changeme (cid,txt) {
		document.getElementById(cid).innerHTML = txt;
	}
	
	//cid: element on assignat el valor
	function setPage (cid,txt) {
		document.getElementById(cid).value = txt;
	}
	
	//el mític toggle per modificar la visibilitat
	function toggle(obj) {
	var el = document.getElementById(obj);
	if ( el.style.display != 'none' ) {
		el.style.display = 'none';
	}
	else {
		el.style.display = '';
	}

}
</script>

<?php

?>