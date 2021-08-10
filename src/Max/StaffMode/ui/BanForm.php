<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};
use CortexPE\DiscordWebhookAPI\{Message, Webhook, Embed};

class BanForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function BanningForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
            if($data == "ban") {
                self::BanningFormPartTwo($player);
            } elseif($data == "unban") {
                self::UnBanningForm($player);
            } elseif( $data == "list") {
                self::ListBanningForm($player);
            }
			return true;
        });
        $form->setTitle("Banning Menu");
        $form->addButton("Ban", -1, "", "ban");
        $form->addButton("UnBan", -1, "", "unban");
        $form->addButton("See full ban list", -1, "", "list");
        $player->sendForm($form);
    }

    public function UnBanningForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if (count($this->plugin->getbannedplayersname()) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
            }

            $target = $this->plugin->getbannedplayersname()[$data["unbanplayer"]];
            if($this->plugin->history->exists(strtolower($target))){ 
                $history = $this->plugin->history->get(strtolower($target));
                array_unshift($history, ["type" => "unbanned", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
                $this->plugin->history->set(strtolower($target), $history);
            } else {
                $this->plugin->history->set(strtolower($target), ([["type" => "unbanned", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
            }
            $this->plugin->history->save();
            $this->plugin->banList->remove(strtolower($target));
            $this->plugin->banList->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully unbanned player ".$target);
			return true;
        });
        $form->setTitle("UnBanning Form");
        $form->addDropdown("Pick the player you want to unban", $this->plugin->getbannedplayersname(), null, "unbanplayer");
        $form->addInput("Reason:", "Ex.: False Ban", "", "reason");
        $player->sendForm($form);
    }

    public function ListBanningForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
			return true;
        });
        $form->setTitle("Ban List");
        foreach ($this->plugin->banList->getAll() as $bannedplayersnamekey => $bannedplayersinfo) {
            if ((int)$bannedplayersinfo["unbantime"] - (int)$bannedplayersinfo["time"] == -1) {
                $form->setContent("§4".$bannedplayersnamekey." is currently banned.\n§rBy: ".(string)$bannedplayersinfo["staff"]."\nReason: ".(string)$bannedplayersinfo["reason"]."\nDuration: Forever\n".$form->getContent());
            } else {
                $time = (int)($bannedplayersinfo["unbantime"] - $bannedplayersinfo["time"]);
                $days = (int)($time / 86400);
                $hours = (int)(($time - ($days * 86400)) / 3600);
                $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                $date = (date("M j Y", (int)$bannedplayersinfo["time"]));
                $form->setContent("§4".$bannedplayersnamekey." is currently banned.\n§rBy: ".(string)$bannedplayersinfo["staff"]."\nReason: ".(string)$bannedplayersinfo["reason"]."\nDate: ".$date."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s\n".$form->getContent());
            }
        }
        $player->sendForm($form);
    }

    public function BanningFormPartTwo(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if($data["days"] == "0" and $data["hours"] == "0" and $data["minutes"] == "0" and $data["seconds"] == "0" and !$data["forever"]) {
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
                } elseif(Server::getInstance()->getPlayer($this->plugin->getonlineplayersname()[$data["name"]]) === null) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
					return true;
                } elseif ($this->plugin->getonlineplayersname()[$data["name"]] == $player->getName()) {
					$player->sendMessage("§7[§bStaffMode§7] §cCannot ban yourself!");
					return true;
				} else {
                    $target = $this->plugin->getonlineplayersname()[$data["name"]];
                }
            } else {
                $target = $data["offlinename"];
            }

            if (!$data["forever"]) {
                $time = (((int)$data["days"] * 86400) + ((int)$data["hours"] * 3600) + ((int)$data["minutes"] * 60) + ((int)$data["seconds"]));
                $days = (int)$data["days"];
                $hours = (int)$data["hours"];
                $minutes = (int)$data["minutes"];
                $seconds = (int)$data["seconds"];
                if (Server::getInstance()->getPlayer($target)) {
                    Server::getInstance()->getPlayer($target)->kick("§cYou have been banned!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s", false, $target." was Banned.");
                }
				if ($data["broadcast"]) {
					Server::getInstance()->broadcastMessage("§7[§bStaffMode§7] §c".$target."§4 was banned by §c".$player->getName()."§4. Reason: §c".$data["reason"]."§4. Duration: §c".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
				}
            } else {
                $time = -1;
                if (Server::getInstance()->getPlayer($target)) {
                    Server::getInstance()->getPlayer($target)->kick("§cYou have been banned!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: Forever", false, $target." was Banned.");
                }
				if ($data["broadcast"]) {
					Server::getInstance()->broadcastMessage("§7[§bStaffMode§7] §c".$target."§4 was banned by §c".$player->getName()."§4. Reason: §c".$data["reason"]."§4. Duration: §cForever");
				}
            }

            if($this->plugin->alias->exists(strtolower($target))){
                $Address = (string)$this->plugin->alias->get(strtolower($target))["IPAddress"];
                $DeviceId = (string)$this->plugin->alias->get(strtolower($target))["DeviceId"];
                $SelfSignedId = (string)$this->plugin->alias->get(strtolower($target))["SelfSignedId"];
                $ClientRandomId = (string)$this->plugin->alias->get(strtolower($target))["ClientRandomId"];
                $this->plugin->banList->set(strtolower($target), ["unbantime" => (time() + $time), "time" => time(), "staff" => $player->getName(), "reason" => $data["reason"], "adress" => $Address, "deviceid" => $DeviceId, "selfsignedid" => $SelfSignedId]);
            } else {
                $this->plugin->banList->set(strtolower($target), ["unbantime" => (time() + $time), "time" => time(), "staff" => $player->getName(), "reason" => $data["reason"]]);
            }
            $this->plugin->banList->save();
            if($this->plugin->history->exists(strtolower($target))){
                $history = $this->plugin->history->get(strtolower($target));
                array_unshift($history, ["type" => "banned", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time(), "unbantime" => (time() + $time)]);
                $this->plugin->history->set(strtolower($target), $history);
            } else {
                $this->plugin->history->set(strtolower($target), ([["type" => "banned", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time(), "unbantime" => (time() + $time)]]));
            }
            $this->plugin->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully banned player ".$target);

            if ($this->plugin->config->get("DiscordWebhooks-Bans")) {
                $webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Bans-Link"));
                $msg = new Message();
                $msg->setUsername("StaffMode-Bans");
                $msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
                $embed = new Embed();
                $embed->setTitle($target." was banned");
                $embed->setColor(0x00FF00);
                $embed->addField("Banned by", $player->getName());
                $embed->addField("Reason", $data["reason"]);
                if (!$data["forever"]) {
                    $embed->addField("Duration", "".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                } else {
                    $embed->addField("Duration", "Forever");
                }
                $msg->addEmbed($embed);
                $webHook->send($msg);
            }
			return true;
        });
        $form->setTitle("Banning Form");
        $form->addDropdown("Pick the player you want to ban", $this->plugin->getonlineplayersname(), null, "name");
        $form->addInput("Or type the §lEXACT§r name of the player you want to ban", "Ex.: ".$player->getName(), "", "offlinename");
        $form->addInput("Reason of ban:", "Ex.: Hacking", "", "reason");
        $form->addLabel("Duration of ban:");
        $form->addToggle("Forever?", false, "forever");
        $form->addInput("Days:", "", "0", "days");
        $form->addInput("Hours:", "", "0", "hours");
        $form->addInput("Minutes:", "", "0", "minutes");
        $form->addInput("Seconds:", "", "0", "seconds");
		$form->addToggle("Broadcast?", false, "broadcast");
        $player->sendForm($form);
    }

}