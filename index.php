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
 * 2012 - 2018, gnd
 *
 */

session_start();
/*
SESSION anatomy:
    - user_logged
    - user_data[]:
        - id
        - name
        - sid
    - sc_logged
    - sc_data[]:
        - id
    - cities[]
    - city_aliases[]
    - countries[]
    - cuntry_aliases[]
    - followed[]
    - ignored[]
    - seen[]
*/
include("php-soundcloud/Services/Soundcloud.php");
include "db_class.php";
include "functions.php";
include "sttngs.php";

$mydb = new db();
$mydb->connect();

$soundcloud = new Services_Soundcloud($sc_client_id, $sc_client_secret, $sc_redirect);
$authorizeUrl = $soundcloud->getAuthorizeUrl();
$soundcloud->setCurlOptions(CURLOPT_CAINFO, '/etc/ssl/certs/mozilla_root_certs.pem');  // USES NEW NEW CA BUNDLE

include "requests.php";

/**
 * If not logged in show user login link
 *
 */
if (!isset($_SESSION["user_logged"]) || ($_SESSION["user_logged"] === 0)) {
    //echo "<a href=\"" . $authorizeUrl . "\">Connect with SoundCloud</a>";
    login_form();
    die();
}


/**
 * 'Settings' page
 *
 *
 */
else if (isset($_SESSION["user_logged"]) && ($_SESSION["user_logged"] === 1) && isset($_REQUEST["settings"])) {

    $uid = validate_int($_REQUEST["settings"], $mydb->db);
    // user edits himself
    if ($uid == $_SESSION["user_data"]["id"]) {
        user_pagetop($dev_version);
        edit_user_form($_SESSION["user_data"]["name"], $_SESSION["user_data"]["id"], 1, true);
        echo "</body></html>";
        die();
    }

    // admin edits user
    else if ($_SESSION["user_data"]["sid"] > 1) {
        $data = $mydb->getUserDataByID($uid);
        $line = mysqli_fetch_array($data);
        $username = $line["username"];
        $enabled = $line["enabled"];
        user_pagetop($dev_version);
        edit_user_form($username, $uid, $enabled, false);
        echo "</body></html>";
        die();
    }
}


/**
 * 'Admin' page
 *
 *
 */
else if (isset($_SESSION["user_logged"]) && ($_SESSION["user_logged"] === 1) && ($_SESSION["user_data"]["sid"] > 1) && isset($_REQUEST["admin"]) && ($_REQUEST["admin"] === "1")) {

  user_pagetop($dev_version);
  add_user_form();

    // USER TABLE STARTING
    echo "<br/><br/>Manage users<br/>\n";
	echo "<table border=\"0\" cellspacing=\"0\">\n";
    echo "<tr><td>username&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>role&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>status&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>action</td></tr>\n";
    $data = $mydb->getUsers();
    while ($line = mysqli_fetch_array($data)) {
        display_user_data($line);
	}
	echo "</table>";

    echo "</div></body></html>";
    die();

}


/**
 * If user logged in but not nto SC show SC login
 *
 */
else if ((isset($_SESSION["user_logged"]) && ($_SESSION["user_logged"] === 1)) && (!isset($_SESSION["sc_logged"]) || ($_SESSION["sc_logged"] === 0))) {

    user_pagetop($dev_version);
    // also show pass change option
    echo "<a href=\"" . $authorizeUrl . "\">Connect with SoundCloud</a>";
    die();
}


/**
 * Show all followed users from a requested city
 *
 * This prints the contents of the cities array
 *
 */
if (sc_logged() && isset($_REQUEST["city"])) {
    pagetop($dev_version);
    $qcity = validate_str($_REQUEST["city"], $mydb->db);

    if (array_key_exists($qcity, $_SESSION["cities"])) {
        $count = count($_SESSION["cities"][$qcity]);
        echo "Following $count users from $qcity:</br>";
        foreach($_SESSION["cities"][$qcity] as $user) {
            echo "<span class=\"artist_name\"><a class=\"artist\" href=\"http://soundcloud.com/" . $user["permalink"] . "\">" . $user["username"] . "</a> ";
            echo "(<a class=\"ignore\" target=\"_blank\" href=\"index.php?ignore=" . $user["id"] . "\">ignore</a>)</span><br/>\n";
        }
    }
    die();
}


/**
 * Show all followed users from a requested country
 *
 * This prints the contents of the countries array
 *
 */
else if (sc_logged() && isset($_REQUEST["country"])) {
    pagetop($dev_version);
    $qcountry = validate_str($_REQUEST["country"], $mydb->db);
    if(array_key_exists($qcountry, $_SESSION["countries"])) {
        $count = count($_SESSION["countries"][$qcountry]);
        echo "Following $count users from $qcountry:</br>";

        foreach($_SESSION["countries"][$qcountry] as $user) {
            echo "<span class=\"artist_name\"><a class=\"artist\" href=\"http://soundcloud.com/" . $user["permalink"] . "\">" . $user["username"] . "</a> ";
            echo "(<a class=\"ignore\" target=\"_blank\" href=\"index.php?ignore=" . $user["id"] . "\">ignore</a>)</span><br/>\n";
        }
    }
    die();
}

/**
 * Aliases management page
 *
 * This prints all cities and countries
 * and city alias creation form
 * and country alias creation form
 *
 */
else if (sc_logged() && isset($_REQUEST["aliases"]) && ($_REQUEST["aliases"] === "1")) {
    pagetop($dev_version);
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
else if (sc_logged() && isset($_REQUEST["userstalk"]) && ($_REQUEST["userstalk"] === "1")) {

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
    pagetop($dev_version);
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
            display_user_results($followed_users);
            echo "<br/>found in " .($followed_after - $followed_before). " sec.";

            // show more results
            echo "<br/><br/><b>$quser is followed by ".count($followed_by_users)." users from $qcity: </b></br></br>";
            usort($followed_by_users, 'compare_by_tracks');
            display_user_results($followed_by_users);
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
else if (sc_logged() && isset($_REQUEST["seek"]) && ($_REQUEST["seek"] === "1")) {

    $followed_ids = $_SESSION["followed"];
    $ignored_ids = $_SESSION["ignored"];

    // FILTER FORM
    pagetop($dev_version);

    // new city search
    echo "city: <input type=\"text\" id=\"seek_city\" value=\"\" /> ";
    echo "depth: <input type=\"text\" id=\"seek_depth\" value=\"\" /> ";
    echo "<input type=\"submit\" onclick=\"seekxml('trencin',0)\" value=\"search\" /> ";
    echo "<input type=\"submit\" id=\"switch_seen\" onclick=\"switch_seen()\" value=\"show hidden\" />";
    echo " <span id=\"search_status\"/></span>";

    echo "<div id=\"results\">";
    echo "<br/><br/>";
    echo "\n\n<table style=\"border: 0px;\">\n";
    echo "<thead><tr>";
    echo "<th>hide</th>";
    echo "<th id=\"th_name\" onclick=\"change_sorting('name')\" class=\"sortable\" >name&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_rank\" onclick=\"change_sorting('rank')\" class=\"sortable\" >rank&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_depth\" onclick=\"change_sorting('depth')\" class=\"sortable\" >depth&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_deg\" onclick=\"change_sorting('deg')\" class=\"sortable\" >degree&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_followers\" onclick=\"change_sorting('followers')\" class=\"sortable\" >followers&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_tracks\" onclick=\"change_sorting('tracks')\" class=\"sortable\" >tracks&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_mta\" onclick=\"change_sorting('mta')\" class=\"sortable\" title=\"Median Track Age (days ago)\">mta&nbsp;&nbsp;&nbsp;</th>";
    echo "<th id=\"th_lta\" onclick=\"change_sorting('lta')\" class=\"sortable\" title=\"Last Track Age (days ago)\">lta&nbsp;&nbsp;&nbsp;</th>";
    echo "<th>description</th></tr></thead>\n";
    echo "<tbody id=\"results_body\"></tbody></table>";
    echo "</div>";

    echo "<input id=\"uid\" type=\"hidden\" value=\"".$_SESSION["user_data"]["id"]."\">";
    echo "</div></body></html>";
}


/**
 * 'Index' page
 *
 * This prints all the cities and countries
 * where users followed by the current users come from
 *
 */
else if (sc_logged()) {
    // AK NIC TAK POTOM DAJ SEARCH BOX
    pagetop($dev_version);
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
