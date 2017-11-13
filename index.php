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

session_start();
include("php-soundcloud/Services/Soundcloud.php");
include "db_class.php";
include "char_functions.php";
include "sttngs.php";

$mydb = new db();
$mydb->connect();

$soundcloud = new Services_Soundcloud($sc_client_id, $sc_client_secret, $sc_redirect);
$authorizeUrl = $soundcloud->getAuthorizeUrl();

if(!isset($_SESSION["logged"])) {
    $_SESSION["logged"] = 0;
}

// VERIFIES CERTS WITH NEW CA BUNDLE
$soundcloud->setCurlOptions(CURLOPT_CAINFO, '/etc/ssl/certs/mozilla_root_certs.pem');


////////////////////
//////////////////// Here we define some functions
////////////////////

/**
 * Sanitizes a input parameter of the type string
 *
 * @param string $input input string
 * @return Sanitized input string
 */
function validate_str($input, $link) {
    return mysqli_real_escape_string($link, $input);
}

/**
 * Sanitizes a input parameter of the type int
 * 
 * If the input is not a number, execution is halted
 *
 * @param string $input input integer
 * @return Sanitized input integer
 */
function validate_int($input, $link) {
    $num = mysqli_real_escape_string($link, strip_tags(escapeshellcmd($input)));
    if(is_numeric($num)||($num == "")) {
        return (int) $num;
    } else {
        die("Input parameter tot a number");
    }
}

/**
 * Prints a single soundcloud track as embed
 *
 * @param string $uri Track uri from soundcloud
 * @param string $perma Track permalink from soundcloud
 * @param string $title Track title from soundcloud
 * @param string $tags Track trags from soundcloud
 */
function track($uri, $perma, $title, $tags) {
    echo "<a class=\"track\" href=\"" . $perma . "\">" . $title . "</a>\n\n";
    echo "<object height=\"81\" width=\"100%\">\n";
    echo "<param name=\"movie\" value=\"http://player.soundcloud.com/player.swf?url=" . $uri . "\"></param>\n";
    echo "<param name=\"allowscriptaccess\" value=\"always\"></param>\n";
    echo "<embed allowscriptaccess=\"always\" height=\"81\" src=\"http://player.soundcloud.com/player.swf?url=" . $uri . "\" type=\"application/x-shockwave-flash\" width=\"100%\">\n";
    echo "</embed></object>\n";
    echo "<span class=\"tags\"><b>Tags:</b> $tags</span>\n";
}

/**
 * Prints a single soundcloud track as permalink and tags
 *
 * @param string $uri Track uri from soundcloud
 * @param string $perma Track permalink from soundcloud
 * @param string $title Track title from soundcloud
 * @param string $tags Track trags from soundcloud
 */
function crack($uri, $perma, $title, $tags) {
    echo "<a class=\"track\" href=\"" . $perma . "\">" . $title . "</a>\n\n";
    echo "<span class=\"tags\"><b>Tags:</b> $tags</span>\n";
}

/**
 * Prints a form to search for users from $city
 *
 * @param string $city City to search in
 * @param int    $depth Depth of search
 * @param bool   $ignore Show ignored users switch
 */
function filterform_citysearch($city, $depth, $ignore) {
    if($ignore == true) {
        $ignore = "checked=checked";
    } else {
        $ignore = "";
    }
    echo "<form action=\"index.php?seek=1\" method=\"post\">\n";
    echo "city: <input type=\"text\" name=\"seek_city\" value=\"$city\"/>\n";
    echo "depth: <input type=\"text\" name=\"seek_depth\" value=\"$depth\" />\n";
    echo "show ignored: <input name=\"ignoreign\" type=\"checkbox\" $ignore />\n";
    echo "<input type=\"hidden\" name=\"search\" value=\"1\" />\n";
    echo "<br/><br/><input type=\"submit\" name=\"submit\" />\n";
    echo "</form><br/><br/><br/>\n";
}


/**
 * Prints a form to search for users followed by or following $user
 *
 * @param string $user User whose connections are to be checked
 * @param string $city City to search in
 * @param int    $depth Depth of search
 * @param bool   $ignore Show ignored users switch
 */
function filterform_userstalk($user, $city, $limit, $ignore) {
    if($ignore == true) {
        $ignore = "checked=checked";
    } else {
        $ignore = "";
    }
    echo "<form action=\"index.php?userstalk=1\" method=\"post\">\n";
    echo "user: <input type=\"text\" name=\"userstalk_user\" value=\"$user\"/><br/>\n";
    echo "city: <input type=\"text\" name=\"userstalk_city\" value=\"$city\"/>\n";
    echo "show ignored: <input name=\"ignoreign\" type=\"checkbox\" $ignore />\n";
    echo "<input type=\"hidden\" name=\"search\" value=\"1\" />\n";
    echo "<br/><br/><input type=\"submit\" name=\"submit\" />\n";
    echo "</form><br/><br/><br/>\n";
}

/**
 * Prints a form to add a new city alias
 * 
 *
 * @param string $city a maligned city name that is fond in results
 * @param string $alias alias, in this case the real name of the city 
 */
function cityalias($city, $alias) {
    echo "<form action=\"index.php?cityalias=1\" method=\"post\">\n";
    echo "city: <input type=\"text\" name=\"qcity\" /><br/>\n";
    echo "alias: <input type=\"text\" name=\"qalias\" />\n";
    echo "<br/><br/><input type=\"submit\" name=\"submit\" />\n";
    echo "</form><br/><br/><br/>\n";
}

/**
 * Prints a form to add a new country alias
 * 
 *
 * @param string $country a maligned country name that is fond in results
 * @param string $alias alias, in this case the real name of the country 
 */
function countryalias($country, $alias) {
    echo "<form action=\"index.php?countryalias=1\" method=\"post\">\n";
    echo "city: <input type=\"text\" name=\"qcountry\" /><br/>\n";
    echo "alias: <input type=\"text\" name=\"qalias\" />\n";
    echo "<br/><br/><input type=\"submit\" name=\"submit\" />\n";
    echo "</form><br/><br/><br/>\n";
}

/**
 * Prints the HTML page top and navigatio menu
 * 
 */
function pagetop() {
    echo "<html><head>\n";
    echo "<link href=\"citysearch.css\" rel=\"stylesheet\" type=\"text/css\" />\n";
    echo "<title>citysearch</title></head><body>\n";
    echo "<div class=\"main\" style=\"width: 800px;\">\n";
    echo "<div class=\"nav\" >\n";
    echo "<a href=\"/citysearch/index.php\">main</a> / \n";
    echo "<a href=\"/citysearch/index.php?seek=1\">search</a> / \n";
    echo "<a href=\"/citysearch/index.php?userstalk=1\">userstalk</a> / \n";
    echo "<a href=\"/citysearch/index.php?aliases=1\">aliases</a> / \n";
    echo "Logged in as " . $_SESSION["user_data"]["username"];
    echo " [<a href=\"/citysearch/index.php?logout=1\">logout</a>]\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "<br/><br/>";
}

/**
 * Prints found users into a table
 * 
 * This first checks whether a user is in ignored (and later also seen) users
 * 
 * @param array $users results provided by the search
 * @param array $ignored_users preloaded array of ignored user ids
 */
function display_user_results($users, $ignored_users) {
    if (count($users) > 0 ) {
        echo "\n\n<table style=\"border: 0px;\"><tr><td>name</td><td>trcks</td><td>rnk</td><td>fol</td><td>description</td></tr>\n";
        foreach ( $users as $user ) {
            $link = $user["permalink"];
            $name = $user["username"];
            $tracks = $user["tracks"];
            $desc = $user["description"];
            $followers = $user["count_followers"];
            $rank = $user["rank"];
            if (in_array($user["id"], $ignored_users) ) {
                echo "<tr><td class=\"artist_name_ignored\"><a class=\"artist\" target=\"_blank\" href=\"http://soundcloud.com/" . $link . "\">" . $name . "</a> ";
                echo "[<a class=\"ignore\" target=\"_blank\" href=\"index.php?unignore=" . $user["id"] . "\">unign</a>";
                echo ",<a class=\"seen\" target=\"_blank\" href=\"index.php?seen=" . $user["id"] . "\">see</a>]</td>";
                echo "<td><span class=\"artist_info\"><b> " . $tracks . "</b></span></td>";
                echo "<td><span class=\"artist_info\" title=\"$desc\">".substr(strip_tags($desc),0,250)."</span></td></tr>\n";
            } else if (in_array($user["id"], $seen_users) ) {
                echo "<tr><td class=\"artist_name_seen\"><a class=\"artist\" target=\"_blank\" href=\"http://soundcloud.com/" . $link . "\">" . $name . "</a> ";
                echo "[<a class=\"ignore\" target=\"_blank\" href=\"index.php?ignore=" . $user["id"] . "\">ign</a>";
                echo ",<a class=\"seen\" target=\"_blank\" href=\"index.php?seen=" . $user["id"] . "\">unsee</a>]</td>";
                echo "<td><span class=\"artist_info\"><b> " . $tracks . "</b></span></td>";
                echo "<td><span class=\"artist_info\" title=\"$desc\">".substr(strip_tags($desc),0,250)."</span></td></tr>\n";
            } else {
                echo "<tr><td class=\"artist_name\"><a class=\"artist\" target=\"_blank\" href=\"http://soundcloud.com/" . $link . "\">" . $name . "</a> ";
                echo "[<a class=\"ignore\" target=\"_blank\" href=\"index.php?ignore=" . $user["id"] . "\">ign</a>";
                echo ",<a class=\"seen\" target=\"_blank\" href=\"index.php?seen=" . $user["id"] . "\">see</a>]</td>";
                echo "<td class=\"artist_info\"><b> " . $tracks . "</b></td>";
                echo "<td class=\"artist_info\"><b> " . $rank . "</b></td>";
                echo "<td class=\"artist_info\"><b> " . $followers . "</b></td>";
                echo "<td class=\"artist_info\" title=\"".strip_tags(addslashes($desc))."\">".substr(strip_tags(addslashes($desc)),0,250)."</td></tr>\n";
            }
        }
        echo "</table>\n\n";
    }
}

/**
 * Sorts found users according to how many tracks they have
 * 
 * 
 * @param array $a 
 * @param array $b
 */
function compare_by_tracks($a, $b) {
    return $a['tracks'] < $b['tracks'];
}

/**
 * Sorts found users according to their rank and tracks
 * 
 * ranking shold be done not only by
 * - how many ties one user has within a specific city
 * - how many tracks he has
 * but also: 
 * - show users with newer tracks higher then users with old tracks (fix)
 * - show users with more tracks higher then with fewer tracks (fix)
 * 
 * 
 * @param array $a 
 * @param array $b
 */
function gnd_rank1($a, $b) {
    // one rank point equals three tracks
    $arank = $a['tracks'] * $a['rank'];
    $brank = $b['tracks'] * $b['rank'];
    return $arank < $brank;
}

/**
 * Sorts arrays according to how many elements they have
 * 
 * 
 * @param array $arrays
 */
function sort_by_length($arrays) {
    $lengths = array_map('count', $arrays);
    arsort($lengths);
    $return = array();

    foreach(array_keys($lengths) as $k)
        $return[$k] = $arrays[$k];
    return $return;
}

////////////////////
//////////////////// Here we process requests 
////////////////////

/**
 * Adds a user id into the ignore list
 * 
 */
if (isset($_REQUEST["ignore"]) && ($_REQUEST["ignore"] != 0)) {
    
    $iid = validate_int($_REQUEST["ignore"], $mydb->db);
    $mydb->addIgnore($_SESSION["user_data"]["id"], $iid);
    
    // Preload ignored ids
    $ignored_ids = array();
    $data = $mydb->getIgnores($_SESSION["user_data"]["id"]);
    while($line = mysqli_fetch_array($data)) {
        $ignored_ids[] = $line["iid"];
    }
    $_SESSION["ignored"] = $ignored_ids;
    
    header('Location: index.php');
}

/**
 * Adds a user id into the seen list
 * 
 */
if (isset($_REQUEST["seen"]) && ($_REQUEST["seen"] != 0)) {
    $iid = validate_int($_REQUEST["seen"], $mydb->db);
    $mydb->addSeen($_SESSION["user_data"]["id"], $iid);
    
    // Preload seen ids
    $seen_ids = array();
    $data = $mydb->getSeen($_SESSION["user_data"]["id"]);

    while($line = mysqli_fetch_array($data)) {
        $seen_ids[] = $line["iid"];
    }
    $_SESSION["seen"] = $seen_ids;
    
    header('Location: index.php');
}

/**
 * Adds a alias for a malformed city name
 * 
 * If a city like 'kassa' is found in results, create an alias 
 * and add it to other results from the real town (in this case 'kosice')
 */
else if (isset($_REQUEST["cityalias"]) && ($_REQUEST["cityalias"] == 1)) {
    $qcity = validate_str($_REQUEST["qcity"], $mydb->db);
    $qalias = validate_str($_REQUEST["qalias"], $mydb->db);
    $mydb->addCityAlias($_SESSION["user_data"]["id"], $qcity, $qalias);
    
    // Preload aliases - this can be done purely in-session (fix)
    $data = $mydb->getCityAliases($_SESSION["user_data"]["id"]);
    while($line = mysqli_fetch_array($data)) {
        $single_alias = array();
        $single_alias[] = $line["id"];
        $single_alias[] = $line["city"];
        $city_aliases[$line["alias"]][] = $single_alias;
    }
    $_SESSION["city_aliases"] = $city_aliases;
    
    header('Location: index.php?aliases=1');
}

/**
 * Deletes an city alias
 * 
 */
else if (isset($_REQUEST["delcityalias"])) {
    $delalias = validate_int($_REQUEST["delcityalias"], $mydb->db);
    $mydb->delCityAlias($_SESSION["user_data"]["id"], $delalias);
    
    // Preload aliases - this can be done purely in-session (fix)
    $data = $mydb->getCityAliases($_SESSION["user_data"]["id"]);
    while($line = mysqli_fetch_array($data)) {
        $single_alias = array();
        $single_alias[] = $line["id"];
        $single_alias[] = $line["city"];
        $city_aliases[$line["alias"]][] = $single_alias;
    }
    $_SESSION["city_aliases"] = $city_aliases;
    
    header('Location: index.php?aliases=1');
}

/**
 * Adds a alias for a malformed country name
 * 
 * If a country like 'slovakistan' is found in results, create an alias 
 * and add it to other results from the real town (in this case 'slovakia')
 */
else if (isset($_REQUEST["countryalias"]) && ($_REQUEST["countryalias"] == 1)) {
    $qcountry = validate_str($_REQUEST["qcountry"], $mydb->db);
    $qalias = validate_str($_REQUEST["qalias"], $mydb->db);
    $mydb->addCountryAlias($_SESSION["user_data"]["id"], $qcountry, $qalias);
    
    // Preload aliases - this can be done purely in-session (fix)
    $data = $mydb->getCountryAliases($_SESSION["user_data"]["id"]);
    while($line = mysqli_fetch_array($data)) {
        $single_alias = array();
        $single_alias[] = $line["id"];
        $single_alias[] = $line["country"];
        $contry_aliases[$line["alias"]][] = $single_alias;
    }
    $_SESSION["country_aliases"] = $country_aliases;
    
    header('Location: index.php?aliases=1');
}

/**
 * Deletes an country alias
 * 
 */
else if (isset($_REQUEST["delcountryalias"])) {
    $delalias = validate_str($_REQUEST["delcountryalias"], $mydb->db);
    $mydb->delCountryAlias($_SESSION["user_data"]["id"], $delalias);
    
    // Preload aliases - this can be done purely in-session (fix)
    $data = $mydb->getCountryAliases($_SESSION["user_data"]["id"]);
    while($line = mysqli_fetch_array($data)) {
        $single_alias = array();
        $single_alias[] = $line["id"];
        $single_alias[] = $line["country"];
        $contry_aliases[$line["alias"]][] = $single_alias;
    }
    $_SESSION["country_aliases"] = $country_aliases;
    
    header('Location: index.php?aliases=1');
}

/**
 * Logout event
 * 
 */
if (isset($_REQUEST["logout"]) && ($_REQUEST["logout"] == 1)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
}

/**
 * Login event
 * 
 * This is the first segment to run, right after Soundcloud login
 * Here we preload into the session:
 * - city aliases
 * - country aliases
 * - followed users (and their data - id, username, permalink) into:
 *  - cities - a associative array of arrays (eg. cities['kosice'] is an array of all followed users from Kosice
 *  - countries - a associative array of arrays (eg. countries['slovakia'] is an array of all followed users from Slovakia
 * - ignores
 */
if (isset($_GET["code"])) {
    
    try {
        $accessToken = $soundcloud->accessToken($_GET['code']);
        $_SESSION["logged"] = 1; // (fix)
        $followed_ids = array();
        $ignored_ids = array();
        $seen_ids = array();
        try {
            $me = json_decode($soundcloud->get('me'), true);
        }
        catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            exit($e->getMessage());
        }
        $_SESSION["user_data"] = array('access_token' =>$accessToken['access_token'],
                                       'refresh_token' =>$accessToken['refresh_token'],
                                       'expires' => time()+$accessToken['expires_in'],
                                       'id' =>$me['id'],
                                       'username' =>$me['username'],
                                       'name' =>$me['full_name'],
                                       'avatar' =>$me['avatar_url'],
                                       'followed_count' =>$me['followings_count']);
        // Preload aliased cities 
        $city_aliases = array();
        $data = $mydb->getCityAliases($_SESSION["user_data"]["id"]);
        while ($line = mysqli_fetch_array($data)) {
            $single_alias = array();
            $single_alias[] = $line["id"];
            $single_alias[] = $line["city"];
            $city_aliases[$line["alias"]][] = $single_alias;
        }
        $_SESSION["city_aliases"] = $city_aliases;

        // Preload aliased countries
        $country_aliases = array();
        $data = $mydb->getCountryAliases($_SESSION["user_data"]["id"]);
        while ($line = mysqli_fetch_array($data)) {
            $single_alias = array();
            $single_alias[] = $line["id"];
            $single_alias[] = $line["country"];
            $country_aliases[$line["alias"]][] = $single_alias;
        }
        $_SESSION["country_aliases"] = $country_aliases;
        
        // Preload followed ids
        try {
            $offset = 0;
            $cities = array();
            $countries = array();
            
            $following = json_decode($soundcloud->get('me/followings.json', array('limit' => $sc_page_limit, 'offset' => $offset)), true);
            $next_href = $following["next_href"];

            while ( $next_href ) {
                foreach($following["collection"] as $followed) {
                    $followed_ids[] = $followed["id"];
                    $city = remove_accents(strtolower(trim($followed["city"])));
                    $country = remove_accents(strtolower(trim($followed["country"])));
                    $cityfound = 0;
                    $countryfound = 0;
                    
                    // city aliases
                    $city = preg_replace('/\s+/', ' ', $city);
                    if (array_key_exists($city, $city_aliases)) {
                        $cityfound = 1;
                        foreach ($city_aliases[$city] as $single_alias) {
                            $valid_user_data = array();
                            $valid_user_data["id"] = $followed["id"];
                            $valid_user_data["username"] = $followed["username"];
                            $valid_user_data["permalink"] = $followed["permalink"];
                            $cities[$single_alias[1]][] = $valid_user_data;
                        }
                    }
                    if($cityfound == 0) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $cities[$city][] = $valid_user_data;
                    }
                    
                    // country aliases
                    if (array_key_exists($country, $country_aliases)) {
                        $countryfound = 1;
                        foreach ($country_aliases[$country] as $single_alias) {
                            $valid_user_data = array();
                            $valid_user_data["id"] = $followed["id"];
                            $valid_user_data["username"] = $followed["username"];
                            $valid_user_data["permalink"] = $followed["permalink"];
                            $countries[$single_alias[1]][] = $valid_user_data;
                        }
                    }
                    if ($countryfound == 0) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $countries[$country][] = $valid_user_data;
                    }
                }
                $offset += $sc_page_limit;
                $following = json_decode($soundcloud->get($next_href, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                $next_href = $following["next_href"];
            }

            // finish the rest of the data
            if ( !$next_href ) {
                foreach($following["collection"] as $followed) {
                    $followed_ids[] = $followed["id"];
                    $city = remove_accents(strtolower(trim($followed["city"])));
                    $country = remove_accents(strtolower(trim($followed["country"])));
                    $cityfound = 0;
                    $countryfound = 0;
                    
                    // city aliases
                    $city = preg_replace('/\s+/', ' ', $city);
                    if (array_key_exists($city, $city_aliases)) {
                        $cityfound = 1;
                        foreach ($city_aliases[$city] as $single_alias) {
                            $valid_user_data = array();
                            $valid_user_data["id"] = $followed["id"];
                            $valid_user_data["username"] = $followed["username"];
                            $valid_user_data["permalink"] = $followed["permalink"];
                            $cities[$single_alias[1]][] = $valid_user_data;
                        }
                    }
                    if($cityfound == 0) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $cities[$city][] = $valid_user_data;
                    }
                    
                    // country aliases
                    if (array_key_exists($country, $country_aliases)) {
                        $countryfound = 1;
                        foreach ($country_aliases[$country] as $single_alias) {
                            $valid_user_data = array();
                            $valid_user_data["id"] = $followed["id"];
                            $valid_user_data["username"] = $followed["username"];
                            $valid_user_data["permalink"] = $followed["permalink"];
                            $countries[$single_alias[1]][] = $valid_user_data;
                        }
                    }
                    if ($countryfound == 0) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $countries[$country][] = $valid_user_data;
                    }
                }
            }

            foreach($following["collection"] as $followed) {
                $followed_ids[] = $followed["id"];
            }
            $_SESSION["followed"] = $followed_ids;
            ksort($cities);
            $_SESSION["cities"] = sort_by_length($cities);
            ksort($countries);
            $_SESSION["countries"] = sort_by_length($countries);
        }
        catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            exit($e->getMessage());
        }
        
        // Preload ignored ids
        $data = $mydb->getIgnores($_SESSION["user_data"]["id"]);

        while($line = mysqli_fetch_array($data)) {
            $ignored_ids[] = $line["iid"];
        }
        $_SESSION["ignored"] = $ignored_ids;
        
        Header('Location: index.php');
    }
    catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
        exit($e->getMessage());
    }
}

/**
 * If not logged in show SC login link
 * 
 */
if($_SESSION["logged"] == 0) {
    echo "<a href=\"" . $authorizeUrl . "\">Connect with SoundCloud</a>";
}

/**
 * Show all followed users from a requested city
 * 
 * This prints the contents of the cities array
 * 
 */
else if(isset($_REQUEST["city"])) {
    pagetop();
    $qcity = validate_str($_REQUEST["city"], $mydb->db);
    
    if (array_key_exists($qcity, $_SESSION["cities"])) {
        $count = count($_SESSION["cities"][$qcity]);
        echo "Following $count users from $qcity:</br>";
        foreach($_SESSION["cities"][$qcity] as $user) {
            echo "<span class=\"artist_name\"><a class=\"artist\" href=\"http://soundcloud.com/" . $user["permalink"] . "\">" . $user["username"] . "</a> ";
            echo "(<a class=\"ignore\" target=\"_blank\" href=\"index.php?ignore=" . $user["id"] . "\">ignore</a>)</span><br/>\n";
        }
    }
}


/**
 * Show all followed users from a requested country
 * 
 * This prints the contents of the countries array
 * 
 */
else if(isset($_REQUEST["country"])) {
    pagetop();
    $qcountry = validate_str($_REQUEST["country"], $mydb->db);
    if(array_key_exists($qcountry, $_SESSION["countries"])) {
        $count = count($_SESSION["countries"][$qcountry]);
        echo "Following $count users from $qcountry:</br>";

        foreach($_SESSION["countries"][$qcountry] as $user) {
            echo "<span class=\"artist_name\"><a class=\"artist\" href=\"http://soundcloud.com/" . $user["permalink"] . "\">" . $user["username"] . "</a> ";
            echo "(<a class=\"ignore\" target=\"_blank\" href=\"index.php?ignore=" . $user["id"] . "\">ignore</a>)</span><br/>\n";
        }
    }
}


/**
 * Aliases management page
 * 
 * This prints all cities and countries
 * and city alias creation form 
 * and country alias creation form
 * 
 */
else if(isset($_REQUEST["aliases"])&&($_REQUEST["aliases"] == 1)) {
    pagetop();
    $count_users = count($_SESSION["followed"]);
    echo "<b>Following $count_users users from these cities:</b><br/>";

    foreach ($_SESSION["cities"] as $city => $cityusers) {
        $countcityusers = count($cityusers);
        echo "<a href=\"index.php?city=".urlencode($city)."\">$city ($countcityusers)</a> ";
    }
    echo "<br/><br/>";
    echo "<b>Following $count_users users from these countries:</b><br/>";

    foreach ($_SESSION["countries"] as $country => $countryusers) {
        $countcountryusers = count($countryusers);
        echo "<a href=\"index.php?country=".urlencode($country)."\">$country ($countcountryusers)</a> ";
    }
    echo "<br/><br/>";
    
    echo "<b>create an alias for a country:</b>";
    countryalias("", "");
    
    echo "<b>create an alias for a city:</b>";
    cityalias("", "");
    
    echo "<b>Current country aliases:</b><br/>";
    foreach ($_SESSION["country_aliases"] as $alias => $countries_array) {
        foreach($countries_array as $single_array) {
            echo "$alias -> $single_alias[1] <a href=index.php?delcountryalias=$single_alias[0]>delete</a><br/>";
        }
    }
    
    echo "<b>Current city aliases:</b><br/>";
    foreach ($_SESSION["city_aliases"] as $alias => $cities_array) {
        foreach($cities_array as $single_alias) {
            echo "$alias -> $single_alias[1] <a href=index.php?delcityalias=$single_alias[0]>delete</a><br/>";
        }
    }
}


/**
 * 'Userstalk' page
 * 
 * This prints a form to search through a specific user's followers and followings
 * This searches for such users that 
 * a, follow or a followed by the $userstalk_user 
 * b, come from $userstalk_city
 * 
 * Can be used for example to check who from  'odessa' follows a specific label on SC (or vice versa)
 * 
 */
else if (isset($_REQUEST["userstalk"]) && ($_REQUEST["userstalk"] == 1)) {
   
    $quser = validate_str($_POST["userstalk_user"], $mydb->db);
    $qcity = validate_str($_POST["userstalk_city"], $mydb->db);
    $show_ign = false;
    if (isset($_POST["ignoreign"])) {
        $show_ign = validate_str($_POST["ignoreign"], $mydb->db);
        if($show_ign == "on") {
            $show_ign = true;
        }
    }
    $qsearch = validate_str($_POST["search"], $mydb->db);
    $followed_ids = $_SESSION["followed"];
    $ignored_ids = $_SESSION["ignored"];
    $seen_ids = $_SESSION["seen"];
    
    // FILTER FORM
    pagetop();
    echo "<b>search for musicians from a city connected to a specific user:</b>";
    filterform_userstalk($quser, $qcity, $userlimit, $show_ign);
    $followed_users = array();
    $followed_by_users = array();
    if($qsearch == 1) {
        try {
            // get people from qcity followed by the user
            $followed_before = microtime(true);
            $json_object = json_decode(file_get_contents('https://api.soundcloud.com/resolve.json?url=http://soundcloud.com/' . $quser . '&client_id=' . $sc_client_id));
            $id = $json_object-> {'id'};
            $offset = 0;
            
            $following = json_decode($soundcloud->get('users/' . $id . '/followings', array('limit' => $sc_page_limit, 'offset' => $offset)), true);
            $next_href = $following["next_href"];

            // process paging
            while ( $next_href ) {
                foreach($following["collection"] as $followed) {
                    $city = strtolower($followed["city"]);
                    if( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) && (!in_array($followed["id"], $ignored_ids)) ) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $valid_user_data["description"] = $followed["description"];
                        $valid_user_data["tracks"] = $followed["track_count"];
                        $followed_users[$followed["id"]] = $valid_user_data;
                    }
                }
                $offset += $sc_page_limit;
                $following = json_decode($soundcloud->get($next_href, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                $next_href = $following["next_href"];
            } 
            
            // finish the rest of the users
            if ( !$next_href ) {
                foreach($following["collection"] as $followed) {
                    $city = strtolower($followed["city"]);
                    if( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) && (!in_array($followed["id"], $ignored_ids)) ) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $valid_user_data["description"] = $followed["description"];
                        $valid_user_data["tracks"] = $followed["track_count"];
                        $followed_users[$followed["id"]] = $valid_user_data;
                    }
                }
            }
            $followed_after = microtime(true);
            
            // check people from qcity following the user
            $following_before = microtime(true);
            $offset = 0;
            
            $following = json_decode($soundcloud->get('users/' . $id . '/followers', array('limit' => $sc_page_limit, 'offset' => $offset)), true);
            $next_href = $following["next_href"];

            // process paging
            while ( $next_href ) {
                foreach ( $following["collection"] as $followed ) {
                    $city = strtolower($followed["city"]);
                    if ( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) && (!in_array($followed["id"], $ignored_ids)) ) {
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $valid_user_data["description"] = $followed["description"];
                        $valid_user_data["tracks"] = $followed["track_count"];
                        $followed_by_users[$followed["id"]] = $valid_user_data;
                    }
                }
                $offset += $sc_page_limit;
                $following = json_decode($soundcloud->get($next_href, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                $next_href = $following["next_href"];
            }
            
            // finish the rest of the users
            if ( !$next_href ) {
                foreach($following["collection"] as $followed) {
                    $city = strtolower($followed["city"]);
                    if( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) && (!in_array($followed["id"], $ignored_ids)) ) {
                        $followed_ids[] = $followed["id"];
                        $valid_user_data = array();
                        $valid_user_data["id"] = $followed["id"];
                        $valid_user_data["username"] = $followed["username"];
                        $valid_user_data["permalink"] = $followed["permalink"];
                        $valid_user_data["description"] = $followed["description"];
                        $valid_user_data["tracks"] = $followed["track_count"];
                        $followed_by_users[$followed["id"]] = $valid_user_data;
                    }
                }
            }
            
            $following_after = microtime(true);
            
            // show results
            echo "<b>$quser is following ".count($followed_users)." users from $qcity: </b></br></br>";
            usort($followed_users, 'compare_by_tracks');
            display_user_results($followed_users, $ignored_ids);
            echo "<br/>found in " .($followed_after - $followed_before). " sec.";
            
            // show more results
            echo "<br/><br/><b>$quser is followed by ".count($followed_by_users)." users from $qcity: </b></br></br>";
            usort($followed_by_users, 'compare_by_tracks');
            display_user_results($followed_by_users, $ignored_ids);
            echo "<br/>found in " .($following_after - $following_before). " sec.";
            
            echo "</body></html>";
        }
        
        catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            if(!isset($_REQUEST["user"])) {
                exit($e->getMessage());
            }
        }
        
    }
}


/**
 * 'Citysearch' page
 * 
 * This prints a form to search users from a particular town
 * 
 * This recursively searches for such users that
 * a, are followed by the current user
 * b, come from $userstalk_city
 * c, are followed by users found in a previous iteration
 * 
 * eg. depth 0:
 * - first the "seed" is identified as such users from $qcity
 * that are followed by the current user
 * - then these users are searched and every user they follow 
 * is tested if comming from $qcity, if yes, user is added to the results
 * 
 * depth 1 would be all of the above but at the end the results are
 * added into the 'seed' and the search is done again over this arrau
 * 
 * depth2 would be the results of depth 0 + 1 searched for the users they follow
 * 
 * This obviously can grow pretty big pretty fast. 
 * Search times are shown after the results to get a sense of how much it might take
 *
 * Try depth 0 or 1 first
 * 
 */
else if(isset($_REQUEST["seek"])&&($_REQUEST["seek"] == 1)) {
    
    $qcity = validate_str($_POST["seek_city"], $mydb->db);
    $max_depth = validate_int($_POST["seek_depth"], $mydb->db);
    $show_ign = false;
    if (isset($_POST["ignoreign"])) {
        $show_ign = validate_str($_POST["ignoreign"], $mydb->db);
        if($show_ign == "on") {
            $show_ign = true;
        }
    }
    $qsearch = validate_int($_POST["search"], $mydb->db);
    $followed_ids = $_SESSION["followed"];
    $ignored_ids = $_SESSION["ignored"];
    
    // FILTER FORM
    pagetop();
    echo "<b>search for all musicians from a city:</b>";
    filterform_citysearch($qcity, $max_depth, $show_ign);
    echo "</div></body></html>";
    
    // Iteratively search for users from a given city
    $seed_ids = array();
    if($qsearch == 1) {
        $cities = $_SESSION["cities"];
        if(array_key_exists($qcity, $cities)) {
            echo "<b>Showing all users from $qcity followed by:</br></b>";
            foreach($cities[$qcity] as $cityusers) {
                $name = $cityusers["username"];
                $link = $cityusers["permalink"];
                echo "<span class=\"artist_name\"><a class=\"artist\" target=\"_blank\" href=\"http://soundcloud.com/" . $link . "\">" . $name . "</a></span> ";
                $seed_ids[] = $cityusers["id"];
            }
            echo "<br/><br/>";
            
            // now the main part
            $initial_seed_ids = $seed_ids;
            $found_ids = $seed_ids;
            $found_users = array();
            $before = microtime(true);
            
            for ($depth = 0; $depth <= $max_depth; $depth ++) {
                foreach ($seed_ids as $id) {
                    $offset = 0;
                    
                    $following = json_decode($soundcloud->get('users/' . $id . '/followings', array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                    $next_href = $following["next_href"];

                    while (isset($next_href)) {
                        foreach ($following["collection"] as $followed) {
                            $city = strtolower($followed["city"]);
                            if ( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) && (!in_array($followed["id"], $ignored_ids)) ) {
                                if (!in_array($followed["id"], $found_ids)) {
                                    $found_ids[] = $followed["id"];
                                    $valid_user_data = array();
                                    $valid_user_data["id"] = $followed["id"];
                                    $valid_user_data["username"] = $followed["username"];
                                    $valid_user_data["permalink"] = $followed["permalink"];
                                    $valid_user_data["description"] = $followed["description"];
                                    $valid_user_data["tracks"] = $followed["track_count"];
                                    $valid_user_data["rank"] = 0;
                                    $found_users[$followed["id"]] = $valid_user_data;
                                } else if (!in_array($followed["id"], $initial_seed_ids)) {
                                    $found_users[$followed["id"]]["rank"] = $found_users[$followed["id"]]["rank"] + 1;
                                }
                            }
                        }
                        $offset += $sc_page_limit;
                        $following = json_decode($soundcloud->get($next_href, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                        $next_href = $following["next_href"];
                    }
                    
                    // finish the rest of the users
                    if ( !$next_href ) {
                        foreach ($following["collection"] as $followed) {
                            $city = strtolower($followed["city"]);
                            if ( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) && (!in_array($followed["id"], $ignored_ids)) ) {
                                if (!in_array($followed["id"], $found_ids)) {
                                    $found_ids[] = $followed["id"];
                                    $valid_user_data = array();
                                    $valid_user_data["id"] = $followed["id"];
                                    $valid_user_data["username"] = $followed["username"];
                                    $valid_user_data["permalink"] = $followed["permalink"];
                                    $valid_user_data["description"] = $followed["description"];
                                    $valid_user_data["tracks"] = $followed["track_count"];
                                    $valid_user_data["rank"] = 0;
                                    $found_users[$followed["id"]] = $valid_user_data;
                                } else if (!in_array($followed["id"], $initial_seed_ids)) {
                                    $found_users[$followed["id"]]["rank"] = $found_users[$followed["id"]]["rank"] + 1;
                                }
                            }
                        }
                    }
                }
                
                // expand seed by the newly found users
                foreach ($found_ids as $found_id) {
                    if (!in_array($found_id, $seed_ids)) {
                        $seed_ids[] = $found_id;
                    }
                }
            }
            
            // show them
            usort($found_users, 'gnd_rank1');
            display_user_results($found_users, $ignored_ids);
                
            $count_users = count($found_users);
            $after = microtime(true);
            echo "Shown $count_users users in $qcity that have tracks. Executed in: " .($after - $before). " sec. Depth = ".($depth-1)." <br/><br/><br/>";
        } else {
            echo "No users from $city currently followed";
        }
    }
} 


/**
 * 'Index' page
 * 
 * This prints all the cities and countries 
 * where users followed by the current users come from
 * 
 */
else {
    // AK NIC TAK POTOM DAJ SEARCH BOX
    pagetop();
    $count_users = count($_SESSION["followed"]);
    echo "<b>Following $count_users users from these cities:</b><br/>";

    foreach($_SESSION["cities"] as $city => $cityusers) {
        $countcityusers = count($cityusers);
        echo "<a href=\"index.php?city=".urlencode($city)."\">$city ($countcityusers)</a> ";
    }
    echo "<br/><br/>";
    echo "<b>Following $count_users users from these countries:</b><br/>";

    foreach($_SESSION["countries"] as $country => $countryusers) {
        $countcountryusers = count($countryusers);
        echo "<a href=\"index.php?country=".urlencode($country)."\">$country ($countcountryusers)</a> ";
    }
    echo "<br/><br/>";
}

?>