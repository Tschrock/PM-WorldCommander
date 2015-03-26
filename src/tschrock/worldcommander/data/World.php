<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\data;

use \pocketmine\level\Position;

/**
 * Description of World
 *
 * @author tyler
 */
class World extends Area{

    /** @var Region[] */
    protected $worldRegions;

    public function isDirty() {
        if($this->dirty){
            return true;
        }
        else {
            foreach ($this->worldRegions as $region) {
                if ($region->isDirty()){
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * @param string|Position $nameOrPosition
     * @return Region
     */
    public function getTopRegion($nameOrPosition) {
        return array_shift($this->getRegions($nameOrPosition));
    }

    /**
     * @param string|Position $nameOrPosition
     * @return Region[]
     */
    public function getRegions($nameOrPosition) {
        if (is_string($nameOrPosition)) {
            foreach ($this->worldRegions as $region) {
                if ($region->getName() == $nameOrPosition) {
                    return array($region);
                }
            }
        } elseif ($nameOrPosition instanceof \pocketmine\level\Position) {
            $rtn = array();
            foreach ($this->worldRegions as $region) {
                if ($region->isInside($nameOrPosition)) {
                    $rtn[] = $region;
                }
            }
            uasort($rtn, array($this, "compareRegionPriority"));
            return $rtn;
        } else {
            return array();
        }
    }

    public function compareRegionPriority($a, $b) {
        if ($a->getPriority() > $b->getPriority()) {
            return 1;
        } elseif ($a->getPriority() < $b->getPriority()) {
            return -1;
        } else
            return 0;
    }

    public function toArray() {
        $regionsArr = array();
        foreach ($this->worldRegions as $region) {
            $regionsArr[$region->getName()] = $region->toArray();
        }
        return array(
            "W_NAME" => $this->name,
            "W_FLAGS" => $this->flags,
            "W_REGIONS" => $regionsArr
        );
    }

    public function fromArray($data) {
        $this->dirty = false;
        $this->name = $data["W_NAME"];
        $this->flags = $data["W_FLAGS"];
        $this->worldRegions = array();
        foreach ($data["W_REGIONS"] as $regionData) {
            if (isset($regionData["R_NAME"])) {
                $regionObj = $this->getTopRegion($regionData["R_NAME"]) ? : $this->addRegion(new Region());
                $regionObj->fromArray($regionData);
            } else {
                // Invalid config
            }
        }
    }

    public function addRegion($region) {
        $this->dirty = true;
        $name = $region instanceof Region ? $region->getName() : $region["R_NAME"];
        $this->worldRegions[$name] = $region instanceof Region ? $region : new Region($region);
        return $this->worldRegions[$name];
    }

    function __construct($worldData = false) {
        if ($worldData !== false) {
            $this->fromArray($worldData);
        } else {
            $this->dirty = false;
            $this->name = "";
            $this->flags = array();
            $this->worldRegions = array();
        }
    }

}
