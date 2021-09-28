<?php

namespace Mateo\SimpleHomeMost\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class DatabaseAsync extends AsyncTask
{

    /**
     * @var callable
     */
    private $call1;
    private $call2;
    private $call3;

    public function __construct(callable $call1, callable $call2, ...$call3) {
        $this->call1 = $call1;
        $this->call2 = $call2;
        $this->call3 = $call3;
    }

    public function onRun(): void {
        call_user_func($this->call1, $this, $this->call3);
    }

    public function onCompletion(): void {
        call_user_func($this->call2, $this, Server::getInstance(), $this->call3);
    }
}