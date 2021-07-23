<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use Max\StaffMode\EventListener;
use pocketmine\{Player, Server};

class TeleportForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function TeleportForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
            $target = Server::getInstance()->getPlayer($data);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }
            $from = $player->getLevel();
            $to = $target->getLevel();
            if(!$this->plugin->config->get("Allow-World-Change")){
                if($from !== $to){
                    $player->sendMessage("§7[§bStaffMode§7] §cCannot teleport to player in world: ".$to->getName());
                } else {
                    $player->teleport($target);
                    $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully teleported to player ".$target->getName());
                }
            } else {
                $player->teleport($target);
                $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully teleported to player ".$target->getName());
            }
        });
        $form->setTitle("Teleportation Menu");
        $from = $player->getLevel();
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if(!$this->plugin->config->get("Allow-World-Change")){
                if ($player->getLevel() !== $onlinePlayer->getLevel()) {
                    $form->addButton("§4".$onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
                } else {
                    $form->addButton("§2".$onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
                }
            } else {
                $form->addButton("§2".$onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
            }
        }
        $player->sendForm($form);
    }
}