<?php

namespace tschrock\worldcommander;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use tschrock\worldcommander\flag\iFlag;
use pocketmine\Server;
use pocketmine\permission\Permission;

/**
 * Description of FlagHandler
 *
 * @author tyler
 */
class FlagHelper {

    /**
     * @var WorldCommander
     */
    private $wCommander;

    /**
     * @var array<iFlag>
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

        // Add the permission nodes for the flag.
        Server::getInstance()->getPluginManager()->addPermission(
                new Permission("tschrock.worldcommander.flags." . $iflag->getName() . ".edit"
                , "Allow a person to change the " . $iflag->getName() . "flag.", false));
        Server::getInstance()->getPluginManager()->addPermission(
                new Permission("tschrock.worldcommander.flags." . $iflag->getName() . ".bypass"
                , "Allow a person to bypass the " . $iflag->getName() . "flag.", false));
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

        // Remove the permission nodes for the flag.
        Server::getInstance()->getPluginManager()->removePermission(
                Server::getInstance()->getPluginManager()->getPermission(
                        "tschrock.worldcommander.flags." . $iflag->getName() . ".edit"
        ));
        Server::getInstance()->getPluginManager()->removePermission(
                Server::getInstance()->getPluginManager()->getPermission(
                        "tschrock.worldcommander.flags." . $iflag->getName() . ".bypass"
        ));

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

    /**
     * Gets the help for all flags (or the specified one).
     * @param iFlag|string $flag
     * @param boolean $short
     * @return string
     */
    public function getHelp($flag = false, $short = false) {
        if ($flag === false) {
            $rtn = "";
            foreach ($this->flags as $iflag) {
                if ($iflag instanceof iFlag) {
                    $rtn .= $this->getHelp($iflag, true) . "\n";
                }
            }
            return $rtn;
        } elseif (($iflag = $this->getFlag($flag)) instanceof iFlag) {
            $rtn = "/wc flag <world> " . $iflag->getUsage();
            $rtn .= $short ? "" : "\n  - " . $iflag->getDescription();
            return $rtn;
        }
    }

    /**
     * Weather or not a player can edit a flag.
     * @param CommandSender $sender
     * @param iFlag|string $flag
     * @return boolean
     */
    public function canEditFlag(CommandSender $sender, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        return $sender->hasPermission("tschrock.worldcommander.all") ||
                ($this->wCommander->getConfig()->get(Utilities::CONFIG_OPS) && $sender->isOp()) ||
                $sender->hasPermission("tschrock.worldcommander.flags") ||
                $sender->hasPermission("tschrock.worldcommander.flags." . $iflag->getName() . ".edit");
    }

    /**
     * Weather or not a player can bypass a flag.
     * @param CommandSender $sender
     * @param iFlag|string $flag
     * @return boolean
     */
    public function canBypassFlag(CommandSender $sender, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        return ($this->wCommander->getConfig()->get(Utilities::CONFIG_EXCLD_WCALL) && $sender->hasPermission("tschrock.worldcommander.all")) ||
                ($this->wCommander->getConfig()->get(Utilities::CONFIG_EXCLD_OP) && $sender->isOp()) ||
                $sender->hasPermission("tschrock.worldcommander.flags") ||
                $sender->hasPermission("tschrock.worldcommander.flags." . $iflag->getName() . ".bypass");
    }

    /**
     * Gets the value of the flag for a specified area.
     * @param Position|string $area
     * @param iFlag|string $flag
     * @return mixed
     */
    public function getFlagValue($area, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }

        $rtn = null;

        $regions = $this->wCommander->getDataProvider()->getRegion($area);

        if ($regions != null) {
            $rtn = $this->wCommander->getDataProvider()->getRegionFlag(array_shift($regions), $iflag->getName());
        } else {
            $rtn = $this->wCommander->getDataProvider()->getWorldFlag($area, $iflag->getName());
        }

        if ($rtn === null) {
            $rtn = $iflag->getDefaultValue();
        }

        return $rtn;
    }

    /**
     * Sets the value of the flag for a specified area.
     * @param Position|string $area
     * @param iFlag|string $flag
     * @param mixed $value
     * @return boolean
     */
    public function setFlagValue($area, $flag, $value) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        
        return $this->wCommander->getDataProvider()->setFlag($area, $iflag->getName(), $value);
    }
}
