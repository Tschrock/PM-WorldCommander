<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\data;

use tschrock\worldcommander\flag\iFlag;

/**
 * Description of Area
 *
 * @author tyler
 */
class Area {
    /** @var string */
    protected $name;

    /** @var array */
    protected $flags;
    
    /** @var bool */
    protected $dirty;

    public function isDirty() {
        return $this->dirty;
    }

    public function getName() {
        return $this->name;
    }
    

    /**
     * @param iFlag $iFlag
     * @return mixed
     */
    public function getFlag($iFlag) {
        $flagName = $iFlag->getName();
        return isset($this->flags[$flagName]) ? $this->flags[$flagName] : $iFlag->getDefaultValue();
    }

    /**
     * @param string|iFlag $nameOrFlag
     * @param mixed $value
     * @return mixed
     */
    public function setFlag($nameOrFlag, $value) {
        $this->dirty = true;
        if (is_string($nameOrFlag)) {
            $flagName = $nameOrFlag;
        } elseif ($nameOrFlag instanceof iFlag) {
            $flagName = $nameOrFlag->getName();
        } else {
            return null;
        }
        $this->flags[$flagName] = $value;
        return $this->flags[$flagName];
    }

    /**
     * @param string|iFlag $nameOrFlag
     * @param mixed $value
     * @return mixed
     */
    public function unsetFlag($nameOrFlag) {
        $this->dirty = true;
        if (is_string($nameOrFlag)) {
            $flagName = $nameOrFlag;
        } elseif ($nameOrFlag instanceof iFlag) {
            $flagName = $nameOrFlag->getName();
        } else {
            return null;
        }
        unset($this->flags[$flagName]);
    }
}
