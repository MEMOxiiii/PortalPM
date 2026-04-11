# PortalPM

A PocketMine-MP 5.x plugin that connects your PM5 server to a [Portal](https://github.com/Paroxity/portal) proxy via TCP socket, enabling cross-server player transfers and server management across your network.

> **Updated & maintained fork** — fully ported to **PocketMine-MP API 5.0** with support for the latest Portal proxy (Go-based).

---

## Features

- **Cross-server player transfers** — seamlessly move players between servers connected to the same Portal proxy.
- **Server discovery** — query the proxy for a live list of all connected servers and their player counts.
- **Player lookup** — find which server any player is on across the entire network.
- **Player latency tracking** — receive real-time latency updates for connected players from the proxy.
- **Legacy auth interception** — automatically converts gophertunnel's legacy authentication format to PM5's expected format, enabling Go-based proxy (Dragonfly/gophertunnel) → PM5 connections.
- **Built-in commands** — `/transfer`, `/server`, `/servers` with configurable permissions.
- **Fully configurable** — enable/disable individual commands, set proxy credentials, and name your server instance.

---

## Compatibility

| Requirement | Version |
|---|---|
| PocketMine-MP | **5.x** (API 5.0.0+) |
| PHP | **8.1+** |
| Portal Proxy | Go-based Portal proxy |

---

## Installation

1. Download the latest `.phar` from [Releases](https://github.com/MEMOxiiii/PortalPM/releases) or build from source.
2. Place the `.phar` file in your PocketMine-MP server's `plugins/` folder.
3. Start the server once to generate the config file, then stop it.
4. Edit `plugin_data/Portal/config.yml` (see [Configuration](#configuration) below).
5. Start the server again.

---

## Configuration

The config file is located at `plugin_data/Portal/config.yml`:

```yaml
# The address of the Portal proxy
proxy-address: "127.0.0.1"

socket:
  # The TCP port the Portal proxy is listening on
  port: 19131
  # The secret key — must match the Portal proxy's config
  secret: ""

server:
  # The name this server will register as on the proxy
  name: "Hub1"

command:
  # Enable or disable all commands
  enable: true
  commands:
    # Enable/disable individual commands
    transfer: true
    server: true
```

### Configuration Details

| Key | Type | Default | Description |
|---|---|---|---|
| `proxy-address` | string | `"127.0.0.1"` | IP address of the Portal proxy. Use `127.0.0.1` if on the same machine. |
| `socket.port` | int | `19131` | TCP port the proxy's socket server listens on. |
| `socket.secret` | string | `""` | Authentication secret. **Must match** the `secret` in your Portal proxy config. |
| `server.name` | string | `"Hub1"` | The name this server registers as on the proxy. Must be unique per server. |
| `command.enable` | bool | `true` | Master toggle for all built-in commands. |
| `command.commands.transfer` | bool | `true` | Enable the `/transfer` command. |
| `command.commands.server` | bool | `true` | Enable the `/server` command. |

---

## Commands

| Command | Description | Permission |
|---|---|---|
| `/transfer <server>` | Transfer yourself to another server | `portal.command.transfer` |
| `/transfer <server> <player>` | Transfer another player to a server | `portal.command.transfer` |
| `/server` | Check which server you are on | `portal.command.server.self` |
| `/server <player>` | Check which server another player is on | `portal.command.server.other` |
| `/servers` | List all servers connected to the proxy | `portal.command.servers` |

### Permissions

| Permission | Default | Description |
|---|---|---|
| `portal.command.transfer` | **op** | Allow using the transfer command |
| `portal.command.server` | **op** | Parent permission for server command |
| `portal.command.server.self` | **true** | Allow checking your own server |
| `portal.command.server.other` | **op** | Allow checking other players' server |
| `portal.command.servers` | **op** | Allow listing all servers |

---

## Server Settings (Required)

If you are connecting through a **Go-based proxy** (gophertunnel / Dragonfly), you need to adjust your `pocketmine.yml`:

```yaml
player:
  # Disable XUID verification — required because the proxy uses self-signed auth
  verify-xuid: false
```

> **Why?** The Go proxy uses `EnableLegacyAuth` mode which sends self-signed JWTs without Xbox Live XUID. The plugin intercepts and converts the auth format, but XUID verification must be disabled.

---

## How It Works

1. **Connection** — On server start, the plugin opens a TCP socket connection to the Portal proxy and authenticates using the configured secret.
2. **Registration** — After successful authentication, the server registers itself with its name and address.
3. **Auth Interception** — When a player connects through the proxy, the plugin intercepts the `LoginPacket` and converts the legacy auth format (`{"chain":["jwt"]}`) to PM5's expected format (`{"AuthenticationType":2, "Certificate":"...", "Token":""}`).
4. **Communication** — The plugin communicates with the proxy using a custom binary packet protocol over TCP for transfers, player lookups, and server list queries.

---

## API Usage

Developers can use the Portal API in their plugins:

```php
use paroxity\portal\Portal;

// Get the Portal instance
$portal = Portal::getInstance();

// Transfer a player
$portal->transferPlayer($player, "lobby");

// Transfer by UUID
$portal->transferPlayerByUUID($uuid, "survival", function(?Player $player, int $status, string $error) {
    // Handle response
});

// Find a player across all servers
$portal->findPlayer(null, "PlayerName", function(UuidInterface $uuid, string $name, bool $online, string $server) {
    if ($online) {
        // Player is on $server
    }
});

// Get list of all servers
$portal->requestServerList(function(array $servers) {
    foreach ($servers as $entry) {
        echo $entry->getName() . " - " . $entry->getPlayerCount() . " players\n";
    }
});

// Get player latency
$latency = $portal->getPlayerLatency($player);
```

---

## Issues

If you encounter any problems, please [open an issue](https://github.com/MEMOxiiii/PortalPM/issues) with:
- Your PocketMine-MP version
- The error message or log output
- Your `config.yml` (remove the secret before sharing)
- Steps to reproduce the issue