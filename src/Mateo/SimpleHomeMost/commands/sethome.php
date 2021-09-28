<?php

namespace Mateo\SimpleHomeMost\commands;

use Mateo\SimpleHomeMost\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

class sethome extends Command
{
    public function __construct(string $name, Plugin $owner)
    {
        parent::__construct($name, $owner);
        $this->setDescription("Set home command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player)
        {
            if (!isset($args[0])) {
                $sender->sendMessage("§c» Command usage: /sethome <home>");
                return;
            }
            Main::getDataHandler()->addHome($sender->getXuid(), $args[0], $sender->getPosition());
        }
    }
}