<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};
use CortexPE\DiscordWebhookAPI\{Message, Webhook, Embed};

class PlayerInfoForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function PlayerInfoForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

			if($data == "boloadd") {
				self::AddBoloForm($player);
			} elseif($data == "bololist") {
				self::BoloListForm($player);
			} elseif($data == "reports") {
                self::ReportsForm($player);
            } elseif($data == "history") {
                self::HistoryForm($player);
            }
            return true;
        });
        $form->setTitle("Player Info Menu");
		if ($player->hasPermission("staffmode.tools.playerinfo.bolo.add")) {
			$form->addButton("Add BOLO", -1, "", "boloadd");
		}
		if ($player->hasPermission("staffmode.tools.playerinfo.bolo")) {
			$form->addButton("BOLO List", -1, "", "bololist");
		}
		if ($player->hasPermission("staffmode.tools.playerinfo.reports")) {
			$form->addButton("Reports", -1, "", "reports");
		}
		if ($player->hasPermission("staffmode.tools.playerinfo.history")) {
			$form->addButton("History", -1, "", "history");
		}

        $player->sendForm($form);
    }

	public function AddBoloForm(Player $player) : void {
		$form = new CustomForm(function (Player $player, $data) {
			if($data === null) {
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

			if($this->plugin->boloList->exists("bolos")){
				$bolos = $this->plugin->boloList->get("bolos");
				array_unshift($bolos, ["target" => $target, "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
				$this->plugin->boloList->set("bolos", $bolos);
			} else {
				$this->plugin->boloList->set("bolos", ([["target" => $target, "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
			}
			$this->plugin->boloList->save();

			if($this->plugin->history->exists(strtolower($target))){
				$history = $this->plugin->history->get(strtolower($target));
				array_unshift($history, ["type" => "boloed", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]);
				$this->plugin->history->set(strtolower($target), $history);
			} else {
				$this->plugin->history->set(strtolower($target), ([["type" => "boloed", "reason" => $data["reason"], "staff" => $player->getName(), "time" => time()]]));
			}
			$this->plugin->history->save();
			$player->sendMessage("§7[§bStaffMode§7] §aSuccessfully BOLOED player ".$target);

			foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
				if ($onlinePlayer->hasPermission("staffmode.alerts")) {
					$onlinePlayer->sendMessage("§7[§bStaffMode§7] §c".$target." §4was just added to the BOLO list for: §6".$data["reason"]." §4by: §6".$player->getName());
				}
			}

			if ($this->plugin->config->get("DiscordWebhooks-Bolos")) {
				$webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Bolos-Link"));
				$msg = new Message();
				$msg->setUsername("StaffMode-Bolos");
				$msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
				$embed = new Embed();
				$embed->setTitle($target." was Boloed");
				$embed->setColor(0x00FF00);
				$embed->addField("Boloed by", $player->getName());
				$embed->addField("Reason", $data["reason"]);
				$msg->addEmbed($embed);
				$webHook->send($msg);
			}
			return true;
		});
		$form->setTitle("BOLO (Be On Look-Out for)");
		$form->addDropdown("Pick the player you want to BOLO", $this->plugin->getonlineplayersname(), null, "name");
		$form->addInput("Or type the §lEXACT§r name of the player you want to BOLO", "Ex.: ".$player->getName(), "", "offlinename");
		$form->addInput("Reason of BOLO:", "Ex.: Maybe hacking", "", "reason");
		$player->sendForm($form);
	}

	public function BoloListForm(Player $player) : void {
		$form = new SimpleForm(function (Player $player, $data) {
			return true;
		});
		$form->setTitle("BOLO (Be On Look-Out for)");

		if($this->plugin->boloList->exists("bolos")){
			if (count($this->plugin->boloList->get("bolos")) == 0){
				$form->setContent("There is currently no one on the BOLO list.");
			}
			foreach($this->plugin->boloList->get("bolos") as $key => $boloList) {
				$staff = (string)$boloList["staff"];
				$reason = (string)$boloList["reason"];
				$target = (string)$boloList["target"];
				$date = date("M j Y", (int)($boloList["time"]));
				$form->setContent($form->getContent()."\n\n§c".$target." is on the BOLO list.\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
			}
		}

		$player->sendForm($form);
	}

	public function ReportsForm(Player $player) : void {
		$form = new CustomForm(function (Player $player, $data) {
			if($data === null) {
				return true;
			}
			$reports = $this->plugin->reportList->get("reports");
			foreach ($data as $key => $toggle) {
				if (is_string($key)) continue;
				if ($toggle) {
					array_splice($reports, (int)$key, 1);
				}
			}
			$this->plugin->reportList->set("reports", $reports);
			$this->plugin->reportList->save();
			return true;
		});
		$form->setTitle("Reports");
		if($this->plugin->reportList->exists("reports")){
			if (count($this->plugin->reportList->get("reports")) == 0){
				$form->addLabel("There are no open reports at the moment.");
			}
			foreach($this->plugin->reportList->get("reports") as $key => $reportList) {
				$staff = (string)$reportList["staff"];
				$reason = (string)$reportList["reason"];
				$target = (string)$reportList["target"];
				$date = date("M j Y", (int)($reportList["time"]));
				$form->addLabel("\n§aReport #".($key + 1)."\n§c".$target." was previously reported.\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date, "label");
				if ($player->hasPermission("staffmode.tools.playerinfo.reports.close")) {
					$form->addToggle("Close #" . ($key + 1), False, strval($key));
				}
			}
		}
		$player->sendForm($form);
	}

    public function HistoryForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
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

            self::HistoryFormPartTwo($player, $target);
			return true;
        });
        $form->setTitle("History");
        $form->addDropdown("Pick the player you want to see", $this->plugin->getonlineplayersname(), null, "name");
        $form->addInput("Or type the §lEXACT§r name of the player you want to see", "Ex.: ".$player->getName(), "", "offlinename");

        $player->sendForm($form);
    }

    public function HistoryFormPartTwo(Player $player, string $target) : void {
        $form = new SimpleForm(function (Player $player, $data) use ($target) {
			return true;
        });
        $form->setTitle("History");
        if($this->plugin->history->exists(strtolower($target))){
            foreach($this->plugin->history->get(strtolower($target)) as $history) {
                $staff = (string)$history["staff"];
                $reason = (string)$history["reason"];
                $offence = (string)$history["type"];
                $date = (date("M j Y", (int)$history["time"]));
                if ($offence == "banned") {
                    if((int)$history["unbantime"] - $history["time"] == -1) {
                        $form->setContent($form->getContent()."\n§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                    } else {
                        $time = ((int)$history["unbantime"] - $history["time"]);
                        $days = (int)($time / 86400);
                        $hours = (int)(($time - ($days * 86400)) / 3600);
                        $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                        $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                        $form->setContent($form->getContent()."\n\n§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                    }
                } else {
                    $form->setContent($form->getContent()."\n\n§c".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
                }
            }
        }
        $player->sendForm($form);
    }
}