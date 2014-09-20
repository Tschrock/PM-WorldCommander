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
use pocketmine\event\entity\EntityDamageByEntityEvent;
use tschrock\worldcommander\Utilities;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;

/**
 * Description of GamemodeFlag
 *
 * @author tschrock
 */
class PvPFlag extends Flag implements Listener {

    public function __construct(WorldCommander $wCommander, Plugin $owner) {
        parent::__construct("pvp"
                , $wCommander
                , "Control pvp in your worlds/regions."
                , "pvp <true/false>"
                , array()
                , $owner);
    }

    public function getDefaultValue() {
        return Server::getInstance()->getProperty("pvp");
    }
    
    public function onEnable() {
        Server::getInstance()->getPluginManager()->registerEvents($this, $this->owner);
    }


    public function handleCommand(CommandSender $sender, $area, $args) {
        $pvp = implode(" ", $args);
        if (($pvpBool = Utilities::parseBoolean($pvp)) === null) {
            $sender->sendMessage("'$pvp' isn't a correct pvp value. Must be 'true' or 'false'.");
        } else {
            parent::handleCommand($sender, $area, $pvpBool);
        }
    }
    
    
    /**
     * @param EntityDamageByEntityEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onEntityHurtEntity(EntityDamageByEntityEvent $event)
    {
        if ($event->getEntity() instanceof Player && $event->getDamager() instanceof Player) {
            #$victim = $event->getEntity();
            $attacker = $event->getDamager();

            if (!$this->wCommander->getFlagHelper()->canBypassFlag($attacker, $this)) {
                # Check PVP
                if (!$this->wCommander->getFlagHelper()->getFlagValue($attacker, $this)) {
                    $attacker->sendMessage("You are not allowed to PvP in this area!");
                    $event->setCancelled();
                    return false;
                }
            }
        }
    }

}
 