<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};
use CortexPE\DiscordWebhookAPI\{Message, Webhook, Embed};

class MuteForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function MutingForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
            if($data == "mute") {
                self::MutingFormPartTwo($player);
            } elseif($data == "unmute") {
                self::UnMutingForm($player);
            } elseif( $data == "list") {
                self::ListMutingForm($player);
            }
			return true;
        });
        $form->setTitle("Muting Menu");
        $form->addButton("Mute", -1, "", "mute");
        $form->addButton("UnMute", -1, "", "unmute");
        $form->addButton("See full mute list", -1, "", "list");
        $player->sendForm($form);
    }

    public function UnMutingForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
            if (count($this->plugin->getmutedplayersname()) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
            }

            $target = $this->plugin->getmutedplayersname()[$data["unmuteplayer"]];
            if($this->plugin->history->exists(strtolower($target))){
                $history = $this->plugin->history->get(strtolower($target));
                array_unshift($history, ["type" => "unmuted", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
                $this->plugin->history->set(strtolower($target), $history);
            } else {
                $this->plugin->history->set(strtolower($target), ([["type" => "unmuted", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
            }
            $this->plugin->history->save();
            $this->plugin->muteList->remove(strtolower($target));
            $this->plugin->muteList->save();
            if (Server::getInstance()->getPlayer($target)) {
                Server::getInstance()->getPlayer($target)->sendMessage("§aYou have been unmuted!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]);
            }
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully unmuted player ".$target);
			return true;
        });
        $form->setTitle("UnMuting Menu");
        $form->addDropdown("Pick the player you want to unmute", $this->plugin->getmutedplayersname(), null, "unmuteplayer");
        $form->addInput("Reason:", "Ex.: False Mute", "", "reason");
        $player->sendForm($form);
    }

    public function ListMutingForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
			return true;
        });
        $form->setTitle("Mute List");
        foreach ($this->plugin->muteList->getAll() as $mutedplayersnamekey => $mutedplayersinfo) {
            if ((int)$mutedplayersinfo["unmutetime"] - (int)$mutedplayersinfo["time"] == -1) {
                $form->setContent("§4".$mutedplayersnamekey." is currently muted.\n§rBy: ".(string)$mutedplayersinfo["staff"]."\nReason: ".(string)$mutedplayersinfo["reason"]."\nDuration: Forever\n".$form->getContent());
            } else {
                $time = (int)($mutedplayersinfo["unmutetime"] - (int)$mutedplayersinfo["time"]);
                $days = (int)($time / 86400);
                $hours = (int)(($time - ($days * 86400)) / 3600);
                $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                $date = date("M j Y", (int)($mutedplayersinfo["time"]));
                $form->setContent("§4".$mutedplayersnamekey." is currently muted.\n§rBy: ".(string)$mutedplayersinfo["staff"]."\nReason: ".(string)$mutedplayersinfo["reason"]."\nDate: ".$date."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s\n".$form->getContent());
            }
        }
        $player->sendForm($form);
    }

    public function MutingFormPartTwo(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if($data["days"] == "0" and $data["hours"] == "0" and $data["minutes"] == "0" and $data["seconds"] == "0" and $data["forever"] == false) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify an amount of time!");
				return true;
            }

            if($data["reason"] == "") {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
				return true;
            }

            if ($data["offlinename"] == "") {
                if (count($this->plugin->getonlineplayersname()) == 0) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
					return true;
                }
                if(Server::getInstance()->getPlayer($this->plugin->getonlineplayersname()[$data["name"]]) === null) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
					return true;
                } else {
                    $target = $this->plugin->getonlineplayersname()[$data["name"]];
                }
            } else {
                $target = $data["offlinename"];
            }

            if ($data["forever"] == false) {
                $time = (((int)$data["days"] * 86400) + ((int)$data["hours"] * 3600) + ((int)$data["minutes"] * 60) + ((int)$data["seconds"]));
                $days = (int)$data["days"];
                $hours = (int)$data["hours"];
                $minutes = (int)$data["minutes"];
                $seconds = (int)$data["seconds"];
                if (Server::getInstance()->getPlayer($target)) {
                    Server::getInstance()->getPlayer($target)->sendMessage("§cYou have been muted!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                }
            } else {
                $time = -1;
                if (Server::getInstance()->getPlayer($target)) {
                    Server::getInstance()->getPlayer($target)->sendMessage("§cYou have been muted!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: Forever");
                }
            }
            $this->plugin->muteList->set(strtolower($target), (["unmutetime" => (time() + $time), "time" => time(), "staff" => $player->getName(), "reason" => $data["reason"]]));
            $this->plugin->muteList->save();
            if($this->plugin->history->exists(strtolower($target))){
                $history = $this->plugin->history->get(strtolower($target));
                array_unshift($history, ["type" => "muted", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time(), "unbantime" => (time() + $time)]);
                $this->plugin->history->set(strtolower($target), $history);
            } else {
                $this->plugin->history->set(strtolower($target), ([["type" => "muted", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time(), "unbantime" => (time() + $time)]]));
            }
            $this->plugin->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully muted player ".$target);

            if ($this->plugin->config->get("DiscordWebhooks-Mutes") == true) {
                $webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Mutes-Link"));
                $msg = new Message();
                $msg->setUsername("StaffMode-Mutes");
                $msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
                $embed = new Embed();
                $embed->setTitle($target." was muted");
                $embed->setColor(0x00FF00);
                $embed->addField("Muted by", $player->getName());
                $embed->addField("Reason", $data["reason"]);
                if ($data["forever"] == false) {
                    $embed->addField("Duration", "".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                } else {
                    $embed->addField("Duration", "Forever");
                }
                $msg->addEmbed($embed);
                $webHook->send($msg);
            }
			return true;
        });
        $form->setTitle("Muting Menu");
        $form->addDropdown("Pick the player you want to mute", $this->plugin->getonlineplayersname(), null, "name");
        $form->addInput("Or type the §lEXACT§r name of the player you want to mute", "Ex.: ".$player->getName(), "", "offlinename");
        $form->addInput("Reason of mute:", "Ex.: Spamming", "", "reason");
        $form->addLabel("Duration of mute:");
        $form->addToggle("Forever?", false, "forever");
        $form->addInput("Days:", "", "0", "days");
        $form->addInput("Hours:", "", "0", "hours");
        $form->addInput("Minutes:", "", "0", "minutes");
        $form->addInput("Seconds:", "", "0", "seconds");
        $player->sendForm($form);
    }
}