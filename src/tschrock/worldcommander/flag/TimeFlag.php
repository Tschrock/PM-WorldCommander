<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\flag;

use tschrock\worldcommander\WorldCommander;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\Server;
use tschrock\worldcommander\Utilities;
use pocketmine\command\CommandSender;

/**
 * Description of GamemodeFlag
 *
 * @author tschrock
 */
class TimeFlag extends Flag {

    public function __construct(WorldCommander $wCommander, Plugin $owner) {
        parent::__construct("time"
                , $wCommander
                , "Control the time in your worlds."
                , "time <value/equation>"
                , array()
                , $owner);
    }

    public function getDefaultValue() {
        return null;
    }
    
    public function handleCommand(CommandSender $sender, $area, $args)
    {
        parent::handleCommand($sender, $area, implode(" ", $args));
    }
    
    public function onEnable() {
        $this->owner->getServer()->getScheduler()->scheduleRepeatingTask(new TimeFlagTask($this->owner, $this), $this->owner->getConfig()->get(Utilities::CONFIG_TIME));
    }
    

}
 