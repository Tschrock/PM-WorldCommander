<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\flag;

use tschrock\worldcommander\flag\iFlag;
use tschrock\worldcommander\WorldCommander;
use pocketmine\plugin\Plugin;

/**
 * Description of Flag
 *
 * @author tyler
 */
class Flag implements iFlag
{

    protected $name;
    protected $description;
    protected $aliases;
    /**
     *
     * @var Plugin
     */
    protected $owner;
    protected $usage;
    /**
     *
     * @var WorldCommander
     */
    protected $wCommander;
    

    public function __construct($name, WorldCommander $wCommander, $description = "", $usage = "", array $aliases = array(),Plugin $owner = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->usage = $usage;
        $this->aliases = $aliases;
        $this->owner = $owner;
        $this->wCommander = $wCommander;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * 
     * @return Plugin
     */
    public function getOwnerPlugin()
    {
        return $this->owner;
    }

    public function getUsage()
    {
        return $this->usage;
    }

    public function handleCommand(\pocketmine\command\CommandSender $sender, $area, $args)
    {
        if($this->wCommander->getDataProvider()->setFlag($area, $this->name, $args) == false){
            $sender->sendMessage("'" . $area . "' isn't a valid world/region.");
        }
        $sender->sendMessage("Set '" + $this->getName() + "' flag to '" + $args + "' in area '" + $area + "'.");
    }

    public function getDefaultValue() {
        return null;
    }

    public function onDisable(){
        
    }

    public function onEnable(){
        
    }

}
