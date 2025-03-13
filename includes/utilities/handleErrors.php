<?php
class ErrorList
{
    private static $errors = [];

    public static function add($message)
    {
        self::$errors[] = $message;
    }

    public static function getAll()
    {
        return self::$errors;
    }

    public static function hasErrors()
    {
        return !empty(self::$errors);
    }

    public static function clear()
    {
        self::$errors = [];
    }
}