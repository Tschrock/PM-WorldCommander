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
class FlagManager {

    /**
     *
     * @var WorldCommander
     */
    private $wCommander;

    /**
     *
     * @var array<iFlag>
     */
    private $flags;

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
        $iflag->onEnable();
        $this->wCommander->getLogger()->info("registered flag: " . $iflag->getName());

        // Add the permission nodes for the flag.
        Server::getInstance()->getPluginManager()->addPermission(
                new Permission("tschrock.worldcommander.flags." . $iflag->getName() . ".edit"
                , "Allow a person to change the " . $iflag->getName() . "flag.", false));
        Server::getInstance()->getPluginManager()->addPermission(
                new Permission("tschrock.worldcommander.flags." . $iflag->getName() . ".bypass"
                , "Allow a person to bypass the " . $iflag->getName() . "flag.", false));
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
        $this->flags[$iflag->getName()]->onDisable();
        $this->wCommander->getLogger()->info("unregistered flag: " . $flag->getName());
        unset($this->flags[$iflag->getName()]);
    }

    /**
     * 
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
     * 
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

    public function getFlagValue($area, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }

        $rtn = null;

        if ($area instanceof Position) {
            $regions = $this->wCommander->getDataProvider()->getRegion($area->level->getName(), $area);

            if ($regions != null) {
                $rtn = $this->wCommander->getDataProvider()->getRegionFlag(array_shift($regions), $iflag->getName());
            } else {
                $rtn = $this->wCommander->getDataProvider()->getWorldFlag($area->level->getName(), $iflag->getName());
            }
        } else {
            $regions = $this->wCommander->getDataProvider()->getRegion($area);

            if ($regions != null) {
                $rtn = $this->wCommander->getDataProvider()->getRegionFlag(array_shift($regions), $iflag->getName());
            } else {
                $rtn = $this->wCommander->getDataProvider()->getWorldFlag($area, $iflag->getName());
            }
        }

        if ($rtn === null) {
            $rtn = $iflag->getDefaultValue();
        }

        return $rtn;
    }

}
