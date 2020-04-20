<?php

declare(strict_types=1);
include_once("config.php");

final class ConnectDB
{
    private static $instance = null;
    public const SQLSERVER = Config::SQLSERVER;
    public const DATABASE = Config::DATABASE;
    public const TABLE = Config::TABLE;
    private const USER = Config::USER;
    private const PW = Config::PW;
    public $link;

    public static function getInstance(): ConnectDB
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __construct()
    {
        if (php_sapi_name() == 'cli') {
            header("Content-Type: text/plain");
        }
        $this->connectDB();
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    private function flushBuffer()
    {
        if (php_sapi_name() != 'cli') {
            ob_flush();
        }
        flush();
    }


    private function connectDB(): mysqli
    {
        $this->link = mysqli_connect($this::SQLSERVER, $this::USER, $this::PW, $this::DATABASE);
        if ($this->link === false) {
            die("<!-- ERROR: Could not connect. " . mysqli_connect_error() . " -->\n");
        }
        $this->flushBuffer();

        if (mysqli_select_db($this->link, $this::DATABASE)) {
            /* echo "<!-- Database selected successfully -->\n";*/
        } else {
            echo "<!-- ERROR: Could not select Database. " . mysqli_error($this->link) . " -->\n";
        }
        $this->flushBuffer();

        $sql = "SET NAMES 'utf8'";
        if (mysqli_query($this->link, $sql)) {
            /*   echo "<!-- Switched to UTF-8 -->\n";*/
        } else {
            echo "<!-- ERROR: Could not able to execute $sql. " . mysqli_error($this->link) . "\n -->";
        }
        return $this->link;
    }
}
