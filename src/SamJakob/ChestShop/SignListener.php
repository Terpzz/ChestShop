<?php

/*
 * Copyright 2017 (c) Sam J.H. Mearns - All Rights Reserved
 */

namespace SamJakob\ChestShop;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\event\Listener;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\metadata\BlockMetadataStore;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use onebone\economyapi\EconomyAPI;

class SignListener implements Listener {

    private $plugin;
    private $alertedPlayers;

    public function __construct(ChestShop $plugin) {
        $this->plugin = $plugin;
        $this->alertedPlayers = array();
    }

    function onSignChange(SignChangeEvent $event) {
        if (!in_array($event->getPlayer()->getName(), $this->alertedPlayers)) {
            $event->getPlayer()->sendMessage("§9[ChestShop] Smack the sign to confirm changes.");
            array_push($this->alertedPlayers, $event->getPlayer()->getName());
        }
    }

    //$chest = $this->findChest($event->getBlock());
    function onSignSmack(PlayerInteractEvent $event) {
        if ($event->getAction() != PlayerInteractEvent::RIGHT_CLICK_BLOCK || $event->getBlock()->getTypeId() != VanillaBlocks::WALL_SIGN) {
            return;
        }

        $sign = $event->getPlayer()->getWorld()->getTile($event->getBlock());
        $signBlock = $sign;
        $metadata = $blockMetadataStore;
        $blockMetadataStore = $metadata->getMatadata(); 
        $sign = $signBlock->getText();

        if (strtoupper($sign[0]) === "[CHESTSHOP]") {
            if (!$this->findChest($event->getBlock())) {
                // Check if chestshop is not on a chest
                $signBlock->setText("§c[ChestShop]");
                $event->getPlayer()->sendMessage("§c[ChestShop] You must place the sign on a chest.");
                return;
            }

            if ($sign[1] === "buy") {
                if (is_numeric($sign[2]) && is_numeric(str_replace("$", "", $sign[3]))) {
                    $signBlock->setText("§1[ChestShop]", $sign[1], $sign[2], "$" . str_replace("$", "", $sign[3]));
                    $metadata->setMetaData("owner", new MetadataValue($plugin));

                    $event->getPlayer()->sendMessage("§a[ChestShop] Shop successfully created. It will sell items in the chest from first slot to last.");
                } else {
                    $signBlock->setText("§c[ChestShop]");
                    $event->getPlayer()->sendMessage("§c[ChestShop] Lines 3 & 4 must be numbers.");
                    return;
                }
            } else {
                $signBlock->setText("§c[ChestShop]");
                $event->getPlayer()->sendMessage("§c[ChestShop] Your shop type must be [buy].");
                return;
            }
        } else if (strtoupper($sign[0]) === "§1[CHESTSHOP]") {
            $amount = $sign[2];
            $price = str_replace("$", "", $sign[3]);

            $chest = $event->getPlayer()->getLevel()->getTile($this->findChest($event->getBlock()));
            $inventory = $chest->getInventory();
            $item = $inventory->getItem(0);

            if ($item->getCount() >= $amount) {
                if (EconomyAPI::getInstance()->myMoney($event->getPlayer()) >= $price) {
                    EconomyAPI::getInstance()->reduceMoney($event->getPlayer(), $price);
                    if($item->getCount() == $amount){
                        $inventory->clear(0);
                    }else{
                        $item->setCount($item->getCount() - $amount);
                    }
                    
                    $event->getPlayer()->getInventory()->addItem(new Item($item->getTypeId(), $item->getDamage(), $amount));
                    $event->getPlayer()->sendMessage("§a[ChestShop] You have successfully bought " . $amount . "x " . $item->getName());
                }else{
                    $event->getPlayer()->sendMessage("§c[ChestShop] Sorry, you can't afford this item.");
                }
            } else {
                $event->getPlayer()->sendMessage("§c[ChestShop] Sorry, this shop is out of stock.");
            }
        }
    }

    public function findChest(Block $sign) {
        // Chest is north of sign
        if ($sign->getSide(Vector3::SIDE_NORTH)->getTypeId() === VanillaBlocks::CHEST) {
            return Chest::fromObject($sign->asVector3()->getSide(Vector3::SIDE_NORTH), $sign->getWorld());

            // Chest is south of sign
        } else if ($sign->getSide(Vector3::SIDE_SOUTH)->getTypeId() === VanillaBlocks::CHEST) {
            return Chest::fromObject($sign->asVector3()->getSide(Vector3::SIDE_SOUTH), $sign->getWorld());

            // Chest is east of sign
        } else if ($sign->getSide(Vector3::SIDE_EAST)->getTypeId() === VanillaBlocks::CHEST) {
            return Chest::fromObject($sign->asVector3()->getSide(Vector3::SIDE_EAST), $sign->getWorld());

            // Chest is west of sign
        } else if ($sign->getSide(Vector3::SIDE_WEST)->getTypeId() === VanillaBlocks::CHEST) {
            return Chest::fromObject($sign->asVector3()->getSide(Vector3::SIDE_WEST), $sign->getWorld());
        } else {
            return false;
        }
    }

}
