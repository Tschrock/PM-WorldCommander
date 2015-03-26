<?php

namespace tschrock\worldcommander;

use tschrock\worldcommander\data\DataManager;
use tschrock\worldcommander\flag\FlagManager;
use tschrock\worldcommander\data\Area;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class WorldCommander extends PluginBase {

    /** @var DataManager */
    protected $dataManager;

    public function getDataManager() {
        return $this->dataManager;
    }

    /** @var FlagManager */
    protected $flagManager;

    public function getFlagManager() {
        return $this->flagManager;
    }

    /** @var iFlag[] */
    protected $includedFlags;

    public function registerFlag(flag\iFlag $iflag) {
        $this->getFlagManager()->registerFlag($iflag);
    }

    public function unregisterFlag(flag\iFlag $iflag) {
        $this->getFlagManager()->registerFlag($iflag);
    }

    /**
     * The onLoad function.
     */
    public function onLoad() {
        $this->saveDefaultConfig();
        $this->dataManager = new DataManager($this->getDataFolder() . "worldDataV2.yml");
        $this->flagManager = new FlagManager($this);

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
            $this->getFlagManager()->registerFlag($flag);
        }
        $this->getFlagManager()->enableFlags();
        $this->reloadConfig();
    }

    /**
     * The onDisable function.
     * 
     * Unregisters all built-in flags.
     */
    public function onDisable() {
        $this->getFlagManager()->disableFlags();
        foreach ($this->includedFlags as $flag) {
            $this->getFlagManager()->unregisterFlag($flag);
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
                    case "f":
                    case "flag":
                    case "flags":
                        $this->oncommand_flags($sender, $args);
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
            case "wcf":
                $this->oncommand_flags($sender, $args);
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

    public function oncommand_flags(CommandSender $sender, array $args) {

        switch (array_shift($args)) {
            case "help":
                Utilities::sendSplitMessage($sender, "Commands:\n"
                        . TextFormat::GRAY . "/wcf help"
                        . TextFormat::DARK_GRAY . " -- get this help.\n"
                        . TextFormat::GRAY . "/wcf list"
                        . TextFormat::DARK_GRAY . "   -- list all flags.\n"
                        . TextFormat::GRAY . "/wcf info <flag>"
                        . TextFormat::DARK_GRAY . "  -- get info for a flag.\n"
                        . TextFormat::GRAY . "/wcf set <area> <flag> <value>"
                        . TextFormat::DARK_GRAY . " -- set a flag.\n"
                        . TextFormat::GRAY . "/wcf unset <area> <flag>"
                        . TextFormat::DARK_GRAY . "    -- unsets a flag."
                );
                break;
            case "ls":
            case "list":
                Utilities::sendSplitMessage($sender, "Available flags:\n"
                        . $this->getFlagManager()->getHelp(false, FlagManager::HELP_MEDIUM)
                );
                break;
            case "info":
                if (count($args) == 1) {
                    if (Utilities::doesWorldExist($this->getServer(), $args[0])) {
                        $sender->sendMessage("World '$args[0]' has " . count($this->getDataProvider()->getWorldFlags($args[0])) . " flags set.");
                    } elseif ($this->getDataProvider()->isRegion($args[0])) {
                        $sender->sendMessage("Region '$args[0]' has " . count($this->getDataProvider()->getRegionFlags($args[0])) . " flags set.");
                    } elseif ($this->getFlagManager()->getFlag($args[0]) != false) {
                        $sender->sendMessage($this->getFlagManager()->getHelp($args[0], FlagManager::HELP_LONG));
                    } else {
                        $sender->sendMessage("Usage: /wc flag <area> <flag> or /wc flag help");
                    }
                } else {
                    $sender->sendMessage("Usage: /wc flag info <area/flag> ");
                }
                break;
            case "set":
                $area = $this->getNamedArea($sender, array_shift($args));

                if (($iflag = $this->getFlagManager()->getFlag(array_shift($args))) == false) {
                    $sender->sendMessage("That flag doesn't exist.");
                    return;
                } else {
                    if ($this->getFlagManager()->canEditFlag($sender, $area, $iflag)) {
                        $iflag->handleCommand($sender, $area, $args);
                    } else {
                        $sender->sendMessage("You don't have permission to edit that flag in that area!");
                    }
                }

                break;
            case "unset":
                $area = $this->getNamedArea($sender, array_shift($args));

                if (($iflag = $this->flagHelper->getFlag(array_shift($args))) == false) {
                    $sender->sendMessage("That flag doesn't exist.");
                    return;
                } else {
                    if ($this->getFlagManager()->canEditFlag($sender, $area, $iflag)) {
                        $area->unsetFlag($iflag);
                        $sender->sendMessage("Successfully unset '" . $iflag->getName() . "' in area '" . $area->getName() . "'.");
                    } else {
                        $sender->sendMessage("You don't have permission to edit that flag in that area!");
                    }
                }

                break;
            default:
                $sender->sendMessage("Usage: /wc flag <help|list|info|set|unset >");
                break;
        }
    }

    /**
     * @param CommandSender $sender
     * @param string $areaName
     * @return boolean|Area
     */
    public function getNamedArea($sender, $areaName) {
        if (($areaName == "@world" || $areaName == "@region") && !($sender instanceof Player && $sender->spawned)) {
            $sender->sendMessage("You can only use @world/@region in-game.");
        } elseif ($areaName == "@world") {
            return $this->getDataManager()->getWorld($sender);
        } elseif ($areaName == "@region") {
            $regions = $this->getDataManager()->getRegions($sender);
            if (isset($regions[0])) {
                return $regions[0];
            } else {
                $sender->sendMessage("You aren't in any regions! Did you mean @world?");
            }
        } else {
            $area = $this->getDataManager()->getWorld($sender)->getTopRegion($areaName);
            if ($area !== null) {
                return $area;
            }
            $sender->sendMessage("'$areaName' isn't a valid region.");
        }
        return false;
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
                        $sender->sendMessage("You don't have permission to manage regions!");
                        return;
                    }
                    $sender->sendMessage(implode(", ", array_keys($this->getDataProvider()->getAllRegionData())));
                    break;
                default:
                    $sender->sendMessage("Usage: /regions <pos1|pos2|create|delete|list>");
                    break;
            }
        } else if ($sender instanceof \pocketmine\command\ConsoleCommandSender) {
            switch (strtolower(array_shift($args))) {
                case "pos1":
                    $sender->sendMessage("You must be in-game to set positions.");
                    break;
                case "pos2":
                    $sender->sendMessage("You must be in-game to set positions.");
                    break;
                case "new":
                case "create":
                    $sender->sendMessage("You must be in-game to create regions.");
                    break;
                case "delete":
                case "remove":
                case "rm":
                    if (count($args) > 1) {
                        if ($args[0] != $args[1]) {
                            $sender->sendMessage("Region names must match! '/region delete <name> <confirm name>'");
                        } elseif (!$this->getDataProvider()->isRegion($args[0])) {
                            $sender->sendMessage("That region doesn't exist!");
                        } else {
                            $this->getDataProvider()->removeRegion($args[0]);
                            $sender->sendMessage("Removed region '$args[0]'?");
                        }
                    } elseif (count($args) > 0) {
                        if (!$this->getDataProvider()->isRegion($args[0])) {
                            $sender->sendMessage("That region doesn't exist!");
                        } else {
                            $sender->sendMessage("Are you sure you want to delete '$args[0]'?");
                            $sender->sendMessage("Use '/region delete <name> <confirm name>'");
                        }
                    } else {
                        $sender->sendMessage("Usage: /region delete <name>");
                    }

                    break;
                case "list":
                case "ls":
                    $sender->sendMessage(implode(", ", array_keys($this->getDataProvider()->getAllRegionData())));
                    break;
                default:
                    $sender->sendMessage("Usage: /regions <pos1|pos2|create|delete|list>");
                    break;
            }
        } else {
            $sender->sendMessage("You don't have permission to manage regions!");
        }
    }

}
