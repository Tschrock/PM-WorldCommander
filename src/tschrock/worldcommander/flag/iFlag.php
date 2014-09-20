<?php

namespace tschrock\worldcommander\flag;

use \pocketmine\command\CommandSender;

/**
 *
 * @author tyler
 */
interface iFlag {

    /**
     * @return string
     **/
    public function getName();

    /**
     * @return string
     **/
    public function getDescription();

    /**
     * @return string
     **/
    public function getAliases();

    /**
     * @return string
     **/
    public function getUsage();

    public function getDefaultValue();
    
    /**
     * @return \pocketmine\plugin\Plugin
     **/
    public function getOwnerPlugin();

    public function onEnable();

    public function onDisable();

    public function handleCommand(CommandSender $sender, $area, $args);
}
