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
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockEvent;
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
        $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
    }


    public function handleCommand(CommandSender $sender, Area $area, $args) {
        $radius = array_shift($args);
        if (!is_numeric($radius)) {
            $sender->sendMessage("Spawnprotection radius must be a number!");
        } else {
            $radiusNum = intval($radius);
            parent::handleCommand($sender, $area, $radiusNum);
        }
    }

    public function getDefaultValue() {
        return $this->owner->getServer()->getSpawnRadius();
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
        if (!$this->wCommander->getFlagManager()->canBypassFlag($player, $block, $this)) {
            $level = $player->getLevel();
            $protRadius = $this->wCommander->getDataManager()->getWorld($player)->getFlag($this);
            if ($protRadius > -1) {
                $target = new Vector2($block->x, $block->z);
                $source = new Vector2($level->getSpawn()->x, $level->getSpawn()->z);
                if ($target->distance($source) <= $protRadius) {
                    $player->sendMessage("You are not allowed to edit blocks near spawn!");
                    $event->setCancelled();
                    return false;
                }
            }
        }
    }

}
 