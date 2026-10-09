<?php

class Database extends PDO
{
    private static $instance = null;

    private function __construct($dsn, $user, $pass)
    {
        parent::__construct($dsn, $user, $pass);

        $this->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $this->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );
    }

    public static function getInstance(
        $dsn,
        $user = null,
        $pass = null
    ) {
        if (self::$instance === null) {
            self::$instance = new Database($dsn, $user, $pass);
        }

        return self::$instance;
    }
}