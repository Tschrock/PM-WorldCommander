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
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;

/**
 * Description of GamemodeFlag
 *
 * @author tschrock
 */
class GamemodeFlag extends Flag implements Listener {

    public function __construct(WorldCommander $wCommander, Plugin $owner) {
        parent::__construct("gamemode"
                , $wCommander
                , "Set a player's gamemode when they are in a world. No regions yet :("
                , "gamemode <mode>"
                , array("gm", "mode")
                , $owner);
    }

    public function onEnable() {
        $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
    }

    public function getDefaultValue() {
        return $this->owner->getServer()->getDefaultGamemode();
    }

    /**
     * @param EntityLevelChangeEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled false
     */
    public function onLevelChange(EntityLevelChangeEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $this->checkPlayerGamemode($entity, $event->getTarget());
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onRespawn(PlayerRespawnEvent $event) {
        $this->checkPlayerGamemode($event->getPlayer());
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled true
     */
    public function onQuit(PlayerQuitEvent $event) {
        $this->checkPlayerGamemode($event->getPlayer());
    }

    public function checkPlayerGamemode(Player $player, $world = false) {
        if ($world === false) {
            $world = $player->getLevel()->getName();
        } else {
            $world = $world->getName();
        }

        $isExcluded = $this->wCommander->getFlagHelper()->canBypassFlag($player, $world, $this);
        $worldGamemode = $this->wCommander->getFlagHelper()->getFlagValue($world, $this);

        if ($worldGamemode === "none") {
            return true;
        } elseif (($gamemodeTo = Server::getGamemodeFromString($worldGamemode)) == -1) {
            $this->owner->getLogger()->warning($worldGamemode . ' isn\'t a valid gamemode! Using default gamemode instead!');
            $gamemodeTo = $this->owner->getServer()->getDefaultGamemode();
        }

        $gamemodeNeedsChanged = $player->getGamemode() !== ($gamemodeTo);

        if (!$isExcluded && ($gamemodeTo !== false) && $gamemodeNeedsChanged) {
            $player->setGamemode($gamemodeTo);
            return true;
        } else {
            return false;
        }
    }

    public function handleCommand(CommandSender $sender, $area, $args) {
        $gmode = implode(" ", $args);
        if (Server::getGamemodeFromString($gmode) == -1) {
            $sender->sendMessage("'$gmode' isn't a correct gamemode.");
        } else {
            parent::handleCommand($sender, $area, $gmode);
        }
    }

}
 