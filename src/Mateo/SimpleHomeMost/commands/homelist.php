<?php

namespace Mateo\SimpleHomeMost\commands;

use Mateo\SimpleHomeMost\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

class homelist extends Command
{
    public function __construct(string $name, Plugin $owner)
    {
        parent::__construct($name, $owner);
        $this->setDescription("List all home command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) Main::getDataHandler()->listHome($sender->getXuid());
    }
}