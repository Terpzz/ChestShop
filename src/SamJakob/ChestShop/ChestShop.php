<?php

/*
 * Copyright 2017 (c) Sam J.H. Mearns - All Rights Reserved
 */

namespace SamJakob\ChestShop;

use pocketmine\plugin\PluginBase;

class ChestShop extends PluginBase {
    
    const MESSAGE_TIMEOUT = 60;
    
    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents(new SignListener($this), $this);
        $this->getLogger()->info("Plugin was successfully enabled.");
    }
    
    public function onDisable(): void {
        $this->getLogger()->info("Plugin was successfully disabled.");
    }
    
}
