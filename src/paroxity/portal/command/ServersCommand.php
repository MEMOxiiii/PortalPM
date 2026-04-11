<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use paroxity\portal\packet\types\ServerListEntry;
use paroxity\portal\Portal;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ServersCommand extends Command
{
	private Portal $plugin;

	public function __construct(Portal $plugin)
	{
		parent::__construct("servers", "Get a list of servers on the proxy", "/servers");
		$this->plugin = $plugin;
		$this->setPermission("portal.command.servers");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool
	{
		if(!$this->testPermission($sender)) {
			return true;
		}

		$this->plugin->requestServerList(function(array $servers) use($sender) {
			$serverList = array_map(fn(ServerListEntry $server) => $server->getName() . TextFormat::GREEN . " (" . $server->getPlayerCount() . " players)" . TextFormat::RESET, $servers);
			$sender->sendMessage("There are " . TextFormat::GREEN . count($servers) . TextFormat::RESET . " servers connected to the proxy: " . implode(", ", $serverList));
		});

		return true;
	}
}