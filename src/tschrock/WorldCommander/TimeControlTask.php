<?php

namespace tschrock\WorldCommander;

use pocketmine\scheduler\PluginTask;
use Pentangle\EqOS\EOS;

class TimeControlTask extends PluginTask
{

    /** @var WorldCommander */
    protected $owner;

    /** @var EOS */
    protected $eos;

    public function onrun($currentTick)
    {
        $worlds = $this->owner->getServer()->getLevels();
        foreach ($worlds as $world) {
            $this->updateWorldTime($world, $currentTick);
        }
    }

    public function updateWorldTime(\pocketmine\level\Level $world, $currentTick)
    {
        $timeData = $this->owner->utilities->getFlag($world->getName(), Utilities::FLAG_TIME);
        $time = $this->calculateTime($timeData, $currentTick, $world->getTime());
        $world->setTime($time);
    }

    public function calculateTime($timeData, $currentTick, $currentTime)
    {
        $timeArr = preg_split('/([\+\-\*\/\(\)])/m', $timeData, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        ##var_dump($timeArr);
        foreach ($timeArr as $timeKey => $timeVal) {
            if (array_search($timeVal, array("+", "-", "*", "/", "(", ")", "sin", "cos", "tan", "abs")) === false) {
                $timeArr[$timeKey] = $this->parseIdentifier($timeVal, $currentTick, $currentTime);
            }
        }
        ##var_dump($timeArr);
        return $this->doMath(implode("", $timeArr));
    }

    static protected $timeIdentifiers = array(
        "sunrise" => 0,
        "morning" => 2000,
        "day" => 4000,
        "noon" => 6000,
        "afternoon" => 8000,
        "evening" => 10000,
        "sunset" => 12000,
        "night" => 15000,
        "midnight" => 18000,
    );

    public function parseIdentifier($timeVal, $currentTick, $currentTime)
    {
        if (is_numeric($timeVal)) {
            return $timeVal;
        } elseif (isset(self::$timeIdentifiers[$timeVal])) {
            return self::$timeIdentifiers[$timeVal];
        } elseif (array_search($timeVal, array("realtime", "auto", "now")) !== false) {
            switch ($timeVal) {
                case "realtime":
                    return self::realtimeToMctime(time());
                case "auto":
                    return $currentTick;
                case "now":
                    return $currentTime;
            }
        } elseif (array_search($timeVal, array("sin", "cos", "tan", "abs")) !== false) {
            return $timeVal;
        } else {
            return self::realtimeToMctime(strtotime($timeVal));
        }
    }

    ###############################
    ##                           ##
    ##     Utility functions     ##
    ##                           ##
    ###############################

    /**
     * Uses eqEOS to solve math equations.
     * 
     * @param string $equation The equation to solve.
     * @return int The integer result of the equation.
     */
    public function doMath($equation)
    {
        if (!isset($this->eos)) {
            $this->eos = new EOS();
        }
        return round($this->eos->solveIF($equation));
    }

    /**
     * Checks if a string contains a specific substring(s).
     * 
     * @param string $haystack The string to search through.
     * @param string|array $needle A string or an array of strings to look for.
     * @return bool Whether or not $haystack containes $needle.
     */
    public static function str_contains(string $haystack, mixed $needle)
    {
        if (is_string($needle)) {
            return (strpos($haystack, $needle) !== false);
        } elseif (is_array($needle)) {
            foreach ($needle as $smallNeedle) {
                if (strpos($haystack, $smallNeedle) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Converts Minecraft time into real time.
     * 
     * Note: 24 hours = 24000 & 6:00 = 0
     * 
     * @param int $mctime The minecraft time.
     * @return int The number of seconds after midnight in real time.
     */
    static public function mctimeToRealtime($mctime)
    {
        $baseTime = ($mctime + 6000) % 24000; # Adjust MCTime to match 24hr clock and get rid of extra days.
        $deciHours = $baseTime / 1000; # Convert MCTime -> hours
        return floor($deciHours * 60 * 60); # Convert hours -> secconds
    }

    /**
     * Converts real time into Minecraft time.
     * 
     * Note: 24 hours = 24000 & 6:00 = 0
     * 
     * @param int $realtime The number of seconds after midnight in real time.
     * @return int The Minecraft time.
     */
    static public function realtimeToMctime($realtime)
    {
        $baseTime = ($realtime / 60 / 60); # Convert seconds -> hours
        $mctime = $baseTime * 1000; # Convert hours -> MCTime
        return ($mctime - 6000) % 24000; # Adjust MCTime to match 24hr clock and get rid of extra days.
    }

}
