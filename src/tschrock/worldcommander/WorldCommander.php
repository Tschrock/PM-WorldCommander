<?php

namespace tschrock\worldcommander;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;

class WorldCommander extends PluginBase {

    /** @var WorldCommander */
    private static $instance = null;

    /**
     * @return WorldCommander
     */
    public static function getInstance() {
        return self::$instance;
    }

    /**
     *
     * @var array<iFlag>
     */
    protected $inclFlags;

    /**
     *
     * @var YMLDataProvider
     */
    protected $dataProvider;

    public function getDataProvider() {
        return $this->dataProvider;
    }

    /**
     *
     * @var FlagManager
     */
    protected $flagHelper;

    public function getFlagHelper() {
        return $this->flagHelper;
    }

    public function __construct() {
        self::$instance = $this;
    }
    
    /**
     * The onLoad function.
     */
    public function onLoad() {
        $this->dataProvider = new YMLDataProvider($this->getDataFolder() . "worldData.yml");
        $this->flagHelper = new FlagManager($this);

        $this->inclFlags[] = new flag\GamemodeFlag($this, $this);
        $this->inclFlags[] = new flag\PvPFlag($this, $this);
    }

    /**
     * The onEnable function.
     */
    public function onEnable() {
        //$this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
        //$this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeControlTask($this), $this->getConfig()->get(Utilities::CONFIG_TIME));

        foreach ($this->inclFlags as $flag) {
            $this->getFlagHelper()->registerFlag($flag);
        }

        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    /**
     * The onDisable function.
     */
    public function onDisable() {
        foreach ($this->inclFlags as $flag) {
            $this->getFlagHelper()->unregisterFlag($flag);
        }
    }

    /**
     * The command handler - Handles user input for the /wc commands.
     * 
     * @param  $sender The person who sent the command.
     * @param \pocketmine\command\Command $command The command.
     * @param string $label The label for the command. - What's this?
     * @param array $args The arguments with the command.
     * @return boolean Wether or not the command succeded.
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch ($command->getName()) {
            case "wc":
                switch (array_shift($args)) {
                    case "c":
                    case "create":
                        $this->oncommand_world_create($sender, $args);
                        break;
                    case "l":
                    case "load":
                        $this->oncommand_world_load($sender, $args);
                        break;
                    case "u":
                    case "unload":
                        $this->oncommand_world_unload($sender, $args);
                        break;
                    case "f":
                    case "flag":
                    case "flags":
                        $this->oncommand_flags($sender, $args);
                        break;
                    case "t":
                    case "tp":
                    case "teleport":
                        $this->oncommand_tp($sender, $args);
                        break;
                    default:
                        return false;
                }
                break;
            case "wcc":
                $this->oncommand_world_create($sender, $args);
                break;
            case "wcl":
                $this->oncommand_world_load($sender, $args);
                break;
            case "wcu":
                $this->oncommand_world_unload($sender, $args);
                break;
            case "wcf":
                $this->oncommand_flags($sender, $args);
                break;
            case "wctp":
                $this->oncommand_tp($sender, $args);
                break;
            default:
                return false;
        }
        return true;
    }

    public function oncommand_world_create(CommandSender $sender, array $args) {
        if (!isset($args[0]) || !is_string($args[0]) || $args[0] == '' || $args[0] == null) {
            $sender->sendMessage("[WorldCommander] Usage: /wc create <worldname>");
        } elseif (!ctype_alnum($args[0])) {
            $sender->sendMessage("[WorldCommander] World names must contain only letters and numbers!");
        } elseif ($this->getServer()->getLevelByName($args[0]) != null) {
            $sender->sendMessage("[WorldCommander] This world already exists!");
        } else {
            $world = $this->utilities->checkWorldName($args[0]);
            if ($world != true) {
                $sender->sendMessage($world);
            } elseif (!$this->utilities->checkPerms($sender, Utilities::PERM_CREATE)) {
                $sender->sendMessage("[WorldCommander] You don't have permission to create worlds!");
            } else {
                $sender->sendMessage("[WorldCommander] Starting generation of world '$args[0]' in the background.");
                $this->getServer()->generateLevel(array_shift($args), array_shift($args), array_shift($args), $args);
            }
        }
    }

    public function oncommand_world_load(CommandSender $sender, array $args) {
        $world = array_shift($args);

        if (!isset($world) || !is_string($world) || $world == '' || $world == null) {
            $sender->sendMessage("[WorldCommander] Usage: /wc load <worldname>");
        } elseif ($this->utilities->doesWorldExist($world) == false) {
            $sender->sendMessage("[WorldCommander] That world doesn't exist!");
        } elseif (!$this->utilities->checkPerms($sender, Utilities::PERM_LOAD)) {
            $sender->sendMessage("[WorldCommander] You don't have permission to load worlds!");
        } else {
            $sender->sendMessage("[WorldCommander] Attempting to load world '$world'");
            $result = $this->getServer()->loadLevel($world);
            if ($result) {
                $sender->sendMessage("[WorldCommander] Successfully loaded world '$world'");
            } else {
                $sender->sendMessage("[WorldCommander] Failed to load world '$world'. See console for details.");
            }
        }
    }

    public function oncommand_world_unload(CommandSender $sender, array $args) {
        if (!isset($args[0]) || !is_string($args[0]) || $args[0] == '' || $args[0] == null) {
            $sender->sendMessage("[WorldCommander] Usage: /wc unload <worldname>");
        } elseif (($world = $this->utilities->getWorld($sender, $args)) == false) {
            $sender->sendMessage("[WorldCommander] That world doesn't exist!");
        } elseif (!$this->utilities->checkPerms($sender, Utilities::PERM_UNLOAD)) {
            $sender->sendMessage("[WorldCommander] You don't have permission to unload worlds!");
        } else {
            $name = $world->getName();
            $sender->sendMessage("[WorldCommander] Attempting to unload world '$name'");
            $result = $this->getServer()->unloadLevel($world);
            if ($result) {
                $sender->sendMessage("[WorldCommander] Successfully unloaded world '$name'");
            } else {
                $sender->sendMessage("[WorldCommander] Failed to unload world '$name'. See console for details.");
            }
        }
    }

    public function oncommand_flags(CommandSender $sender, array $args) {
        $area = array_shift($args);
        if ($area == "@world" || $area == "@region") {
            if ($sender instanceof Player && $sender->spawned) {
                if ($area == "@world") {
                    $area = $sender->getLevel()->getName();
                } elseif ($area == "@region") {
                    $regions = $this->dataProvider->getRegion($sender->getLevel()->getName(), $sender->getPosition());
                    if (isset($regions[0])) {
                        $area = $regions[0];
                    } else {
                        $sender->sendMessage("You aren't in any regions! Did you mean @world?");
                        return;
                    }
                }
            } else {
                $sender->sendMessage("You can only use @world/@region in-game.");
                return;
            }
        }

        if (!$this->dataProvider->isValidArea($area)) {
            $sender->sendMessage("'$area' isn't a valid area! It must be a world or region.");
            return;
        }

        if (($iflag = $this->flagHelper->getFlag(array_shift($args))) !== false) {
            $iflag->handleCommand($sender, $area, $args);
        } else {
            $sender->sendMessage("That flag doesn't exist.");
            return;
        }
    }

    public function oncommand_tp(CommandSender $sender, array $args) {

        if (count($args) == 1) {
            if ($sender instanceof Player) {
                $player = $sender;
                $world = $this->utilities->getWorld($sender, $args);
                if ($world === false) {
                    return;
                }
            } else {
                $sender->sendMessage("You must put a player!");
                return;
            }
        } elseif (count($args) == 2) {
            $player = $this->getServer()->getPlayerExact(array_shift($args));
            if ($player === null) {
                $sender->sendMessage("That player doesn't exist or isn't online!");
                return;
            }
            $world = $this->utilities->getWorld($sender, $args);
            if ($world === false) {
                return;
            }
        } else {
            $sender->sendMessage("Usage: /wc tp [player] <world>");
            return;
        }

        $player->teleport($world->getSafeSpawn());
    }

}
