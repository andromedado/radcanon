<?php

class ModelConfig extends ModelApp
{
    const TYPE_PX = 'px',
        TYPE_INT = 'int',
        TYPE_STR = 'str',
        TYPE_CASH = 'cash',
        TYPE_BOOL = 'bool';

    const DEFAULT_TYPE = self::TYPE_STR;

    const BOOL_YES = 'true',
        BOOL_NO = 'false',
        DISPLAY_YES = 'Yes',
        DISPLAY_NO = 'No';

    public $config_id,
        $key,
        $type,
        $name,
        $value;

    public $displayValue,
        $inputValue,
        $inputType;

    protected $dbFields = array(
        'config_id', 'key', 'type',
        'name', 'value',
    );
    protected $readOnly = array(
        'displayValue', 'inputType',
        'inputValue',
    );
    protected $requiredFields = array(
        'key' => 'Configuration Key is required',
    );
    protected $whatIAm = 'Configuration',
        $table = 'configs',
        $idCol = 'config_id';
    protected static $WhatIAm = 'Configuration',
        $Table = 'configs',
        $IdCol = 'config_id',
        $AllData = array();

    protected static $Types = array(
        self::TYPE_PX => 'px',
        self::TYPE_CASH => 'Cash',
        self::TYPE_INT => 'Number',
        self::TYPE_STR => 'Text',
        self::TYPE_BOOL => 'Yes/No',
    );

    protected static $Options = array(
        self::TYPE_BOOL => array(
            self::BOOL_YES => self::DISPLAY_YES,
            self::BOOL_NO => self::DISPLAY_NO,
        ),
    );

    protected function load()
    {
        parent::load();
        if (empty($this->type)) {
            $this->type = self::DEFAULT_TYPE;
        }
        $this->displayValue = $this->value;
        switch ($this->type) {
            case self::TYPE_CASH:
                $this->inputType = 'input';
                $this->inputValue = $this->displayValue = UtilsNumber::cashFormat($this->value);
                break;
            case self::TYPE_PX:
                $this->inputType = 'input';
                $this->inputValue = $this->displayValue = sprintf('%dpx', $this->value);
                break;
            case self::TYPE_BOOL:
                $this->inputType = 'select';
                $this->displayValue = $this->value === self::BOOL_YES ? self::DISPLAY_YES : self::DISPLAY_NO;
                break;
            case self::TYPE_INT:
                $this->inputType = 'input';
                break;
            default:
                $this->inputType = 'textarea';
        }
        if (is_null($this->inputValue)) {
            $this->inputValue = $this->value;
        }
    }

    protected function preFilterVars(&$vars)
    {
        switch ($this->type) {
            case self::TYPE_PX:
            case self::TYPE_INT:
                $vars['value'] = UtilsNumber::parseInt($vars['value']);
                break;
            case self::TYPE_CASH:
                $vars['value'] = UtilsNumber::cashToNum($vars['value']);
                break;
        }
    }

    protected function appendAdditionalData(array &$data)
    {
        $data['Types'] = self::$Types;
        if ($this->inputType === 'select') {
            $data['options'] = self::$Options[$this->type];
        }
    }

    public static function value($key, $defaultTo = null)
    {
        $O = self::findOneByField('key', $key);
        if ($O->isValid()) {
            return $O->value;
        }
        return $defaultTo;
    }

    protected static $createTableSQLQuery = "
CREATE TABLE `configs` (
  `config_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(20) NOT NULL,
  `type` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(80) NOT NULL DEFAULT '',
  `value` tinytext NOT NULL,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `key` (`key`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
}


