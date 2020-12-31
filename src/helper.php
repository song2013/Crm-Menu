<?php
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!function_exists('set_default_database_connection')){
    function set_default_database_connection($database){
        DB::purge('mysql');//断开mysql连接
        Config::set('database.connections.mysql.database', $database);
        DB::reconnect('mysql');//重新连接mysql
        DB::setDefaultConnection('mysql');//将mysql设为默认连接
    }
}