<?php
echo "\n\n<script language=\"JavaScript\" type=\"text/javascript\">";
echo "\n<!-- // Non-Static Javascript functions";

echo "\n function addAllContacts(emails) {";

$idc = optional_param('id', 0, PARAM_INT); 
$option = optional_param('option', 0, PARAM_INT); 

if($option==1) {
    echo "\n   field=window.document.theform.destiny;";
    echo "\n   field.value=emails;";
//	echo "\n   for (i=0;i<emails.length;i++){";
//    echo "\n     field.value= field.value+ \",\" + emails[i];";		
//	echo "\n   }";
} else {
    echo "location.href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$idc&option=1&tousr=\"+email;";
}

echo "\n }";

echo "\n function addContact(email) {";

#$query=$_SERVER[QUERY_STRING];
#$aux=split('&',$query);
#$aux2=split('=',$aux[0]);
#$idc=$aux2[1];
#$aux2=split('=',$aux[1]);
#$option=$aux2[1];

$idc = optional_param('id', 0, PARAM_INT); 
$option = optional_param('option', 0, PARAM_INT); 

if($option==1) {
    echo "\n   field=window.document.theform.destiny;";
    echo "\n   if(field.value==\"\"){";
    echo "\n     field.value= email;";
    echo "\n   } else {";
    echo "\n    var bool=0;";
    echo "\n    var comprova=field.value.split(\",\");";
    echo "\n    var n=comprova.length;";
    echo "\n    var i=0;";

    echo "\n    while(i<=n && bool==0){";
    echo "\n      if (email==comprova[i]) {";
    echo "\n        bool=1;";
    echo "\n      }";
    echo "\n      i++;";
    echo "\n    }";

    echo "\n    if (bool==0) {";
    echo "\n      field.value= field.value + \",\" + email;";
    echo "\n    }";
    echo "\n   }";
} else {
    echo "location.href=\"$CFG->wwwroot/mod/internalmail/view.php?id=$idc&option=1&tousr=\"+email;";
}

echo "\n }";

?>

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
    } else {
	el.style.display = '';
    }
}
// done hiding -->
</script>

<?php

?>