<?php
 
namespace tschrock\worldcommander;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use tschrock\worldcommander\flag\iFlag;

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
     * @param \tschrock\WorldCommander\iFlag $flag The flag to register.
     */
    public function registerFlag(iFlag $flag) {
        $this->flags[$flag->getName()] = $flag;
        $flag->onEnable();
    }

    public function unregisterFlag($flag) {
        $iflag = $this->getFlag($flag);
        $this->flags[$iflag->getName()]->onDisable();
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

    public function getHelp($flag = false) {
        if ($flag === false) {
            $rtn = "";
            foreach ($this->flags as $iflag) {
                if ($iflag instanceof iFlag) {
                    $rtn .= $this->getHelp($iflag) . "\n";
                }
            }
            return $rtn;
        } elseif (($iflag = $this->getFlag($flag)) instanceof iFlag) {
            return str_pad("/wc flag <world> " . $iflag->getUsage(), 30) . "  - " . $iflag->getDescription();
        }
    }

    public function canEditFlag(CommandSender $sender, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        return $sender->hasPermission("tschrock.worldcommander.all") ||
                ($this->plugin->getConfig()->get(self::CONFIG_OPS) && $sender->isOp()) ||
                $sender->hasPermission("tschrock.worldcommander.flags") ||
                $sender->hasPermission("tschrock.worldcommander.flags." . $iflag->getName() . ".edit");
    }

    public function canBypassFlag(CommandSender $sender, $flag) {
        $iflag = $this->getFlag($flag);
        if ($iflag === false) {
            return false;
        }
        return ($this->plugin->getConfig()->get(self::CONFIG_EXCLD_WCALL) && $sender->hasPermission("tschrock.worldcommander.all")) ||
                ($this->plugin->getConfig()->get(self::CONFIG_EXCLD_OP) && $sender->isOp()) ||
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
            $regions = $this->wCommander->getDataProvider()->getRegion($position->level->getName(), $position);

            if ($regions != null) {
                $rtn = $this->wCommander->getDataProvider()->getRegionFlag(array_shift($regions), $iflag->getName());
            } else {
                $rtn = $this->wCommander->getDataProvider()->getWorldFlag($position->level->getName(), $iflag->getName());
            }
        } else {
            $regions = $this->wCommander->getDataProvider()->getRegion($area, $position);

            if ($regions != null) {
                $rtn = $this->wCommander->getDataProvider()->getRegionFlag(array_shift($regions), $iflag->getName());
            } else {
                $rtn = $this->wCommander->getDataProvider()->getWorldFlag($area, $iflag->getName());
            }
        }
        
        if ($rtn === null){
            $rtn = $iflag->getDefaultValue();
        }
        
        return $rtn;
    }

}
