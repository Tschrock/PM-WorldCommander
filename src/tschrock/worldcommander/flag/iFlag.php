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
     * */
    public function getName();

    /**
     * @return string
     * */
    public function getDescription();

    /**
     * @return string
     * */
    public function getAliases();

    /**
     * @return string
     * */
    public function getUsage();

    public function getDefaultValue();

    /**
     * @return \pocketmine\plugin\Plugin
     * */
    public function getOwnerPlugin();

    /**
     * Called when the flag is enabled
     */
    public function onEnable();

    public function isEnabled();

    /**
     * Called when the flag is disabled
     * Use this to free open things and finish actions
     */
    public function onDisable();

    public function isDisabled();

    public function handleCommand(CommandSender $sender, $area, $args);
}
