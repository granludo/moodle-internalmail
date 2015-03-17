<?PHP

global $CFG;

/*echo "<html>";		
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


echo "</SCRIPT>";*/

//echo "</html>";


class block_search_contacts extends block_base {

	//funció que es crida al arrancar una instància del mòdul
    function init() {
        $this->title = get_string('block_Search_Contacts', 'internalmail');
        $this->version = 2006032100;
        $this->course = get_record('course','id',$this->instance->pageid);

    }
	
	function get_content() {
		
		global $USER, $CFG,$cm;
		
		if($this->content !== NULL) 
		{	
    		return $this->content;
		}
		//		$course=get_record('course','id',$this->instance->pageid);
        $this->content = new stdClass;
      
		
		//posem el formulari
		//posem el formulari de cerca
		$this->content->text = '<form method="POST" action="contacts/search.php?id='.$cm->id.'&compact=yes" target="bssearch">' .
				'<input id="sfield" type="text" name="search" onChange="setPage(\'idpage\',\'0\');"/>' .
				'<input type="submit" id="search_but" name="doit" value="'.get_string('search').'"/>' .
				'<input type="hidden" id="idpage" name="page" value="0" />' .
			'</form>';
		//posem el iframe ocult
		$this->content->text.= '<iframe id="idsearch" name="bssearch" src="contacts/search.php?id='.$cm->id.'&compact=yes" style="display:none;"></iframe>';
		//posem el div de resultats
		$this->content->text.= '<div id="search_res"></div>';
		
		return $this->content;
	}
	
	
	function get_res () {
		 
		global $inform;
		$res = 'HOLA';
		
		/*
		if (isset($dfform['result'])) {
			
			$res.= '<table border=0>
					<tr>
						<td><b>'.get_string('searchresults').': '.$dfform['field'].'</b><hr></td>
					</tr>';
			
			if (count($dfform['result']['pagename'])!=0){
				foreach ($dfform['result']['pagename'] as $result){
					$aux = $dfform['field'];
					$res.="<tr>
							<td nowrap>
							<a href=\"view.php?id=$cm->id&amp;gid=$groupmember->groupid&amp;page=$result&amp;dfsetup=dfwiki_block_search&amp;dfsearch=$aux\">$result</a>
							</td>
						</tr>";
				}
			}else{
				$res.='<tr><td>'.get_string('noresults').'</td></tr>';
			}

			
			//if (isset($dfform['incontent'])){
	
			if (count($dfform['result']['content'])!=0){
				$res.= '<tr>
					<td><b>'.get_string('resultincontent',$modname).'</b><hr></td>
				</tr>';
				foreach ($dfform['result']['content'] as $result){
					$aux = $dfform['field'];
					$res.="<tr>
							<td nowrap>
								<a href=\"view.php?id=$cm->id&amp;gid=$groupmember->groupid&amp;page=$result&amp;dfsetup=dfwiki_block_search&amp;dfsearch=$aux\">$result</a>
							</td>
						</tr>";
				}
			}
			//}			
			$res.= '</table>';
			
			
		}
		*/
		return $res;
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