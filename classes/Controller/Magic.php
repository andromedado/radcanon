<?php

class ControllerMagic extends ControllerApp
{
	
	public function create()
	{
		return $this->_create(array('destination' => array($this->baseName)));
	}
	
	public function review($id = null)
	{
		return $this->_review($id);
	}
	
	public function update($id = null)
	{
		return $this->_update($id, array('destination' => array($this->baseName, 'review', $id)));
	}
	
	public function delete($id = null)
	{
		return $this->_delete($id, array('destination' => array($this->baseName)));
	}
	
	public function index()
	{
		return $this->_index();
	}
	
}

