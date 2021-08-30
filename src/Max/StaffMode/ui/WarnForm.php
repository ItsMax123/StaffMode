<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};
use CortexPE\DiscordWebhookAPI\{Message, Webhook, Embed};

class WarnForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function WarningForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data, $playernamelist) {
            if($data === null) {
                return true;
            }

            if (count($playernamelist) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
            }

            $target = Server::getInstance()->getPlayer($playernamelist[$data["name"]]);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
            }

            if($data["reason"] == "") {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
				return true;
            }

            self::SendWarningForm($target, $data["reason"]);
            if($this->plugin->history->exists(strtolower($target->getName()))){
                $history = $this->plugin->history->get(strtolower($target->getName()));
                array_unshift($history, ["type" => "warned", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
                $this->plugin->history->set(strtolower($target->getName()), $history);
            } else {
                $this->plugin->history->set(strtolower($target->getName()), ([["type" => "warned", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
            }
            $this->plugin->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully warned player ".$target->getName());

            if ($this->plugin->config->get("DiscordWebhooks-Warnings")) {
                $webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Warnings-Link"));
                $msg = new Message();
                $msg->setUsername("StaffMode-Warnings");
                $msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
                $embed = new Embed();
                $embed->setTitle($target->getName()." was warned");
                $embed->setColor(0x00FF00);
                $embed->addField("Warned by", $player->getName());
                $embed->addField("Reason", $data["reason"]);
                $msg->addEmbed($embed);
                $webHook->send($msg);
            }
			return true;
        });
		$playernamelist = $this->plugin->getonlineplayersname();
        $form->setTitle("Warning Form");
        $form->addDropdown("Pick the player you want to warn", $playernamelist, null, "name");
        $form->addInput("Reason of warn:", "Ex.: Inappropriate Build", "", "reason");

        $player->sendForm($form);
    }

    public function SendWarningForm(Player $target, string $reason) : void {
        $form = new SimpleForm(function (Player $target, $data) use ($reason) {
            $target->sendMessage("§7[§bStaffMode§7] §6YOU HAVE BEEN WARNED!\n§rReason for warn:  ".$reason);
        });
        $form->setTitle("§6YOU HAVE BEEN WARNED!");
        $form->setContent("Reason for warn:  ".$reason);
        $target->sendForm($form);
    }
}