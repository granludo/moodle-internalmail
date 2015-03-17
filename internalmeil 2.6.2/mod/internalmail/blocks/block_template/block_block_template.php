<?PHP

class block_block_template extends block_base {

	//funció que es crida al arrancar una instància del mòdul
    function init() {
        $this->title = get_string('block_template_name', 'internalmail');
        $this->version = 2004081200;
    }
	
	function get_content() {
		if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = 'block_template';
		
		$this->content->items[] = '<a href="http://www.google.com">google</a>';
		$this->content->icons[] = '<img src="icon.gif">';
		
		$this->content->text = 'Your text here';
		
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