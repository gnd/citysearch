<?php

/**
 * CitySearch
 *
 * An open source application to mine users from Soundcloud
 *
 * This is based on php-soundcloud available at 
 * https://github.com/mptre/php-soundcloud
 * 
 *
 * 2012 - 2017, gnd
 *
 */
 
class db {
    var $link;
    var $prfx;

    function connect() {
        include "sttngs.php";
        $this->db = new mysqli($db_host, $db_user, $db_pass, $db_name);
        $this->prfx = $db_prfx;
    }
    
    function close() {
        $this->db->close();
    }
    
    function addIgnore($uid, $iid) {
        $result = $this->db->query("INSERT INTO " . $this->prfx . "_ignores VALUES(0, $uid, $iid)");
        return $result;
    }

    function delIgnore($uid, $iid) {
        $result = $this->db->query("DELETE FROM " . $this->prfx . "_ignores WHERE uid = $uid AND iid = $iid LIMIT 1");
        return $result;
    }

    function getIgnores($uid) {
        $result = $this->db->query("SELECT iid FROM " . $this->prfx . "_ignores WHERE uid = $uid");
        return $result;
    }

    function addSeen($uid, $iid) {
        $result = $this->db->query("INSERT INTO " . $this->prfx . "_seen VALUES(0, $uid, $iid, '')");
        return $result;
    }

    function delSeen($uid, $iid) {
        $result = $this->db->query("DELETE FROM " . $this->prfx . "_seen WHERE uid = $uid AND iid = $iid LIMIT 1");
        return $result;
    }

    function getSeen($uid) {
        $result = $this->db->query("SELECT iid, reason FROM " . $this->prfx . "_seen WHERE uid = $uid");
        return $result;
    }

    function addCityAlias($uid, $city, $alias) {
        $result = $this->db->query("INSERT INTO " . $this->prfx . "_alias VALUES(0, $uid, '$city', '$alias')");
        return $result;
    }

    function delCityAlias($uid, $aliasid) {
        $result = $this->db->query("DELETE FROM " . $this->prfx . "_alias WHERE uid = $uid AND id = '$aliasid' LIMIT 1");
        return $result;
    }

    function getCityAliases($uid) {
        $result = $this->db->query("SELECT id, city, alias FROM " . $this->prfx . "_alias WHERE uid = $uid");
        return $result;
    }

    function addCountryAlias($uid, $country, $alias) {
        $result = $this->db->query("INSERT INTO " . $this->prfx . "_country_alias VALUES(0, $uid, '$country', '$alias')");
        return $result;
    }

    function delCountryAlias($uid, $aliasid) {
        $result = $this->db->query("DELETE FROM " . $this->prfx . "_country_alias WHERE uid = $uid AND id = '$aliasid' LIMIT 1");
        return $result;
    }

    function getCountryAliases($uid) {
        $result = $this->db->query("SELECT id, country, alias FROM " . $this->prfx . "_country_alias WHERE uid = $uid");
        return $result;
    }
    
    // create table ed_sess_status ( id int AUTO_INCREMENT PRIMARY KEY, uid int, status varchar(10), curr_dep int, max_dep int);
    function updateSessionStatus($uid, $status, $current_depth, $max_depth) {
        $result = $this->db->query("INSERT INTO " . $this->prfx . "_sess_status VALUES(0, $uid, '$status', $current_depth, $max_depth)");
        return $result;
    }
    
    // create table ed_sess_progress ( id int AUTO_INCREMENT PRIMARY KEY, uid int, progress int);
    function updateSessionProgress($uid, $progress) {
        $result = $this->db->query("INSERT INTO " . $this->prfx . "_sess_progress VALUES(0, $uid, ".(int)(100*$progress).")");
        return $result;
    }
    
    function getSessionStatus($uid) {
        $result = $this->db->query("SELECT status, curr_dep, max_dep FROM " . $this->prfx . "_sess_status WHERE uid = $uid ORDER BY id DESC LIMIT 1");
        return $result;
    }
    
    function getSessionProgress($uid) {
        $result = $this->db->query("SELECT progress FROM " . $this->prfx . "_sess_progress WHERE uid = $uid ORDER BY id DESC LIMIT 1");
        return $result;
    }
    
    function clearSessionStatus($uid) {
        $result = $this->db->query("DELETE FROM " . $this->prfx . "_sess_status WHERE uid = $uid");
        return $result;
    }
    
    function clearSessionProgress($uid) {
        $result = $this->db->query("DELETE FROM " . $this->prfx . "_sess_progress WHERE uid = $uid");
        return $result;
    }
}

?>
