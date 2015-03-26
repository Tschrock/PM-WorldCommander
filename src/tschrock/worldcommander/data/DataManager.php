<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\data;

use tschrock\worldcommander\flag\iFlag;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\utils\Config;

/**
 * Description of DataManager
 *
 * @author tyler
 */
class DataManager {

    /** @var World[] */
    protected $worlds = array();

    /** @var Config */
    protected $config;
    
    /** @var bool */
    protected $dirty;
    
    public function isDirty() {
        if($this->dirty){
            return true;
        }
        else {
            foreach ($this->worlds as $world) {
                if ($world->isDirty()){
                    return true;
                }
            }
        }
        return false;
    }

    function __construct($configLocation) {
        $this->config = new Config($configLocation, Config::YAML);
        $this->load();
    }
    
    /**
     * @param Position $position
     * @return bool|Area
     */
    public function getArea($position){
        return $this->getTopRegion($position) ? : $this->getWorld($position);
    }

    /**
     * @param Position $position
     * @return bool|Region
     */
    public function getTopRegion($position) {
        $world = $this->getWorld($position);
        return ($world) ? $world->getTopRegion($position) : false;
    }

    /**
     * @param Position $position
     * @return bool|Region[]
     */
    public function getRegions($position) {
        $world = $this->getWorld($position);
        return ($world) ? $world->getRegions($position) : false;
    }

    /**
     * @param string|Position|Level $nameOrPositionOrLevel
     * @return boolean|World
     */
    public function getWorld($nameOrPositionOrLevel) {
        if (is_string($nameOrPositionOrLevel)) {
            $worldName = $nameOrPositionOrLevel;
        } elseif ($nameOrPositionOrLevel instanceof \pocketmine\level\Position) {
            $worldName = $nameOrPositionOrLevel->getLevel()->getName();
        } elseif ($nameOrPositionOrLevel instanceof \pocketmine\level\Level) {
            $worldName = $nameOrPositionOrLevel->getName();
        } else {
            return false;
        }
        foreach ($this->worlds as $world) {
            if ($world->getName() == $worldName) {
                return $world;
            }
        }
        return $this->addWorld(new World());
    }

    public function load() {
        foreach ($this->config->getAll() as $worldData) {
            if (isset($worldData["W_NAME"])) {
                $worldObj = $this->getWorld($worldData["R_NAME"]) ? : $this->addWorld(new World());
                $worldObj->fromArray($worldData);
            } else {
                // Invalid config
            }
        }
    }

    public function save() {
        foreach ($this->worlds as $world) {
            $this->config->set($world->getName(), $world->toArray());
        }
        $this->config->save();
        $this->load();
    }

    public function addWorld($world) {
        $this->dirty = true;
        $name = $world instanceof World ? $world->getName() : $world["W_NAME"];
        $this->worlds[$name] = $world instanceof World ? $world : new World($world);
        return $this->worlds[$name];
    }

}
