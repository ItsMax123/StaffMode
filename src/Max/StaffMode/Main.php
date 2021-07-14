<?php

declare(strict_types=1);

namespace Max\StaffMode;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\utils\Config;
use pocketmine\{Player, Server};
use pocketmine\item\Item;
use pocketmine\command\{Command, CommandSender};
use pocketmine\nbt\tag\StringTag;

use pocketmine\event\player\{PlayerInteractEvent, PlayerCommandPreprocessEvent, PlayerDropItemEvent, PlayerKickEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerPreLoginEvent};
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};

use Max\StaffMode\Forms\{SimpleForm, CustomForm};

class Main extends PluginBase implements Listener {

	private $contents;
	private $position;
    private $staffmodestatus;
    private $frozenstatus;
    private $banList;
    private $history;
    private $reportList;

    public function onEnable() {
        if(!file_exists($this->getDataFolder())){
            mkdir($this->getDataFolder());
        }
        #$this->saveResource("config.yml");
        $this->banList = new Config($this->getDataFolder()."BanList.yml", Config::YAML);
        $this->history = new Config($this->getDataFolder()."History.yml", Config::YAML);
        $this->reportList = new Config($this->getDataFolder()."ReportList.yml", Config::YAML);
        #$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    //Exit staff mode and regain original position and inventory:

    private function exitstaffmode(Player $player) {
        if($this->staffmodestatus[$player->getName()] === True) {
            $player->getInventory()->setContents($this->contents[$player->getName()]);
            $player->teleport($this->position[$player->getName()]);
            $player->setGamemode(Player::SURVIVAL);
            $this->staffmodestatus[$player->getName()] = False;
            $player->sendPopup("§cYou are no longer in staffmode.");
        }
    }

    //Check if player is banned and kick him if he is:

    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        $player = $event->getPlayer();
        $now = time();
        if($this->banList->exists(strtolower($player->getName()))){
            if(((int)explode("<|>", $this->banList->get(strtolower($player->getName())))[0] > $now)OR((((int)explode("<|>", $this->banList->get(strtolower($player->getName())))[0]) - ((int)explode("<|>", $this->banList->get(strtolower($player->getName())))[1])) == -1)){
                $staff = (string)explode("<|>", $this->banList->get(strtolower($player->getName())))[2];
                $reason = (string)explode("<|>", $this->banList->get(strtolower($player->getName())))[3];
                if((((int)explode("<|>", $this->banList->get(strtolower($player->getName())))[0]) - ((int)explode("<|>", $this->banList->get(strtolower($player->getName())))[1])) == -1) {
                    $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                } else {
                    $time = (int)(explode("<|>", $this->banList->get(strtolower($player->getName())))[0] - $now);
                    $days = (int)($time / 86400);
                    $hours = (int)(($time - ($days * 86400)) / 3600);
                    $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                    $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                    $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nTime left: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                }
                $event->setCancelled();
            }
            return;
        }
    }

    //Set player default statuses (Might not be neccesary):

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $this->staffmodestatus[$player->getName()] = False;
        $this->frozenstatus[$player->getName()] = False;
    }

    //All three next functions are to call exist staff mode when they exit the server:

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $this->exitstaffmode($player);
    }

    public function onKick(PlayerKickEvent $event){
        $player = $event->getPlayer();
        $this->exitstaffmode($player);
    }

    public function onDisable() {
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            $this->exitstaffmode($player);
        }
    }

    //Staffmode command:

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "staffmode":
                if($this->staffmodestatus[$sender->getName()] === False) {
                    $this->contents[$sender->getName()] = $sender->getInventory()->getContents();
                    $this->position[$sender->getName()] = $sender->getPosition();
                    $this->staffmodestatus[$sender->getName()] = True;
                    $sender->getInventory()->clearAll();
                    $sender->setGamemode(Player::SPECTATOR);
                    //$sender->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
                    $sender->sendPopup("§aYou are now in staffmode.");

                    //COMPASS | TELEPORT TO PLAYER
                    $compass = Item::get(Item::COMPASS, 0, 1);
                    $compass->setCustomName("§aTeleport to player");
                    $compass->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $compass->setLore(["§rRight click to open teleportation menu.\nHit a player to see their username."]);
                    $sender->getInventory()->setItem(0, $compass);

                    //BOOK | SEE PLAYER HISTORY
                    $book = Item::get(Item::BOOK, 0, 1);
                    $book->setCustomName("§aPlayer History");
                    $book->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $book->setLore(["§rRight click to open history menu."]);
                    $sender->getInventory()->setItem(1, $book);

                    //PAPER | WARN THE PLAYER
                    $fire = Item::get(Item::PAPER, 0, 1);
                    $fire->setCustomName("§6Warn a player");
                    $fire->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $fire->setLore(["§rRight click to open warning menu."]);
                    $sender->getInventory()->setItem(2, $fire);

                    //ICE BLOCK | FREEZE THE PLAYER
                    $ice = Item::get(Item::PACKED_ICE, 0, 1);
                    $ice->setCustomName("§bFreeze a player");
                    $ice->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $ice->setLore(["§rRight click to open freezing menu."]);
                    $sender->getInventory()->setItem(3, $ice);

                    //FIRE | UNFREEZE THE PLAYER
                    $fire = Item::get(Item::FIRE, 0, 1);
                    $fire->setCustomName("§eUnfreeze a player");
                    $fire->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $fire->setLore(["§rRight click to open unfreezing menu."]);
                    $sender->getInventory()->setItem(4, $fire);
                    
                    //GOLD SWORD | KICK THE PLAYER
                    $gsword = Item::get(Item::GOLDEN_SWORD, 0, 1);
                    $gsword->setCustomName("§cKick a player");
                    $gsword->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $gsword->setLore(["§rRight click to open kicking menu."]);
                    $sender->getInventory()->setItem(5, $gsword);

                    //GOLD AXE | BAN THE PLAYER
                    $gaxe = Item::get(Item::GOLDEN_AXE, 0, 1);
                    $gaxe->setCustomName("§4Ban a player");
                    $gaxe->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $gaxe->setLore(["§rRight click to open banning menu."]);
                    $sender->getInventory()->setItem(6, $gaxe);

                    //REDSTONE_TORCH | EXIT STAFF MODE
                    $rtorch = Item::get(Item::LIT_REDSTONE_TORCH, 0, 1);
                    $rtorch->setCustomName("§cExit StaffMode");
                    $rtorch->setNamedTagEntry(new StringTag("staffmode", "true"));
                    $rtorch->setLore(["§rRight click to exit StaffMode."]);
                    $sender->getInventory()->setItem(8, $rtorch);
                } else {
                    $sender->sendMessage("§7[§bStaffMode§7] §cYou are already in StaffMode!");
                }
				return true;
            case "report":
                $this->ReportForm($sender);
                return true;
            default:
                throw new \AssertionError("This line will never be executed");
		}
	}

    public function PlayerInteractEvent(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if($this->frozenstatus[$player->getName()] === True) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
        $item = $event->getItem();
        $nbt = $item->getNamedTagEntry("staffmode");
        if($nbt === null) return;
        if($this->staffmodestatus[$player->getName()] === True) {
            if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
                if ($item->getId() == Item::COMPASS) {
                    if ($player->hasPermission("staffmode.tools.teleport")) {
                        $this->TeleportForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::BOOK) {
                    if ($player->hasPermission("staffmode.tools.history")) {
                        $this->HistoryForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::PAPER) {
                    if ($player->hasPermission("staffmode.tools.warn")) {
                        $this->WarningForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::PACKED_ICE) {
                    if ($player->hasPermission("staffmode.tools.freeze")) {
                        $this->FreezingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::FIRE) {
                    if ($player->hasPermission("staffmode.tools.unfreeze")) {
                        $this->UnfreezingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::GOLDEN_SWORD) {
                    if ($player->hasPermission("staffmode.tools.kick")) {
                        $this->KickingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::GOLDEN_AXE) {
                    if ($player->hasPermission("staffmode.tools.ban")) {
                        $this->BanningForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::LIT_REDSTONE_TORCH) {
                    if ($player->hasPermission("staffmode.tools.exit")) {
                        $this->exitstaffmode($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                }
            }
        }
    }

    public function BlockBreakEvent(BlockBreakEvent $event){
        $player = $event->getPlayer();
        if($this->frozenstatus[$player->getName()] === True) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        if($this->frozenstatus[$player->getName()] === True) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    public function PlayerDropItemEvent(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        if($this->staffmodestatus[$player->getName()] === True) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot drop items while in StaffMode!");
        }
    }

    public function PlayerCommandPreprocessEvent(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if($this->frozenstatus[$player->getName()] === True) {
            if(($message !== "/staffmode")and(substr($message, 0, 1) == "/")){
                $event->setCancelled();
                $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
            }
        }
    }

    public function EntityDamageByEntityEvent(EntityDamageByEntityEvent $event){
        $player = $event->getDamager();
        if($this->frozenstatus[$player->getName()] === True) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
        if ($player instanceof Player && $event->getEntity() instanceof Player) {
            if($this->staffmodestatus[$player->getName()] === True) {
                $player->sendMessage("§7[§bStaffMode§7] §aThe player you just hit is: ".$event->getEntity()->getName());
            }
        }
    }
   
    public function ReportForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
            if ($data == "Someone Offline") {
                $this->ReportFormPartTwo($player);
            } else {
                $target = Server::getInstance()->getPlayer($data);
                if($target === null) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                    return;
                }
                $this->ReportFormPartThree($player, $target->getName());
            }
        });
        $form->setTitle("Report Menu");
        $form->setContent("Report a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }
        $form->addButton("Someone Offline", -1, "", "Someone Offline");

        $player->sendForm($form);
    }

    public function ReportFormPartTwo(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = $data["name"];
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }

            $this->ReportFormPartThree($player, $target);
        });
        $form->setTitle("Report Menu");
        $form->addInput("EXACT name of player to Report:", "Ex.: ".$player->getName(), "", "name");

        $player->sendForm($form);
    }

    public function ReportFormPartThree(Player $player, string $target) : void {
        $form = new CustomForm(function (Player $player, $data) use ($target) {
            if($data === null) {
                return true;
            }

            if(!isset($data["reason"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
                return;
            }
            if($this->history->exists(strtolower($target))){
                $this->history->set(strtolower($target), ("reported<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<&>".$this->history->get(strtolower($target))));
            } else {
                $this->history->set(strtolower($target), ("reported<|>".$data["reason"]."<|>".$player->getName()."<|>".time()));
            }
            $this->history->save();

            if($this->reportList->exists("reports")){
                $this->reportList->set("reports", ("".$target."<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<&>".$this->reportList->get("reports")));
            } else {
                $this->reportList->set("reports", ("".$target."<|>".$data["reason"]."<|>".$player->getName()."<|>".time()));
            }
            $this->reportList->save();

            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully reported player ".$target);
        });
        $form->setTitle("Report Menu");
        $form->addInput("Reason:", "Ex.: Hacking", "", "reason");

        $player->sendForm($form);
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
            $player->teleport($target);
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully teleported to player ".$target->getName());
        });
        $form->setTitle("Teleportation Menu");
        $form->setContent("Teleport to a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }

        $player->sendForm($form);
    }

    public function HistoryForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            if ($data == "Someone Offline") {
                $this->HistoryFormPartTwo($player);
            } elseif($data == "reports"){
                $this->HistoryFormReportsVersion($player);
            } else {
                $target = Server::getInstance()->getPlayer($data);
                if($target === null) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                    return;
                }
                $this->HistoryFormPartThree($player, $data);
            }
        });
        $form->setTitle("History Menu");

        $form->setContent(" - Look at a list of all reports by clicking the 'All Reports' button.\n - Look at a specific person's history by clicking their name.\n - Find someone offline by clicking the 'Someone Offline' button.");

        $form->addButton("§0All Reports", -1, "", "reports");
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }
        $form->addButton("§0Someone Offline", -1, "", "Someone Offline");

        $player->sendForm($form);
    }

    public function HistoryFormPartTwo(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = $data["name"];
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }

            $this->HistoryFormPartThree($player, $target);
        });
        $form->setTitle("History Menu");
        $form->addInput("EXACT name of player:", "Ex.: ".$player->getName(), "", "name");

        $player->sendForm($form);
    }

    public function HistoryFormPartThree(Player $player, string $target) : void {
        $form = new SimpleForm(function (Player $player, $data) use ($target) {
            if($data === null) {
                return true;
            }
        });
        $form->setTitle("History Menu");
        if($this->history->exists(strtolower($target))){
            foreach(explode("<&>", $this->history->get(strtolower($target))) as $history) {
                $staff = (string)explode("<|>", $history)[2];
                $reason = (string)explode("<|>", $history)[1];
                $offence = (string)explode("<|>", $history)[0];
                $date = date("M j Y", (int)(explode("<|>", $history)[3]));
                if ($offence == "banned") {
                    if((int)(explode("<|>", $history)[4] - explode("<|>", $history)[3]) == -1) {
                        $form->setContent($form->getContent()."\n§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                        $player->sendMessage("§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                    } else {
                        $time = (int)(explode("<|>", $history)[4] - explode("<|>", $history)[3]);
                        $days = (int)($time / 86400);
                        $hours = (int)(($time - ($days * 86400)) / 3600);
                        $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                        $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                        $form->setContent($form->getContent()."\n§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                        $player->sendMessage("§4".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                    }
                } else {
                    $form->setContent($form->getContent()."\n§c".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
                    $player->sendMessage("§c".$target." was previously ".$offence.".\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
                }
            }
        }
        $player->sendForm($form);
    }

    public function HistoryFormReportsVersion(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
        });
        $form->setTitle("Reports-List Menu");
        if($this->reportList->exists("reports")){
            foreach(explode("<&>", $this->reportList->get("reports")) as $reportList) {
                $staff = (string)explode("<|>", $reportList)[2];
                $reason = (string)explode("<|>", $reportList)[1];
                $target = (string)explode("<|>", $reportList)[0];
                $date = date("M j Y", (int)(explode("<|>", $reportList)[3]));
                $form->setContent($form->getContent()."\n§c".$target." was previously reported.\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
                $player->sendMessage("§c".$target." was previously reported.\n§rBy: ".$staff."\nReason: ".$reason."\nDate: ".$date);
            }
        }
        $player->sendForm($form);
    }

    public function WarningForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = Server::getInstance()->getPlayer($data);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }
            $this->WarningFormPartTwo($player, $target);
        });
        $form->setTitle("Warning Menu");
        $form->setContent("Warn a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }

        $player->sendForm($form);
    }

    public function WarningFormPartTwo(Player $player, Player $target) : void {
        $form = new CustomForm(function (Player $player, $data) use ($target) {
            if($data === null) {
                return true;
            }

            if(!isset($data["reason"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
                return;
            }

            $this->WarningFormPartThree($target, $data["reason"]);
            if($this->history->exists(strtolower($target->getName()))){
                $this->history->set(strtolower($target->getName()), ("warned<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<&>".$this->history->get(strtolower($target->getName()))));
            } else {
                $this->history->set(strtolower($target->getName()), ("warned<|>".$data["reason"]."<|>".$player->getName()."<|>".time()));
            }
            $this->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully warned player ".$target->getName());
        });
        $form->setTitle("Warning Menu");
        $form->addInput("Reason:", "Ex.: Glitching", "", "reason");

        $player->sendForm($form);
    }

    public function WarningFormPartThree(Player $target, string $reason) : void {
        $form = new SimpleForm(function (Player $target, $data) use ($reason) {
            $target->sendMessage("§7[§bStaffMode§7] §6YOU HAVE BEEN WARNED!\n§rReason for warn:  ".$reason);
        });
        $form->setTitle("§6YOU HAVE BEEN WARNED!");
        $form->setContent("Reason for warn:  ".$reason);
        $target->sendForm($form);
    }

    public function FreezingForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = Server::getInstance()->getPlayer($data);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }
            $target->setImmobile(true);
            $target->sendTitle("§bFrozen", "§cDo not log off and listen to staff!");
            $this->frozenstatus[$target->getName()] = True;
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully froze player ".$target->getName());
        });
        $form->setTitle("Freezing Menu");
        $form->setContent("Freeze a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }

        $player->sendForm($form);
    }

    public function UnfreezingForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = Server::getInstance()->getPlayer($data);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }
            $target->setImmobile(false);
            $target->sendTitle("§eUnfrozen", "§aYou can now move again.");
            $this->frozenstatus[$target->getName()] = False;
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully unfroze player ".$target->getName());

        });
        $form->setTitle("Unfreezing Menu");
        $form->setContent("Unfreeze a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }

        $player->sendForm($form);
    }

    public function KickingForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = Server::getInstance()->getPlayer($data);
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }
            $this->KickingFormPartTwo($player, $target);
        });
        $form->setTitle("Kicking Menu");
        $form->setContent("Kick a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }

        $player->sendForm($form);
    }

    public function KickingFormPartTwo(Player $player, Player $target) : void {
        $form = new CustomForm(function (Player $player, $data) use ($target) {
            if($data === null) {
                return true;
            }

            if(!isset($data["reason"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
                return;
            }

            $reason = (string)$data["reason"];
            $target->kick("§cYou have been kicked by staff.\n§rReason: " . (string)$data["reason"], false);
            if($this->history->exists(strtolower($target->getName()))){
                $this->history->set(strtolower($target->getName()), ("kicked<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<&>".$this->history->get(strtolower($target->getName()))));
            } else {
                $this->history->set(strtolower($target->getName()), ("kicked<|>".$data["reason"]."<|>".$player->getName()."<|>".time()));
            }
            $this->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully kicked player ".$target->getName());
        });
        $form->setTitle("Kicking Menu");
        $form->addInput("Reason:", "Ex.: Glitching", "", "reason");

        $player->sendForm($form);
    }

    public function BanningForm(Player $player) : void {
        $form = new SimpleForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }
            if ($data == "Someone Offline") {
                $this->BanningFormPartTwo($player);
            } else {
                $target = Server::getInstance()->getPlayer($data);
                if($target === null) {
                    $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                    return;
                }
                $this->BanningFormPartThree($player, $target);
            }
        });
        $form->setTitle("Banning Menu");
        $form->setContent("Ban a player by clicking their name.");

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $form->addButton($onlinePlayer->getName(), -1, "", $onlinePlayer->getName());
        }
        $form->addButton("Someone Offline", -1, "", "Someone Offline");

        $player->sendForm($form);
    }

    public function BanningFormPartTwo(Player $player) : void {
        $form = new CustomForm(function (Player $player, $data) {
            if($data === null) {
                return true;
            }

            $target = $data["name"];
            if($target === null) {
                $player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
                return;
            }

            $this->BanningFormPartFour($player, $target);
        });
        $form->setTitle("Banning Menu");
        $form->addInput("EXACT name of player to ban:", "Ex.: ".$player->getName(), "", "name");

        $player->sendForm($form);
    }

    public function BanningFormPartThree(Player $player, Player $target) : void {
        $form = new CustomForm(function (Player $player, $data) use ($target) {
            if($data === null) {
                return true;
            }

            if(!isset($data["days"]) and !isset($data["hours"]) and !isset($data["minutes"]) and !isset($data["seconds"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify an amount of time!");
                return;
            }

            if(!isset($data["reason"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
                return;
            }
            if ($data["forever"] == false) {
                $time = (((int)$data["days"]) + ((int)$data["hours"]) + ((int)$data["minutes"]) + ((int)$data["seconds"]));
                $days = (int)$data["days"];
                $hours = (int)$data["hours"];
                $minutes = (int)$data["minutes"];
                $seconds = (int)$data["seconds"];
                $target->kick("§cYou have been banned!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s", false, $target->getName()." was Banned.");
            } else {
                $time = -1;
                $target->kick("§cYou have been banned!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: Forever", false, $target->getName()." was Banned.");
            }
            $this->banList->set(strtolower($target->getName()), ("".(time() + $time)."<|>".time()."<|>".$player->getName()."<|>".$data["reason"]));
            $this->banList->save();
            if($this->history->exists(strtolower($target->getName()))){
                $this->history->set(strtolower($target->getName()), ("banned<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<|>".(time() + $time)."<&>".$this->history->get(strtolower($target->getName()))));
            } else {
                $this->history->set(strtolower($target->getName()), ("banned<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<|>".(time() + $time)));
            }
            $this->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully banned player ".$target->getName());
        });
        $form->setTitle("Banning Menu");
        $form->addLabel("Duration of ban:");
        $form->addToggle("Forever?", false, "forever");
        $form->addInput("Days:", "", "0", "days");
        $form->addInput("Hours:", "", "0", "hours");
        $form->addInput("Minutes:", "", "0", "minutes");
        $form->addInput("Seconds:", "", "0", "seconds");
        $form->addInput("Reason:", "Ex.: Hacking", "", "reason");

        $player->sendForm($form);
    }

    public function BanningFormPartFour(Player $player, string $target) : void {
        $form = new CustomForm(function (Player $player, $data) use ($target) {
            if($data === null) {
                return true;
            }

            if(!isset($data["days"]) and !isset($data["hours"]) and !isset($data["minutes"]) and !isset($data["seconds"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify an amount of time!");
                return;
            }

            if(!isset($data["reason"])) {
                $player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
                return;
            }
            if ($data["forever"] == false) {
                $time = (((int)$data["days"]) + ((int)$data["hours"]) + ((int)$data["minutes"]) + ((int)$data["seconds"]));
                $days = (int)$data["days"];
                $hours = (int)$data["hours"];
                $minutes = (int)$data["minutes"];
                $seconds = (int)$data["seconds"];
                //$target->kick("§cYou have been banned!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s", false, $target->getName()." was Banned.");
            } else {
                $time = -1;
                //$target->kick("§cYou have been banned!\n§rBy: ".$player->getName()."\nReason: ".$data["reason"]."\nDuration: Forever", false, $target->getName()." was Banned.");
            }
            $this->banList->set(strtolower($target), ("".(time() + $time)."<|>".time()."<|>".$player->getName()."<|>".$data["reason"]));
            $this->banList->save();
            if($this->history->exists(strtolower($target))){
                $this->history->set(strtolower($target), ("banned<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<|>".(time() + $time)."<&>".$this->history->get(strtolower($target))));
            } else {
                $this->history->set(strtolower($target), ("banned<|>".$data["reason"]."<|>".$player->getName()."<|>".time()."<|>".(time() + $time)));
            }
            $this->history->save();
            $player->sendMessage("§7[§bStaffMode§7] §aSuccessfully banned player ".$target);
        });
        $form->setTitle("Banning Menu");
        $form->addLabel("Duration of ban:");
        $form->addToggle("Forever?", false, "forever");
        $form->addInput("Days:", "", "0", "days");
        $form->addInput("Hours:", "", "0", "hours");
        $form->addInput("Minutes:", "", "0", "minutes");
        $form->addInput("Seconds:", "", "0", "seconds");
        $form->addInput("Reason:", "Ex.: Hacking", "", "reason");

        $player->sendForm($form);
    }
}