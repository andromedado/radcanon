<?php

class AppErrors extends HtmlC {
	public $tag = 'h2';
	public $attributes = array(
		'class' => 'f_msg msg',
	);
	protected $errors = array();
	
	/**
	 * @return HtmlErrors
	 */
	public function add ($error) {
		$this->errors[] = $error;
		return $this;
	}
	
	public function __toString() {
		$this->emptyOut();
		foreach ($this->errors as $error) {
			$this->append(Html::n($this->tag, $this->attributes, $error));
		}
		return parent::__toString();
	}
	
}

?>