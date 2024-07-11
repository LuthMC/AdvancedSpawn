<?php

namespace Luthfi\AdvancedSpawn;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\level\sound\NoteBlockSound;
use pocketmine\Server;

class Main extends PluginBase {

    /** @var Config */
    private $spawnConfig;

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->spawnConfig = $this->getConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($sender instanceof Player) {
            switch ($command->getName()) {
                case "setspawn":
                    if (count($args) < 1) {
                        $sender->sendMessage("Usage: /setspawn <name>");
                        return false;
                    }
                    $spawnName = array_shift($args);
                    $this->setSpawn($sender, $spawnName);
                    return true;

                case "spawn":
                    if (count($args) < 1) {
                        $sender->sendMessage("Usage: /spawn <name>");
                        return false;
                    }
                    $spawnName = array_shift($args);
                    $this->teleportToSpawn($sender, $spawnName);
                    return true;
            }
        } else {
            $sender->sendMessage("This command can only be used in-game.");
        }
        return false;
    }

    private function setSpawn(Player $player, string $name) {
        $level = $player->getLevel();
        $pos = $player->asVector3();
        $this->spawnConfig->setNested("AdvancedSpawn.$name.enabled", true);
        $this->spawnConfig->setNested("AdvancedSpawn.$name.x", $pos->getX());
        $this->spawnConfig->setNested("AdvancedSpawn.$name.y", $pos->getY());
        $this->spawnConfig->setNested("AdvancedSpawn.$name.z", $pos->getZ());
        $this->spawnConfig->setNested("AdvancedSpawn.$name.world", $level->getFolderName());
        $this->spawnConfig->save();

        $player->sendMessage("Spawn '$name' successfully set!");
    }

    private function teleportToSpawn(Player $player, string $name) {
        if ($this->spawnConfig->exists("AdvancedSpawn.$name") && $this->spawnConfig->get("AdvancedSpawn.$name.enabled")) {
            $x = $this->spawnConfig->get("AdvancedSpawn.$name.x");
            $y = $this->spawnConfig->get("AdvancedSpawn.$name.y");
            $z = $this->spawnConfig->get("AdvancedSpawn.$name.z");
            $worldName = $this->spawnConfig->get("AdvancedSpawn.$name.world");

            $level = $this->getServer()->getLevelByName($worldName);
            if ($level instanceof Level) {
                $pos = new Position($x, $y, $z, $level);

                $effectBlindness = new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 20 * 5, 1);
                $effectNausea = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 20 * 5, 1);
                $player->addEffect($effectBlindness);
                $player->addEffect($effectNausea);

                $player->teleport($pos);
                $level->addSound(new NoteBlockSound($pos));

                $player->addTitle("You Successfully Teleport To $name");

                $player->sendMessage("Teleported to spawn '$name'.");
            } else {
                $player->sendMessage("World '$worldName' not found.");
            }
        } else {
            $player->sendMessage("Spawn '$name' does not exist or is not enabled.");
        }
    }
}
