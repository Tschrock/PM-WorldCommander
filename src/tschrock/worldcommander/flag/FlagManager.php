<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\flag;

use tschrock\worldcommander\data\Area;
use tschrock\worldcommander\WorldCommander;
use tschrock\worldcommander\Utilities;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;

/**
 * Description of FlagManager
 *
 * @author tyler
 */
class FlagManager {

    /**
     * @var WorldCommander
     */
    private $wCommander;

    /**
     * @var iFlag[]
     */
    private $flags;
    private $enabled = false;

    public function __construct(WorldCommander $wCommander) {
        $this->wCommander = $wCommander;
    }

    /**
     * Registers a flag to the WorldCommander plugin.
     * @param \tschrock\WorldCommander\iFlag $iflag The flag to register.
     */
    public function registerFlag(iFlag $iflag) {

        // Register the flag and enable it.
        $this->flags[$iflag->getName()] = $iflag;
        if ($this->enabled) {
            $this->enableFlag($iflag);
        }
        $this->wCommander->getLogger()->info("registered flag: " . $iflag->getName());
    }

    public function enableFlags() {
        foreach ($this->flags as $flag) {
            $this->enableFlag($flag);
        }
        $this->enabled = true;
    }

    public function disableFlags() {
        foreach ($this->flags as $flag) {
            $this->disableFlag($flag);
        }
        $this->enabled = false;
    }

    public function enableFlag($flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return;
        }
        if ($iflag->isDisabled()) {
            $iflag->onEnable();
            $iflag->setEnabled(true);
            $this->wCommander->getLogger()->info("enabled flag: " . $iflag->getName());
        }
    }

    public function disableFlag($flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return;
        }
        if ($iflag->isEnabled()) {
            $iflag->onDisable();
            $iflag->setEnabled(false);
            $this->wCommander->getLogger()->info("disabled flag: " . $iflag->getName());
        }
    }

    public function unregisterFlag($flag) {

        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return;
        }

        // Disable the flag and unregister the flag.
        $this->disableFlag($iflag);
        $this->wCommander->getLogger()->info("unregistered flag: " . $iflag->getName());
        unset($this->flags[$iflag->getName()]);
    }

    /**
     * Finds a flag by it's name or alias
     * @param string $name
     * @return iFlag|bool
     */
    public function findFlag($name) {
        if (isset($this->flags[$name])) {
            return $this->flags[$name];
        } else {
            foreach ($this->flags as $iflag) {
                if (array_search($name, $iflag->getAliases()) !== false) {
                    return $iflag;
                }
            }
        }
        return false;
    }

    /**
     * Get the flag by type or name.
     * @param iFlag|string $flag
     * @return iFlag|bool
     */
    public function getFlag($flag) {
        if ($flag instanceof iFlag) {
            return $this->flags[$flag->getName()];
        } else {
            return $this->findFlag($flag);
        }
    }

    const HELP_SHORT = 0;
    const HELP_MEDIUM = 1;
    const HELP_LONG = 2;

    /**
     * Gets the help for all flags (or the specified one).
     * @param iFlag|string $flag
     * @param boolean $short
     * @return string
     */
    public function getHelp($flag = false, $type = self::HELP_MEDIUM) {
        if ($flag === false) {
            $rtn = "";
            foreach ($this->flags as $iflag) {
                if ($iflag instanceof iFlag) {
                    $rtn .= " " . $this->getHelp($iflag, $type) . "\n";
                }
            }
            return $rtn;
        } elseif (($iflag = $this->getFlag($flag)) instanceof iFlag) {

            $rtn = $iflag->getUsage();

            if ($type === self::HELP_MEDIUM) {
                $rtn = "/wc flag set <area> " . $rtn;
                if ($type === self::HELP_LONG) {
                    $rtn .= "\n  - " . $iflag->getDescription();
                }
            }
            return $rtn;
        }
    }

    /**
     * Weather or not a player can edit a flag.
     * @param CommandSender $sender
     * @param Area|Position $area
     * @param iFlag|string $flag
     * @return boolean
     */
    public function canEditFlag(CommandSender $sender, Area $area, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        if ($area instanceof Position) {
            $area = $this->wCommander->getDataManager()->getArea($area);
        }
        return $sender->hasPermission("tschrock.worldcommander.all") ||
                ($this->wCommander->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()) ||
                $sender->hasPermission("tschrock.worldcommander.flags") ||
                $sender->hasPermission("tschrock.worldcommander.flags.edit") ||
                $sender->hasPermission("tschrock.worldcommander.flag." . $iflag->getName() . ".edit") ||
                $sender->hasPermission("tschrock.worldcommander.area." . $area->getName() . ".edit") ||
                $sender->hasPermission("tschrock.worldcommander.areaflag." . $area->getName() . "." . $iflag->getName() . ".edit");
    }

    /**
     * Weather or not a player can bypass a flag.
     * @param CommandSender $player
     * @param Area|Position $area
     * @param iFlag|string $flag
     * @return boolean
     */
    public function canBypassFlag(\pocketmine\Player $player, Area $area, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        if ($area instanceof Position) {
            $area = $this->wCommander->getDataManager()->getArea($area);
        }
        return ($this->wCommander->getConfig()->get(Utilities::CONFIG_EXCLD_WCALL) && $player->hasPermission("tschrock.worldcommander.all")) ||
                ($this->wCommander->getConfig()->get(Utilities::CONFIG_EXCLD_OP) && $player->isOp()) ||
                $player->hasPermission("tschrock.worldcommander.flags") ||
                $player->hasPermission("tschrock.worldcommander.flags.bypass") ||
                $player->hasPermission("tschrock.worldcommander.flag." . $iflag->getName() . ".bypass") ||
                $player->hasPermission("tschrock.worldcommander.area." . $area->getName() . ".bypass") ||
                $player->hasPermission("tschrock.worldcommander.areaflag." . $area->getName() . "." . $iflag->getName() . ".bypass");
    }

}
