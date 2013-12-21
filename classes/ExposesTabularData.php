<?php

interface ExposesTabularData
{
    public function getColumns();
    public function getRowAttributes();
    public function getCellValueForColumn($column);
    public function getCellAttributesForColumn($column);
    public function hasTFoot();
    public function getTFootAttributes();
    public function getBaseTFootCellValueForColumn($column);
    public function mutateTFootCellValueForColumn(&$value, $column);
    public function finalTFootCellMutate(&$cell, $column);
}

