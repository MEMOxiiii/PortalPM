<?php
declare(strict_types = 1);

namespace paroxity\portal\command;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use paroxity\portal\packet\TransferResponsePacket;
use paroxity\portal\Portal;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;

class TransferCommand extends BaseCommand
{
	public function __construct(private Portal $portal)
	{
		parent::__construct(
			$portal,
			"transfer",
			"Fast transfer player to another server",
		);
		$this->setPermission("portal.command.transfer");
	}

	protected function prepare(): void
	{
		$this->registerArgument(0, new RawStringArgument("server"));
		$this->registerArgument(1, new RawStringArgument("player", true));
	}

	/**
	 * @param mixed[] $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		$server = $args["server"];

		if(!isset($args["player"])) {
			if(!$sender instanceof Player) {
				$sender->sendMessage(TextFormat::RED . "Usage: /transfer <server> <player>");
				return;
			}
			$this->transfer($sender, $sender->getUniqueId(), $server);
			return;
		}

		$playerName = $args["player"];
		$player = $this->portal->getServer()->getPlayerByPrefix($playerName);
		if(!$player instanceof Player){
			$this->portal->findPlayer(null, $playerName, function(UuidInterface $uuid, string $foundName, bool $online, string $currentServer) use ($sender, $server): void {
				if(!$online) {
					$sender->sendMessage(TextFormat::RED . "Player could not be found");
					return;
				}

				$this->transfer($sender, $uuid, $server);
			});
			return;
		}

		$this->transfer($sender, $player->getUniqueId(), $server);
	}

	private function transfer(CommandSender $sender, UuidInterface $uuid, string $server): void
	{
		$this->portal->transferPlayerByUUID($uuid, $server, function(?Player $player, int $status, string $error) use ($sender, $server): void {
			switch($status) {
				case TransferResponsePacket::RESPONSE_SUCCESS:
					if($player !== null && $player->isOnline()) {
						$player->sendMessage(TextFormat::GREEN . "You were transferred to " . $server);
					}
					$sender->sendMessage(TextFormat::GREEN . "Player was transferred to " . $server . " successfully");
					$this->portal->getLogger()->info("Transfer to " . $server . " by " . $sender->getName());
				break;

				case TransferResponsePacket::RESPONSE_SERVER_NOT_FOUND:
					$sender->sendMessage(TextFormat::RED . "Server: " . $server . " not found");
				break;

				case TransferResponsePacket::RESPONSE_ALREADY_ON_SERVER:
					$sender->sendMessage(TextFormat::RED . "Player is already on that server");
				break;

				case TransferResponsePacket::RESPONSE_PLAYER_NOT_FOUND:
					$sender->sendMessage(TextFormat::RED . "Player could not be found");
				break;

				case TransferResponsePacket::RESPONSE_ERROR:
					$sender->sendMessage(TextFormat::RED . "An error occurred while trying to transfer the player");
					$sender->sendMessage(TextFormat::RED . "Error: " . $error);
				break;
			}
		});
	}
}