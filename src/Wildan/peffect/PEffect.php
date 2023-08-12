<?php

namespace Wildan\peffect;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\utils\Config;

class PEffect extends PluginBase {

    /** @var Config */
    private $config;

    private $cooldowns = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "peffect") {
            if (!$sender instanceof Player) {
                $sender->sendMessage($this->config->get("in_game_only_message"));
                return true;
            }

            if (!$sender->hasPermission("peffect.command.use")) {
                $sender->sendMessage($this->config->get("no_permission_message"));
                return true;
            }

            if (count($args) < 1) {
                $sender->sendMessage($this->config->get("usage_message"));
                return true;
            }

            $effectType = strtolower($args[0]);

            if ($this->isCooldownActive($sender)) {
                $remainingTime = $this->cooldowns[$sender->getName()] - time();
                $cooldownMessage = str_replace("{remaining-time}", $remainingTime, $this->config->get("cooldown_message"));
                $sender->sendMessage($cooldownMessage);
                return true;
            }

            $effectDuration = $this->config->get("effect_duration", 120);
            $effectLevel = $this->config->get("effect_level", 1);

            $effect = $this->createEffect($effectType, $effectDuration, $effectLevel);

            if ($effect === null) {
                $sender->sendMessage($this->config->get("invalid_effect_message"));
                return true;
            }

            $player = $sender;
            $player->getEffects()->add($effect);
            $effectName = ucfirst($effectType);
            $effectAppliedMessage = str_replace(["{effect}", "{duration}"], [$effectName, $effectDuration], $this->config->get("effect_applied_message"));
            $player->sendMessage($effectAppliedMessage);

            $this->cooldowns[$player->getName()] = time() + $this->config->get("effect_cooldown", 60); // Default cooldown of 60 seconds

            return true;
        }
        return false;
    }

    private function createEffect(string $effectType, int $duration, int $level): ?EffectInstance {
        switch ($effectType) {
            case "haste":
                return new EffectInstance(VanillaEffects::HASTE(), 20 * $duration, $level);
            case "speed":
                return new EffectInstance(VanillaEffects::SPEED(), 20 * $duration, $level);
            case "jump":
                return new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * $duration, $level);
            case "strength":
                return new EffectInstance(VanillaEffects::STRENGTH(), 20 * $duration, $level);
            case "regeneration":
                return new EffectInstance(VanillaEffects::REGENERATION(), 20 * $duration, $level);
            default:
                return null;
        }
    }

    private function isCooldownActive(Player $player): bool {
        $playerName = $player->getName();
        if (isset($this->cooldowns[$playerName]) && $this->cooldowns[$playerName] > time()) {
            return true;
        }
        return false;
    }
}
