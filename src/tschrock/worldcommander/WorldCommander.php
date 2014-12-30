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

    /** @return WorldCommander */
    public static function getInstance() {
        return self::$instance;
    }

    /** @var YMLDataProvider */
    protected $dataProvider;

    /** @var YMLDataProvider */
    public function getDataProvider() {
        return $this->dataProvider;
    }

    /** @var FlagHelper  */
    protected $flagHelper;

    /** @var FlagHelper  */
    public function getFlagHelper() {
        return $this->flagHelper;
    }

    /** @var array<iFlag> */
    protected $includedFlags;

    public function __construct() {
        self::$instance = $this;
    }

    /**
     * The onLoad function.
     */
    public function onLoad() {
        $this->dataProvider = new YMLDataProvider($this->getDataFolder() . "worldData.yml");
        $this->flagHelper = new FlagHelper($this);

        $this->includedFlags = array(
            new flag\GamemodeFlag($this, $this),
            new flag\PvPFlag($this, $this),
            new flag\SpawnProtectionFlag($this, $this),
            new flag\TimeFlag($this, $this),
            new flag\BuildFlag($this, $this)
        );
    }

    /**
     * The onEnable function.
     * 
     * Registers all built-in flags and loads the config.
     */
    public function onEnable() {
        foreach ($this->includedFlags as $flag) {
            $this->getFlagHelper()->registerFlag($flag);
        }

        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    /**
     * The onDisable function.
     * 
     * Unregisters all built-in flags.
     */
    public function onDisable() {
        foreach ($this->includedFlags as $flag) {
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
                    case "r":
                    case "region":
                    case "regions":
                        $this->oncommand_region($sender, $args);
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
            case "wcr":
            case "region":
            case "regions":
                $this->oncommand_region($sender, $args);
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
            if ($sender->hasPermission("tschrock.worldcommander.all") ||
                    $sender->hasPermission("tschrock.worldcommander.worlds") ||
                    $sender->hasPermission("tschrock.worldcommander.worlds.create") ||
                    ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp())) {
                $sender->sendMessage("[WorldCommander] Starting generation of world '$args[0]' in the background.");
                $this->getServer()->generateLevel(array_shift($args), array_shift($args), array_shift($args), $args);
            } else {
                $sender->sendMessage("[WorldCommander] You don't have permission to create worlds!");
            }
        }
    }

    public function oncommand_world_load(CommandSender $sender, array $args) {
        $world = array_shift($args);

        if (!isset($world) || !is_string($world) || $world == '' || $world == null) {
            $sender->sendMessage("[WorldCommander] Usage: /wc load <worldname>");
        } elseif (!Utilities::doesWorldExist($world)) {
            $sender->sendMessage("[WorldCommander] That world doesn't exist!");
        } elseif (!($sender->hasPermission("tschrock.worldcommander.all") ||
                $sender->hasPermission("tschrock.worldcommander.worlds") ||
                $sender->hasPermission("tschrock.worldcommander.worlds.load") ||
                ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
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
        } elseif (!Utilities::doesWorldExist($args[0])) {
            $sender->sendMessage("[WorldCommander] That world doesn't exist!");
        } elseif (!($sender->hasPermission("tschrock.worldcommander.all") ||
                $sender->hasPermission("tschrock.worldcommander.worlds") ||
                $sender->hasPermission("tschrock.worldcommander.worlds.unload") ||
                ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
            $sender->sendMessage("[WorldCommander] You don't have permission to unload worlds!");
        } else {
            $name = $args[0];
            $sender->sendMessage("[WorldCommander] Attempting to unload world '$name'");
            $result = $this->getServer()->unloadLevel($this->getServer()->getLevelByName($name));
            if ($result) {
                $sender->sendMessage("[WorldCommander] Successfully unloaded world '$name'");
            } else {
                $sender->sendMessage("[WorldCommander] Failed to unload world '$name'. See console for details.");
            }
        }
    }

    public function oncommand_flags(CommandSender $sender, array $args) {

        switch (array_shift($args)) {
            case "help":
                Utilities::sendSplitMessage($sender, "Commands: " .
                        "\n    /wc flag help    - get this help." .
                        "\n    /wc flag list    - list all flags." .
                        "\n    /wc flag info <flag|area>  - get info for a flag/world/region." .
                        "\n    /wc flag set <area> <flag> <value>  - set the flag in an area."
                );
                break;
            case "ls":
            case "list":
                Utilities::sendSplitMessage($sender, "Available flags:\n"
                        . $this->getFlagHelper()->getHelp()
                );
                break;
            case "info":
                if (Utilities::doesWorldExist($arg)) {
                    $sender->sendMessage("World '$arg' has " . count($this->getDataProvider()->getWorldFlags($arg)) . " flags set.");
                } elseif ($this->getDataProvider()->isRegion($arg)) {
                    $sender->sendMessage("Region '$arg' has " . count($this->getDataProvider()->getRegionFlags($arg)) . " flags set.");
                } elseif ($this->getFlagHelper()->getFlag($arg) != false) {
                    $sender->sendMessage($this->getFlagHelper()->getHelp($arg));
                } else {
                    $sender->sendMessage("Usage: /wc flag <area> <flag> or /wc flag help");
                }
                break;
            case "set":
                $area = array_shift($args);

                if (($area == "@world" || $area == "@region") && !($sender instanceof Player && $sender->spawned)) {
                    $sender->sendMessage("You can only use @world/@region in-game.");
                    return;
                }

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

                if (!$this->dataProvider->isValidArea($area)) {
                    $sender->sendMessage("'$area' isn't a valid area! It must be a world or region.");
                    return;
                }

                if (($iflag = $this->flagHelper->getFlag(array_shift($args))) == false) {
                    $sender->sendMessage("That flag doesn't exist.");
                    return;
                } else {
                    $iflag->handleCommand($sender, $area, $args);
                }

                break;
            default:
                $sender->sendMessage("Usage: /wc flag set <area> <flag> <value>  or  /wc flag help");
                break;
        }
    }

    public function oncommand_tp(CommandSender $sender, array $args) {


        if (count($args) == 1) {
            if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                    $sender->hasPermission("tschrock.worldcommander.tp") ||
                    ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                $sender->sendMessage("You don't have permission to teleport.");
                return;
            }
            if ($sender instanceof Player) {
                $player = $sender;
                $world = Server::getInstance()->getLevelByName(array_shift($args));
                if ($world === false) {
                    return;
                }
            } else {
                $sender->sendMessage("You must put a world!");
                return;
            }
        } elseif (count($args) == 2) {
            if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                    $sender->hasPermission("tschrock.worldcommander.tp.other") ||
                    ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                $sender->sendMessage("You don't have permission to teleport other players.");
                return;
            }
            $player = $this->getServer()->getPlayerExact(array_shift($args));
            if ($player === null) {
                $sender->sendMessage("That player doesn't exist or isn't online!");
                return;
            }
            $world = Server::getInstance()->getLevelByName(array_shift($args));
            if ($world === false) {
                $sender->sendMessage("That world doesn't exist!");
                return;
            }
        } else {
            $sender->sendMessage("Usage: /wc tp [player] <world>");
            return;
        }

        $player->teleport($world->getSafeSpawn());
    }

    protected $positions = array();

    public function oncommand_region(CommandSender $sender, array $args) {


        if ($sender instanceof Player) {
            switch (strtolower(array_shift($args))) {
                case "pos1":
                    if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                            $sender->hasPermission("tschrock.worldcommander.regions") ||
                            ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                        $sender->sendMessage("You don't have permission to manage regions!");
                        return;
                    }
                    if (!isset($this->positions[$sender->getName()])) {
                        $this->positions[$sender->getName()] = array();
                    }
                    $this->positions[$sender->getName()]["pos1"] = $sender->getPosition();
                    $sender->sendMessage("Position 1 set to " . $sender->getPosition());
                    break;
                case "pos2":
                    if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                            $sender->hasPermission("tschrock.worldcommander.regions") ||
                            ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                        $sender->sendMessage("You don't have permission to manage regions!");
                        return;
                    }
                    if (!isset($this->positions[$sender->getName()])) {
                        $this->positions[$sender->getName()] = array();
                    }
                    $this->positions[$sender->getName()]["pos2"] = $sender->getPosition();
                    $sender->sendMessage("Position 2 set to " . $sender->getPosition());
                    break;
                case "new":
                case "create":
                    if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                            $sender->hasPermission("tschrock.worldcommander.regions") ||
                            $sender->hasPermission("tschrock.worldcommander.regions.create") ||
                            ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                        $sender->sendMessage("You don't have permission to create regions!");
                        return;
                    }
                    if (!(count($args) > 0)) {
                        $sender->sendMessage("Usage: /region create <name> <priority>");
                    } elseif (!(isset($this->positions[$sender->getName()]) &&
                            isset($this->positions[$sender->getName()]["pos1"]) &&
                            isset($this->positions[$sender->getName()]["pos2"]))) {
                        $sender->sendMessage("You must mark the two corners of your region with '/region pos1' and '/region pos2'.");
                    } elseif ($this->getDataProvider()->isRegion($args[0])) {
                        $sender->sendMessage("That region already exists!");
                    } else {
                        $pos1 = $this->positions[$sender->getName()]["pos1"];
                        $pos2 = $this->positions[$sender->getName()]["pos2"];
                        $priority = isset($args[1]) ? $args[1] : 0;
                        if ($pos1->getLevel()->getName() != $pos2->getLevel()->getName()) {
                            $sender->sendMessage("pos1 and pos2 must be in the same world!");
                        } else {
                            $this->getDataProvider()->createRegion($args[0], $pos1, $pos2, $priority);
                            $sender->sendMessage("Successfully created region '$args[0]'");
                        }
                    }

                    break;
                case "delete":
                case "remove":
                case "rm":
                    if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                            $sender->hasPermission("tschrock.worldcommander.regions") ||
                            $sender->hasPermission("tschrock.worldcommander.regions.delete") ||
                            ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                        $sender->sendMessage("You don't have permission to delete regions!");
                        return;
                    }
                    if (count($args) > 1) {
                        if ($args[0] != $args[1]) {
                            $sender->sendMessage("Region names must match! '/region delete <name> <confirm name>'");
                        } elseif (!$this->getDataProvider()->isRegion($args[0])) {
                            $sender->sendMessage("That region doesn't exists!");
                        } else {
                            $this->getDataProvider()->removeRegion($args[0]);
                            $sender->sendMessage("Removed region '$args[0]'?");
                        }
                    } elseif (count($args) > 0) {
                        $sender->sendMessage("Are you sure you want to delete '$args[0]'?");
                        $sender->sendMessage("Use '/region delete <name> <confirm name>'");
                    } else {
                        $sender->sendMessage("Usage: /region delete <name>");
                    }

                    break;
                case "list":
                case "ls":
                    if (!($sender->hasPermission("tschrock.worldcommander.all") ||
                            $sender->hasPermission("tschrock.worldcommander.regions") ||
                            ($this->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()))) {
                        $sender->sendMessage("You don't have permission to mannage regions!");
                        return;
                    }
                    $sender->sendMessage(implode(", ", array_keys($this->getDataProvider()->getAllRegionData())));
                    break;
                default:
                    $sender->sendMessage("Usage: /regions <pos1|pos2|create|delete|list>");
                    break;
            }
        }
    }

}
