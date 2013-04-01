<?php

/*****
 * Nicely abstracted Application Form
 * 
 * @author Shad Downey
 * @version 1.2
 */
class AppForm {
	/** @var Html $form */
	public $form;
	/** @var Html $submit */
	public $submit;
	/** @var Html $submitWrap */
	public $submitWrap;
	/** @var Html $header */
	public $header;
	/** @var Html $table */
	public $table;
	/** @var Boolean $headerAfter */
	public $headerAfter = false;
	/** @var Array $hiddenFields */
	public $hiddenFields = array();
	
	protected $buttonPosition = 'after';
	protected $cols = 1;
	protected $forceUseTable = false;
	protected $fields = array();
	protected $id_prefix = '';
	protected $uid = 1;
	/** @var Request $req */
	protected $req = NULL;
	
	public function __construct($headerValue, $action = '', array $fields = array(), Request $req = NULL) {
		if (is_string($headerValue) && !empty($headerValue)) {
			$this->header = Html::n('h2', '', $headerValue);
		} else {
			$this->header = $headerValue;
		}
		$this->id_prefix = '_' . uniqid();
		if (is_array($action)) {
			$action = FilterAppRoutes::buildUrl($action);
		}
		$this->form = Html::n('form', 'm:post')->action($action);
		$this->table = Html::n('table', 'c:autoT');
		$this->submit = Html::n('input', 't:submit', 'Submit');
		$this->submitWrap = Html::n('div', 'align:center');
		$this->req = $req;
		foreach ($fields as $k => $v) {
			$this->addField($v, $k);
		}
	}
	
	public function addHiddenField ($name, $value = NULL) {
		if (!is_object($name) || !is_a($name, 'Html')) {
			$name = Html::n('input', 't:hidden', $value)->name($name);
		}
		$this->hiddenFields[$name->name] = $name;
	}
	
	public function hiddenField ($name) {
		if (array_key_exists($this->hiddenFields[$name])) {
			return $this->hiddenFields[$name];
		}
		return NULL;
	}
	
	/**
	 * Not found methods passed off to the Form Element
	 * @return AppForm
	 */
	public function __call($func, $args) {
		call_user_func_array(array($this->form, $func), $args);
		return $this;
	}
	
	/**
	 * Set the button position to 'before' or 'after'
	 * @param String $where
	 * @return AppForm
	 */
	public function setButtonPosition($where) {
		$this->buttonPosition = $where === 'after' ? 'after' : 'before';
		return $this;
	}
	
	/**
	 * Set the number of paired columns
	 * @param Int $num
	 * @return AppForm
	 */
	public function setCols($num) {
		$this->cols = abs((int)$num);
		return $this;
	}
	
	/**
	 * Force the use of the table (even if `fields` is empty)
	 * @return AppForm
	 */
	public function forceUseTable() {
		$this->forceUseTable = true;
		return $this;
	}
	
	/**
	 * Accessor for fields' inputs
	 * @param String $field Name of field requested
	 * @return Html
	 */
	public function field($field) {
		if (!isset($this->fields[$field])) throw new ExceptionBase('Undefined Field requested');
		return $this->fields[$field]['input'];
	}
	
	/**
	 * Add a field to this form
	 * @param String $label
	 * @param Html|String $input
	 * @return AppForm
	 */
	public function addField($label, $input, $suffix = NULL) {
		if (is_string($input)) {
			$input = Html::n('input', 't:text')->name($input);
		}
		if (is_a($input, 'Html') && !is_null($this->req) && $input->type != 'password') {
			$input->value($this->req->post($input->name, ''));
		}
		if ($input->id === '') {
			$input->id = $this->id_prefix . $this->uid++;
		}
		$this->fields[$input->name] = array('label' => $label, 'input' => $input, 'suffix' => $suffix);
		return $this;
	}
	
	/**
	 * Render this form
	 * @return HtmlC
	 */
	public function render() {
		$c = new HtmlC;
		$c->append($F = clone $this->form);
		if (!empty($this->fields) || $this->forceUseTable) {
			$F->append($T = clone $this->table);
			$cols = 0;
			$tr = $T->nTR();
			foreach ($this->fields as $n => $Field) {
				$tr->th(Html::n('label', $Field['input']->id, $Field['label']))->td($Field['input'] . (empty($Field['suffix']) ? '' : $Field['suffix']));
				$cols++;
				if ($cols >= $this->cols) {
					$tr = $T->nTR();
					$cols = 0;
				}
			}
			while ($cols < $this->cols && $cols > 0) {
				$cols++;
				$tr->th('')->td('');
			}
		}
		$b = clone $this->submitWrap;
		$b->append(clone $this->submit);
		if ($this->buttonPosition === 'after') {
			$F->append($b);
		} else {
			$F->prepend($b);
		}
		foreach ($this->hiddenFields as $field) {
			$F->prepend($field);
		}
		if (!empty($this->header)) {
			if ($this->headerAfter) {
				$c->append($this->header);
			} else {
				$c->prepend($this->header);
			}
		}
		return $c;
	}
	
	public function __toString() {
		return strval($this->render());
	}
	
}

?>