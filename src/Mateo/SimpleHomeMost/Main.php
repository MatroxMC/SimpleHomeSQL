<?php

declare(strict_types=1);

namespace Mateo\SimpleHomeMost;

use Mateo\SimpleHomeMost\commands\delhome;
use Mateo\SimpleHomeMost\commands\home;
use Mateo\SimpleHomeMost\commands\homelist;
use Mateo\SimpleHomeMost\commands\sethome;
use Mateo\SimpleHomeMost\events\PlayerListener;
use Mateo\SimpleHomeMost\handlers\DataHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Main extends PluginBase{


    private static Main $instance;
    private static DataHandler $datahandler;

    public function onEnable()
    {
       Main::$instance = $this;
       Main::setCommands();
       Main::setHandler();
       Main::setListener();
    }

    public function onDisable()
    {
        Main::getDataHandler()->closeDatabase();
    }

    public static function getInstance(): Main
    {
        return self::$instance;
    }

    public static function getDataHandler(): DataHandler
    {
        return Main::$datahandler;
    }

    private static function setHandler()
    {
        Main::$datahandler = new DataHandler();
    }

    private static function setListener()
    {
        $map = Server::getInstance()->getPluginManager();
        $map->registerEvents(new PlayerListener(), Main::getInstance());
    }

    private static function setCommands()
    {
        Server::getInstance()->getCommandMap()->register("home", new home("home", self::getInstance()));
        Server::getInstance()->getCommandMap()->register("sethome",  new sethome("sethome", self::getInstance()));
        Server::getInstance()->getCommandMap()->register("delhome", new delhome("delhome", self::getInstance()));
        Server::getInstance()->getCommandMap()->register("homelist", new homelist("homelist", self::getInstance()));
    }
}