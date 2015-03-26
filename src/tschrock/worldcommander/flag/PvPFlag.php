<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\flag;

use tschrock\worldcommander\WorldCommander;
use tschrock\worldcommander\data\Area;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
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
                , "pvp <true|false>"
                , array()
                , $owner);
    }

    public function getDefaultValue() {
        return $this->owner->getServer()->getProperty("pvp");
    }

    public function onEnable() {
        $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
    }

    public function handleCommand(CommandSender $sender, Area $area, $args) {
        if (($pvpBool = Utilities::parseBoolean(array_shift($args))) === null) {
            $sender->sendMessage("Usage: " . $this->wCommander->getFlagManager()->getHelp($this));
        } else {
            parent::handleCommand($sender, $area, $pvpBool);
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onEntityHurt(EntityDamageEvent $event) {
        if ($event instanceof EntityDamageByEntityEvent &&
                $event->getEntity() instanceof Player &&
                $event->getDamager() instanceof Player &&
                $event->getFinalDamage() != 0) {
            $victim = $event->getEntity();
            $attacker = $event->getDamager();
            # Check PVP
            if (!$this->wCommander->getDataManager()->getArea($victim)->getFlag($this) &&
                    !$this->wCommander->getFlagManager()->canBypassFlag($attacker, $attacker, $this)) {
                $attacker->sendMessage("You are not allowed to PvP in this area!");
                $event->setCancelled();
                return false;
            }
        }
    }

}
