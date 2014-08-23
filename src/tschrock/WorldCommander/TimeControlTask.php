<?php

namespace tschrock\WorldCommander;

use pocketmine\scheduler\PluginTask;

class TimeControlTask extends PluginTask
{

    /** @var WorldCommander */
    protected $owner;

    public function onrun()
    {
        $worlds = $this->owner->getServer()->getLevels();
        foreach ($worlds as $world) {
            $timeData = $this->owner->utilities->getFlag($world->getName(), Utilities::FLAG_TIME);
            $timeToSet = Utilities::calculateTime($timeData);
            if ($timeToSet["stopped"]) {
                $world->stopTime();
            } else {
                $world->startTime();
            }
            $world->setTime($timeToSet["time"]);
        }
    }

    static public function calculateTime($timeData)
    {
        if (is_numeric($timeData)) {
            $time = intval($timeData);
        } elseif (is_string($timeData)) {
            $operators = array("+", "-", "*", "/");
            if (0 < count(array_intersect(array_map('strtolower', explode('', $timeData)), $operators))) {
                
            } else {
                $time = $this->parseTime($time);
            }
        } else {
            
        }
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

    static public function parseTime($time)
    {
        $time = str_replace(array_flip(self::$timeIdentifiers), self::$timeIdentifiers, $time);
        $time = str_replace("now", self::realtimeToMctime(time()), $time);
        
    }

    static public function mctimeToRealtime()
    {
        
    }

    static public function realtimeToMctime()
    {
        
    }
}
