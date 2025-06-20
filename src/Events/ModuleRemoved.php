<?php

namespace Rcv\Core\Events;

class ModuleRemoved
{
    public $moduleName;

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
    }
} 