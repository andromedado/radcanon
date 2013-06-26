<?php

class ModelEmailTemplate extends ModelApp
{
    protected $et_id;
    protected $adid = 0;
    protected $type = 0;
    protected $active = 'yes';
    protected $name = '';
    protected $subject = '';
    protected $body = '';

    protected $dbFields = array(
        'et_id',
        'adid',
        'type',
        'active',
        'name',
        'subject',
        'body',
    );
    protected $tplVars = array(
    );
    protected $requiredFields = array(
        'name' => 'You must name the template',
        'subject' => 'The template must have a subject',
        'body' => 'The template may not be empty',
    );
    protected $varDelimiter = '~';
    protected $whatIAm = 'Email Template';
    protected $table = 'email_templates';
    protected $idCol = 'et_id';
    protected static $WhatIAm = 'Email Template';
    protected static $Table = 'email_templates';
    protected static $IdCol = 'et_id';
    protected static $AllData = array();

    public function load () {
        parent::load();
        if (!$this->valid) {
            $this->body = <<<EOT
<table border="0" cellspacing="5" cellpadding="0">
    <tbody>
        <tr>
            <td>
                <!-- Header of Some Sort -->
            </td>
        </tr>
        <tr>
            <td>
                <p></p>
            </td>
        </tr>
    </tbody>
</table>
EOT;
        }
    }

    public function getVarDelimiter () {
        return $this->varDelimiter;
    }

    public function getVars () {
        return $this->tplVars;
    }

    public function translate ($str, Model $model) {
        $m = NULL;
        foreach ($this->tplVars as $var => $label) {
            if (strpos($str, $this->varDelimiter . $var . $this->varDelimiter) !== false) {
                list($c, $attr) = explode('.', $var);
                if ($c === 'Model') {
                    $O = $model;
                } else {
                    $m = 'get' . $c;
                    $O = $model->$m();
                }
                $str = str_replace($this->varDelimiter . $var . $this->varDelimiter, $O->$attr, $str);
            }
        }
        return $str;
    }

}

/*
CREATE TABLE email_templates (
 et_id INT IDENTITY NOT NULL PRIMARY KEY,
 adid INT NOT NULL,
 type SMALLINT NOT NULL,
 inactive SMALLINT NULL,
 name VARCHAR(30) NOT NULL,
 subject VARCHAR(60) NOT NULL,
 body TEXT NOT NULL );
 */

