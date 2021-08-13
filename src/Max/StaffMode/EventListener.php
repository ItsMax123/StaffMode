<?php

declare(strict_types=1);

namespace Max\StaffMode;

use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\{LoginPacket};

use pocketmine\event\player\{PlayerChatEvent, PlayerInteractEvent, PlayerCommandPreprocessEvent, PlayerDropItemEvent, PlayerKickEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerPreLoginEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent, EntityLevelChangeEvent};
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\server\{DataPacketReceiveEvent, QueryRegenerateEvent};
use pocketmine\Server;
use BlockHorizons\PerWorldPlayer\events\PerWorldPlayerDataSaveEvent;
use BlockHorizons\PerWorldPlayer\world\data\PlayerWorldData;

use function in_array;

class EventListener implements Listener {

    public function __construct($pl) {
        $this->plugin = $pl;
        $pl->getServer()->getPluginManager()->registerEvents($this, $pl);
    }

	/**
	 * @priority LOWEST
	 */

	public function onPerWorldPlayerDataSave(PerWorldPlayerDataSaveEvent $event) {
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			var_dump("yay");
			$this->plugin->exitstaffmode($player);
			$event->setPlayerWorldData(PlayerWorldData::fromPlayer($player));
		}
	}

    //If player is NOT banned, save all their alias info when they join.
    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if($packet instanceof LoginPacket){
            if(!Player::isValidUserName($packet->username)){
                return;
            }
            if($this->plugin->banList->exists(strtolower($player->getName()))){
                if (($this->plugin->banList->get(strtolower($player->getName()))["unbantime"] < time())AND($this->plugin->banList->get(strtolower($player->getName()))["unbantime"] - $this->plugin->banList->get(strtolower($player->getName()))["time"] != -1)) {
                    $this->plugin->alias->set(strtolower($packet->username), ["IPAddress" => $player->getAddress(), "DeviceId" => $packet->clientData['DeviceId'], "SelfSignedId" => $packet->clientData['SelfSignedId'], "ClientRandomId" => (string)$packet->clientData['ClientRandomId']]);
                    $this->plugin->alias->save();
                }
            } else {
                $this->plugin->alias->set(strtolower($packet->username), ["IPAddress" => $player->getAddress(), "DeviceId" => $packet->clientData['DeviceId'], "SelfSignedId" => $packet->clientData['SelfSignedId'], "ClientRandomId" => (string)$packet->clientData['ClientRandomId']]);
                $this->plugin->alias->save();
            }
        }
    }

    //Check if player is banned and kick him if he is:

    public function onPreLogin(PlayerPreLoginEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->banList->exists(strtolower($player->getName()))){
            if (($this->plugin->banList->get(strtolower($player->getName()))["unbantime"] > time())OR($this->plugin->banList->get(strtolower($player->getName()))["unbantime"] - $this->plugin->banList->get(strtolower($player->getName()))["time"] == -1)) {
                $staff = (string)$this->plugin->banList->get(strtolower($player->getName()))["staff"];
                $reason = (string)$this->plugin->banList->get(strtolower($player->getName()))["reason"];
                if((int)$this->plugin->banList->get(strtolower($player->getName()))["unbantime"] - (int)$this->plugin->banList->get(strtolower($player->getName()))["time"] == -1) {
                    $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                } else {
                    $time = (int)$this->plugin->banList->get(strtolower($player->getName()))["unbantime"] - time();
                    $days = (int)($time / 86400);
                    $hours = (int)(($time - ($days * 86400)) / 3600);
                    $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                    $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                    $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nTime left: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                }
                $event->setCancelled();
            } else {
                $this->plugin->banList->remove(strtolower($player->getName()));
                $this->plugin->banList->save();
            }
        }
        foreach ($this->plugin->banList->getAll() as $bannedplayerinfo) {
            if (isset($bannedplayerinfo["adress"])) {
                $BannedAddress = $bannedplayerinfo["adress"];
                $BannedDeviceId = $bannedplayerinfo["deviceid"];
                $BannedSelfSignedId = $bannedplayerinfo["selfsignedid"];
                $PlayerAddress = $this->plugin->alias->get(strtolower($player->getName()))["IPAddress"];
                $PlayerDeviceId = $this->plugin->alias->get(strtolower($player->getName()))["DeviceId"];
                $PlayerSelfSignedId = $this->plugin->alias->get(strtolower($player->getName()))["SelfSignedId"];
                if(($BannedAddress == $PlayerAddress)OR($BannedDeviceId == $PlayerDeviceId)OR($BannedSelfSignedId == $PlayerSelfSignedId)) {
                    $staff = $bannedplayerinfo["staff"];
                    $reason = $bannedplayerinfo["reason"];
                    if((((int)$bannedplayerinfo["unbantime"]) - ((int)$bannedplayerinfo["time"])) == -1) {
                        $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                    } else {
                        $time = ((int)$bannedplayerinfo["unbantime"] - time());
                        $days = (int)($time / 86400);
                        $hours = (int)(($time - ($days * 86400)) / 3600);
                        $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                        $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                        $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nTime left: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                    }
                    $event->setCancelled();
                }
            }
        }
    }

    //Silent Join & Warning reports
    /**
     * @priority HIGHEST
     */

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->config->get("SilentJoin")) {
			if ($player->hasPermission("staffmode.silent")) {
				$event->setJoinMessage(null);
			}
		}
		foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
			if(in_array($onlinePlayer->getName(), $this->plugin->staffmodestatus)) {
				Server::getInstance()->removePlayerListData($onlinePlayer->getUniqueId());
			}
		}
		foreach($this->plugin->reportList->get("reports") as $reportList) {
			if ($player->getName() == (string)$reportList["target"]) {
				foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
					if ($onlinePlayer->hasPermission("staffmode.alerts")) {
						$onlinePlayer->sendMessage("§7[§bStaffMode§7] §c".$player->getName()." §4was previously reported for: §6".$reportList["reason"]);
					}
				}
			}
		}
		foreach($this->plugin->boloList->get("bolos") as $boloList) {
			if ($player->getName() == (string)$boloList["target"]) {
				foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
					if ($onlinePlayer->hasPermission("staffmode.alerts")) {
						$onlinePlayer->sendMessage("§7[§bStaffMode§7] §c".$player->getName()." §4is on the BOLO list for: §6".$boloList["reason"]);
					}
				}
			}
		}
		if ($player->hasPermission("staffmode.alerts")) {
			foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
				foreach($this->plugin->reportList->get("reports") as $reportList) {
					if ($onlinePlayer->getName() == (string)$reportList["target"]) {
						$player->sendMessage("§7[§bStaffMode§7] §c".$onlinePlayer->getName()." §4was previously reported for: §6".$reportList["reason"]);
					}
				}
				foreach($this->plugin->boloList->get("bolos") as $boloList) {
					if ($onlinePlayer->getName() == (string)$boloList["target"]) {
						$player->sendMessage("§7[§bStaffMode§7] §c".$onlinePlayer->getName()." §4is on the BOLO list for: §6".$boloList["reason"]);
					}
				}
			}
		}
    }

	public function onQuery(QueryRegenerateEvent $event) {
		$visibleplayers = [];
		foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
			if(in_array($onlinePlayer->getName(), $this->plugin->staffmodestatus)) {
				$online = $event->getPlayerCount();
				$event->setPlayerCount($online - 1);
			} else {
				array_push($visibleplayers, $onlinePlayer);
			}
		}
		$event->setPlayerList($visibleplayers);
	}

    //Prevent people from changing world in staff mode (to prevent original inventory loss and other bugs) if there is a perworldinventory plugin
    /**
     * @priority HIGHEST
     * @ignoreCancelled true
     */

    public function onChangeWorld(EntityLevelChangeEvent $event) : void{
        if ($this->plugin->config->get("Allow-World-Change")) return;
        $player = $event->getEntity();
        if($player instanceof Player){
            $from = $event->getOrigin();
            $to = $event->getTarget();
            if($from !== $to){
				if(in_array($player->getName(), $this->plugin->staffmodestatus)) {
                    $event->setCancelled();
                    $player->sendMessage("§7[§bStaffMode§7] §cCannot change world while in staffmode.");
                }
            }
        }
    }

    //Silent leave & leave staff mode
	/**
	 * @priority LOWEST
	 */

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $this->plugin->exitstaffmode($player);
        if($this->plugin->config->get("SilentLeave")){
            if ($player->hasPermission("staffmode.silent")) {
                $event->setQuitMessage(null);
            }
        }
    }

    //Leave staff mode
	/**
	 * @priority LOWEST
	 */

    public function onKick(PlayerKickEvent $event){
        $this->plugin->exitstaffmode($event->getPlayer());
    }

    //Prevent block break when frozen

    public function onBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    //Prevent block place when frozen

    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    //Prevent item drop when frozen or in staffmode

    public function onDropItem(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->staffmodestatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot drop items while in StaffMode!");
        }
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    //Prevent commands when frozen and chat when muted

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            if(($message !== "/staffmode")and($message !== "/sm")and(substr($message, 0, 1) == "/")){
                $event->setCancelled();
                $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
            }
        }
        if((substr($message, 0, 1) != "/")or(substr($message, 0, 4) == "/say")){
            if($this->plugin->muteList->exists(strtolower($player->getName()))){
                if(((int)$this->plugin->muteList->get(strtolower($player->getName()))["unmutetime"] > time())OR((((int)$this->plugin->muteList->get(strtolower($player->getName()))["unmutetime"]) - ((int)$this->plugin->muteList->get(strtolower($player->getName()))["time"])) == -1)){
                    $staff = (string)$this->plugin->muteList->get(strtolower($player->getName()))["staff"];
                    $reason = (string)$this->plugin->muteList->get(strtolower($player->getName()))["reason"];
                    if((((int)$this->plugin->muteList->get(strtolower($player->getName()))["unmutetime"]) - ((int)$this->plugin->muteList->get(strtolower($player->getName()))["time"])) == -1) {
                        $player->sendMessage("§cYou are muted!\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                    } else {
                        $time = (int)($this->plugin->muteList->get(strtolower($player->getName()))["unmutetime"] - time());
                        $days = (int)($time / 86400);
                        $hours = (int)(($time - ($days * 86400)) / 3600);
                        $minutes = (int)(($time - (($days * 86400) + ($hours * 3600))) / 60);
                        $seconds = (int)($time - (($days * 86400) + ($hours * 3600) + ($minutes * 60)));
                        $player->sendMessage("§cYou are muted!\n§rBy: ".$staff."\nReason: ".$reason."\nTime left: ".$days."d, ".$hours."h, ".$minutes."m, ".$seconds."s");
                    }
                    $event->setCancelled();
                } else {
                    $this->plugin->muteList->remove(strtolower($player->getName()));
                    $this->plugin->muteList->save();
                }
            }
        }
    }

    //StaffChat
	/**
	 * @priority HIGHEST
	 */

	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->staffchatstatus)) {
			$recipients = [];
			foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
				if ($onlinePlayer->hasPermission("staffmode.command.staffchat")) {
					array_push($recipients, $onlinePlayer);
				}
			}
			if (!$recipients) return;
			$event->setFormat("§7[§3StaffChat§7] <§d".$player->getName()."§7>§r ".$event->getMessage());
			$event->setRecipients($recipients);
		}
	}

    //Prevent dying when frozen

    public function onDamage(EntityDamageEvent $event){
        $player = $event->getEntity();
        if ($player instanceof Player) {
			if(in_array($player->getName(), $this->plugin->frozenstatus)) {
                $event->setCancelled();
            }
        }
    }

    //Prevent getting hit or hitting when frozen & Freeze player by hitting them with freeze tool when in staff mode & Get player name when hitting player with anything else when in staffmode (To get name of invis people)

    public function onHit(EntityDamageByEntityEvent $event){
        $attacker = $event->getDamager();
        $victim = $event->getEntity();
        if (!$attacker instanceof Player) return;
		if(in_array($attacker->getName(), $this->plugin->frozenstatus)) {
			$event->setCancelled();
			$attacker->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
		}
		if (!$victim instanceof Player) return;
		if(in_array($attacker->getName(), $this->plugin->staffmodestatus)) {
			if ($attacker->getInventory()->getItemInHand()->getId() == Item::PACKED_ICE) {
				if(in_array($victim->getName(), $this->plugin->frozenstatus)) {
					$victim->setImmobile(false);
					$victim->sendTitle("§eUnfrozen", "§aYou can now move again.");
					unset($this->plugin->frozenstatus[array_search($victim->getName(), $this->plugin->frozenstatus)]);
					$attacker->sendMessage("§7[§bStaffMode§7] §aSuccessfully froze player ".$victim->getName());
					return;
				} else {
					$victim->setImmobile(true);
					$victim->sendTitle("§bFrozen", "§cDo not log off and listen to staff!");
					$this->plugin->frozenstatus[] = $victim->getName();
					$attacker->sendMessage("§7[§bStaffMode§7] §aSuccessfully unfroze player ".$victim->getName());
					return;
				}
			} else {
				$attacker->sendMessage("§7[§bStaffMode§7] §aThe name of the player you just hit is: ".$event->getEntity()->getName());
			}
		}
		if(in_array($victim->getName(), $this->plugin->frozenstatus)) {
			$event->setCancelled();
			$attacker->sendMessage("§7[§bStaffMode§7] §cCannot attack a frozen player!");
		}
    }

    //Prevent interaction when frozen & Use staffmode tools

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
        $item = $event->getItem();
        $nbt = $item->getNamedTagEntry("staffmode");
        if($nbt === null) return;
		if(in_array($player->getName(), $this->plugin->staffmodestatus)) {
            if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
                if ($item->getId() == Item::COMPASS) {
                    if ($player->hasPermission("staffmode.tools.teleport")) {
                        $this->plugin->TeleportForm->TeleportForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::BOOK) {
                    if ($player->hasPermission("staffmode.tools.playerinfo")) {
                        $this->plugin->PlayerInfoForm->PlayerInfoForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::CHEST) {
					if ($player->hasPermission("staffmode.tools.inventorymanager")) {
						$this->plugin->InventoryManagerForm->InventoryManagerForm($player);
					} else {
						$player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
					}
				} elseif($item->getId() == Item::PAPER) {
                    if ($player->hasPermission("staffmode.tools.warn")) {
                        $this->plugin->WarnForm->WarningForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::PACKED_ICE) {
                    if ($player->hasPermission("staffmode.tools.freeze")) {
                        $this->plugin->FreezeForm->FreezingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::GOLDEN_HOE) {
                    if ($player->hasPermission("staffmode.tools.mute")) {
                        $this->plugin->MuteForm->MutingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::GOLDEN_BOOTS) {
                    if ($player->hasPermission("staffmode.tools.kick")) {
                        $this->plugin->KickForm->KickingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::GOLDEN_AXE) {
                    if ($player->hasPermission("staffmode.tools.ban")) {
                        $this->plugin->BanForm->BanningForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::LIT_REDSTONE_TORCH) {
                    if ($player->hasPermission("staffmode.tools.exit")) {
                    	$this->plugin->exitstaffmode($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                }
            }
        }
    }
}