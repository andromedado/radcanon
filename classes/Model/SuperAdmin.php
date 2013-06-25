<?php

class ModelSuperAdmin extends ModelAdmin
{
    const MIN_VALID_LEVEL = 10;

    public function displayIsSuperAdmin () {
        return 'Yes';
    }

}

