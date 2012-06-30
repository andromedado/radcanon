<?php

/**
 * TabSet - Organize Content into distinct tabs
 *
 * @package RAD-Canon
 * @author Shad Downey
 */
class TabSet {
	/** @var Html */
	protected $Wrap = NULL;
	/** @var Html */
	protected $HeadWrap = NULL;
	/** @var Html */
	protected $BodyWrap = NULL;
	/** @var Html */
	protected $HeadWrappers = NULL;
	/** @var Html */
	protected $BodyWrappers = NULL;
	protected $tabHeaders = array();
	protected $tabContents = array();
	
	public function __construct(Html $Wrap = NULL,
								Html $HeadWrap = NULL,
								Html $BodyWrap = NULL,
								Html $HeadWrappers = NULL,
								Html $BodyWrappers = NULL) {
		Template::addS('tabSet');
		if (is_null($Wrap)) $Wrap = Html::n('div', 'c:tabSetWrap');
		if (is_null($HeadWrap)) $HeadWrap = Html::n('div', 'c:headWrap');
		if (is_null($BodyWrap)) $BodyWrap = Html::n('div', 'c:bodyWrap');
		if (is_null($HeadWrappers)) $HeadWrappers = Html::n('div', 'c:headWrapper');
		if (is_null($BodyWrappers)) $BodyWrappers = Html::n('div', 'c:bodyWrapper');
		$this->Wrap = $Wrap;
		$this->HeadWrap = $HeadWrap;
		$this->BodyWrap = $BodyWrap;
		$this->HeadWrappers = $HeadWrappers;
		$this->BodyWrappers = $BodyWrappers;
	}
	
	/**
	 * Expose the wrapper
	 * @return Html
	 */
	public function getWrap() {
		return $this->Wrap;
	}
	
	/**
	 * Set the wrapper
	 * @return TabSet
	 */
	public function setWrap(Html $Wrap) {
		$i = $this->Wrap->ExposeInner();
		foreach ($i as $v) {
			$Wrap->append($v);
		}
		$this->Wrap = $Wrap;
		return $this;
	}
	
	/**
	 * Prepend a tab to this Tab Set
	 * @param mixed $header What to put in the Tab Header
	 * @param mixed $content What to put in the Tab Body
	 * @return TabSet
	 */
	public function prependTab($header, $content) {
		return $this->prependTabHeader($header)->prependTabContent($content);
	}
	
	/**
	 * Add a tab header to the Tab Set
	 * @param mixed $header What to put in the Tab Header
	 * @return TabSet
	 */
	public function prependTabHeader($header) {
		array_unshift($this->tabHeaders, $header);
		return $this;
	}
	
	/**
	 * Add a tab content to the Tab Set
	 * @param mixed $content What to put in the Tab Body
	 * @return TabSet
	 */
	public function prependTabContent($content) {
		array_unshift($this->tabContents, $content);
		return $this;
	}
	
	/**
	 * Add a tab to this Tab Set
	 * @param mixed $header What to put in the Tab Header
	 * @param mixed $content What to put in the Tab Body
	 * @return TabSet
	 */
	public function addTab($header, $content) {
		return $this->addTabHeader($header)->addTabContent($content);
	}
	
	/**
	 * Add a tab header to the Tab Set
	 * @param mixed $header What to put in the Tab Header
	 * @return TabSet
	 */
	public function addTabHeader($header) {
		$this->tabHeaders[] = $header;
		return $this;
	}
	
	/**
	 * Add a tab content to the Tab Set
	 * @param mixed $content What to put in the Tab Body
	 * @return TabSet
	 */
	public function addTabContent($content) {
		$this->tabContents[] = $content;
		return $this;
	}
	
	/**
	 * Alias as __toString
	 * @return String
	 */
	public function render() {
		return "{$this}";
	}
	
	/**
	 * Render the tabset as a string
	 * @return String
	 */
	public function __toString() {
		$Wrap = clone $this->Wrap;
		$HeadWrap = clone $this->HeadWrap;
		$BodyWrap = clone $this->BodyWrap;
		$Wrap->append($HeadWrap, $BodyWrap);
		foreach ($this->tabHeaders as $header) {
			$HW = clone $this->HeadWrappers;
			$HW->a($header)->apT($HeadWrap);
		}
		$HeadWrap->a('<div style="clear:both;"></div>');
		foreach ($this->tabContents as $content) {
			$BW = clone $this->BodyWrappers;
			$BW->a($content)->apT($BodyWrap);
		}
		return "{$Wrap}";
	}
	
} // END

?>