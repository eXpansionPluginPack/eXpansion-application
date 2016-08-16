<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'autoload.php';

use ManiaLivePlugins\eXpansion\Helpers\Console;

ini_set('display_errors', 1);
error_reporting(E_WARNING ^ E_DEPRECATED);
gc_enable();

new \setup();

class setup
{

    private $ip = "127.0.0.1";
    private $port = 5000;
    private $user = "SuperAdmin";
    private $pass = "SuperAdmin";
    private $admins = array();
    private $connection = null;


    private $db_ip = "127.0.0.1";
    private $db_port = "3306";
    private $db_user = "";
    private $db_pass = "";
    private $db_database = "";

    function __construct()
    {


        $expansion = <<<'EOT'
   
   

                     __   __                      _             
                     \ \ / /                     (_)            
                  ___ \ ' / _ __   __ _ _ __  ___ _  ___  _ __  
                 / _ \ > < | '_ \ / _` | '_ \/ __| |/ _ \| '_ \ 
                |  __// . \| |_) | (_| | | | \__ \ | (_) | | | |
                 \___/_/ \_\ .__/ \__,_|_| |_|___/_|\___/|_| |_|
...........................| |.........Plugin Pack for Manialive...............
                           |_|                                                  
EOT;
        Console::out($expansion);

        Console::out("\nWelcome to " . Console::b_white . "e" . Console::b_cyan . "X" . Console::b_white . "pansion" . Console::white . " setup\n");
        Console::out("\n");
        $this->tryConnect();
        $this->setAdmins();
        $this->setMySql();
        Console::out(Console::b_green . "Everything is now OK.\n");
        Console::out("Write config.ini file (y)? ");
        if ($this->getInput("y") == "y") {
            $this->writeConfig();

            Console::out(Console::b_green . "Start eXpansion (y)? ");
            if ($this->getInput("y") == "y") {
                exec(PHP_BINARY . "bootstrapper.php");
            } else {

            }
        } else {
            Console::out("Aborted, config file not modified.");
            exit(1);
        }


    }

    function getInput($defaultValue = "")
    {

        echo Console::b_yellow;
        echo "\e[s";
        $line = trim(fgets(STDIN));
        if ($line == "") {
            "\e[u";
            echo $defaultValue . "\n";
            return $defaultValue;
        }
        return $line;
    }


    function tryConnect()
    {
        Console::out(Console::b_green . "Dedicated server connection setup:\n");
        Console::out(Console::white . "Please enter dedicated server ip (127.0.0.1): ");
        $this->ip = $this->getInput("127.0.0.1");
        Console::out(Console::white . "Please enter dedicated xml-rpc port (5000): ");
        $this->port = (int)$this->getInput(5000);
        Console::out(Console::white . "Please enter dedicated user (SuperAdmin): ");
        $this->user = $this->getInput("SuperAdmin");
        Console::out(Console::white . "Please enter dedicated password (SuperAdmin): ");
        $this->pass = $this->getInput("SuperAdmin");
        $connection = null;
        try {
            Console::out("Trying to connect...", "Connect", Console::b_green);
            $this->connection = \Maniaplanet\DedicatedServer\Connection::factory($this->ip, $this->port, 5, $this->user, $this->pass);
            Console::success();
            Console::outTm("Connected to server: " . $this->connection->getServerName() . "\n");
        } catch (\Exception $ex) {
            Console::fail();
            Console::out_error($ex->getMessage());
            Console::out("Try again (y)?");
            if ($this->getInput("y") == "y") {
                $this->tryConnect();
            } else {
                exit(0);
            }
        }
        return;
    }

    function setAdmins()
    {
        Console::out(Console::b_green . "\nNext we'll setup master admins...\n");
        $players = $this->connection->getPlayerList();
        if ($players) {
            Console::out("Found " . count($players) . " players at server...\n");
            $x = 1;
            foreach ($players as $player) {
                Console::outTm("Player:" . $player->nickName . "\n");
                Console::out(Console::white . "[" . $x . "/" . count($players) . "] Add " . $player->login . " as master admin (y/" . Console::b_white . "n" . Console::white . ")? " . Console::b_yellow);
                $answer = $this->getInput("n");
                if ($answer == "y") {
                    $this->admins[] = $player->login;
                }
                $x++;
            }
        } else {
            Console::out(Console::white . "Enter in-game login for Masteradmin: " . Console::b_yellow);
            $this->admins[] = $this->getInput("");
        }
    }

    function setMySql()
    {
        Console::out(Console::b_green . "Next we'll setup mysql-connection...\n");
        Console::out(Console::white . "Please enter mysql server ip (127.0.0.1): ");
        $this->db_ip = $this->getInput("127.0.0.1");
        Console::out(Console::white . "Please enter mysql server port (3306): ");
        $this->db_port = (int)$this->getInput(3306);
        Console::out(Console::white . "Please enter mysql user: ");
        $this->db_user = $this->getInput("");
        Console::out(Console::white . "Please enter mysql password: ");
        $this->db_pass = $this->getInput("");
        Console::out("Please enter mysql database name (" . $this->db_user . "): ");
        $this->db_database = $this->getInput($this->db_user);

        try {
            echo Console::white;

            Console::out("Trying to connect...", "MySQLI", Console::b_green);
            @ManiaLive\Database\Connection::getConnection($this->db_ip, $this->db_pass, $this->db_user, $this->db_database, "MySQLI", $this->db_port);
            Console::ok();
        } catch (\Exception $ex) {
            Console::fail();
            Console::out_error($ex->getMessage());
            Console::out("Try again (y)? ");
            if ($this->getInput("y") == "y") {
                $this->setMySql();
            } else {
                exit(0);
            }
        }
    }


    function writeConfig()
    {
        $config = ";------------------
; Dedicated Server
;------------------
config.maxErrorCount = 15
server.host = '" . $this->ip . "'
server.port = " . $this->port . "
server.user = '" . $this->user . "'
server.password = '" . $this->pass . "'

;------------------
; Database
;------------------
database.enable = true

; Connection configuration
database.host = '" . $this->db_ip . "'
database.port = " . $this->db_port . "
database.username = '" . $this->db_user . "'
database.password = '" . $this->db_pass . "'
database.database = '" . $this->db_database . "'

;------------------
; Master Admins
;------------------
";
        foreach ($this->admins as $admin) {
            $config .= "manialive.admins[] = '" . $admin . "'\n";
        }

        $config .= "
;------------------
; Plugins
;------------------

manialive.plugins[] = 'ManiaLivePlugins\\eXpansion\\AutoLoad\\AutoLoad'
";

        $filename = __DIR__ . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.ini";
        if (file_exists($filename)) {
            Console::out("config.ini exists, replace contents (n)?");
            if ($this->getInput("n") == "y") {
                file_put_contents($filename, $config);
            } else {
                exit(0);
            }
        }
        file_put_contents($filename, $config);

    }


}

