<?php

declare(strict_types=1);

namespace Max\StaffMode;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

//use pocketmine\utils\{Config, TextFormat};
use pocketmine\{Player, Server};
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\{TextPacket, LoginPacket};

use pocketmine\event\player\{PlayerInteractEvent, PlayerCommandPreprocessEvent, PlayerDropItemEvent, PlayerKickEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerPreLoginEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent, EntityLevelChangeEvent};
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
use pocketmine\event\server\DataPacketReceiveEvent;

use Max\StaffMode\ui\{ReportForm, TeleportForm, PlayerInfoForm, WarnForm, FreezeForm, MuteForm, KickForm, BanForm};

class EventListener implements Listener {

    public function __construct($pl) {
        $this->plugin = $pl;
        $pl->getServer()->getPluginManager()->registerEvents($this, $pl);
    }

    //IF player is not banned, save all their user info.

    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        $alias = ["IPAddress" => null, "DeviceId" => null, "SelfSignedId" => null, "ClientRandomId" => null];
        if($packet instanceof LoginPacket){
            if(!Player::isValidUserName($packet->username)){
                return;
            }
            if($this->plugin->banList->exists(strtolower($player->getName()))){
                if (($this->plugin->banList->get(strtolower($player->getName()))["unbantime"] < time())OR($this->plugin->banList->get(strtolower($player->getName()))["unbantime"] - $this->plugin->banList->get(strtolower($player->getName()))["time"] != -1)) {
                    $this->plugin->alias->set(strtolower($packet->username), ["IPAddress" => (string)$player->getAddress(), "DeviceId" => $packet->clientData['DeviceId'], "SelfSignedId" => $packet->clientData['SelfSignedId'], "ClientRandomId" => (string)$packet->clientData['ClientRandomId']]);
                    $this->plugin->alias->save();
                }
            } else {
                $this->plugin->alias->set(strtolower($packet->username), ["IPAddress" => (string)$player->getAddress(), "DeviceId" => $packet->clientData['DeviceId'], "SelfSignedId" => $packet->clientData['SelfSignedId'], "ClientRandomId" => (string)$packet->clientData['ClientRandomId']]);
                $this->plugin->alias->save();
            }
        }
    }

    //Check if player is banned and kick him if he is:

    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
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
            if (count($bannedplayerinfo) == 7) {
                $BannedAddress = $bannedplayerinfo["IPAddress"];
                $BannedDeviceId = $bannedplayerinfo["DeviceId"];
                $BannedSelfSignedId = $bannedplayerinfo["SelfSignedId"];
                $BannedClientRandomId = $bannedplayerinfo["ClientRandomId"];
    
                $PlayerAddress = $this->plugin->alias->get(strtolower($player->getName()))["IPAddress"];
                $PlayerDeviceId = $this->plugin->alias->get(strtolower($player->getName()))["DeviceId"];
                $PlayerSelfSignedId = $this->plugin->alias->get(strtolower($player->getName()))["SelfSignedId"];
                $PlayerClientRandomId = $this->plugin->alias->get(strtolower($player->getName()))["ClientRandomId"];
                if(($BannedAddress == $PlayerAddress)OR($BannedDeviceId == $PlayerDeviceId)OR($BannedSelfSignedId == $PlayerSelfSignedId)OR($BannedClientRandomId == $PlayerClientRandomId)) {
                    $staff = $bannedplayerinfo[2];
                    $reason = $bannedplayerinfo[3];
                    if((((int)$bannedplayerinfo[0]) - ((int)$bannedplayerinfo[1])) == -1) {
                        $player->close("", "§cYou are banned!\n§rBy: ".$staff."\nReason: ".$reason."\nDuration: Forever");
                    } else {
                        $time = ((int)$bannedplayerinfo[0] - time());
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

    /**
     * @param PlayerJoinEvent $event
     * @priority HIGHEST
     */

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $this->plugin->staffmodestatus[$player->getName()] = False;
        $this->plugin->frozenstatus[$player->getName()] = False;
        if($this->plugin->config->get("SilentJoin")){
            if($player->hasPermission("staffmode.silent")) {
                $event->setJoinMessage(null);
            }
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onEntityTeleport(EntityLevelChangeEvent $event) : void{
        if ($this->plugin->config->get("Allow-World-Change")) return;
        $player = $event->getEntity();
        if($player instanceof Player){
            $from = $event->getOrigin();
            $to = $event->getTarget();
            if($from !== $to){
                if($this->plugin->staffmodestatus[$player->getName()]) {
                    $event->setCancelled();
                    $player->sendMessage("§7[§bStaffMode§7] §cCannot change world while in staffmode.");
                }
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
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

    /**
     * @param PlayerKickEvent $event
     * @priority LOWEST
     */

    public function onKick(PlayerKickEvent $event){
        $player = $event->getPlayer();
        $this->plugin->exitstaffmode($player);
    }

    public function BlockBreakEvent(BlockBreakEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->frozenstatus[$player->getName()]) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->frozenstatus[$player->getName()]) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    public function PlayerDropItemEvent(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->staffmodestatus[$player->getName()]) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot drop items while in StaffMode!");
        }
        if($this->plugin->frozenstatus[$player->getName()]) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
    }

    public function PlayerCommandPreprocessEvent(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if($this->plugin->frozenstatus[$player->getName()]) {
            if(($message !== "/staffmode")and(substr($message, 0, 1) == "/")){
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

    public function EntityDamageEvent(EntityDamageEvent $event){
        $player = $event->getEntity();
        if ($player instanceof Player) {
            if($this->plugin->frozenstatus[$player->getName()]) {
                $event->setCancelled();
            }
        }
    }

    public function EntityDamageByEntityEvent(EntityDamageByEntityEvent $event){
        $attacker = $event->getDamager();
        $victim = $event->getEntity();
        if ($attacker instanceof Player) {
            if($this->plugin->frozenstatus[$attacker->getName()]) {
                $event->setCancelled();
                $attacker->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
            }
            if($this->plugin->staffmodestatus[$attacker->getName()]) {
                if ($attacker->getInventory()->getItemInHand()->getId() == Item::PACKED_ICE) {
                    if ($this->plugin->frozenstatus[$victim->getName()]) {
                        $victim->setImmobile(false);
                        $victim->sendTitle("§eUnfrozen", "§aYou can now move again.");
                        $this->plugin->frozenstatus[$victim->getName()] = False;
                        $attacker->sendMessage("§7[§bStaffMode§7] §aSuccessfully froze player ".$victim->getName());
                        return;
                    } else {
                        $victim->setImmobile(true);
                        $victim->sendTitle("§bFrozen", "§cDo not log off and listen to staff!");
                        $this->plugin->frozenstatus[$victim->getName()] = True;
                        $attacker->sendMessage("§7[§bStaffMode§7] §aSuccessfully unfroze player ".$victim->getName());
                        return;
                    }
                } else {
                    $attacker->sendMessage("§7[§bStaffMode§7] §aThe name of the player you just hit is: ".$event->getEntity()->getName());
                }
            }
        }
        if ($victim instanceof Player) {
            if($this->plugin->frozenstatus[$victim->getName()]) {
                $event->setCancelled();
                $attacker->sendMessage("§7[§bStaffMode§7] §cCannot attack a frozen player!");
            }
        }
    }

    public function PlayerInteractEvent(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->frozenstatus[$player->getName()]) {
            $event->setCancelled();
            $player->sendMessage("§7[§bStaffMode§7] §cCannot do that while frozen!");
        }
        $item = $event->getItem();
        $nbt = $item->getNamedTagEntry("staffmode");
        if($nbt === null) return;
        if($this->plugin->staffmodestatus[$player->getName()]) {
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
                } elseif($item->getId() == Item::GOLDEN_SWORD) {
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