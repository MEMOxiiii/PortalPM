<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use paroxity\portal\Portal;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;
use function strtolower;

class ServerCommand extends Command
{
	private Portal $plugin;

	public function __construct(Portal $plugin)
	{
		parent::__construct("server", "Check which server you are on currently", "/server [player]");
		$this->plugin = $plugin;
		$this->setPermission("portal.command.server");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if(!$this->testPermission($sender)) {
			return true;
		}

		$target = $sender->getName();
		if($sender instanceof ConsoleCommandSender && !isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . "Usage: /server <player>");
			return true;
		}
		if(isset($args[0]) && !$sender->hasPermission("portal.command.server.other")) {
			$sender->sendMessage(TextFormat::RED . "You don't have the permission to check server of other player");
			return true;
		}
		if(isset($args[0])) {
			$target = $args[0];
		}

		$this->plugin->findPlayer(null, $target, function(UuidInterface $uuid, string $playerName, bool $online, string $server) use ($sender): void {
			if(!$online) {
				$sender->sendMessage(TextFormat::RED . "Player: $playerName could not be found");
				return;
			}

			if(strtolower($sender->getName()) === strtolower($playerName)) {
				$sender->sendMessage(TextFormat::GREEN . "You are currently on $server");
			}else{
				$sender->sendMessage(TextFormat::GREEN . "Player: $playerName is currently on $server");
			}
		});

		return true;
	}
}