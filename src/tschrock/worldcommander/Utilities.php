<?php

namespace tschrock\worldcommander;

use pocketmine\Server;
use pocketmine\command\CommandSender;

class Utilities
{

    const CONFIG_OPS = "opHasAllPermissions";
    const CONFIG_EXCLD_OP = "opIsExcluded";
    const CONFIG_EXCLD_WCALL = "worldcommander.allIsExcluded";
    const CONFIG_BLOCK = "blockBanType";
    const CONFIG_WORLDS = "worlds";
    const CONFIG_TIME = "timeUpdateInterval";
    
    
    /**
     * Tests whether or not a point lies between two other points.\
     * 
     * @param number $testpoint The point to test.
     * @param number $point1 The first limit.
     * @param number $point2 The seccond limit.
     * @return bool The result.
     */
    public static function isBetween($testpoint, $point1, $point2)
    {
        return (($point1 < $testpoint && $point2 > $testpoint) ||
                ($point1 > $testpoint && $point2 < $testpoint));
    }

    /**
     * Checks if a world exists.
     * 
     * @param string $worldName The name of the world.
     * @return bool
     */
    public static function doesWorldExist($worldName)
    {
        return file_exists(Server::getInstance()->getDataPath() . "worlds/" . $worldName);
    }

    /**
     * Parses a string into a boolean value, returns null otherwise.
     * 
     * @param string $text The string to parse.
     * @return null|boolean The boolean value of the string.
     */
    public static function parseBoolean($text)
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
                return null;
        }
    }
    
    public static function sendSplitMessage(CommandSender $sender, $message){
        $splitStr = explode("\n", $message);
        foreach ($splitStr as $line) {
            $sender->sendMessage($line);
        }
    }

}
