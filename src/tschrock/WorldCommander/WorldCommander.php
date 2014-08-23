<?php

namespace tschrock\WorldCommander;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;

class WorldCommander extends PluginBase
{

    /**
     *
     * @var Utilities
     */
    public $utilities;

    /**
     * The onLoad function - empty.
     */
    public function onLoad()
    {
        
    }

    /**
     * The onEnable function - just setting up the config.
     */
    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeControlTask($this), $this->getConfig()->get(Utilities::CONFIG_TIME));
        $this->utilities = new Utilities($this);
        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    /**
     * The onDisable function - also empty.
     */
    public function onDisable()
    {
        
    }

    /**
     * The command handler - Handles user input for the /mail command.
     * 
     * @param \pocketmine\command\CommandSender $sender The person who sent the command.
     * @param \pocketmine\command\Command $command The command.
     * @param string $label The label for the command. - What's this?
     * @param array $args The arguments with the command.
     * @return boolean Wether or not the command succeded.
     */
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
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

    public function oncommand_world_create(CommandSender $sender, array $args)
    {
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

    public function oncommand_world_load(CommandSender $sender, array $args)
    {
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

    public function oncommand_world_unload(CommandSender $sender, array $args)
    {
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

    public function oncommand_flags(CommandSender $sender, array $args)
    {

        if (($world = $this->utilities->getWorld($sender, $args, false)) instanceof \pocketmine\level\Level || !(isset($args[0]))) {
            switch (array_shift($args)) {
                case "gm":
                case "gamemode":
                    $gmtext = array_shift($args);
                    if (!is_string($gmtext) || $gmtext == '' || $gmtext == null) {
                        $sender->sendMessage("[WorldCommander] Usage: /wc flag gamemode <creative|survival|adventure|spectator>");
                    } else {
                        $gm = Server::getGamemodeFromString($gmtext);
                        if ($gm == -1) {
                            $sender->sendMessage("[WorldCommander] That isn't a correct gamemode! (Must be Creative/Survival/Adventure/Spectator)");
                            return true;
                        } else {
                            $sender->sendMessage("[WorldCommander] Set gamemode in '" . $world->getName() . "' to " . Server::getGamemodeString($gm));
                            return $this->utilities->c_setFlag($sender, $world, Utilities::FLAG_GM, $gm);
                        }
                    }
                    break;
                case "pvp":
                    $pvp = $this->utilities->parseBoolean(array_shift($args));
                    if ($pvp === "NA") {
                        $sender->sendMessage("[WorldCommander] That isn't a correct pvp value! (Must be true or false)");
                        return true;
                    } else {
                        return $this->utilities->c_setFlag($sender, $world, Utilities::FLAG_PVP, $pvp);
                    }
                    break;
//                case "fire":
//                case "allowfire":
//                    $firetxt = strtolower(array_shift($args));
//                    $fire = ($firetxt == "true") ? true : ($firetxt == "1") ? true : ($firetxt == "t") ? true : $firetxt;
//                    $fire2 = ($fire == "false") ? false : ($fire == "0") ? false : ($fire == "-1") ? false : ($fire == "f") ? false : null;
//                    if ($fire2 === null) {
//                        $sender->sendMessage("[WorldCommander] That isn't a correct allowfire value! (Must be true or false)");
//                        return true;
//                    } else {
//                        return $this->utilities->c_setFlag($sender, $world, Utilities::FLAG_FIRE, $fire2);
//                    }
//                    break;
                case "spawn":
                case "spawnprotection":
                    $radius = array_shift($args);
                    if (!is_numeric($radius)) {
                        $sender->sendMessage("[WorldCommander] That isn't a correct spawnprotection value! (Must be a number)");
                        return false;
                    } else {
                        return $this->utilities->c_setFlag($sender, $world, Utilities::FLAG_SPAWN, $radius);
                    }
                    break;
                case "blocks":
                case "banblocks":
                case "banedblocks":
                case "bannedblocks":
                    $type = array_shift($args);
                    $block = array_shift($args);
                    if ($block == "") {
                        $sender->sendMessage("[WorldCommander] Usage: /wc flags bannedblocks <add|remove> <blockId[:data]>");
                        break;
                    }
                    if (!is_numeric($block)) {
                        $str = explode(":", $block);
                        if (!is_numeric($str[0]) || !is_numeric($str[1])) {
                            $sender->sendMessage("[WorldCommander] Usage: /wc flags bannedblocks <add|remove> <blockId[:data]>");
                            break;
                        }
                    }
                    switch ($type) {
                        case "add":
                            $arr = $this->utilities->getFlag($world, Utilities::FLAG_BLOCKS);
                            $arr[$block] = true;
                            $this->utilities->setFlag($world, Utilities::FLAG_BLOCKS, $arr);
                            break;
                        case "remove":
                            $arr = $this->utilities->getFlag($world, Utilities::FLAG_BLOCKS);
                            unset($arr[$block]);
                            $this->utilities->setFlag($world, Utilities::FLAG_BLOCKS, $arr);
                            break;
                        default:
                            $sender->sendMessage("[WorldCommander] Usage: /wc flags bannedblocks <add|remove> <blockId[:data]>");
                            break;
                    }
                    break;
                case "time":

                    $arg2 = array_shift($args);

                    if (!isset($arg2)) {
                        $sender->sendMessage("[WorldCommander] Usage: /wc flag [world] time <realtime|auto|(number)|(+|-|*|/)(number>");
                    } elseif ($arg2 == "auto") {
                        
                    } elseif ($arg2 == "realtime") {
                        
                    } elseif (is_numeric($arg2)) {
                        
                    } else {
                        
                    }
                    return $this->utilities->c_setFlag($sender, $world, Utilities::FLAG_TIME, $arg2);
                default:
                    $sender->sendMessage(" ");
                    $sender->sendMessage("[WorldCommander] Available flags:");
                    $sender->sendMessage("     Flag:                   Usage:");
                    $sender->sendMessage("  gamemode          - /wc flag [world] gamemode <gamemode>");
                    $sender->sendMessage("  pvp                  - /wc flag [world] pvp <true|false>");
                    #$sender->sendMessage("  allowfire            - /wc flag [world] allowfire <true|false>");
                    $sender->sendMessage("  spawnprotection  - /wc flag [world] spawnprotection <radius>");
                    $sender->sendMessage("  bannedblocks     - /wc flag [world] bannedblocks <add|remove> <block>");
                    $sender->sendMessage("  time                  - /wc flag [world] time <realtime|auto|(number)|(+|-|*|/)(number>");
                    break;
            }
        } else {
            if (is_string($world)) {
                $sender->sendMessage($world);
            }
        }
    }

    public function oncommand_tp(CommandSender $sender, array $args)
    {

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
