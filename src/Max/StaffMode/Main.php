<?php

declare(strict_types=1);

namespace Max\StaffMode;

use pocketmine\{Player, Server};
use pocketmine\item\Item;
use pocketmine\utils\{Config};
use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\nbt\tag\StringTag;

use Max\StaffMode\ui\{ReportForm, TeleportForm, PlayerInfoForm, WarnForm, FreezeForm, MuteForm, KickForm, BanForm};
use muqsit\invmenu\InvMenuHandler;

class Main extends PluginBase{
    public $contents, $position, $gamemode, $staffmodestatus, $staffchatstatus, $frozenstatus, $banList, $muteList, $history, $reportList, $alias, $config, $ReportForm, $TeleportForm, $PlayerInfoForm, $WarnForm, $FreezeForm, $MuteForm, $KickForm, $BanForm, $DefaultConfig;

    public function onEnable() {
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        new EventListener($this);
        $this->ReportForm = new ReportForm($this);
        $this->TeleportForm = new TeleportForm($this);
        $this->PlayerInfoForm = new PlayerInfoForm($this);
        $this->WarnForm = new WarnForm($this);
        $this->FreezeForm = new FreezeForm($this);
        $this->MuteForm = new MuteForm($this);
        $this->KickForm = new KickForm($this);
        $this->BanForm = new BanForm($this);

        if(!file_exists($this->getDataFolder())){
            mkdir($this->getDataFolder());
        }
        $this->banList = new Config($this->getDataFolder()."BanList.yml", Config::YAML);
        $this->muteList = new Config($this->getDataFolder()."MuteList.yml", Config::YAML);
        $this->history = new Config($this->getDataFolder()."History.yml", Config::YAML);
        $this->reportList = new Config($this->getDataFolder()."ReportList.yml", Config::YAML);
        $this->alias = new Config($this->getDataFolder()."Alias.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);

		$this->DefaultConfig = array(
			"Allow-World-Change" => true,
			"FakeLeave" => true,
			"FakeLeave-Message" => "§e<player> left the game",
			"FakeJoin" => false,
			"FakeJoin-Message" => "§e<player> joined the game",
			"SilentJoin" => true,
			"SilentLeave" => false,
			"DiscordWebhooks-Reports" => false,
			"DiscordWebhooks-Reports-Link" => "https://discord.com/api/webhooks/865604048789831730/zZC1IsbWc0MdCiUZROhgs0q_V1b0BJ7B_kA4I8MG_89VdMhpC0RQ3ur71AVrcvUymCn3",
			"DiscordWebhooks-Warnings" => false,
			"DiscordWebhooks-Warnings-Link" => "https://discord.com/api/webhooks/865604048789831730/zZC1IsbWc0MdCiUZROhgs0q_V1b0BJ7B_kA4I8MG_89VdMhpC0RQ3ur71AVrcvUymCn3",
			"DiscordWebhooks-Mutes" => false,
			"DiscordWebhooks-Mutes-Link" => "https://discord.com/api/webhooks/865604048789831730/zZC1IsbWc0MdCiUZROhgs0q_V1b0BJ7B_kA4I8MG_89VdMhpC0RQ3ur71AVrcvUymCn3",
			"DiscordWebhooks-Kicks" => false,
			"DiscordWebhooks-Kicks-Link" => "https://discord.com/api/webhooks/865604048789831730/zZC1IsbWc0MdCiUZROhgs0q_V1b0BJ7B_kA4I8MG_89VdMhpC0RQ3ur71AVrcvUymCn3",
			"DiscordWebhooks-Bans" => false,
			"DiscordWebhooks-Bans-Link" => "https://discord.com/api/webhooks/865604048789831730/zZC1IsbWc0MdCiUZROhgs0q_V1b0BJ7B_kA4I8MG_89VdMhpC0RQ3ur71AVrcvUymCn3"
		);

		//Automatically update config file if plugin gets updated
		if ($this->config->getAll() != $this->DefaultConfig) {
			foreach ($this->DefaultConfig as $key => $data) {
				if($this->config->exists($key)) {
					$this->DefaultConfig[$key] = $this->config->get($key);
				}
			}
			$this->config->setAll($this->DefaultConfig);
			$this->config->save();
		}

		if ($this->getServer()->getPluginManager()->getPlugin("PerWorldPlayer")) {
			$this->config->set("Allow-World-Change", false);
			$this->config->save();
		}
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($sender instanceof Player) {
            switch($command->getName()){
                case "staffmode":
                    $this->enterstaffmode($sender);
                    return true;
				case "staffchat":
					$this->togglestaffchat($sender);
					return true;
                case "report":
                    $this->ReportForm->ReportForm($sender);
                    return true;
                default:
                    throw new \AssertionError("This line will never be executed");
            }
        } else {
            $this->getLogger()->info("§cYou can only use this command in the game.");
            return true;
        }
    }

	public function togglestaffchat(Player $player) {
		if(!$this->staffchatstatus[$player->getName()]) {
			$this->staffchatstatus[$player->getName()] = True;
			$player->sendMessage("§7[§bStaffMode§7] §aYou are now in staffchat.");
		} else {
			$this->staffchatstatus[$player->getName()] = False;
			$player->sendMessage("§7[§bStaffMode§7] §aYou are no longer in staffchat.");
		}
	}

    public function enterstaffmode(Player $player) {
        if(!$this->staffmodestatus[$player->getName()]) {
            $this->contents[$player->getName()] = $player->getInventory()->getContents();
            $this->position[$player->getName()] = $player->getPosition();
            $this->gamemode[$player->getName()] = $player->getGamemode();
            $player->getInventory()->clearAll();
            //$player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
            $player->setGamemode(Player::SPECTATOR);
            $this->staffmodestatus[$player->getName()] = True;
            $player->sendPopup("§aYou are now in staffmode.");

            //Fake Leave message
            if($this->config->get("FakeLeave")){
                Server::getInstance()->removeOnlinePlayer($player);
                $message = $this->getConfig()->get("FakeLeave-Message");
                $name = $player->getName();
                $message = str_replace("<player>", "$name", $message);
                $this->getServer()->broadcastMessage($message);
            }
    
            //COMPASS | TELEPORT TO PLAYER
            $compass = Item::get(Item::COMPASS, 0, 1);
            $compass->setCustomName("§aTeleport to player");
            $compass->setNamedTagEntry(new StringTag("staffmode", "true"));
            $compass->setLore(["§rRight click to open teleportation menu.\nHit a player to see their username."]);
            $player->getInventory()->setItem(0, $compass);
    
            //BOOK | SEE PLAYER HISTORY
            $book = Item::get(Item::BOOK, 0, 1);
            $book->setCustomName("§aPlayer Info");
            $book->setNamedTagEntry(new StringTag("staffmode", "true"));
            $book->setLore(["§rRight click to open player info menu."]);
            $player->getInventory()->setItem(1, $book);
    
            //PAPER | WARN THE PLAYER
            $fire = Item::get(Item::PAPER, 0, 1);
            $fire->setCustomName("§dWarn a player");
            $fire->setNamedTagEntry(new StringTag("staffmode", "true"));
            $fire->setLore(["§rRight click to open warning menu."]);
            $player->getInventory()->setItem(2, $fire);
    
            //ICE BLOCK | FREEZE THE PLAYER
            $ice = Item::get(Item::PACKED_ICE, 0, 1);
            $ice->setCustomName("§bFreeze a player");
            $ice->setNamedTagEntry(new StringTag("staffmode", "true"));
            $ice->setLore(["§rRight click to open freezing menu.\nHit a player to freeze them."]);
            $player->getInventory()->setItem(3, $ice);
                
            //GOLD HOE | MUTE THE PLAYER
            $ghoe = Item::get(Item::GOLDEN_HOE, 0, 1);
            $ghoe->setCustomName("§6Mute a player");
            $ghoe->setNamedTagEntry(new StringTag("staffmode", "true"));
            $ghoe->setLore(["§rRight click to open muting menu."]);
            $player->getInventory()->setItem(4, $ghoe);
    
            //GOLD SWORD | KICK THE PLAYER
            $gsword = Item::get(Item::GOLDEN_SWORD, 0, 1);
            $gsword->setCustomName("§cKick a player");
            $gsword->setNamedTagEntry(new StringTag("staffmode", "true"));
            $gsword->setLore(["§rRight click to open kicking menu."]);
            $player->getInventory()->setItem(5, $gsword);
    
            //GOLD AXE | BAN THE PLAYER
            $gaxe = Item::get(Item::GOLDEN_AXE, 0, 1);
            $gaxe->setCustomName("§4Ban a player");
            $gaxe->setNamedTagEntry(new StringTag("staffmode", "true"));
            $gaxe->setLore(["§rRight click to open banning menu."]);
            $player->getInventory()->setItem(6, $gaxe);
    
            //REDSTONE_TORCH | EXIT STAFF MODE
            $rtorch = Item::get(Item::LIT_REDSTONE_TORCH, 0, 1);
            $rtorch->setCustomName("§cExit StaffMode");
            $rtorch->setNamedTagEntry(new StringTag("staffmode", "true"));
            $rtorch->setLore(["§rRight click to exit StaffMode."]);
            $player->getInventory()->setItem(8, $rtorch);
        }
    }

    public function exitstaffmode(Player $player) {
        if($this->staffmodestatus[$player->getName()]) {
            $player->getInventory()->setContents($this->contents[$player->getName()]);
            $player->teleport($this->position[$player->getName()]);
            $player->setGamemode($this->gamemode[$player->getName()]);
            $this->staffmodestatus[$player->getName()] = False;
            Server::getInstance()->addOnlinePlayer($player);
            $player->sendPopup("§cYou are no longer in staffmode.");

            //Fake join message
            if($this->config->get("FakeJoin")){
                $message = $this->getConfig()->get("FakeJoin-Message");
                $name = $player->getName();
                $message = str_replace("<player>", "$name", $message);
                $this->getServer()->broadcastMessage($message);
            }
        }
    }

    public function getonlineplayersname() {
        $onlineplayersname = [];
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            array_push($onlineplayersname, $onlinePlayer->getName());
        }
        return $onlineplayersname;
    }

    public function getmutedplayersname() {
        $mutedplayersname = [];
        foreach ($this->muteList->getAll() as $mutedplayersnamekey => $mutedplayersinfo) {
            array_push($mutedplayersname, $mutedplayersnamekey);
        }
        return $mutedplayersname;
    }

    public function getbannedplayersname() {
        $bannedplayersname = [];
        foreach ($this->banList->getAll() as $bannedplayersnamekey => $bannedplayersinfo) {
            array_push($bannedplayersname, $bannedplayersnamekey);
        }
        return $bannedplayersname;
    }
}