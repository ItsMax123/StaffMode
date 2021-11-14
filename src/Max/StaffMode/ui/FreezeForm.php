<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\{Player, Server};

class FreezeForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function FreezingForm(Player $player) : void {
		$playernamelist = $this->plugin->getonlineplayersname();
        $form = new CustomForm(function (Player $player, $data) use ($playernamelist) {
            if($data === null) {
                return true;
            }

            if (count($playernamelist) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
            }

            $target = Server::getInstance()->getPlayer($playernamelist[$data["player"]]);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
            }
            if ($data["unfreeze"] == false) {
                $target->setImmobile(true);
                $target->sendTitle("§bFrozen", "§cDo not log off and listen to staff!");
                $this->plugin->frozenstatus[$target->getName()] = True;
                $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully froze player ".$target->getName());
            } else {
                $target->setImmobile(false);
                $target->sendTitle("§eUnfrozen", "§aYou can now move again.");
                $this->plugin->frozenstatus[$target->getName()] = False;
                $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully unfroze player ".$target->getName());
            }
			return true;
        });
        $form->setTitle("Freezing Menu");
        $form->addLabel("Toggle if you want to §lUnFreeze§r a player");
        $form->addToggle("Unfreeze?", false, "unfreeze");
        $form->addDropdown("Pick the player you want to freeze/unfreeze", $playernamelist, null, "player");

        $player->sendForm($form);
    }
}