<?php


namespace Mateo\SimpleHomeMost\Tasks;


use Mateo\SimpleHomeMost\Main;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;

class TeleportTask extends Task
{
    private $uuid;
    private int $time = 10;
    private Position $position;
    private ?Position $position2 = null;

    public function __construct($uuid, $time, Position $position)
    {
        $this->uuid = $uuid;
        $this->time = $time;
        $this->position = $position;

    }

    public function onRun(): void
    {
        $player = Server::getInstance()->getPlayerByRawUUID($this->uuid);

        if (!is_null($player)) {
            if (is_null($this->position2)) {
                $this->position2 = $player->getPosition();
            } else {
                $px = intval($player->getPosition()->getX());
                $py = intval($player->getPosition()->getY());
                $pz = intval($player->getPosition()->getZ());

                $x = intval($this->position2->getX());
                $y = intval($this->position2->getY());
                $z = intval($this->position2->getZ());

                if (($px !== $x) || ($py !== $y) || ($pz !== $z)) {
                    $player->sendMessage('§c» The teleportation has been cancelled.');
                    $this->getHandler()->cancel();
                }

                if ($this->time <= 0) {
                    $player->teleport($this->position);
                    $this->getHandler()->cancel();
                } else {
                    $player->sendPopup("§a» Teleport in " . $this->time);
                    $this->time--;
                }
            }
        }else $this->getHandler()->cancel();
    }
}