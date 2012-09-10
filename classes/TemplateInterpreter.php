<?php

class TemplateInterpreter
{
	public $tplFields;
	public $tplFieldDelimiter;
	
	public function __construct(array $tplFields, $tplFieldDelimiter = '')
	{
		$this->tplFields = $tplFields;
		$this->tplFieldDelimiter = $tplFieldDelimiter;
	}
	
	public function renderWith($content, array $renderWith = array(), $finalWrapOpen = '', $finalWrapClose = '')
	{
		foreach ($renderWith as $tplField => $value) {
			if (!in_array($tplField, $this->tplFields)) continue;
			$content = str_replace($this->tplFieldDelimiter . $tplField . $this->tplFieldDelimiter, $finalWrapOpen . $value . $finalWrapClose, $content);
		}
		return $content;
	}
	
}

