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
use pocketmine\block\Block;
use pocketmine\Server;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockEvent;
use tschrock\worldcommander\Utilities;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\math\Vector2;

/**
 * Description of GamemodeFlag
 *
 * @author tschrock
 */
class SpawnProtectionFlag extends Flag implements Listener {

    public function __construct(WorldCommander $wCommander, Plugin $owner) {
        parent::__construct("spawnprotection"
                , $wCommander
                , "Protect spawn in your worlds. (Use 'build' for regions)"
                , "spawnprotection <radius>"
                , array("spawn", "spawnprotect")
                , $owner);
    }

    public function onEnable() {
        Server::getInstance()->getPluginManager()->registerEvents($this, $this->owner);
    }


    public function handleCommand(CommandSender $sender, $area, $args) {
        $radius = implode(" ", $args);
        if (!is_numeric($radius)) {
            $sender->sendMessage("'$radius' isn't a correct spawnprotection value. Must be a radius.");
        } else {
            $radiusNum = intval($radius);
            parent::handleCommand($sender, $area, $radiusNum);
        }
    }

    public function getDefaultValue() {
        return Server::getInstance()->getSpawnRadius();
    }
    
    
    /**
     * @param BlockPlaceEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        
        return $this->checkSpawnProtection($player, $block, $event);
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        return $this->checkSpawnProtection($player, $block, $event);
    }
    
    public function checkSpawnProtection(Player $player, Block $block, BlockEvent $event){
        if (!$this->wCommander->getFlagHelper()->canBypassFlag($player, $this)) {
            $world = $player->getLevel();
            $protRadius = $this->wCommander->getFlagHelper()->getFlagValue($player, $this);
            if ($protRadius > -1) {
                $target = new Vector2($block->x, $block->z);
                $source = new Vector2($world->getSpawn()->x, $world->getSpawn()->z);
                if ($target->distance($source) <= $protRadius) {
                    $player->sendMessage("You are not allowed to edit blocks near spawn!");
                    $event->setCancelled();
                    return false;
                }
            }
        }
    }

}
 