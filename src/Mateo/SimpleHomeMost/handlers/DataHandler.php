<?php

namespace Mateo\SimpleHomeMost\handlers;

use Mateo\SimpleHomeMost\Main;
use Mateo\SimpleHomeMost\Tasks\DatabaseAsync;
use Mateo\SimpleHomeMost\Tasks\TeleportTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class DataHandler
{
    public static \SQLite3 $database;
    public static array $config;
    public array $movements = [];

    CONST FILENAME_DB = "home.db";
    CONST FILENAME_CONFIG = "config.yml";

    public function __construct()
    {
        DataHandler::openDatabase();
        DataHandler::initDatabase();

        DataHandler::initConfig();
    }

    private static function openDatabase()
    {
        DataHandler::$database = new \SQLite3(self::getPatchDatabase());
    }

    private static function initDatabase()
    {
        $session = DataHandler::getSession();
        $prepare = $session->prepare("CREATE TABLE IF NOT EXISTS homes ( id INTEGER PRIMARY KEY AUTOINCREMENT, uuid VARCHAR(30), home_name TEXT, home_vector TEXT, created_at NUMERIC)");

        if ($session->lastErrorMsg() == 0)
        {
            $prepare->execute();
        }else{
            Main::getInstance()->getLogger()->warning("Error: ". $session->lastErrorMsg());
        }
    }

    public static function getAsyncSession($patch): \SQLite3
    {
        return new \SQLite3($patch);
    }

    private static function getSession(): ?\SQLite3
    {
        return self::$database;
    }

    private static function initConfig()
    {
        $config = new Config(self::getPatchConfig());
        if (!$config->exists("countdown_teleport"))
        {
            $config->set("countdown_teleport", 5);
            $config->set("max_home", 5);
            $config->save();
        }
        DataHandler::$config = $config->getAll();
    }

    public function closeDatabase()
    {
        DataHandler::$database->close();
    }

    private static function getPatchConfig() : string
    {
        return str_replace("\\", "/", Main::getInstance()->getDataFolder() . self::FILENAME_CONFIG);
    }

    private static function getPatchDatabase(): string
    {
        return str_replace("\\", "/", Main::getInstance()->getDataFolder() . self::FILENAME_DB);
    }

    public function sendAsync(DatabaseAsync $callable)
    {
        Server::getInstance()->getAsyncPool()->submitTask($callable);
    }

    public function addHome(string $uuid, $name, Position $position)
    {
        $patch = self::getPatchDatabase();
        $position_encoded = base64_encode(serialize(["vector3" => $position->asVector3(), "level" => $position->getWorld()->getFolderName()]));
        $max_home = self::$config["max_home"];

        $a = new DatabaseAsync(function (DatabaseAsync $databaseAsync) use ($patch, $uuid, $name, $position_encoded, $max_home){

            $ddd = DataHandler::getAsyncSession($patch);
            $results = $ddd->query('SELECT home_name FROM homes WHERE uuid = "'. $uuid .'"');
            $result = [];
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) $result[] = $row;
            $all = array_column($result, "home_name");
            $home_number = count($result);

            if($home_number >= $max_home)
            {
                $databaseAsync->setResult(["error" => true, "errorMessage" => "max_home_set", "data" => []]);
            }elseif (empty($result) or !in_array($name, $all))
            {
                $ddd->query('INSERT INTO homes (uuid, home_name, home_vector, created_at) VALUES ("'. $uuid.'", "'. $name.'", "'. $position_encoded.'", '. time().');');
                $databaseAsync->setResult(["error" => false, "errorMessage" => $ddd->lastErrorMsg(), "data" => []]);
            }else{
                $databaseAsync->setResult(["error" => true, "errorMessage" => "This home name is already used", "data" => []]);

            }

            $ddd->close();
        }, function (DatabaseAsync $databaseAsync, Server $server) use ($uuid){
            $player = $server->getPlayerByRawUUID($uuid);
            if (!is_null($player))
            {
                if (!$databaseAsync->getResult()["error"])
                {
                    $player->sendMessage("§a» The home has been saved.");
                }else{
                    if ($databaseAsync->getResult()["errorMessage"] === "max_home_set")
                    {
                        $player->sendMessage("§c» Your are max home limit.");
                    }else{
                        $player->sendMessage("§c» The home already exists.");
                    }
                }
            }
        });

        $this->sendAsync($a);
    }

    public function removeHome(string $uuid, $name)
    {
        $patch = self::getPatchDatabase();

        $a = new DatabaseAsync(function (DatabaseAsync $databaseAsync) use ($patch, $uuid, $name){

            $ddd = DataHandler::getAsyncSession($patch);
            $results = $ddd->query('SELECT home_name FROM homes WHERE uuid = "'. $uuid .'"');
            $result = [];
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) $result[] = $row;
            $all = array_column($result, "home_name");

            if (!empty($result) and in_array($name, $all))
            {
                $ddd->query('DELETE FROM homes WHERE uuid = "'. $uuid.'" and home_name = "'. $name .'"');
                $databaseAsync->setResult(["error" => false, "errorMessage" => $ddd->lastErrorMsg(), "data" => []]);
            }else{
                $databaseAsync->setResult(["error" => true, "errorMessage" => "home name no found", "data" => []]);
            }
            $ddd->close();
        }, function (DatabaseAsync $databaseAsync, Server $server) use ($uuid){
            $player = $server->getPlayerByRawUUID($uuid);
            if (!is_null($player))
            {
                if (!$databaseAsync->getResult()["error"])
                {
                    $player->sendMessage("§a» The home has been deleted.");
                }else{
                    $player->sendMessage("§c» The home does not exist.");
                }
            }
        });

        $this->sendAsync($a);
    }

    public function listHome($uuid)
    {
        $patch = self::getPatchDatabase();

        $a = new DatabaseAsync(function (DatabaseAsync $databaseAsync) use ($patch, $uuid){

            $ddd = DataHandler::getAsyncSession($patch);
            $results = $ddd->query('SELECT home_name FROM homes WHERE uuid = "'. $uuid .'"');
            $result = [];
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) $result[] = $row;
            $all = array_column($result, "home_name");

            if (!empty($result))
            {
                $databaseAsync->setResult(["error" => false, "errorMessage" => $ddd->lastErrorMsg(), "data" => ["homes" => $all]]);
            }else{
                $databaseAsync->setResult(["error" => true, "errorMessage" => "no home found", "data" => []]);
            }
            $ddd->close();
        }, function (DatabaseAsync $databaseAsync, Server $server) use ($uuid){

            $player = $server->getPlayerByRawUUID($uuid);
            if (!is_null($player))
            {
                if (!$databaseAsync->getResult()["error"])
                {
                    $player->sendMessage("§a» Home list: §r". implode(", ", $databaseAsync->getResult()["data"]["homes"]));
                }else{
                    $player->sendMessage("§c» No home");
                }
            }
        });

        $this->sendAsync($a);
    }

    public function teleportHome($uuid, $name)
    {
        $patch = self::getPatchDatabase();

        $a = new DatabaseAsync(function (DatabaseAsync $databaseAsync) use ($patch, $uuid, $name){

            $ddd = DataHandler::getAsyncSession($patch);
            $results = $ddd->query('SELECT * FROM homes WHERE uuid = "'. $uuid .'" and home_name = "'. $name.'"');
            $result = [];
            while ($row = $results->fetchArray(SQLITE3_ASSOC)) $result[] = $row;
            $all = array_column($result, "home_name");
            if (!empty($result) and in_array($name, $all))
            {
                $databaseAsync->setResult(["error" => false, "errorMessage" => $ddd->lastErrorMsg(), "data" => ["home" => $result[0]]]);
            }else{
                $databaseAsync->setResult(["error" => true, "errorMessage" => "home no found", "data" => []]);
            }
            $ddd->close();
        }, function (DatabaseAsync $databaseAsync, Server $server) use ($uuid){

            $player = $server->getPlayerByRawUUID($uuid);
            if (!is_null($player))
            {
                if (!$databaseAsync->getResult()["error"])
                {
                    $decode = unserialize(base64_decode($databaseAsync->getResult()["data"]["home"]["home_vector"]));
                    $level = $server->getWorldManager()->getWorldByName($decode["level"]);

                    if (is_null($level)) $server->getWorldManager()->loadWorld($decode["level"]);
                    if (!is_null($server->getWorldManager()->getWorldByName($decode["level"])))
                    {
                        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($uuid, DataHandler::$config["countdown_teleport"], new Position($decode["vector3"]->x, $decode["vector3"]->y, $decode["vector3"]->z, $level)), 20);
                    }
                }else{
                    $player->sendMessage("§c» The home does not exist.");
                }
            }
        });
        $this->sendAsync($a);
    }
}
