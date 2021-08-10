<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};

class PlayerInfoForm {
    
    public function __construct($pl) {
        $this->plugin = $pl;
    }

    public function PlayerInfoForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if($data == "reports") {
                self::ReportsForm($player);
            } elseif($data == "history") {
                self::HistoryForm($player);
            }
            return true;
        });
        $form->setTitle("Player Info Menu");

        $form->addButton("Reports", -1, "", "reports");
        $form->addButton("History", -1, "", "history");

        $player->sendForm($form);
    }

	public function ReportsForm(Player $player) : void {
		$form = new SimpleForm(function (Player $player, $data) {
			return true;
		});
		$form->setTitle("Reports");
		if($this->plugin->reportList->exists("reports")){
			foreach($this->plugin->reportList->get("reports") as $reportList) {
				$staff = (string)$reportList["staff"];
				$reason = (string)$reportList["reason"];
				$target = (string)$reportList["target"];
				$date = date("M j Y", (int)($reportList["time"]));
				$form->setContent($form->getContent()."\n§c".$target." was previously reported.\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
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
                        $form->setContent($form->getContent()."\n§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                    }
                } else {
                    $form->setContent($form->getContent()."\n§c".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
                }
            }
        }
        $player->sendForm($form);
    }
}