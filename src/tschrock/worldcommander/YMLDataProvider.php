<?php

namespace tschrock\worldcommander;

use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;

/**
 * A class for all Data-related functions.
 *
 * @author tyler
 * @
 */
class YMLDataProvider {

    protected $worldConfig = false;
    protected $dataFile;

    public function __construct($dataFile) {
        $this->dataFile = $dataFile;
    }

    /**
     * Gets the main data file. 
     * 
     * @return Config
     */
    public function getWCConfig() {
        if ($this->worldConfig === false) {
            $this->worldConfig = new Config($this->dataFile, Config::YAML, array(
                "_WORLDS" => array(),
                "_REGIONS" => array(),
            ));
        }
        return $this->worldConfig;
    }

    /**
     * Gets all of the world data.
     * 
     * @return array An array containing the data for each world.
     */
    public function getAllWorldData() {
        return $this->getWCConfig()->get("_WORLDS");
    }

    /**
     * Gets world data for a specific world.
     * 
     * @param string $world The world to get the data from.
     * @return array An array containing the world's data.
     */
    public function getWorldData($world) {
        $worldData = $this->getAllWorldData();
        return self::safeArrayGet($worldData, $world);
    }

    /**
     * Gets all of the world flags.
     * 
     * @return array An array containing the flags for each world.
     */
    public function getAllWorldFlags() {
        return $this->getAllWorldData();
    }

    /**
     * Gets world flags for a specific world.
     * 
     * @param string $world The world to get the flags from.
     * @return array An array containing the world's flags.
     */
    public function getWorldFlags($world) {
        return $this->getWorldData($world);
    }

    /**
     * Gets the data for a specific world flag.
     * 
     * @param string|Position $world The world to get the flag data from.
     * @param string $flag The flag to get the data from.
     * @return mixed The data from the flag.
     */
    public function getWorldFlag($world, $flag) {
        if ($world instanceof Position) {
            $world = $world->getLevel()->getName();
        }
        $worldFlags = $this->getWorldFlags($world);
        return self::safeArrayGet($worldFlags, $flag);
    }

    /**
     * Sets the data for a specific world flag.
     * 
     * @param string $world The world to set the flag in.
     * @param string $flag The flag to store the data in.
     * @param string $value The data to store in the flag.
     * @return void
     */
    public function setWorldFlag($world, $flag, $value) {
        $allWorldData = $this->getAllWorldData();
        $allWorldData[$world][$flag] = $value;
        $this->getWCConfig()->set("_WORLDS", $allWorldData);
        $this->getWCConfig()->save();
    }

    /**
     * Get the region(s) enclosing a specific location.
     * 
     * Returns a list of all of the regions at a location in order of region
     * priority. The order of regions with the same priority is undefined.
     * 
     * @param string|Position $world The world.
     * @param Vector3 $location The location.
     * @return array An array of the name(s) of all of the region(s) at the location.
     */
    public function getRegion($world, Vector3 $location = null) {
        if ($world instanceof Position) {
            $location = $world;
            $world = $world->getLevel()->getName();
        }

        if ($location != null) {

            $regions = $this->getAllRegionData();
            $rtn = array();

            foreach ($regions as $region => $flags) {
                if ($flags["R_WORLD"] == $world) {
                    if (Utilities::isBetween($location->x, $flags["R_POS1_X"], $flags["R_POS2_X"]) &&
                            Utilities::isBetween($location->y, $flags["R_POS1_Y"], $flags["R_POS2_Y"]) &&
                            Utilities::isBetween($location->z, $flags["R_POS1_Z"], $flags["R_POS2_Z"])) {
                        $rtn[$region] = (isset($flags["R_PRIORITY"])) ? $flags["R_PRIORITY"] : 0;
                    }
                }
            }

            asort($rtn, SORT_NUMERIC);

            return array_keys($rtn);
        } else {
            $regions = $this->getAllRegionData();
            $rtn = array();

            foreach ($regions as $region => $flags) {
                if (strpos($region, $world) !== false) {
                    $rtn[$region] = (isset($flags["R_PRIORITY"])) ? $flags["R_PRIORITY"] : 0;
                }
            }

            asort($rtn, SORT_NUMERIC);

            return array_keys($rtn);
        }
    }

    /**
     * Gets all of the region data.
     * 
     * @return array An array containing the data for each region.
     */
    public function getAllRegionData() {
        return $this->getWCConfig()->get("_REGIONS");
    }

    /**
     * Gets region data for a specific region.
     * 
     * @param string $region The region to get the data for.
     * @return array An array containing the region's data.
     */
    public function getRegionData($region) {
        $regData = $this->getAllRegionData();
        return self::safeArrayGet($regData, $region);
    }

    /**
     * Gets all of the region flags.
     * 
     * @return array An array containing the flags for each region.
     */
    public function getAllRegionFlags() {
        return $this->getAllRegionData();
    }

    /**
     * Gets region flags for a specific region.
     * 
     * @param string $region The region to get the flags for.
     * @return array An array containing the region's flags.
     */
    public function getRegionFlags($region) {
        return $this->getRegionData($region);
    }

    /**
     * Gets the data for a specific region flag.
     * 
     * @param string $region The region to get the flag data from.
     * @param string $flag The flag to get the data from.
     * @return mixed The data from the flag.
     */
    public function getRegionFlag($region, $flag) {
        $regionFlags = $this->getRegionFlags($region);
        return self::safeArrayGet($regionFlags, $flag);
    }

    /**
     * Sets the data for a specific region flag.
     * 
     * @param string $region The region to set the flag in.
     * @param string $flag The flag to store the data in.
     * @param string $value The data to store in the flag.
     * @return void
     */
    public function setRegionFlag($region, $flag, $value) {

        $allRegionData = $this->getAllRegionData();
        $allRegionData[$region][$flag] = $value;
        $this->getWCConfig()->set("_REGIONS", $allRegionData);
        $this->getWCConfig()->save();
    }

    /**
     * Sets the flag for the named area.
     * @param string $area
     * @param string $flag
     * @param mixed $value
     * @return boolean
     */
    public function setFlag($area, $flag, $value) {
        if (Utilities::doesWorldExist($area)) {
            $this->setWorldFlag($area, $flag, $value);
        } elseif (isset($this->getAllRegionFlags()[$area])) {
            $this->setRegionFlag($area, $flag, $value);
        } else {
            return false;
        }
        return true;
    }

    /**
     * Creates a new region.
     * @param string $name
     * @param Position $pos1
     * @param Position $pos2
     * @param number $priority
     * @return boolean
     */
    public function createRegion($name, Position $pos1, Position $pos2, $priority = 0) {
        if ($pos1->getLevel()->getName() != $pos2->getLevel()->getName()) {
            return false;
        }

        $allRegionData = $this->getAllRegionData();
        $allRegionData[$name] = array(
            "R_WORLD" => $pos1->getLevel()->getName(),
            "R_POS1_X" => $pos1->x,
            "R_POS1_Y" => $pos1->y,
            "R_POS1_Z" => $pos1->z,
            "R_POS2_X" => $pos2->x,
            "R_POS2_Y" => $pos2->y,
            "R_POS2_Z" => $pos2->z,
            "R_PRIORITY" => $priority);
        $this->getWCConfig()->set("_REGIONS", $allRegionData);
        $this->getWCConfig()->save();

        return true;
    }

    /**
     * Removes a region
     * @param string $name
     */
    public function removeRegion($name) {
        $allRegionData = $this->getAllRegionData();
        unset($allRegionData[$name]);
        $this->getWCConfig()->set("_REGIONS", $allRegionData);
        $this->getWCConfig()->save();
    }

    public function getRegionInfo($region) {
        if (!$this->isRegion($region)) {
            return null;
        }

        $rFlags = $this->getRegionData($region);

        return "Region '" . $region . "': " . $rFlags['R_WORLD'] . " (" .
                $rFlags['R_POS1_X'] . ", " . $rFlags['R_POS1_Y'] . ", " . $rFlags['R_POS1_Z'] . ") to (" .
                $rFlags['R_POS2_X'] . ", " . $rFlags['R_POS2_Y'] . ", " . $rFlags['R_POS2_Z'] . ") P" . $rFlags['R_PRIORITY'] . "/n" .
                array_count_values($rFlags) - 8 . " flags set.";
    }

    /**
     * Wether or not the named region exists.
     * @param string $region
     * @return boolean
     */
    public function isRegion($region) {
        if (isset($this->getAllRegionData()[$region])) {
            return true;
        }
        return false;
    }

    /**
     * Wether or not the named area is a valid world/region.
     * @param string $area
     * @return boolean
     */
    public function isValidArea($area) {
        return Utilities::doesWorldExist($area) || $this->isRegion($area);
    }

    public static function safeArrayGet($array, $key) {
        return (isset($array[$key])) ? $array[$key] : null;
    }

}
