<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace tschrock\worldcommander\data;

use \pocketmine\level\Position;
use tschrock\worldcommander\Utilities;

/**
 * Description of Region
 *
 * @author tyler
 */
class Region extends Area{

    /** @var Position[] */
    protected $regionBounds;

    /** @var int */
    protected $regionPriority;

    public function getBounds() {
        return $this->regionBounds;
    }

    public function setBounds($newBounds) {
        $this->dirty = true;
        $this->regionBounds = $newBounds;
    }

    /**
     * @param Position $position
     * @return bool
     */
    public function isInside($position) {
        return (Utilities::isBetween($position->x, $this->getBounds()[0]->x, $this->getBounds()[1]->x) &&
                Utilities::isBetween($position->y, $this->getBounds()[0]->y, $this->getBounds()[1]->y) &&
                Utilities::isBetween($position->z, $this->getBounds()[0]->z, $this->getBounds()[1]->z));
    }

    public function getPriority() {
        return $this->regionPriority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority) {
        $this->dirty = true;
        $this->regionPriority = $priority;
    }

    public function toArray() {
        return array(
            "R_NAME" => $this->name,
            "R_BOUNDS" => array(
                array(
                    "x" => $this->regionBounds[0]->x,
                    "y" => $this->regionBounds[0]->y,
                    "z" => $this->regionBounds[0]->z,
                ),
                array(
                    "x" => $this->regionBounds[1]->x,
                    "y" => $this->regionBounds[1]->y,
                    "z" => $this->regionBounds[1]->z,
                )
            ),
            "R_PRIORITY" => $this->regionPriority,
            "R_FLAGS" => $this->flags
        );
    }

    public function fromArray($rData) {
        $this->dirty = false;
        $this->name = $rData["R_NAME"];
        $this->regionBounds = array(
            new Position($rData["R_BOUNDS"][0]["x"], $rData["R_BOUNDS"][0]["y"], $rData["R_BOUNDS"][0]["z"]),
            new Position($rData["R_BOUNDS"][1]["x"], $rData["R_BOUNDS"][1]["y"], $rData["R_BOUNDS"][1]["z"])
        );
        $this->regionPriority = $rData["R_PRIORITY"];
        $this->flags = $rData["R_FLAGS"];
    }

    function __construct($regionData = false) {
        if ($regionData !== false) {
            $this->fromArray($regionData);
        } else {
            $this->dirty = false;
            $this->name = "";
            $this->regionBounds = array(
                new Position(0, 0, 0),
                new Position(0, 0, 0)
            );
            $this->regionPriority = 0;
            $this->flags = array();
        }
    }

}
