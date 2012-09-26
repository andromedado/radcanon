<?php

class ControllerMagic extends ControllerApp
{
	protected $GateKeeperMethods = array(
	);
	
	protected function mayProceed($invoked, array $args)
	{
		$permitted = $return = true;
		foreach ($this->GateKeeperMethods as $method => $methodOnFailure) {
			if (call_user_func(array($this, $method), $invoked, $args) !== true) {
				$permitted = false;
				if (is_null($methodOnFailure)) {
					$return = $this->notFound();
				} else {
					$return = call_user_func(array($this, $methodOnFailure), $invoked, $args);
				}
				break;
			}
		}
		return array($permitted, $return);
	}
	
	protected function determineDestination($invoked, array $args)
	{
		switch ($invoked) {
			case "update":
				$id = isset($args[0]) ? $args[0] : 0;
				$dest = array($this->baseName, 'review', $id);
				break;
			default:
				$dest = array($this->baseName);
		}
		return $dest;
	}
	
	public function create()
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		return $this->_create(array('destination' => $this->determineDestination(__FUNCTION__, $args)));
	}
	
	public function review($id = null)
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		return $this->_review($id);
	}
	
	public function update($id = null)
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		return $this->_update($id, array('destination' => $this->determineDestination(__FUNCTION__, $args)));
	}
	
	public function delete($id = null)
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		return $this->_delete($id, array('destination' => $this->determineDestination(__FUNCTION__, $args)));
	}
	
	public function index()
	{
		$args = func_get_args();
		list($permitted, $return) = $this->mayProceed(__FUNCTION__, $args);
		if (!$permitted) return $return;
		return $this->_index();
	}
	
}

