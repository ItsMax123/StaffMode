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
use pocketmine\event\server\{DataPacketReceiveEvent, QueryRegenerateEvent, CommandEvent};
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\Server;

use function in_array;

class EventListener implements Listener {

    public function __construct($pl) {
        $this->plugin = $pl;
        $pl->getServer()->getPluginManager()->registerEvents($this, $pl);
    }

	public function onCommand(CommandEvent $event){
		if ($event->getCommand() === "stop") {
			foreach(Server::getInstance()->getOnlinePlayers() as $player){
				$this->plugin->exitstaffmode($player);
			}
		}
	}

	/**
	 * @priority LOWEST
	 */

	public function onPluginDisable(PluginDisableEvent $event) {
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$this->plugin->exitstaffmode($player);
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
            $player->sendMessage("§7[§bStaffMode§7] §cCannot break blocks while frozen!");
        }
		if (in_array($player->getName(), $this->plugin->staffmodestatus)) {
			$event->setCancelled();
			$player->sendMessage("§7[§bStaffMode§7] §cCannot break blocks while in staffmode!");
		}
    }

    //Prevent block place when frozen

    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot place blocks while frozen!");
        }
		if (in_array($player->getName(), $this->plugin->staffmodestatus)) {
			$event->setCancelled();
			$player->sendMessage("§7[§bStaffMode§7] §cCannot place blocks while in staffmode!");
		}
    }

    //Prevent item drop when frozen or in staffmode

    public function onDropItem(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
			$event->setCancelled();
			$player->sendMessage("§7[§bStaffMode§7] §cCannot drop items while frozen!");
		}
		if(in_array($player->getName(), $this->plugin->staffmodestatus)) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot drop items while in StaffMode!");
        }
    }

    //Prevent commands when frozen and chat when muted

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();
		if(in_array($player->getName(), $this->plugin->frozenstatus)) {
            if(($message !== "/staffmode")and($message !== "/sm")and(substr($message, 0, 1) == "/")){
                $event->setCancelled();
                $player->sendMessage("§7[§bStaffMode§7] §cCannot run commands while frozen!");
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

    //Prevent receiving damage when frozen

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
		if (!$victim instanceof Player) return;
		if(in_array($victim->getName(), $this->plugin->frozenstatus)) {
			$event->setCancelled();
			$attacker->sendMessage("§7[§bStaffMode§7] §cCannot attack a frozen player!");
		}
		if(in_array($attacker->getName(), $this->plugin->frozenstatus)) {
			$event->setCancelled();
			$attacker->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
		}
		if(in_array($attacker->getName(), $this->plugin->staffmodestatus)) {
			if ($attacker->getInventory()->getItemInHand()->getId() == Item::PACKED_ICE) {
				if(in_array($victim->getName(), $this->plugin->frozenstatus)) {
					$victim->setImmobile(false);
					$victim->sendTitle("§eUnfrozen", "§aYou can now move again.");
					unset($this->plugin->frozenstatus[array_search($victim->getName(), $this->plugin->frozenstatus)]);
					$attacker->sendMessage("§7[§bStaffMode§7] §aSuccessfully froze player ".$victim->getName());
				} else {
					$victim->setImmobile(true);
					$victim->sendTitle("§bFrozen", "§cDo not log off and listen to staff!");
					$this->plugin->frozenstatus[] = $victim->getName();
					$attacker->sendMessage("§7[§bStaffMode§7] §aSuccessfully unfroze player ".$victim->getName());
				}
			} else {
				$attacker->sendMessage("§7[§bStaffMode§7] §aThe name of the player you just hit is: ".$event->getEntity()->getName());
			}
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
                } elseif($item->getId() == Item::CHEST) {
					if ($player->hasPermission("staffmode.tools.inventorymanager")) {
						$this->plugin->InventoryManagerForm->InventoryManagerForm($player);
					} else {
						$player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
					}
				} elseif($item->getId() == Item::PACKED_ICE) {
                    if ($player->hasPermission("staffmode.tools.freeze")) {
                        $this->plugin->FreezeForm->FreezingForm($player);
                    } else {
                        $player->sendMessage("§7[§bStaffMode§7] §cYou do not have permission to use this tool.");
                    }
                } elseif($item->getId() == Item::GOLDEN_BOOTS) {
                    if ($player->hasPermission("staffmode.tools.kick")) {
                        $this->plugin->KickForm->KickingForm($player);
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