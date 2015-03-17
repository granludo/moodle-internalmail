<?php

class block_search extends block_base {

	//funció que es crida al arrancar una instància del mòdul
    function init() {
        $this->title = get_string('search');
        $this->version = 2004081200;
    }
	
	function get_content() 
	{
		global $USER,$CFG;
	
		if($this->content !== NULL) 
		{
    	return $this->content;
    }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = get_string('search');
		
		//montem el formulari
		$query=$_SERVER[QUERY_STRING];
		$aux=split('=',$query);
		$aux2=$aux[1];
		$aux=split('&',$aux2);
		$id=$aux[0];
		
		$form = '<FORM METHOD="POST" ACTION="view.php?id='.$id.'&option=10">
					<table>
					<tr><td>
					<INPUT type="text" name="inform[field]">
					</td></tr>
					<tr><td align=right>
					<INPUT type="submit" name="inform[but]" value="'.get_string('search').'">
					</td></tr>
					</table>
				</FORM>';
		
		
		//$form.=$this->get_results();
		
		$this->content->text = $form;
		
		return $this->content;

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

    /**
     * Are you going to allow multiple instances of each block?
     * If yes, then it is assumed that the block WILL USE per-instance configuration
     * @return boolean
     * @todo finish documenting this function by explaining per-instance configuration further
     */
	 //SI NO VOLS PERMETRES QUE HI HAGI MÉS D'UNA INSTÀNCIA DEL BLOC
	 //ESBORRA AQUESTA FUNCIÓ
    function instance_allow_multiple() {
        // Are you going to allow multiple instances of each block?
        // If yes, then it is assumed that the block WILL USE per-instance configuration
        return true;
    }

}

?>