<?php

class SearchForm {
	public $maxPerRow = 3;
	
	protected $sfID;
	protected $url = '';
	protected $searchValue = 'Search';
	/** @var Html $Table */
	protected $Table = NULL;
	protected $fields = array();
	protected $style = 'horizontal';
	protected $Styles = array('horizontal', 'vertical');
	protected static $ID = 1;
	
	public function __construct($url, array $fields = array(), $style = NULL) {
		Template::addS('searchForm');
		$this->url = $url;
		foreach ($fields as $field => $label) {
			$this->addField($field, $label);
		}
		$this->setStyle($style);
		$this->Table = $T = Html::n('table', 'c:search_form');
		$this->sfID = self::$ID++;
	}
	
	/**
	 * Accessor for the Search Form's Table
	 * @return Html
	 */
	public function getTable() {
		return $this->Table;
	}
	
	/**
	 * Set the Submit Button's Value
	 * @param String $val
	 * @return SearchForm
	 */
	public function setSearchValue($val) {
		$this->searchValue = $val;
		return $this;
	}
	
	/**
	 * Set the Search Form Style
	 * @param String $style
	 * @return SearchForm
	 */
	public function setStyle($style) {
		if (in_array($style, $this->Styles)) {
			$this->style = $style;
		}
		return $this;
	}
	
	/**
	 * Add a field to this search form
	 * @param String $field
	 * @param String $label
	 * @param mixed $input
	 * @return SearchForm
	 */
	public function addField($field, $label, $input = NULL) {
		$this->fields[$field] = array('label' => $label);
		return $this->setFieldInput($field, $input);
	}
	
	/**
	 * Set the input for the given field
	 * @param String $field
	 * @param mixed $input
	 * @return SearchForm
	 */
	public function setFieldInput($field, $input) {
		if (!isset($this->fields[$field])) throw new ExceptionBase('Field not found: ' . $field);
		if (is_null($input)) {
			$input = Html::n('input', 't:text')->name($field);
		}
		$this->fields[$field]['input'] = $input;
		return $this;
	}
	
	/**
	 * Ensure the given field gets a full row
	 * @param String $field
	 * @return SearchForm
	 */
	public function makeFullWidth($field) {
		if (isset($this->fields[$field])) $this->fields[$field]['full'] = true;
		return $this;
	}
	
	public function __set($var, $val) {
		return $this->setFieldInput($var, $val);
	}
	
	public function __get($var) {
		if (empty($this->fields[$var]['input'])) return NULL;
		return $this->fields[$var]['input'];
	}
	
	/**
	 * @return HtmlC
	 */
	public function render() {
		$c = new HtmlC;
		$F = Html::n('form', 'a:;m:post;c:search_form;i:sf_' . $this->sfID, '', $c);
		Html::n('input', 't:hidden;i:sf_' . $this->sfID . '_url', $this->url, $F);
		$T = clone $this->Table;
		$T->apT($F);
		$tr = $T->nTR();
		$field = 0;
		$fulls = array();
		$rows = 1;
		foreach ($this->fields as $Field) {
			if ($this->style === 'horizontal' && !empty($Field['full'])) {
				$fulls[] = $Field;
				continue;
			}
			$tr->th($Field['label'])->td($Field['input']);
			$field++;
			if ($this->style == 'vertical' || $field == $this->maxPerRow) {
				$field = 0;
				$tr = $T->nTR();
				$rows++;
			}
		}
		while ($rows > 1 && $field > 0 && $field < $this->maxPerRow) {
			$field++;
			$tr->th('')->td('');
		}
		foreach ($fulls as $Field) {
			$T->nTR()->th($Field['label'])->td($Field['input'], 'colspan:' . ($this->maxPerRow * 2 - 1));
		}
		Html::n('div', 'align:center', Html::n('input', 't:submit', $this->searchValue), $F);
		Html::n('div', 'i:sf_' . $this->sfID . '_results', '', $c);
		return $c;
	}
	
	public function __toString() {
		return strval($this->render());
	}
	
}

