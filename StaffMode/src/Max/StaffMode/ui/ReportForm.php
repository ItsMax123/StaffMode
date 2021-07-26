<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\{Player, Server};
use CortexPE\DiscordWebhookAPI\{Message, Webhook, Embed};

class ReportForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function ReportForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if (count($this->plugin->getonlineplayersname()) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return true;
            }

            if ($data["offlinename"] == "") {
                if(Server::getInstance()->getPlayer($this->plugin->getonlineplayersname()[$data["name"]]) === null) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
					return true;
                } else {
                    $target = $this->plugin->getonlineplayersname()[$data["name"]];
                }
            } else {
                $target = $data["offlinename"];
            }

            if($data["reason"] == "") {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
				return true;
            }

            if($this->plugin->history->exists(strtolower($target))){
                $history = $this->plugin->history->get(strtolower($target));
                array_unshift($history, ["type" => "reported", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
                $this->plugin->history->set(strtolower($target), $history);
            } else {
                $this->plugin->history->set(strtolower($target), ([["type" => "reported", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
            }
            $this->plugin->history->save();

            if($this->plugin->reportList->exists("reports")){
                $reports = $this->plugin->reportList->get("reports");
                array_unshift($reports, ["target" => $target, "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
                $this->plugin->reportList->set("reports", $reports);
            } else {
                $this->plugin->reportList->set("reports", ([["target" => $target, "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
            }
            $this->plugin->reportList->save();

            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully reported player ".$target);
            if ($this->plugin->config->get("DiscordWebhooks-Reports")) {
                $webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Reports-Link"));
                $msg = new Message();
                $msg->setUsername("StaffMode-Reports");
                $msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
                $embed = new Embed();
                $embed->setTitle($target." was reported");
                $embed->setColor(0x00FF00);
                $embed->addField("Reported by", $player->getName());
                $embed->addField("Reason", $data["reason"]);
                $msg->addEmbed($embed);
                $webHook->send($msg);
            }
			return true;
        });
        $form->setTitle("Report Form");
        $form->addDropdown("Pick the player you want to report", $this->plugin->getonlineplayersname(), null, "name");
        $form->addInput("Or type the §lEXACT§r name of the player you want to report", "Ex.: ".$player->getName(), "", "offlinename");
        $form->addInput("Reason:", "Ex.: Hacking", "", "reason");

        $player->sendForm($form);
    }
}