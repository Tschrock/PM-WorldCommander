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
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use tschrock\worldcommander\Utilities;
use tschrock\worldcommander\YMLDataProvider;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\block\Block;
use pocketmine\event\block\BlockEvent;

/**
 * Description of GamemodeFlag
 *
 * @author tschrock
 */
class BuildFlag extends Flag implements Listener {

    public function __construct(WorldCommander $wCommander, Plugin $owner) {
        parent::__construct("canbuild"
                , $wCommander
                , "Set whether or not people can build in an area."
                , "canbuild <true|false> [exceptions...]"
                , array("build")
                , $owner);
    }

    public function onEnable() {
        Server::getInstance()->getPluginManager()->registerEvents($this, $this->owner);
    }

    public function getDefaultValue() {
        return null;
    }

    public function handleCommand(CommandSender $sender, $area, $args) {
        $canBuild = array_shift($args);
        $exceptionsArr = explode(" ", str_replace(",", " ", implode(" ", $args)));

        if ($canBuild == "add") {
            $flagVal = $this->wCommander->getFlagHelper()->getFlagValue($area, $this);
            $excludeList = YMLDataProvider::safeArrayGet($flagVal, 1);
            $excludeNew = array_merge($excludeList, $exceptionsArr);
            $flagVal[1] = $excludeNew;
            $this->wCommander->getDataProvider()->setFlag($area, $this->getName(), $flagVal);
        } elseif ($canBuild == "remove") {
            $flagVal = $this->wCommander->getFlagHelper()->getFlagValue($area, $this);
            $excludeList = YMLDataProvider::safeArrayGet($flagVal, 1);
            $excludeNew = array_diff($excludeList, $exceptionsArr);
            $flagVal[1] = $excludeNew;
            $this->wCommander->getDataProvider()->setFlag($area, $this->getName(), $flagVal);
        } else {
            if (($canBuildBool = Utilities::parseBoolean($canBuild)) !== null) {
                $sender->sendMessage("'$canBuild' isn't a correct canbuild value. Must be 'true' or 'false' with an optional exclude list; Or 'add <playerlist>' or 'remove <playerlist>' to change the exclude list.");
            } else {
                parent::handleCommand($sender, $area, array($canBuildBool, $exceptionsArr));
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        return $this->checkBuildPerms($player, $block, $event);
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        return $this->checkBuildPerms($player, $block, $event);
    }

    public function checkBuildPerms(Player $player, Block $block, BlockEvent $event) {
        if ($player->getLevel() != $block->getLevel()) {
            return;
        }
        if (!$this->wCommander->getFlagHelper()->canBypassFlag($player, $this)) {
            $world = $player->getLevel();
            $regions = $this->wCommander->getDataProvider()->getRegion($block);

            foreach ($regions as $area) {
                $canBuildVal = $this->wCommander->getFlagHelper()->getFlagValue($area, $this);
                $canBuildBool = YMLDataProvider::safeArrayGet($canBuildVal, 0);
                if ($canBuildBool !== null) {
                    break;
                }
            }

            if ($canBuildBool == null) {
                $canBuildVal = $this->wCommander->getFlagHelper()->getFlagValue($world->getName(), $this);
                $canBuildBool = YMLDataProvider::safeArrayGet($canBuildVal, 0);
            }

            if ($canBuildBool == false) {
                $event->setCancelled();
                return false;
            } else {
                return true;
            }
        }
    }

}
