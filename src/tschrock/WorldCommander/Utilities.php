<?php

namespace tschrock\WorldCommander;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;

class Utilities
{

    const CONFIG_OPS = "opHasAllPermissions";
    const CONFIG_EXCLD_OP = "opIsExcluded";
    const CONFIG_EXCLD_WCALL = "worldcommander.allIsExcluded";
    const CONFIG_BLOCK = "blockBanType";
    const CONFIG_WORLDS = "worlds";
    const CONFIG_TIME = "timeUpdateInterval";

    protected $plugin;
    protected $worldConfig = false;

    public function __construct(WorldCommander $plugin)
    {
        $this->plugin = $plugin;
    }

    public function checkWorldName($name)
    {
        if (is_string($name) && $name != '' && $name != null) {
            if (ctype_alnum($name)) {
                if ($this->plugin->getServer()->getLevelByName($name) == null) {
                    return true;
                } else {
                    return "[WorldCommander] This world already exists!";
                }
            } else {
                return "[WorldCommander] World names must contain only letters and numbers!";
            }
        }
        return "[WorldCommander] You must put a correct world name.";
    }

    public function getWorld(CommandSender $sender, array &$args, $strict = true)
    {
        if (isset($args[0])) {
            $world = $this->plugin->getServer()->getLevelByName($args[0]);
            if ($world != null) {
                array_shift($args);
                return $world;
            }
        }
        if ($sender instanceof \pocketmine\Player) {
            $world = $sender->getLevel();
            if ($world instanceof \pocketmine\level\Level) {
                return $world;
            } elseif ($strict === true) {
                $sender->sendMessage("[WorldCommander] You must put a world!");
            } else {
                return "[WorldCommander] You must put a world!";
            }
        } elseif ($strict === true) {
            $sender->sendMessage("[WorldCommander] You must put a world or use this command in-game!");
        } else {
            return "[WorldCommander] You must put a world or use this command in-game!";
        }

        return false;
    }

    const PERM_CREATE = 0;
    const PERM_LOAD = 1;
    const PERM_UNLOAD = 2;
    const FLAG_GM = 3;
    const FLAG_PVP = 4;
#    const FLAG_FIRE = 5;
    const FLAG_SPAWN = 6;
    const FLAG_BLOCKS = 7;
    const FLAG_TIME = 8;

    public function checkPerms(CommandSender $sender, $perm)
    {
        if ($sender->hasPermission("tschrock.worldcommander.all")) {
            return true;
        }
        if ($this->plugin->getConfig()->get(self::CONFIG_OPS) && $sender->isOp()) {
            return true;
        }
        switch ($perm) {
            case self::PERM_CREATE:
                return $sender->hasPermission("tschrock.worldcommander.worlds") ||
                        $sender->hasPermission("tschrock.worldcommander.worlds.create");
            case self::PERM_LOAD:
                return $sender->hasPermission("tschrock.worldcommander.worlds") ||
                        $sender->hasPermission("tschrock.worldcommander.worlds.load");
            case self::PERM_UNLOAD:
                return $sender->hasPermission("tschrock.worldcommander.worlds") ||
                        $sender->hasPermission("tschrock.worldcommander.worlds.unload");
            case self::FLAG_GM:
                return $sender->hasPermission("tschrock.worldcommander.flags") ||
                        $sender->hasPermission("tschrock.worldcommander.flags.gamemode");
            case self::FLAG_PVP:
                return $sender->hasPermission("tschrock.worldcommander.flags") ||
                        $sender->hasPermission("tschrock.worldcommander.flags.pvp");
//            case self::FLAG_FIRE:
//                return $sender->hasPermission("tschrock.worldcommander.flags") ||
//                        $sender->hasPermission("tschrock.worldcommander.flags.allowfire");
            case self::FLAG_SPAWN:
                return $sender->hasPermission("tschrock.worldcommander.flags") ||
                        $sender->hasPermission("tschrock.worldcommander.flags.spawnprotection");
            case self::FLAG_BLOCKS:
                return $sender->hasPermission("tschrock.worldcommander.flags") ||
                        $sender->hasPermission("tschrock.worldcommander.flags.bannedblocks");
            case self::FLAG_TIME:
                return $sender->hasPermission("tschrock.worldcommander.flags") ||
                        $sender->hasPermission("tschrock.worldcommander.flags.time");
            default:
                return false;
        }
        return false;
    }

    public function c_setFlag(CommandSender $sender, $world, $flag, $value)
    {
        if (!$this->checkPerms($sender, $flag)) {
            $sender->sendMessage("[WorldCommander] You don't have permission to change that flag!");
            return false;
        }
        if (($result = $this->setFlag($world, $flag, $value)) != true) {
            $sender->sendMessage($result);
            return false;
        }
        return true;
    }

    public function setFlag($world, $flag, $value)
    {
        if ($world instanceof \pocketmine\level\Level) {
            $world = $world->getName();
        }

        $worlds = $this->getWorldConfig()->get(self::CONFIG_WORLDS);
        if (!isset($worlds[$world])) {
            $worlds[$world] = array();
        }
        $worlds[$world][$flag] = $value;
        $this->getWorldConfig()->set(self::CONFIG_WORLDS, $worlds);
        $this->getWorldConfig()->save();
        return true;
    }

    public function getFlag($world, $flag)
    {
        if ($world instanceof \pocketmine\level\Level) {
            $world = $world->getName();
        }

        $worlds = $this->getWorldConfig()->get(self::CONFIG_WORLDS);
        if (isset($worlds[$world]) && isset($worlds[$world][$flag])) {
            return $worlds[$world][$flag];
        } else {
            return $this->getDefaultFlagValue($flag);
        }
    }

    public function getDefaultFlagValue($flag)
    {
        switch ($flag) {
            case self::FLAG_GM:
                return $this->plugin->getServer()->getDefaultGamemode();
            case self::FLAG_PVP:
                return $this->plugin->getServer()->getProperty("pvp");
//            case self::FLAG_FIRE:
//                return true;
            case self::FLAG_SPAWN:
                return $this->plugin->getServer()->getSpawnRadius();
            case self::FLAG_BLOCKS:
                return array();
            case self::FLAG_TIME:
                return 'auto';
            default:
                return null;
        }
    }

    public function isPlayerExcluded($player, $flag)
    {
        $player = $this->g_player($player);
        if ($player === false) {
            return false;
        }

        if ($this->plugin->getConfig()->get(self::CONFIG_EXCLD_WCALL) && $player->hasPermission("tschrock.worldcommander.all")) {
            return true;
        }

        if ($this->plugin->getConfig()->get(self::CONFIG_EXCLD_OP) && $player->isOp()) {
            return true;
        }

        switch ($flag) {
            case self::FLAG_GM:
                return $player->hasPermission("tschrock.worldcommander.flags") ||
                        $player->hasPermission("tschrock.worldcommander.flags.gamemode.exclude");
            case self::FLAG_PVP:
                return $player->hasPermission("tschrock.worldcommander.flags") ||
                        $player->hasPermission("tschrock.worldcommander.flags.pvp.exclude");
//            case self::FLAG_FIRE:
//                return $player->hasPermission("tschrock.worldcommander.flags") ||
//                        $player->hasPermission("tschrock.worldcommander.flags.allowfire.exclude");
            case self::FLAG_SPAWN:
                return $player->hasPermission("tschrock.worldcommander.flags") ||
                        $player->hasPermission("tschrock.worldcommander.flags.spawnprotection.exclude");
            case self::FLAG_BLOCKS:
                return $player->hasPermission("tschrock.worldcommander.flags") ||
                        $player->hasPermission("tschrock.worldcommander.flags.bannedblocks.exclude");
            case self::FLAG_TIME:
                return $player->hasPermission("tschrock.worldcommander.flags") ||
                        $player->hasPermission("tschrock.worldcommander.flags.time.exclude");
            default:
                return false;
        }
        return false;
    }

    public function checkPlayerGamemode($player, $world = false)
    {
        $player = $this->g_player($player);
        if ($player === false) {
            return false;
        }

        if ($world === false) {
            $world = $player->getLevel()->getName();
        } else {
            $world = $this->g_world($world)->getName();
        }

        $isExcluded = $this->isPlayerExcluded($player, self::FLAG_GM);
        $worldGamemode = $this->getFlag($world, self::FLAG_GM);

        if ($worldGamemode === "none") {
            return true;
        } elseif (($gamemodeTo = Server::getGamemodeFromString($worldGamemode)) == -1) {
            $this->plugin->getLogger()->warning($worldGamemode . ' isn\'t a valid gamemode! Using default gamemode instead!');
            $gamemodeTo = $this->plugin->getServer()->getDefaultGamemode();
        }

        $gamemodeNeedsChanged = $player->getGamemode() !== ($gamemodeTo);

        if (!$isExcluded && ($gamemodeTo !== false) && $gamemodeNeedsChanged) {
            $player->setGamemode($gamemodeTo);
        } else {
            return false;
        }
    }

    const BLOCKBAN_PLACE = 0;
    const BLOCKBAN_ACTIVATE = 1;
    const BLOCKBAN_CRAFT = 2;
    const BLOCKBAN_BREAK = 3;

    public function getBlockBanOpts()
    {
        $opts = "0000" . decbin($this->plugin->getConfig()->get(self::CONFIG_BLOCK));
        return array(
            self::BLOCKBAN_PLACE => substr($opts, -1, 1),
            self::BLOCKBAN_ACTIVATE => substr($opts, -2, 1),
            self::BLOCKBAN_CRAFT => substr($opts, -3, 1),
            self::BLOCKBAN_BREAK => substr($opts, -4, 1),
        );
    }

    public function isBlockBanned($player, $block, $type)
    {
        $player = $this->g_player($player);
        if ($player === false) {
            return false;
        }

        if ($this->isPlayerExcluded($player, self::FLAG_BLOCKS)) {
            return false;
        } elseif ($this->getBlockBanOpts()[$type] == "0") {
            return false;
        } else {
            $bannedBlocks = $this->getFlag($player->getLevel(), self::FLAG_BLOCKS);
            if (is_null($bannedBlocks)) {
                return false;
            } elseif (isset($bannedBlocks[$this->getBlockString($block)])) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function getBlock($string)
    {
        if ($string instanceof \pocketmine\block\Block) {
            return $string;
        }
        if (is_numeric($string)) {
            return \pocketmine\block\Block::get($string);
        }
        $str = explode(":", $string);
        if (is_numeric($str[0]) && is_numeric($str[1])) {
            return \pocketmine\block\Block::get($str[0], $str[1]);
        }
        return false;
    }

    public function getBlockString($block)
    {
        if ($block instanceof \pocketmine\block\Block) {
            if ($block->getDamage() == 0) {
                return $block->getID();
            } else {
                return $block->getID() . ":" . $block->getDamage();
            }
        } else {
            return 0;
        }
    }

    public function g_player($player, $exact = true)
    {
        if ($player instanceof Player) {
            return $player;
        } elseif (is_string($player)) {
            if ($exact) {
                return $this->plugin->getServer()->getPlayerExact($player);
            } else {
                return $this->plugin->getServer()->getPlayer($player);
            }
        } else {
            return false;
        }
    }

    public function g_world($world)
    {
        if ($world instanceof \pocketmine\level\Level) {
            return $world;
        } elseif (is_string($world)) {
            return $this->plugin->getServer()->getLevelByName($world);
        } elseif (is_numeric($world)) {
            return $this->plugin->getServer()->getLevel($world);
        } else {
            return false;
        }
    }

    /**
     * 
     * @return Config
     */
    public function getWorldConfig()
    {
        if ($this->worldConfig === false) {
            $this->worldConfig = new Config($this->plugin->getDataFolder() . "worldData.yml", Config::YAML, array(
                Utilities::CONFIG_WORLDS => array()
            ));
        }
        return $this->worldConfig;
    }

    public function doesWorldExist($worldName)
    {
        return file_exists($this->plugin->getServer()->getDataPath() . "worlds/" . $worldName);
    }

    public function parseBoolean($text)
    {
        switch (strtolower($text)) {
            case "true":
            case "1":
            case "t":
                return true;
            case "false":
            case "0":
            case "-1":
            case "f":
                return false;
            default:
                return "NA";
        }
    }

}
