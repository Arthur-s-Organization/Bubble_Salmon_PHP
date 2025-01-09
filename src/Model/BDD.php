<?php

class BDD
{
    private static $_instance = null; //Instance de connexion
    private const _DBHOSTNAME_ = "projet_whatsapp-mariadb106";
    private const _DBUSERNAME_ = "docker";
    private const _DBPASSWORD_ = "docker";
    private const _DBNAME_ = "docker";
    private const _DBPORT_ = 3306;

    private function __construct(){}
    private function __clone(){}

    public static function getInstance(){
        if(is_null(self::$_instance)){
            try {
                self::$_instance = new PDO("mysql:host=".self::_DBHOSTNAME_.";port=".self::_DBPORT_.";dbname=".self::_DBNAME_.";charset=utf8", self::_DBUSERNAME_, self::_DBPASSWORD_);
                self::$_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }catch (\Exception $e){
                die("ERREUR BDD : {$e->getMessage()}");
            }
        }
        return self::$_instance;
    }
}