<?php
defined('PaZsCA8p') or die('Include Only');
/**
 * "I hate closing HTML tags myself" Class
 *
 * @package RAD-Canon
 * @version 2.0
 * @author  Shad Downey
 */
class Html {
	protected $tag='div';
	protected $attrs=array();
	protected $inner='';
	protected $innerA=array();
	protected $innerWrapper='';
	protected $selfClosed=false;
	protected $defaultAttribute='id';
	
	/**
	 * @var bool $ignoreColon Ignore the shortcut syntax
	 * `attribute1:value1;attribute2:value2` in the constructor
	 * Important when colons and/or semicolons are valid inside attribute values [a]
	 */
	protected $ignoreColon=false;
	protected $NaturalChildTag='span';
	/**
	 * @var bool $permitNaturalChildAssignment Listen for the syntax:
	 * Html::newE('ul')->li('List Item 1','c:happy')
	 */
	protected $permitNaturalChildAssignment=true;
	protected static $attributeSymbols = array('.' => 'class',
		'#' => 'id');
	protected static $selfClosing=array('input','br','link','img');
	protected static $defaultAttributes=array('table'=>array('border'=>'0',
															 'cellspacing'=>'0',
															 'cellpadding'=>'0'),
											  'img'=>array('border'=>'0',
														   'alt'=>'')
											  );
	protected static $dontAddWhiteSpace = array(
		'span',
		'script',
		'textarea',
		'i',
		'a',
		'td',
		'th',
		'option',
		'label',
		'h1',
		'h2',
		'h3',
		'h4',
	);
	protected static $defaultAttributeKeyletters=array('c'=>'class',
													   'i'=>'id',
													   't'=>'type',
													   'n'=>'name',
													   'v'=>'value',
													   'a'=>'action',
													   'm'=>'method',
													   'w'=>'width',
													   'h'=>'height');
	protected static $Children=array('a'=>'HtmlA',
									 'img'=>'HtmlImg',
									 'label'=>'HtmlLabel',
									 'option'=>'HtmlOption',
									 'select'=>'HtmlSelect',
									 'table'=>'HtmlTable',
									 'tr'=>'HtmlTr',
									 'ul'=>'HtmlUl');
	
	/**
	 * Base HTML Element Constructor
	 * @param string $tag The HTML Element to create
	 * @param string|array $attrs The attributes for the HTML Element
	 * @param string|Html $inner The content[|value] for the HTML Element
	 * @param Html $appendTo Any existing Html that this will append itself to
	 */
	public function __construct($tag = 'div', $attrs = array(), $inner = '', Html $appendTo = NULL){
		if (self::containsAttributeSymbol($tag)) {
			$peices = preg_split(self::getSplitRegExp(), $tag, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			$this->tag = array_shift($peices);
			$attr = 'class';
			while ($p = array_shift($peices)) {
				if (array_key_exists($p, self::$attributeSymbols)) {
					$attr = self::$attributeSymbols[$p];
				} elseif($attr === 'id') {
					$this->__set('id', $p);
				} else {
					$space = $attr . 'Space';
					if (!isset($$space)) $$space = '';
					if (!isset($this->attrs[$attr])) $this->attrs[$attr] = '';
					$this->attrs[$attr] .= $$space . $p;
					$$space = ' ';
				}
			}
			if (!is_array($attrs)) {
				$temp = $attrs;
				$attrs = $inner;
				$inner = $temp;
				unset($temp);
			}
		} else {
			$this->tag = $tag;
		}
		if(array_key_exists($this->tag, self::$defaultAttributes)){
			$this->__set('attrs',self::$defaultAttributes[$this->tag]);
		}
		if(!empty($attrs) && !is_array($attrs)){
			if(!$this->ignoreColon && strpos($attrs,':')!==false){
				$ps=explode(';',$attrs);
				foreach($ps as $p){
					if(strpos($p,':')!==false){
						list($a,$v)=explode(':',$p);
						if(array_key_exists($a,self::$defaultAttributeKeyletters)){
							$a=self::$defaultAttributeKeyletters[$a];
						}
						$this->__set($a,$v);
					}
				}
			}else{
				$this->attrs[$this->defaultAttribute]=$attrs;
			}
		}elseif(!empty($attrs)){
			$this->__set('attrs',$attrs);
		}
		if(!empty($inner) || $inner=='0'){
			$this->innerA[]=$inner;
		}
		$this->selfClosed=in_array($tag,self::$selfClosing);
		if (!is_null($appendTo)) $this->appendTo($appendTo);
	}

	/**
	 * Test the given string for Attribute Symbols
	 * 
	 * @param string $tag
	 * @return bool
	 */
	private static function containsAttributeSymbol($tag) {
		$contains = false;
		foreach (self::$attributeSymbols as $sym => $attr) {
			$contains = $contains || strpos($tag, $sym) !== false;
		}
		return $contains;
	}

	/**
	 * Generates the preg_split Regular Expression
	 * to break the Element into it's parts
	 * 
	 * @return string Regular Expression
	 */
	private static function getSplitRegExp() {
		$exp = '/(';
		$or = '';
		foreach (self::$attributeSymbols as $sym => $attr) {
			$exp .= $or . preg_quote($sym, '/');
			$or = '|';
		}
		$exp .= ')/i';
		return $exp;
	}
	
	/**
	 * Flatten this HTML Element's contents
	 * @return Html
	 */
	public function commit () {
		$A = $this->innerA;
		$this->innerA = array(self::stringify($A));
		return $this;
	}
	
	/**
	 * Calls the given method, with the given arguments, on any Html in the immediate content of this Html (Optionally recursive)
	 * @param string $method The method to call on the Htmls
	 * @param mixed $args Single argument to pass in, or an array of arguments
	 * @param bool $recursive Whether or not to perform the operation recursively
	 * @return Html this, permits chaining
	 */
	public function callOnChildrenHtml($method,$args=array(),$recursive=false){
		foreach ($this->innerA as $V) {
			if (is_object($V) && is_a($V,__CLASS__)) {
				if (is_array($args)) {
					call_user_func_array(array($V,$method), $args);
				} else {
					call_user_func(array($V,$method), $args);
				}
				if ($recursive) {
					$V->callOnChildrenHtml($method,$args,true);
				}
			}
		}
		return $this;
	}
	
	/**
	 * Retrieve the natural child tag for this Html
	 * @return string The natural child tag
	 */
	public function getNaturalChildTag(){
		return $this->NaturalChildTag;
	}
	
	/**
	 * Retrieve an instance of the natural child tag for this Html
	 * @return Html Instance of the natural child
	 */
	public function getNaturalChild(){
		return self::n($this->NaturalChildTag);
	}
	
	/**
	 * Empty out this element
	 * @return Html
	 */
	public function emptyOut() {
		$this->innerA = array();
		return $this;
	}
	
	/**
	 * Accessor for this->innerA
	 * @return array
	 */
	public function exposeInner(){
		return $this->innerA;
	}
	
	/**
	 * Preppend the given content to this HTML Element
	 * @param string|Html $content The content to be prepended
	 * @return Html
	 */
	public function p($c=''){return $this->prepend($c);}

	/**
	 * Preppend the given content to this HTML Element
	 * @param string|Html $content The content to be prepended
	 * @return Html
	 */
	public function prepend($content=''){
		array_unshift($this->innerA,$content);
		return $this;
	}
	
	/**
	 * Prepend _this_ HTML Element to the given Element
	 * @param Html $E The element to which I prepend myself
	 * @return Html
	 */
	public function prT(Html $E){return $this->prependTo($E);}
	
	/**
	 * Prepend _this_ HTML Element to the given Element
	 * @param Html $E The element to which I prepend myself
	 * @return Html
	 */
	public function prependTo(Html $E){
		$E->p($this);
		return $this;
	}
	
	/**
	 * Append the given content to this HTML Element
	 * @param string|Html $content The content to be appended
	 * @return Html
	 */
	public function a() {
		$args = func_get_args();
		call_user_func_array(array($this, 'append'), $args);
		return $this;
	}
	
	/**
	 * Append the given content to this HTML Element
	 * @param string|Html $content The content to be appended
	 * @return Html
	 */
	public function append(){
		$args = func_get_args();
		foreach ($args as $arg) {
			$this->innerA[] = $arg;
		}
		return $this;
	}
	
	/**
	 * Append _this_ HTML Element to the given Element
	 * @param Html $E The element to append myself to
	 * @return Html
	 */
	public function apT(Html $E){return $this->appendTo($E);}
	
	/**
	 * Append _this_ HTML Element to the given Element
	 * @param Html $E The element to append myself to
	 * @return Html
	 */
	public function appendTo(Html $E){
		$E->a($this);
		return $this;
	}
	
	/**
	 * Add the given class to this Element's class
	 * @param String
	 * @return Html $this
	 */
	public function addClass($class) {
		if (!isset($this->attrs['class'])) $this->attrs['class'] ='';
		$this->attrs['class'] = trim($this->attrs['class'] . ' ' . trim($class));
		return $this;
	}
	
	/**
	 * A way to quickly created nested HTML Elements
	 * @param string $ts The '/' delimited list of HTML Elements
	 * @param string|array $a The attributes array or string
	 * @param string $i The node content for the lowest level node
	 * @param Html $appendTo Any existing Html that the top level Object will append itself to
	 * @return Html
	 */
	public static function newChain($ts,$a=array(),$i='', Html $appendTo = NULL){
		$Ts = explode('/',$ts);
		$ft = array_shift($Ts);
		$S = $O = self::newE($ft,$a,'',$appendTo);
		foreach($Ts as $t){
			$S = self::newE($t,$a)->apT($S);
		}
		$S->a($i);
		return $O;
	}
	
	/**
	 * Primary way to instantiate HTML Elements
	 * @param string $t The HTML tag of the element being created
	 * @param string|array $a The attributes array or string
	 * @param string $i The node content [or `value`]
	 * @param Html $appendTo Any existing Html that this will append itself to
	 * @return Html
	 */
	public static function n($t,$a=array(),$i='', Html $appendTo = NULL) {
		return self::newE($t,$a,$i,$appendTo);
	}
	
	/**
	 * Primary way to instantiate HTML Elements
	 * @param string $t The HTML tag of the element being created
	 * @param string|array $a The attributes array or string
	 * @param string $i The node content [or `value`]
	 * @param Html $appendTo Any existing Html that this will append itself to
	 * @return Html
	 */
	public static function newE($t,$a=array(),$i='', Html $appendTo = NULL){
		if (strpos($t,'/')!==false) return self::newChain($t,$a,$i,$appendTo);
		if (self::containsAttributeSymbol($t)) {
			$peices = preg_split(self::getSplitRegExp(), $t, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			$t = array_shift($peices);
			$bits = implode('', $peices);
		}
		if (array_key_exists($t, self::$Children)) {
			$c = self::$Children[$t];
			return new $c($a, $i, $appendTo);
		}
		return new self($t, $a, $i, $appendTo);
	}
	
	/**
	 * Takes a given string, array, or Object and returns a string
	 * @param string|array|object $o The string/array/object to be strung
	 * @return string
	 */
	protected static function stringify($o){
		$str='';
		if(is_array($o) && !is_object($o)){
			foreach($o as $k=>$v){
				$str .= self::stringify($v);
			}
		}else{
			$str.=$o;
		}
		return $str;
	}
	
	/**
	 * Get's the Html's content as a string
	 * @return string
	 */
	public function getContent(){
		return self::stringify($this->innerA);
	}
	
	/**
	 * Present this as a string
	 * @return string
	 */
	public function __toString () {
		$nl=in_array($this->tag,self::$dontAddWhiteSpace)?'':"\n";
		$tb=in_array($this->tag,self::$dontAddWhiteSpace)?'':"\t";
		$p2=$p3='';
		$p1=$nl."<".$this->tag;
		foreach($this->attrs as $attr=>$val){
			if ($attr === 'id') $val = UtilsString::urlSafe($val, false, '_');
			$p1.=' '.$attr.'="'.str_replace('"',"'",$val).'"';
		}
		if ($this->selfClosed) {
			if(!empty($this->innerA) && !isset($this->attrs['value'])){
				$p1.=' value="'.str_replace('"',"'",self::stringify($this->innerA)).'"';
			}
			$p1.=' />';
		} else {
			$p1.=">";
			$inner=self::stringify($this->innerA);
			if(!empty($this->innerWrapper) && !preg_match('/^\s*<'.preg_quote($this->innerWrapper).'/',$inner)){
				$inner=self::n($this->innerWrapper,'',$inner);
			}
			$p2.=$inner;
			$p3.="</".$this->tag.'>';//.$nl;
		}
		return $p1.$tb.trim($p2).$p3;
	}
	
	/**
	 * Permits the chained assignment of attributes
	 * @param string $f Name of the Attribute to Assign
	 * @param array $args Value to be assigned
	 * @return Html
	 */
	public function __call ($f, $args) {
		if ($this->permitNaturalChildAssignment && $f === $this->NaturalChildTag) {
			$i = isset($args[0]) ? $args[0] : '';
			$a = isset($args[1]) ? $args[1] : array();
//			var_dump($f, $args);
			self::newE($this->NaturalChildTag, $a, $i, $this);
			return $this;
		}
		if(count($args)==1){$args=array_shift($args);}
		return $this->__set($f,$args);
	}
	
	/**
	 * Intelligently takes care of attribute assignment
	 * @param string $attr The attribute to assign
	 * @param string $val The value to be assigned
	 * @return Html
	 */
	public function __set($attr,$val){
		if($attr=='attrs' || $attr=='attributes'){
			foreach($val as $k=>$v){
				$this->__set($k,$v);
			}
		}elseif($attr=='inner' || $attr=='innerHTML'){
			$this->innerA=array($val);
		}elseif($attr=='checked'){
			if($val){
				$this->attrs['checked']='checked';
			}
		}elseif($attr=='selected'){
			if($val){
				$this->attrs['selected']='selected';
			}
		}elseif($attr=='disabled'){
			if($val){
				$this->attrs['disabled']='disabled';
			}
		}else{
			$this->attrs[$attr]=$val;
		}
		return $this;
	}
	
	/**
	 * Permits the public access of an HTML Element's Attributes
	 * @param string $attr The Attribute whose value we want
	 * @return string
	 */
	public function __get($attr){
		return empty($this->attrs[$attr])?'':$this->attrs[$attr];
	}
	
}

?>