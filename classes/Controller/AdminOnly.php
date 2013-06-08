<?php

class ControllerAdminOnly extends ControllerOnlyUserType {
    protected $UserType = 'Admin';

    public function editAttr($id = null, $attr = null)
    {
        return parent::_editAttr($id, $attr);
    }

}

