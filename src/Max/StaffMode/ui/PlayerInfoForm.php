<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

use muqsit\invmenu\InvMenu;

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
            } elseif( $data == "invsee") {
                self::InvseeForm($player);
            } elseif( $data == "enderchestsee") {
                self::EnderchestSeeForm($player);
            }
            return true;
        });
        $form->setTitle("Player Info Menu");

        $form->addButton("Reports", -1, "", "reports");
        $form->addButton("History", -1, "", "history");
        $form->addButton("Inventory Spy", -1, "", "invsee");
        $form->addButton("Ender Chest Spy", -1, "", "enderchestsee");

        $player->sendForm($form);
    }

    public function InvseeForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if (count($this->plugin->getonlineplayersname()) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return true;
            }

            if(Server::getInstance()->getPlayer($this->plugin->getonlineplayersname()[$data["name"]]) === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return true;
            } else {
                $targetname = $this->plugin->getonlineplayersname()[$data["name"]];
            }
            $target = Server::getInstance()->getPlayer($targetname);
            $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
            $menu->setListener(InvMenu::readonly());
            $menu->setName($targetname."'s Inventory");
            $menu->getInventory()->setContents($target->getInventory()->getContents());
            for($i = 36; $i < 54; $i++) {
                switch($i) {
                    case 36:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Boots");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 45:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getBoots() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 37:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Leggings");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 46:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getLeggings() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 38:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Chestplate");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 47:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getChestplate() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 39:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Helmet");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 48:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getHelmet() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 41:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Health");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 50:
                        $item = ItemFactory::get(Item::GOLDEN_APPLE, 1, 1);
                        $item->setCustomName("§2".$target->getHealth()."/".$target->getMaxHealth());
                        $menu->getInventory()->setItem($i, $item);
                        break;

                    case 42:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Hunger");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 51:
                        $item = ItemFactory::get(Item::COOKED_BEEF, 1, 1);
                        $item->setCustomName("§2".$target->getFood()."/".$target->getMaxFood());
                        $menu->getInventory()->setItem($i, $item);
                        break;

                    case 43:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Gamemode");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 52:
                        if ($target->getGamemode() == 0){
                            $gamemode = "Survival";
                        } elseif ($target->getGamemode() == 1){
                            $gamemode = "Creative";
                        } elseif ($target->getGamemode() == 2){
                            $gamemode = "Adventure";
                        } elseif ($target->getGamemode() == 3){
                            $gamemode = "Spectator";
                        }
                        $item = ItemFactory::get(Item::BEDROCK, 1, 1);
                        $item->setCustomName("§2".$gamemode);
                        $menu->getInventory()->setItem($i, $item);
                        break;

                    case 44:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Ping");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 53:
                        $item = ItemFactory::get(Item::DAYLIGHT_SENSOR, 1, 1);
                        $item->setCustomName("§2".$target->getPing()."ms");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    
                    default:
                        break;
                }
            }
            $menu->send($player);
			return true;
        });
        $form->setTitle("Inventory Spy Menu");
        $form->addDropdown("Pick the player you want to see", $this->plugin->getonlineplayersname(), null, "name");
        $player->sendForm($form);
    }

    public function EnderchestSeeForm(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if (count($this->plugin->getonlineplayersname()) == 0) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return true;
            }

            if(Server::getInstance()->getPlayer($this->plugin->getonlineplayersname()[$data["name"]]) === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return true;
            } else {
                $targetname = $this->plugin->getonlineplayersname()[$data["name"]];
            }
            $target = Server::getInstance()->getPlayer($targetname);
            $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
            $menu->setListener(InvMenu::readonly());
            $menu->setName($targetname."'s Ender Chest");
            $menu->getInventory()->setContents($target->getEnderChestInventory()->getContents());
            for($i = 27; $i < 36; $i++) {
                $item = ItemFactory::get(Item::STAINED_GLASS, 14, 1);
                $item->setCustomName("§4§lx");
                $menu->getInventory()->setItem($i, $item);
            }
            for($i = 36; $i < 54; $i++) {
                switch($i) {
                    case 36:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Boots");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 45:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getBoots() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 37:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Leggings");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 46:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getLeggings() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 38:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Chestplate");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 47:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getChestplate() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 39:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Helmet");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 48:
                        $menu->getInventory()->setItem($i, $target->getArmorInventory()->getHelmet() ?? ItemFactory::get(Item::AIR));
                        break;

                    case 41:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Health");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 50:
                        $item = ItemFactory::get(Item::GOLDEN_APPLE, 1, 1);
                        $item->setCustomName("§2".$target->getHealth()."/".$target->getMaxHealth());
                        $menu->getInventory()->setItem($i, $item);
                        break;

                    case 42:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Hunger");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 51:
                        $item = ItemFactory::get(Item::COOKED_BEEF, 1, 1);
                        $item->setCustomName("§2".$target->getFood()."/".$target->getMaxFood());
                        $menu->getInventory()->setItem($i, $item);
                        break;

                    case 43:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Gamemode");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 52:
                        if ($target->getGamemode() == 0){
                            $gamemode = "Survival";
                        } elseif ($target->getGamemode() == 1){
                            $gamemode = "Creative";
                        } elseif ($target->getGamemode() == 2){
                            $gamemode = "Adventure";
                        } elseif ($target->getGamemode() == 3){
                            $gamemode = "Spectator";
                        }
                        $item = ItemFactory::get(Item::BEDROCK, 1, 1);
                        $item->setCustomName("§2".$gamemode);
                        $menu->getInventory()->setItem($i, $item);
                        break;

                    case 44:
                        $item = ItemFactory::get(Item::SIGN, 1, 1);
                        $item->setCustomName("§2Ping");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    case 53:
                        $item = ItemFactory::get(Item::DAYLIGHT_SENSOR, 1, 1);
                        $item->setCustomName("§2".$target->getPing()."ms");
                        $menu->getInventory()->setItem($i, $item);
                        break;
                    
                    default:
                        break;
                }
            }
            $menu->send($player);
			return true;
        });
        $form->setTitle("Ender Chest Spy Menu");
        $form->addDropdown("Pick the player you want to see", $this->plugin->getonlineplayersname(), null, "name");
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
        $form->setTitle("History Menu");
        $form->addDropdown("Pick the player you want to see", $this->plugin->getonlineplayersname(), null, "name");
        $form->addInput("Or type the §lEXACT§r name of the player you want to see", "Ex.: ".$player->getName(), "", "offlinename");

        $player->sendForm($form);
    }

    public function HistoryFormPartTwo(Player $player, string $target) : void {
        $form = new SimpleForm(function (Player $player, $data) use ($target) {
			return true;
        });
        $form->setTitle("History Menu");
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

    public function ReportsForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
			return true;
        });
        $form->setTitle("Reports Menu");
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
}