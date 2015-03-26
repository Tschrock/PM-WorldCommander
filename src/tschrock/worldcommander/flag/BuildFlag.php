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
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use tschrock\worldcommander\Utilities;
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
                , "canbuild <true|false|add|remove> [exceptions...]"
                , array("build")
                , $owner);
    }

    public function onEnable() {
        $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
    }

    public function getDefaultValue() {
        return array(null, array());
    }

    public function handleCommand(CommandSender $sender, Area $area, $args) {
        $flagVal = $area->getFlag($this);
        $canBuild = array_shift($args);
        $excludeList = explode(" ", str_replace(",", " ", implode(" ", $args)));
        
        switch ($canBuild) {
            case "add":
                $flagVal[1] = array_merge($flagVal[1], $excludeList);
            case "rm":
            case "remove":
                $flagVal[1] = array_diff($flagVal[1], $excludeList);
                break;
            default:
                if (($canBuildBool = Utilities::parseBoolean($canBuild)) === null) {
                    $sender->sendMessage("Usage: " . $this->wCommander->getFlagManager()->getHelp($this));
                } else {
                    $flagVal = array($canBuildBool, $excludeList);
                }
                break;
        }
        $area->setFlag($this, $flagVal);
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
            $this->owner->getLogger()->error(
                    "Player '" . $player->getName() . "' in world '" . $player->getLevel()->getName() .
                    "' is trying to edit a block '" . $block->getName() . "' in world '" . $player->getLevel()->getName() . "'");
            return;
        }
        if (!$this->wCommander->getFlagManager()->canBypassFlag($player, $block, $this)) {
            $flagValue = $this->wCommander->getDataManager()->getArea($block)->getFlag($this);
            if ($flagValue[0] === false && !in_array($player->getName(), $flagValue[1])) {
                $player->sendMessage("You're not allowed to build here!");
                $event->setCancelled();
                return false;
            } else {
                return true;
            }
        }
    }

}
