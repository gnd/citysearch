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


/**
 * User Login event
 *
 */
if ( isset($_POST["name"]) && isset($_POST["pass"])) {

        $recv_name = validate_str($_POST["name"], $mydb->db);
        $recv_pass = validate_str($_POST["pass"], $mydb->db);

        // pull out stor_pass from the db
        $data = $mydb->getUserPass($recv_name);
        $line = mysqli_fetch_array($data);
        $stor_pass = $line["passwd"];

        // login logic
        if (check_pwd($recv_pass, $stor_pass)) {

            // regenerate session id
            session_regenerate_id();

            // then add data into it
            $data = $mydb->getUserDataByName($recv_name);
            $line = mysqli_fetch_array($data);
            $_SESSION["user_data"] = array('id' => $line["uid"],
                                            'name' => $line["username"],
                                            'sid' => $line["sid"]);
            $_SESSION["user_logged"] = 1;
            Header('Location: index.php');

        } else {
            $_SESSION["user_logged"] = 0;
            die("Incorrect username or password");
        }
}


/**
 * SoundCloud login event
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
if (isset($_SESSION["user_logged"]) && ($_SESSION["user_logged"] === 1) && isset($_GET["code"])) {

    try {
        $accessToken = $soundcloud->accessToken($_GET['code']);
        $_SESSION["sc_logged"] = 1;
        $followed_ids = array();
        $ignored_ids = array();
        $seen_ids = array();
        try {
            $me = json_decode($soundcloud->get('me'), true);
        }
        catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            exit($e->getMessage());
        }
        $_SESSION["sc_data"] = array('access_token' =>$accessToken['access_token'],
                                       'refresh_token' =>$accessToken['refresh_token'],
                                       'expires' => time()+$accessToken['expires_in'],
                                       'id' =>$me['id'],
                                       'username' =>$me['username'],
                                       'name' =>$me['full_name'],
                                       'avatar' =>$me['avatar_url'],
                                       'followed_count' =>$me['followings_count']);

        // Preload aliased cities
        $city_aliases = array();
        $data = $mydb->getCityAliases($_SESSION["sc_data"]["id"]);
        while ($line = mysqli_fetch_array($data)) {
            $single_alias = array();
            $single_alias[] = $line["id"];
            $single_alias[] = $line["city"];
            $city_aliases[$line["alias"]][] = $single_alias;
        }
        $_SESSION["city_aliases"] = $city_aliases;

        // Preload aliased countries
        $country_aliases = array();
        $data = $mydb->getCountryAliases($_SESSION["sc_data"]["id"]);
        while ($line = mysqli_fetch_array($data)) {
            $single_alias = array();
            $single_alias[] = $line["id"];
            $single_alias[] = $line["country"];
            $country_aliases[$line["alias"]][] = $single_alias;
        }
        $_SESSION["country_aliases"] = $country_aliases;

        // clear temp session data from db
        $mydb->clearSessionStatus($_SESSION["user_data"]["id"]);
        $mydb->clearSessionProgress($_SESSION["user_data"]["id"]);
        $mydb->updateSessionStatus($_SESSION["user_data"]["id"], 0, 0, 0);

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
 * Logout event
 *
 */
if (isset($_REQUEST["logout"]) && ($_REQUEST["logout"] === "1")) {
    $_SESSION["user_logged"] = 0;
    $_SESSION["user_data"]["id"] = 0;
    $_SESSION["user_data"]["sid"] = 0;
    $_SESSION["sc_logged"] = 0;
    session_unset();
    session_destroy();
    header('Location: index.php');
}


/**
 * Creates a new user
 *
 */
if ((isset($_REQUEST["adduser"])) && ($_SESSION["user_data"]["sid"] > 1)) {

	$ok = false;

	$newname = validate_str($_POST["name"], $mydb->db);
	$newmail = validate_str($_POST["mail"], $mydb->db);
	$sid = 1; // hardcoded for the time being

	if ($newname == "") {
        header('Content-Type: application/xml');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<result>\n";
        echo "\t<code>0</code>\n";
        echo "\t<reason>Name cant be empty.</reason>\n";
        echo "</result>";
        die();
	} else {
        if ($newmail == "") {
            header('Content-Type: application/xml');
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<result>\n";
            echo "\t<code>0</code>\n";
            echo "\t<reason>Incorrect mail.</reason>\n";
            echo "</result>";
            die();
        } else {
            // check if mail already registered
            $data = $mydb->checkMailExists($newmail);
            $line = mysqli_fetch_array($data);
            if ($line["count"] > 0) {
                header('Content-Type: application/xml');
                echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                echo "<result>\n";
                echo "\t<code>0</code>\n";
                echo "\t<reason>Mail already registered</reason>\n";
                echo "</result>";
                die();
            } else {
                $ok = true;
            }
        }
	}

	if ($ok) {
        // Add user
        $newuid = $mydb->createUser($newname, $newmail, $sid);
        // Generate invite code substr(hash username + mail + salt)
        $hash = get_url_hash($newname . $newmail);
        $url = $SITE_URL . "/index.php?new=" . $hash;
        // Store hash in the db
        $mydb->storeInviteHash($newuid, $newname, $hash);
        // Create mail body
        $body = "Hello $newname,\n\nwelcome to Citysearch!\n\nTo finish registration click on the following link to verify your account:\n";
        $body = $body . $url . "\n\nThanks,\n\nCitysearch.";
        // Now send invitation mail
        send_mail("citysearch@easterndaze.net", $newmail, "Welcome to Citysearch", $body);

        // Announce success
        header('Content-Type: application/xml');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<result>\n";
        echo "\t<code>1</code>\n";
        echo "\t<reason>User successfully invited</reason>\n";
        echo "</result>";
        die();
	}
}


/**
 * Edit a existing user
 *
 */
if ((isset($_REQUEST["edituser"])) && ($_SESSION["user_data"]["sid"] > 0)) {

	$ok = true;

    // TODO - change request back to post
	$uid = validate_int($_REQUEST["uid"], $mydb->db);
	$enabled = 0;

	if ((isset($_REQUEST["enabled"])) && ($_REQUEST["enabled"] != "")) {
		if (strcmp($_REQUEST["enabled"],"on") == 0) {
			$enabled = 1;
		}
	}

	// User editing himself
	if (($uid == $_SESSION["user_data"]["id"]) && ($_SESSION["user_data"]["sid"] > 0)) {

        // check if we have username
        $username = $_SESSION["user_data"]["name"];
        if (!isset($username) || ($username == "")){
            $ok = false;
            die("Cant determine username");
        }

        // change pass if all ok
        if ((isset($_REQUEST["newpass_1"])) && ($_REQUEST["newpass_1"] != "")) {
            $newpass_1 = validate_str($_REQUEST["newpass_1"], $mydb->db);
            $newpass_2 = validate_str($_REQUEST["newpass_2"], $mydb->db);
			$oldpass = validate_str($_REQUEST["oldpass"], $mydb->db);

            // pull out stor_pass from the db
            $data = $mydb->getUserPass($username);
            $line = mysqli_fetch_array($data);
            $stor_pass = $line["passwd"];

            // check if oldpass fits
			if (check_pwd($oldpass, $stor_pass)) {
				if (strcmp($newpass_1,$newpass_2) == 0) {
					if (strcmp($oldpass,$newpass_1) != 0) {
                        // if ($passres[0] == 1) {//TODO: do as PHP too
                        $hash = get_pwd_hash($newpass_1);
						$mydb->updateUserPass($uid, $hash);
					} else {
						$ok = false;
                        //echo "<h1>old pass is a no no</h1><br/>go <a href=index.php?edituser=".$uid.">back</a>";
                        // Output XML
                        header('Content-Type: application/xml');
                        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                        echo "<result>\n";
                        echo "\t<code>0</code>\n";
                        echo "\t<reason>New password the same as old</reason>\n";
                        echo "</result>";
                        die();
					}
				} else {
					$ok = false;
					//echo "<h1>Passwords dont match</h1><br/>go <a href=index.php?edituser=".$uid.">back</a>";
                    // Output XML
                    header('Content-Type: application/xml');
                    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                    echo "<result>\n";
                    echo "\t<code>0</code>\n";
                    echo "\t<reason>Passwords dont match</reason>\n";
                    echo "</result>";
                    die();
				}
			} else {
				$ok = false;
				//echo "<h1>Wrong password</h1><br/>go <a href=index.php?edituser=".$uid.">back</a>";
                // Output XML
                header('Content-Type: application/xml');
                echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                echo "<result>\n";
                echo "\t<code>0</code>\n";
                echo "\t<reason>Bad old password</reason>\n";
                echo "</result>";
                die();
			}
		}
	}

	// Admin edits user
	else if ($_SESSION["user_data"]["sid"] > 1) {
        if ((isset($_REQUEST["newpass_1"])) && ($_REQUEST["newpass_1"] != "")) {
            $newpass_1 = validate_str($_REQUEST["newpass_1"], $mydb->db);
            $newpass_2 = validate_str($_REQUEST["newpass_2"], $mydb->db);
            if (strcmp($newpass_1,$newpass_2) == 0) {
                $hash = get_pwd_hash($newpass_1);
                $mydb->updateUserPass($uid, $hash);
            } else {
				$ok = false;
                //echo "<h1>Passwords dont match</h1><br/>go <a href=ftpass.php?edit=".$uid.">back</a>";
                header('Content-Type: application/xml');
                echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                echo "<result>\n";
                echo "\t<code>0</code>\n";
                echo "\t<reason>Passwords dont match</reason>\n";
                echo "</result>";
                die();
            }
        } else {
            $mydb->updateUserStatus($uid, $enabled);
        }
    }

    // Tell if all ok
    if ($ok) {
        header('Content-Type: application/xml');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<result>\n";
        echo "\t<code>1</code>\n";
        echo "\t<reason>Change successful</reason>\n";
        echo "</result>";
        die();
	}
}


// TODO: distinguish between user and sc_user
/**
 * Adds a user id into the ignore list
 *
 */
if (sc_logged() && isset($_REQUEST["ignore"]) && ($_REQUEST["ignore"] != 0)) {

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
if (sc_logged() && isset($_REQUEST["seen"]) && ($_REQUEST["seen"] != 0)) {
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
 * Removes a user from the seen list
 *
 */
if (sc_logged() && isset($_REQUEST["unsee"]) && ($_REQUEST["unsee"] != 0)) {
    $iid = validate_int($_REQUEST["unsee"], $mydb->db);
    $mydb->delSeen($_SESSION["user_data"]["id"], $iid);

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
 * Shows seen users as a XML
 *
 */
if (sc_logged() && isset($_REQUEST["seenxml"]) && ($_REQUEST["seenxml"] != 0)) {

    // Load seen ids
    $seen_ids = array();
    $data = $mydb->getSeen($_SESSION["user_data"]["id"]);

    // Output XML
    header('Content-Type: application/xml');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<seen>\n";
    while($line = mysqli_fetch_array($data)) {
        echo "\t<id>" . $line["iid"] . "</id>\n";
    }
    echo "</seen>";
    die();
}


/**
 * Shows cities from $_SESSION as XML
 *
 */
 if (sc_logged() && isset($_REQUEST["citiesxml"]) && ($_REQUEST["citiesxml"] != 0)) {

    // Output XML
    header('Content-Type: application/xml');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<cities>\n";

    foreach($_SESSION["cities"] as $city => $foo) {
        // avoid showing empty city
        if (strcmp($city, "") != 0) {
            echo "\t<city>" . str_replace("&", "&#038;", $city) . "</city>\n";
        }
    }

    echo "</cities>";
    die();
}


/**
 * Adds a alias for a malformed city name
 *
 * If a city like 'kassa' is found in results, create an alias
 * and add it to other results from the real town (in this case 'kosice')
 */
if (sc_logged() && isset($_REQUEST["cityalias"]) && ($_REQUEST["cityalias"] === "1")) {
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
if (sc_logged() && isset($_REQUEST["delcityalias"])) {
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
if (sc_logged() && isset($_REQUEST["countryalias"]) && ($_REQUEST["countryalias"] === "1")) {
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
if (sc_logged() && isset($_REQUEST["delcountryalias"])) {
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


/*
 * Just like seek, but output a XML
 *
 */
if (sc_logged() && isset($_REQUEST["seekxml"]) && ($_REQUEST["seekxml"] != 0) ) {

    $qcity = validate_str($_REQUEST["seek_city"], $mydb->db);
    $max_depth = validate_int($_REQUEST["seek_depth"], $mydb->db);
    $followed_ids = $_SESSION["followed"];

    $seed_ids = array();
    $cities = $_SESSION["cities"];

    // Iteratively search for users from a given city
    if (array_key_exists($qcity, $cities)) {
        foreach($cities[$qcity] as $cityusers) {
            $seed_ids[] = $cityusers["id"];
        }

        // now the main part
        $initial_seed_ids = $seed_ids;
        $found_ids = $seed_ids;
        $found_users = array();
        $dj_users = array();
        $failed_users = array();
        $id_index = 0;
        $error_count = 0;
        $before = microtime(true);

        for ($depth = 0; $depth <= $max_depth; $depth ++) {
            $id_index = 0;
            $mydb->updateSessionStatus($_SESSION["user_data"]["id"], 1, $depth, $max_depth);
            foreach ($seed_ids as $id) {
                $id_index += 1;
                $mydb->updateSessionProgress($_SESSION["user_data"]["id"], $id_index / count($seed_ids));
                $offset = 0;

                $user_failed = 0;
                $href_failed = 0;
                try {
                    $following = json_decode($soundcloud->get('users/' . $id . '/followings', array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                    $next_href = $following["next_href"];
                } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                    $user_failed = 1;
                    $error_count++;
                    error_log("Failed getting user data for id: " . $id . " : " . $e);
                }

                // lets be bold and try again almost immediately
                if ($user_failed > 0) {
                    sleep(5);
                    try {
                        $following = json_decode($soundcloud->get('users/' . $id . '/followings', array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                        $next_href = $following["next_href"];
                        $user_failed = 0;
                    } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                        $failed_users[] = $id;
                        $error_count++;
                        error_log("Failed *again* getting user data for id: " . $id . " : " . $e);
                    }
                }

                while (isset($next_href) && ($user_failed < 1) && ($href_failed < 1)) {
                    foreach ($following["collection"] as $followed) {
                        $city = strtolower($followed["city"]);
                        if ( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) ) {
                            if (!in_array($followed["id"], $found_ids)) {
                                if (!in_array($followed["id"], $dj_users)) {

                                    // get info on all users tracks
                                    $track_failed = 0;
                                    try {
                                        $tracks = json_decode($soundcloud->get('users/' . $followed["id"] . '/tracks', array('limit' => $sc_page_limit, 'offset' => 0)), true);
                                    } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                                        error_log("Failed getting track data for id: " . $followed["id"] . " at " . $followed["permalink_url"] . " : " . $e);
                                        $track_failed = 1;
                                        $error_count++;
                                    }

                                    if ((count($tracks) > 0 ) && ($track_failed < 1)) {
                                        $track_genre = array();
                                        $track_tags = array();
                                        $track_times = array();
                                        $track_avg_length = 0;
                                        $listeners = 0;
                                        foreach ($tracks as $track) {
                                            $track_genre[] = trim(strtolower($track["genre"]));
                                            $track_times[] = $track["created_at"];
                                            $track_avg_length = $track_avg_length + $track["duration"];
                                            if (isset($track["playback_count"])) {
                                                $listeners = $listeners + $track["playback_count"];
                                            }
                                        }
                                        // process track data - find median track age & average length
                                        sort($track_times);
                                        if (count($track_times) % 2 == 0) {
                                            // even number of elements, behave as if we would discard the oldest and take that median
                                            $median_track_age = $track_times[count($track_times) / 2];
                                        } else {
                                            if (count($track_times) == 1) {
                                                $median_track_age = $track_times[0];
                                            } else {
                                                // odd number, take middle element
                                                $median_track_age = $track_times[(count($track_times)+1) / 2];
                                            }
                                        }
                                        $track_avg_length = $track_avg_length / count($tracks) / 1000;
                                        $last_track_age = $tracks[0]["created_at"];

                                        // not a dj-set account
                                        if ($track_avg_length < $MAX_ACCEPTED_AVG_TRACK_LENGTH) {
                                            $found_ids[] = $followed["id"];
                                            $valid_user_data = array();
                                            $valid_user_data["id"] = $followed["id"];
                                            $valid_user_data["username"] = $followed["username"];
                                            $valid_user_data["permalink"] = $followed["permalink_url"];
                                            $valid_user_data["description"] = $followed["description"];
                                            $valid_user_data["tracks"] = count($tracks);
                                            $valid_user_data["followers_count"] = $followed["followers_count"];
                                            $valid_user_data["track_genres"] = array_unique($track_genre,SORT_STRING);
                                            $valid_user_data["median_track_age"] = $median_track_age;
                                            $valid_user_data["last_track_age"] = $last_track_age;
                                            $valid_user_data["listeners_count"] = $listeners;
                                            $valid_user_data["degree"] = 0;
                                            $valid_user_data["depth"] = $depth+1;
                                            $found_users[$followed["id"]] = $valid_user_data;
                                        } else {
                                            $dj_users[] = $followed["id"];              // add to dj blacklist
                                        }
                                    }
                                }
                            } else {
                                if (!isset($found_users[$followed["id"]]["degree"])) {
                                    $found_users[$followed["id"]]["degree"] = 0;
                                } else {
                                    $found_users[$followed["id"]]["degree"] = $found_users[$followed["id"]]["degree"] + 1;
                                }
                            }
                        }
                    }

                    // get next page if any
                    $offset += $sc_page_limit;
                    $href_failed = 0;
                    try {
                        $following = json_decode($soundcloud->get($next_href, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                        $next_href = $following["next_href"];
                    } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                        error_log("Failed getting next_href for id: " . $followed["id"] . " at " . $followed["permalink_url"] . " : " . $e);
                        $href_failed = 1;
                        $error_count++;
                    }

                    // lets try again to make sure
                    if ($href_failed > 0) {
                        try {
                            $following = json_decode($soundcloud->get($next_href, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                            $next_href = $following["next_href"];
                            $href_failed = 0;
                        } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                            error_log("Failed *again* getting next_href for id: " . $followed["id"] . " at " . $followed["permalink_url"] . " : " . $e);
                            $error_count++;
                        }
                    }
                }

                // finish the rest of the users
                if ( !$next_href && ($user_failed < 1) ) {
                    foreach ($following["collection"] as $followed) {
                        $city = strtolower($followed["city"]);
                        if ( ($followed['track_count'] > 0) && (strpos($city, $qcity) !== false) ) {
                            if (!in_array($followed["id"], $found_ids)) {
                                if (!in_array($followed["id"], $dj_users)) {

                                    // get info on all users tracks
                                    $track_failed = 0;
                                    try {
                                        $tracks = json_decode($soundcloud->get('users/' . $followed["id"] . '/tracks', array('limit' => $sc_page_limit, 'offset' => 0)), true);
                                    } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                                        error_log("Failed getting track data for id: " . $followed["id"] . " at " . $followed["permalink_url"] . " : " . $e);
                                        $track_failed = 1;
                                        $error_count++;
                                    }

                                    if ((count($tracks) > 0 ) && ($track_failed < 1)) {
                                        $track_genre = array();
                                        $track_tags = array();
                                        $track_times = array();
                                        $track_avg_length = 0;
                                        $listeners = 0;
                                        foreach ($tracks as $track) {
                                            $track_genre[] = trim(strtolower($track["genre"]));
                                            $track_times[] = $track["created_at"];
                                            $track_avg_length = $track_avg_length + $track["duration"];
                                            if (isset($track["playback_count"])) {
                                                $listeners = $listeners + $track["playback_count"];
                                            }
                                        }
                                        // process track data - find median track age & average length
                                        sort($track_times);
                                        if (count($track_times) % 2 == 0) {
                                            // even number of elements, behave as if we would discard the oldest and take that median
                                            $median_track_age = $track_times[count($track_times) / 2];
                                        } else {
                                            if (count($track_times) == 1) {
                                                $median_track_age = $track_times[0];
                                            } else {
                                                // odd number, take middle element
                                                $median_track_age = $track_times[(count($track_times)+1) / 2];
                                            }
                                        }
                                        $track_avg_length = $track_avg_length / count($tracks) / 1000;
                                        $last_track_age = $tracks[0]["created_at"];

                                        // not a dj-set account
                                        if ($track_avg_length < $MAX_ACCEPTED_AVG_TRACK_LENGTH) { // not a dj-set account
                                            $found_ids[] = $followed["id"];
                                            $valid_user_data = array();
                                            $valid_user_data["id"] = $followed["id"];
                                            $valid_user_data["username"] = $followed["username"];
                                            $valid_user_data["permalink"] = $followed["permalink_url"];
                                            $valid_user_data["description"] = $followed["description"];
                                            $valid_user_data["tracks"] = count($tracks);
                                            $valid_user_data["followers_count"] = $followed["followers_count"];
                                            $valid_user_data["track_genres"] = array_unique($track_genre,SORT_STRING);
                                            $valid_user_data["median_track_age"] = $median_track_age;
                                            $valid_user_data["last_track_age"] = $last_track_age;
                                            $valid_user_data["listeners_count"] = $listeners;
                                            $valid_user_data["degree"] = 0;
                                            $valid_user_data["depth"] = $depth+1;
                                            $found_users[$followed["id"]] = $valid_user_data;
                                        } else {
                                            $dj_users[] = $followed["id"];              // add to dj blacklist
                                        }
                                    }
                                }
                            } else {
                                if (!isset($found_users[$followed["id"]]["degree"])) {
                                    $found_users[$followed["id"]]["degree"] = 0;
                                } else {
                                    $found_users[$followed["id"]]["degree"] = $found_users[$followed["id"]]["degree"] + 1;
                                }
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

        // include also the initial seed in the search
        $mydb->updateSessionStatus($_SESSION["user_data"]["id"], 'initial', 0, 0);
        $id_index = 0;
        foreach ($initial_seed_ids as $id) {
            $id_index += 1;
            $mydb->updateSessionProgress($_SESSION["user_data"]["id"], $id_index / count($seed_ids));
            $offset = 0;
            $user_failed = 0;

            try {
                $user = json_decode($soundcloud->get('users/' . $id, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
            } catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                    $user_failed = 1;
                    $error_count++;
                    error_log("Failed getting user data for initial id: " . $id . " : " . $e);
            }

            // try again
            if ($user_failed > 0) {
                sleep(5);
                try {
                    $user = json_decode($soundcloud->get('users/' . $id, array('limit' => $sc_page_limit, 'offset' => $offset)), true);
                    $user_failed = 0;
                } catch(Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                    $failed_users[] = $id;
                    $error_count++;
                    error_log("Failed *again* getting user data for initial id: " . $id . " : " . $e);
                }
            }

            $city = strtolower($user["city"]);
            if ( ($user['track_count'] > 0) && (strpos($city, $qcity) !== false) && ($user_failed < 1)) {
                // get info on all users tracks
                $track_failed = 0;
                try {
                    $tracks = json_decode($soundcloud->get('users/' . $id . '/tracks', array('limit' => $sc_page_limit, 'offset' => 0)), true);
                } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
                    error_log("Failed getting track data for id: " . $id . " at " . $user["permalink_url"] . " : " . $e);
                    $track_failed = 1;
                    $error_count++;
                }

                if ((count($tracks) > 0 ) && ($track_failed < 1)) {
                    $track_genre = array();
                    $track_tags = array();
                    $track_times = array();
                    $track_avg_length = 0;
                    $listeners = 0;
                    foreach ($tracks as $track) {
                        $track_genre[] = trim(strtolower($track["genre"]));
                        $track_times[] = $track["created_at"];
                        $track_avg_length = $track_avg_length + $track["duration"];
                        if (isset($track["playback_count"])) {
                            $listeners = $listeners + $track["playback_count"];
                        }
                    }
                    // process track data - find median track age & average length
                    sort($track_times);
                    if (count($track_times) % 2 == 0) {
                        // even number of elements, behave as if we would discard the oldest and take that median
                        $median_track_age = $track_times[count($track_times) / 2];
                    } else {
                        if (count($track_times) == 1) {
                            $median_track_age = $track_times[0];
                        } else {
                            // odd number, take middle element
                            $median_track_age = $track_times[(count($track_times)+1) / 2];
                        }
                    }
                    $track_avg_length = $track_avg_length / count($tracks) / 1000;
                    $last_track_age = $tracks[0]["created_at"];

                    if ($track_avg_length < $MAX_ACCEPTED_AVG_TRACK_LENGTH) { // not a dj-set account
                        $valid_user_data = array();
                        $found_users[$id]["id"] = $user["id"];
                        $found_users[$id]["username"] = $user["username"];
                        $found_users[$id]["permalink"] = $user["permalink_url"];
                        $found_users[$id]["description"] = $user["description"];
                        $found_users[$id]["tracks"] = count($tracks);
                        $found_users[$id]["followers_count"] = $user["followers_count"];
                        $found_users[$id]["track_genres"] = array_unique($track_genre,SORT_STRING);
                        $found_users[$id]["median_track_age"] = $median_track_age;
                        $found_users[$id]["last_track_age"] = $last_track_age;
                        $found_users[$id]["listeners_count"] = $listeners;
                        $found_users[$id]["depth"] = 0;
                        if (!isset($found_users[$id]["degree"])) { $found_users[$id]["degree"] = 0; }
                    }
                }
            }
        }
        $mydb->clearSessionStatus($_SESSION["user_data"]["id"]);
        $mydb->clearSessionProgress($_SESSION["user_data"]["id"]);
        $mydb->updateSessionStatus($_SESSION["user_data"]["id"], 0, 0, 0);

        // show them
        display_user_results_xml($found_users, $failed_users, $error_count, microtime(true) - $before);

    } else {
        //echo "No users from $city currently followed";
    }
    die();
}
