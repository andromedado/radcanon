<?php

/**
 * Class MoreTabularDataShell
 * A convenience class for when you've gone
 * to all the trouble of implementing `ExposesTabularData`
 * and now you want to expose a new set of columns
 * Just re-implement `getColumns` and you're good to go!
 */
abstract class MoreTabularDataShell
    implements ExposesTabularData
{
    /** @var \ExposesTabularData $wrapped */
    protected $wrapped;

    public function __construct(ExposesTabularData $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->wrapped, $method), $arguments);
    }

    public function __get($var)
    {
        return $this->wrapped->{$var};
    }

    public function getRowAttributes()
    {
        return $this->wrapped->getRowAttributes();
    }

    public function getCellValueForColumn($column, $formatted = true)
    {
        return $this->wrapped->getCellValueForColumn($column, $formatted);
    }

    public function getCellAttributesForColumn($column)
    {
        return $this->wrapped->getCellAttributesForColumn($column);
    }

    public function hasTFoot()
    {
        return $this->wrapped->hasTFoot();
    }

    public function getTFootAttributes()
    {
        return $this->wrapped->getTFootAttributes();
    }

    public function getBaseTFootCellValueForColumn($column)
    {
        return $this->wrapped->getBaseTFootCellValueForColumn($column);
    }

    public function mutateTFootCellValueForColumn(&$value, $column)
    {
        return $this->wrapped->mutateTFootCellValueForColumn($value, $column);
    }

    public function finalTFootCellMutate(&$cell, $column, $formatted = true)
    {
        return $this->wrapped->finalTFootCellMutate($cell, $column, $formatted);
    }

    public static function wrapThem(array $them)
    {
        $wrapped = array();
        foreach ($them as $it) {
            $class = get_called_class();
            $wrapped[] = new $class($it);
        }
        return $wrapped;
    }

}

