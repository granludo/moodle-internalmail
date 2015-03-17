<?PHP

class block_unique_template extends block_base {

	//funció que es crida al arrancar una instància del mòdul
    function init() {
        $this->title = get_string('block_unique_template', 'internalmail');
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
		
		$this->content->text = 'This is a unique instance bloc';
		
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
}

?>