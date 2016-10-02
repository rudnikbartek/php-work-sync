<?php
namespace Rdnk\WorkSync\Model;

class SyncCache {
    static $table_name = 'sync_cache';
    public static $connection;


    public static function clear() {
        self::query('TRUNCATE '.self::table_name() );
    }

    public static function createLock($code) {
        self::query("INSERT INTO sync_cache (`key`, value)
                    SELECT * FROM (SELECT 'LOCK_".$code."', '0') AS tmp
                    WHERE NOT EXISTS (
                        SELECT `key` FROM sync_cache WHERE `key` = 'LOCK_".$code."'
                    ) LIMIT 1");
    }

    public static function lock($code) {
        $query = self::query("UPDATE sync_cache SET sync_cache.value='1' WHERE sync_cache.`key` = 'LOCK_$code' AND sync_cache.value = '0'");
        if(is_object($query)) {
            if($query->rowCount() == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            false;
        }

    }

    public static function waitForLock($code, $microseconds) {
        while(true) {
            if (self::isLocked($code)) {
                usleep($microseconds);
            } else {
                if (self::lock($code)) {
                    return true;
                } else {
                    usleep($microseconds);
                }
            }
        }
    }

    public static function unlock($code) {
        $query = self::query("UPDATE sync_cache SET sync_cache.value='0' WHERE sync_cache.`key` = 'LOCK_$code' AND sync_cache.value = '1'");
        if(is_object($query)) {
            if($query->rowCount() == 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public static function isLocked($code) {
        $obj = self::find('first', array('conditions'=>array('`key` = ?', 'LOCK_'.$code)));

        if($obj->value == '0') {
            return false;
        } else {
            return true;
        }
    }

    public function getValue() {
        $this->value;
    }

    public function getKey() {
        $this->key;
    }

    public static function getValues($key) {
        return self::find('all', array('conditions'=>array('`key` = ?', $key)));
    }

    public static function addItem($key, $value) {
        $item = new self;
        $item->value = $value;
        $item->key = $key;
        $item->save();
    }

}
