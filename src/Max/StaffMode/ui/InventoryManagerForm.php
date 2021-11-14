<?php

declare(strict_types=1);

namespace Max\StaffMode\ui;

use jojoe77777\FormAPI\{SimpleForm, CustomForm};
use pocketmine\{Player, Server};
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use CortexPE\DiscordWebhookAPI\{Message, Webhook, Embed};

use muqsit\invmenu\InvMenu;

class InventoryManagerForm {

	public function __construct($pl) {
		$this->plugin = $pl;
	}

	public function InventoryManagerForm(Player $player) : void {
		$form = new SimpleForm(function (Player $player, $data) {
			if($data === null) {
				return true;
			}

			if( $data == "invsee") {
				self::InvseeForm($player);
			} elseif( $data == "enderchestsee") {
				self::EnderchestSeeForm($player);
			} elseif($data == "invclear") {
				self::InvClearForm($player);
			} elseif($data == "enderchestclear") {
				self::EnderChestClearForm($player);
			} elseif($data == "armorclear") {
				self::ArmorClearForm($player);
			}
			return true;
		});
		$form->setTitle("Inventory Manager Menu");
		if ($player->hasPermission("staffmode.tools.inventorymanager.invspy")) {
			$form->addButton("Inventory Spy", -1, "", "invsee");
		}
		if ($player->hasPermission("staffmode.tools.inventorymanager.enderchestspy")) {
			$form->addButton("EnderChest Spy", -1, "", "enderchestsee");
		}
		if ($player->hasPermission("staffmode.tools.inventorymanager.invclear")) {
			if ($this->plugin->config->get("Allow-Inventory-Clear")) {
				$form->addButton("Inventory Clear", -1, "", "invclear");
			}
		}
		if ($player->hasPermission("staffmode.tools.inventorymanager.enderchestclear")) {
			if ($this->plugin->config->get("Allow-EnderChest-Clear")) {
				$form->addButton("EnderChest Clear", -1, "", "enderchestclear");
			}
		}
		if ($player->hasPermission("staffmode.tools.inventorymanager.armorclear")) {
			if ($this->plugin->config->get("Allow-Armor-Clear")) {
				$form->addButton("Armor Clear", -1, "", "armorclear");
			}
		}

		$player->sendForm($form);
	}

	public function InvseeForm(Player $player) : void {
		$playernamelist = $this->plugin->getonlineplayersname();
		$form = new CustomForm(function (Player $player, $data) use ($playernamelist) {
			if($data === null) {
				return true;
			}

			if (count($playernamelist) == 0) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			}

			if(Server::getInstance()->getPlayer($playernamelist[$data["name"]]) === null) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			} else {
				$targetname = $playernamelist[$data["name"]];
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
		$form->setTitle("Inventory Spy Form");
		$form->addDropdown("Pick the player you want to see", $playernamelist, null, "name");
		$player->sendForm($form);
	}

	public function EnderchestSeeForm(Player $player) : void {
		$playernamelist = $this->plugin->getonlineplayersname();
		$form = new CustomForm(function (Player $player, $data) use ($playernamelist) {
			if($data === null) {
				return true;
			}

			if (count($playernamelist) == 0) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			}

			if(Server::getInstance()->getPlayer($playernamelist[$data["name"]]) === null) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			} else {
				$targetname = $playernamelist[$data["name"]];
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
		$form->setTitle("Ender Chest Spy Form");
		$form->addDropdown("Pick the player you want to see", $playernamelist, null, "name");
		$player->sendForm($form);
	}

	public function InvClearForm(Player $player) : void {
		$playernamelist = $this->plugin->getonlineplayersname();
		$form = new CustomForm(function (Player $player, $data) use ($playernamelist) {
			if($data === null) {
				return true;
			}

			if($data["reason"] == "") {
				$player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
				return true;
			}

			if (count($playernamelist) == 0) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			}

			if(Server::getInstance()->getPlayer($playernamelist[$data["name"]]) === null) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			} elseif ($playernamelist[$data["name"]] == $player->getName()) {
				$player->sendMessage("§7[§bStaffMode§7] §cCannot inventory clear yourself!");
				return true;
			} else {
				$targetname = $playernamelist[$data["name"]];
			}
			$target = Server::getInstance()->getPlayer($targetname);
			$target->getInventory()->clearAll();
			$paper = Item::get(Item::PAPER, 0, 1);
			$paper->setCustomName("§cYour inventory was Cleared by staff!");
			$paper->setLore(["Reason: ".$data["reason"]]);
			$target->getInventory()->addItem($paper);

			$player->sendMessage("§7[§bStaffMode§7] §aSuccessfully cleared the inventory of player ".$targetname);

			if ($this->plugin->config->get("DiscordWebhooks-Inventory-Clears")) {
				$webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Inventory-Clears-Link"));
				$msg = new Message();
				$msg->setUsername("StaffMode-Inventory-Clears");
				$msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
				$embed = new Embed();
				$embed->setTitle($targetname." was inventory cleared");
				$embed->setColor(0x00FF00);
				$embed->addField("Inventory Cleared by", $player->getName());
				$embed->addField("Reason", $data["reason"]);
				$msg->addEmbed($embed);
				$webHook->send($msg);
			}
			return true;
		});
		$form->setTitle("Inventory Clear Form");
		$form->addDropdown("Pick the player you want to inventory clear", $playernamelist, null, "name");
		$form->addInput("Reason of inventory clear:", "Ex.: Glitched Items", "", "reason");
		$player->sendForm($form);
	}

	public function EnderChestClearForm(Player $player) : void {
		$playernamelist = $this->plugin->getonlineplayersname();
		$form = new CustomForm(function (Player $player, $data) use ($playernamelist) {
			if($data === null) {
				return true;
			}

			if($data["reason"] == "") {
				$player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
				return true;
			}

			if (count($playernamelist) == 0) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			}

			if(Server::getInstance()->getPlayer($playernamelist[$data["name"]]) === null) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			} elseif ($playernamelist[$data["name"]] == $player->getName()) {
				$player->sendMessage("§7[§bStaffMode§7] §cCannot ender chest clear yourself!");
				return true;
			} else {
				$targetname = $playernamelist[$data["name"]];
			}
			$target = Server::getInstance()->getPlayer($targetname);
			$target->getEnderChestInventory()->clearAll();
			$paper = Item::get(Item::PAPER, 0, 1);
			$paper->setCustomName("§cYour EnderChest was Cleared by staff!");
			$paper->setLore(["Reason: ".$data["reason"]]);
			$target->getEnderChestInventory()->addItem($paper);


			$player->sendMessage("§7[§bStaffMode§7] §aSuccessfully cleared the EnderChest of player ".$targetname);

			if ($this->plugin->config->get("DiscordWebhooks-EnderChest-Clears")) {
				$webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-EnderChest-Clears-Link"));
				$msg = new Message();
				$msg->setUsername("StaffMode-EnderChest-Clears");
				$msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
				$embed = new Embed();
				$embed->setTitle($targetname." was EnderChest cleared");
				$embed->setColor(0x00FF00);
				$embed->addField("EnderChest Cleared by", $player->getName());
				$embed->addField("Reason", $data["reason"]);
				$msg->addEmbed($embed);
				$webHook->send($msg);
			}
			return true;
		});
		$form->setTitle("EnderChest Clear Form");
		$form->addDropdown("Pick the player you want to ender chest clear", $playernamelist, null, "name");
		$form->addInput("Reason of ender chest clear:", "Ex.: Glitched Items", "", "reason");
		$player->sendForm($form);
	}

	public function ArmorClearForm(Player $player) : void {
		$playernamelist = $this->plugin->getonlineplayersname();
		$form = new CustomForm(function (Player $player, $data) use ($playernamelist) {
			if($data === null) {
				return true;
			}

			if($data["reason"] == "") {
				$player->sendMessage("§7[§bStaffMode§7] §cYou must specify a reason!");
				return true;
			}

			if (count($playernamelist) == 0) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			}

			if(Server::getInstance()->getPlayer($playernamelist[$data["name"]]) === null) {
				$player->sendMessage("§7[§bStaffMode§7] §cPlayer not found!");
				return true;
			} elseif ($playernamelist[$data["name"]] == $player->getName()) {
				$player->sendMessage("§7[§bStaffMode§7] §cCannot armor clear yourself!");
				return true;
			} else {
				$targetname = $playernamelist[$data["name"]];
			}
			$target = Server::getInstance()->getPlayer($targetname);
			$target->getArmorInventory()->clearAll();

			$player->sendMessage("§7[§bStaffMode§7] §aSuccessfully cleared the armor of player ".$targetname);

			if ($this->plugin->config->get("DiscordWebhooks-Armor-Clears")) {
				$webHook = new Webhook($this->plugin->config->get("DiscordWebhooks-Armor-Clears-Link"));
				$msg = new Message();
				$msg->setUsername("StaffMode-Armor-Clears");
				$msg->setAvatarURL("https://www.gstatic.com/images/branding/product/1x/admin_512dp.png");
				$embed = new Embed();
				$embed->setTitle($targetname." was armor cleared");
				$embed->setColor(0x00FF00);
				$embed->addField("Armor Cleared by", $player->getName());
				$embed->addField("Reason", $data["reason"]);
				$msg->addEmbed($embed);
				$webHook->send($msg);
			}
			return true;
		});
		$form->setTitle("Armor Clear Form");
		$form->addDropdown("Pick the player you want to armor clear", $playernamelist, null, "name");
		$form->addInput("Reason of armor clear:", "Ex.: Glitched Items", "", "reason");
		$player->sendForm($form);
	}
}