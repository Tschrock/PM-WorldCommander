<?php

namespace tschrock\WorldCommander;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector2;
use pocketmine\Player;

class PlayerEventListener implements Listener
{

    protected $plugin;

    public function __construct(WorldCommander $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param EntityLevelChangeEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled false
     */
    public function onLevelChange(EntityLevelChangeEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $this->plugin->utilities->checkPlayerGamemode($entity, $event->getTarget());
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onRespawn(PlayerRespawnEvent $event)
    {
        $this->plugin->utilities->checkPlayerGamemode($event->getPlayer());
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled true
     */
    public function onQuit(PlayerQuitEvent $event)
    {
        $this->plugin->utilities->checkPlayerGamemode($event->getPlayer());
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();

        if ($block->isActivable) {

            if (!$this->plugin->utilities->isPlayerExcluded($player, Utilities::FLAG_BLOCKS)) {
                # Check block
                if ($this->plugin->utilities->isBlockBanned($player, $block, Utilities::BLOCKBAN_ACTIVATE)) {
                    $player->sendMessage("[WorldCommander] You are not allowed to interact with that block!");
                    $event->setCancelled();
                    return false;
                }

                # Check item
                if ($this->plugin->utilities->isBlockBanned($player, $item, Utilities::BLOCKBAN_PLACE)) {
                    $player->sendMessage("[WorldCommander] You are not allowed to use that block/item!");
                    $event->setCancelled();
                    return false;
                }
            }


            # Check spawn protection
            if (!$this->plugin->utilities->isPlayerExcluded($player, Utilities::FLAG_SPAWN)) {
                $world = $player->getLevel();
                $prot = $this->plugin->utilities->getFlag($world, Utilities::FLAG_SPAWN);
                if ($prot > -1) {
                    $target = new Vector2($block->x, $block->z);
                    $source = new Vector2($world->getSpawn()->x, $world->getSpawn()->z);
                    if ($target->distance($source) <= $prot) {
                        $player->sendMessage("[WorldCommander] You are not allowed to interact with blocks near spawn!");
                        $event->setCancelled();
                        return false;
                    }
                }
            }
        }
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

        # Check block
        if (!$this->plugin->utilities->isPlayerExcluded($player, Utilities::FLAG_BLOCKS)) {
            if ($this->plugin->utilities->isBlockBanned($player, $block, Utilities::BLOCKBAN_PLACE)) {
                $player->sendMessage("[WorldCommander] You are not allowed to place that block!");
                $event->setCancelled();
                return false;
            }
        }

        # Check spawn protection
        if (!$this->plugin->utilities->isPlayerExcluded($player, Utilities::FLAG_SPAWN)) {
            $world = $player->getLevel();
            $prot = $this->plugin->utilities->getFlag($world, Utilities::FLAG_SPAWN);
            if ($prot > -1) {
                $target = new Vector2($block->x, $block->z);
                $source = new Vector2($world->getSpawn()->x, $world->getSpawn()->z);
                if ($target->distance($source) <= $prot) {
                    $player->sendMessage("[WorldCommander] You are not allowed to place blocks near spawn!");
                    $event->setCancelled();
                    return false;
                }
            }
        }
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

        # Check block
        if ($this->plugin->utilities->isBlockBanned($player, $block, Utilities::BLOCKBAN_BREAK)) {
            $player->sendMessage("[WorldCommander] You are not allowed to break that block!");
            $event->setCancelled();
            return false;
        }

        # Check spawn protection
        if (!$this->plugin->utilities->isPlayerExcluded($player, Utilities::FLAG_SPAWN)) {
            $world = $player->getLevel();
            $prot = $this->plugin->utilities->getFlag($world, Utilities::FLAG_SPAWN);
            if ($prot > -1) {
                $target = new Vector2($block->x, $block->z);
                $source = new Vector2($world->getSpawn()->x, $world->getSpawn()->z);
                if ($target->distance($source) <= $prot) {
                    $player->sendMessage("[WorldCommander] You are not allowed to break blocks near spawn!");
                    $event->setCancelled();
                    return false;
                }
            }
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
        if ($event->getEntity() instanceof \pocketmine\Player && $event->getDamager() instanceof \pocketmine\Player) {
            #$victim = $event->getEntity();
            $attacker = $event->getDamager();


            if (!$this->plugin->utilities->isPlayerExcluded($attacker, Utilities::FLAG_PVP)) {
                # Check PVP
                if (!$this->plugin->utilities->getFlag($attacker->getLevel(), Utilities::FLAG_PVP)) {
                    $attacker->sendMessage("[WorldCommander] You are not allowed to PvP in this world!");
                    $event->setCancelled();
                    return false;
                }
            }
        }
    }
}
