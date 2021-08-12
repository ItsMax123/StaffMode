<?php

namespace Max\StaffMode;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\scheduler\Task;
use pocketmine\Server;

use function in_array;

class StaffModeTask extends Task {

	public function __construct($pl) {
		$this->plugin = $pl;
	}

	public function onRun(int $currentTick){
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player->spawned){
				if(in_array($player->getName(), $this->plugin->staffmodestatus)) {
					$player->sendTip("§k§c!§3!§c!§r §aYou are in staffmode §k§c!§3!§c!");
					$player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), null, 0, false, true));
				}
			}
		}
	}
}