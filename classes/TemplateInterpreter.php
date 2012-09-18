<?php

class TemplateInterpreter
{
	public $tplFields;
	public $tplFieldDelimiter;
	
	/**
	 * @param Array $tplFields
	 * @param String $tplFieldDelimiter
	 */
	public function __construct(array $tplFields, $tplFieldDelimiter = '')
	{
		$this->tplFields = $tplFields;
		$this->tplFieldDelimiter = $tplFieldDelimiter;
	}
	
	/**
	 * With the content containing tpl fields, use the given tpl values to render
	 * Optionally add a pre- and post-fix to all replacements
	 * @param String $content
	 * @param Array $renderWith tpl Values (field => value)
	 * @param String $preFix
	 * @param String $postFix
	 */
	public function renderWith($content, array $renderWith = array(), $preFix = '', $postFix = '')
	{
		foreach ($this->tplFields as $tplField) {
			$repWith = array_key_exists($tplField, $renderWith) ? $renderWith[$tplField] : '';
			$content = str_replace($this->tplFieldDelimiter . $tplField . $this->tplFieldDelimiter, $preFix . $repWith . $postFix, $content);
		}
		return $content;
	}
	
}

