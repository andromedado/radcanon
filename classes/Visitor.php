<?php
defined('PaZsCA8p') or exit;

class Visitor extends Application {
	const ILL_REPUTE = -10;
	
	var $ip;
	var $pip;
	var $f_admin_logins;
	var $user_agent;
	var $last_hit;
	protected $reputation = 0;
	
	protected $idCol='ip';
    protected $table='ip_log';
    protected static $IdCol='ip';
	protected static $Table='ip_log';
	static private $instance=NULL;
	
	private function __construct(){
		$this->pip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		$this->id = $this->ip = sprintf("%u", ip2long($this->pip));
		$ua = (isset($_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'Cron?';
		$t = time();
		$O = new ModelIpLog($this->id);
		$r = $O->createOrUpdateWithVars(array(
			$this->idCol => $this->id,
			'pip' => $this->pip,
			'user_agent' => $ua,
			'last_hit' => $t,
		));
		return $r;
	}
	
	public function raiseReputation() {
		$this->updateVar('reputation', $this->reputation + 1);
	}
	
	public function lowerReputation() {
		if (DEBUG) return;
		$this->updateVar('reputation', $this->reputation - 1);
	}
	
	public function getIP(){
		return $this->ip;
	}
	
	protected function updateVar($var, $val){
		$sql="UPDATE `".$this->table."` 
				SET `".$var."`='".mysql_real_escape_string($val)."' 
				WHERE `".$this->idCol."`='".$this->id."'
				LIMIT 1";
		if (!dbq($sql, true)) throw new ExceptionMySQL($sql.', Class: '.$this->c);
		$this->$var=$val;
		return true;
	}
	
	public function AdminLoginFailure(){
		$r=dbq("UPDATE `".self::$Table."`
					   	SET `f_admin_logins`=`f_admin_logins`+1 
						WHERE `".self::$IdCol."`=".$this->id."
						LIMIT 1", true);
		if(!$r){return mysql_error();}
		return true;
	}
	
	private function __clone(){}
	
	/**
	 * @return Visitor
	 */
	public static function I(){
		if(self::$instance==NULL){
			self::$instance=new self();
		}
		return self::$instance;
	}
	
	
}

?>