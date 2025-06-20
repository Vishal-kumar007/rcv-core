<?php

namespace Rcv\Core\Events;

class ModuleInstalled
{
    public $moduleName;

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
    }
} 