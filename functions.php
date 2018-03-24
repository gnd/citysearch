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
        die("Input parameter not a number");
    }
}

/**
 * Sanitizes a input parameter of the type string
 *
 * This basically makes sure the output ins xml-safe
 * with a couple of things on top of it
 *
 * @param string $input input string
 * @return Sanitized xml-safe input string
 */
function sanitize_for_xml($input) {
    $input = strip_tags(addslashes($input));
    return str_replace(array('&','>','<'), array('&amp;','&gt;','&lt;'), $input);
}


/**
 * Checks if user logged into Soundcloud
 *
 * This is just a wrapper that checks if:
 * - the user is logged
 * - the user is logged into SoundCloud
 *
 * @return bool
 */
function sc_logged() {
    return (isset($_SESSION["user_logged"]) && ($_SESSION["user_logged"] === 1) && isset($_SESSION["sc_logged"]) && ($_SESSION["sc_logged"] === 1));
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
 * Prints the HTML page top and navigation menu
 *
 */
function user_pagetop($dev_version) {
    echo "<html><head>\n";
    echo "<link href=\"citysearch.css\" rel=\"stylesheet\" type=\"text/css\" />\n";
    echo "<script type=\"text/javascript\" src=\"user.js\"></script>\n";
    echo "<title>citysearch</title>\n";
    echo "</head><body>\n";
    echo "<div class=\"main\" style=\"width: 800px;\">\n";
    echo "<div class=\"nav\" >\n";
    echo "<a href=\"/citysearch/index.php?settings=1\">settings</a> / \n";
    if ($_SESSION["user_data"]["sid"] > 1) {
        echo "<a href=\"/citysearch/index.php?admin=1\">admin</a>\n";
    }
    if ($dev_version == 1) {
        echo "Logged in as " . $_SESSION["user_data"]["name"] . " (dev version)";
    } else {
        echo "Logged in as " . $_SESSION["user_data"]["name"];
    }
    echo " [<a href=\"/citysearch/index.php?logout=1\">logout</a>]\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "<br/><br/>";
}

/**
 * Prints the HTML page top and navigation menu
 *
 */
function pagetop($dev_version) {
    echo "<html><head>\n";
    echo "<link href=\"citysearch.css\" rel=\"stylesheet\" type=\"text/css\" />\n";
    echo "<title>citysearch</title>\n";
    echo "<script type=\"text/javascript\" src=\"js.js\"></script>\n";
    echo "</head><body>\n";
    echo "<div class=\"main\" style=\"width: 800px;\">\n";
    echo "<div class=\"nav\" >\n";
    echo "<a href=\"/citysearch/index.php\">main</a> / \n";
    echo "<a href=\"/citysearch/index.php?seek=1\">search</a> / \n";
    echo "<a href=\"/citysearch/index.php?userstalk=1\">userstalk</a> / \n";
    echo "<a href=\"/citysearch/index.php?aliases=1\">aliases</a> / \n";
    echo "<a href=\"/citysearch/index.php?settings=1\">settings</a> / \n";
    if ($_SESSION["user_data"]["sid"] > 1) {
        echo "<a href=\"/citysearch/index.php?admin=1\">admin</a> / \n";
    }
    if ($dev_version == 1) {
        echo "using Soundcloud as " . $_SESSION["sc_data"]["username"] . " (dev version)";
    } else {
        echo "using Soundcloud as " . $_SESSION["sc_data"]["username"];
    }
    echo " [<a href=\"/citysearch/index.php?logout=1\">logout ".$_SESSION["user_data"]["name"]."</a>]\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "<br/><br/>";
}

/**
 * Prints a cityserach user
 *
 *
 */
function display_user_data($line) {
    echo "<tr><td>". $line["username"] ." <a href=index.php?edituser=". $line["uid"] .">edit</a> <a href=index.php?deluser=". $line["uid"] .">del</a> </td><td></td></tr>\n";
}

/**
 * Prints found users into a table
 *
 * This first checks whether a user is in ignored (and later also seen) users
 *
 * @param array $users results provided by the search
 */
function display_user_results($users) {
    if (count($users) > 0 ) {
        echo "<span id=\"switch_seen\" onclick=\"switch_seen()\">hide seen</span>\n";
        echo "\n\n<table style=\"border: 0px;\">\n";
        echo "<thead><tr><th>name</th><th>tracks</th><th>rank</th><th>followers</th><th>description</th></tr></thead>\n";
        echo "<tbody>";
        foreach ( $users as $user ) {
            $link = $user["permalink"];
            $name = $user["username"];
            $tracks = $user["tracks"];
            $desc = $user["description"];
            $followers = $user["count_followers"];
            $rank = $user["rank"];

            // the actual filtering of ignored / seen is happening on js level
            echo "<tr id=\"".$user["id"]."\"><td class=\"artist_name\"><a class=\"artist\" target=\"_blank\" href=\"http://soundcloud.com/" . $link . "\">" . $name . "</a> ";
            echo "[<a class=\"ignore\" onclick=ignore(".$user["id"].")\">ign</a>";
            echo ",<a class=\"seen\" onclick=\"see(".$user["id"].")\">see</a>]</td>";
            echo "<td class=\"artist_info\"><b> " . $tracks . "</b></td>";
            echo "<td class=\"artist_info\"><b> " . $rank . "</b></td>";
            echo "<td class=\"artist_info\"><b> " . $followers . "</b></td>";
            echo "<td class=\"artist_info\" title=\"".strip_tags(addslashes($desc))."\">".substr(strip_tags(addslashes($desc)),0,250)."</td></tr>\n";
        }
        echo "</tbody>";
        echo "</table>\n\n";
    }
}


/**
 * Outputs found users as a XML
 *
 *
 * @param array $users results provided by the search
 */
function display_user_results_xml($users, $failed_users, $error_count, $duration) {

    // Output XML
    header('Content-Type: application/xml');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<results>\n";
    echo "<duration>".$duration."</duration>\n";
    echo "<errors>".$error_count."</errors>\n";

    if (count($failed_users) > 0 ) {
        echo "<failed_users>\n";
        foreach ( $failed_users as $failed_user ) {
            $id = $failed_user["id"];
            echo "\t<failed_user>".$id."</failed_user>\n";
        }
        echo "</failed_users>\n";
    }

    if (count($users) > 0 ) {
        echo "<users>\n";
        foreach ( $users as $user ) {
            $id = $user["id"];
            $link = $user["permalink"];
            $name = $user["username"];
            $tracks = $user["tracks"];
            $description = $user["description"];
            $followers = $user["followers_count"];
            $genres = $user["track_genres"];
            $median_age = $user["median_track_age"];
            $last_age = $user["last_track_age"];
            $listeners = $user["listeners_count"];
            $degree = $user["degree"];
            $depth = $user["depth"];

            echo "<user>\n";
                echo "\t<id>".$id."</id>\n";
                echo "\t<name>".sanitize_for_xml($name)."</name>\n";
                echo "\t<link>".$link."</link>\n";
                echo "\t<tracks>".$tracks."</tracks>\n";
                echo "\t<followers>".$followers."</followers>\n";
                echo "\t<genres>\n";
                foreach ($genres as $genre) { if ($genre != "") { echo "\t\t<genre>".sanitize_for_xml($genre)."</genre>\n"; } }
                echo "\t</genres>\n";
                echo "\t<median_age>".$median_age."</median_age>\n";
                echo "\t<last_age>".$last_age."</last_age>\n";
                echo "\t<listeners>".$listeners."</listeners>\n";
                echo "\t<description>\n\t\t".sanitize_for_xml($description)."\n\t</description>\n";
                echo "\t<degree>".$degree."</degree>\n";
                echo "\t<depth>".$depth."</depth>\n";
            echo "</user>\n";
        }
    }
    echo "</users>\n";
    echo "</results>\n";
}



/**
 * Show login form
 *
 * This prints a login form
 *
 */
function login_form() {
    echo "<form action=\"index.php\" method=\"post\">\n";
    echo "<table width=\"669\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">\n";
    echo "<tr><td id=\"baner\"></td></tr>\n";
    echo "<tr><td class=\"text\">name<br>\n";
    echo "<input name=\"name\" type=\"text\"><br>\n";
    echo "password<br>\n";
    echo "<input name=\"pass\" type=\"password\" >\n";
    echo "<br>\n";
    echo "<input type=\"submit\" value=\"Submit\">\n";
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}

/**
 * Show add user form
 *
 * This prints a form to add users
 *
 */
function add_user_form() {
    echo "<table border=\"0\" cellspacing=\"0\">\n";
    echo "<tr >\n";
    echo "<td class=\"btext\">Create new user<br/><br/>\n";
    echo "</tr>\n";
    echo "<tr >\n";
    echo "<td class=\"text\">name<br>\n";
    echo "<input name=\"newname\" type=\"text\"><br>\n";
    echo "mail <br>\n";
    echo "<input name=\"newmail\" type=\"text\" >\n";
    echo "<br>\n";
    echo "<input type=\"submit\" onclick=\"adduser()\" value=\"Create\">\n";
    echo "</td>\n";
    echo "</tr>\n";
    echo "</table>\n";
}


/**
 * Show edit user form
 *
 * This prints a form to edit user(s)
 *
 */
function edit_user_form($username, $uid, $enabled, $self) {
    if ($_SESSION["user_data"]["sid"] > 0) {
        //echo "\t<br/>\n";
        echo "\t\t<table border=\"0\" cellpadding=\"20\" cellspacing=\"0\">\n";
        if (!$self && $_SESSION["user_data"]["sid"] > 1) {
            echo "\t\t\t<tr >\n";
            echo "\t\t\t\t<td class=\"btext\">Edit " . $username;
            echo "\t\t\t</tr>\n";
        }
        echo "\t\t\t<tr >\n";
        echo "\t\t\t\t<td class=\"text\">\n";// name<br>\n";
        echo "\t\t\t\t\t<input id=\"uid\" type=\"hidden\" value=\"" . $uid . "\">\n";
        echo "\t\t\t\t\t<input id=\"name\" type=\"hidden\" value=\"" . $username . "\" readonly><br>\n";
        echo "\t\t\t\t\told password<br>\n";
        echo "\t\t\t\t\t<input id=\"oldpass\" type=\"password\" >\n";
        echo "\t\t\t\t\t<br>\n";
        echo "\t\t\t\t\tnew password (length at least 10 chars, MUST contain at least one: special character, number, big letter, small letter)<br>\n";
        echo "\t\t\t\t\t<input id=\"newpass_1\" type=\"password\" >\n";
        echo "\t\t\t\t\t<br>\n";
        echo "\t\t\t\t\tconfirm new password<br>\n";
        echo "\t\t\t\t\t<input id=\"newpass_2\" type=\"password\" >\n";
        echo "\t\t\t\t\t<br>\n";
        if (!$self && $_SESSION["user_data"]["sid"] > 1) {
            echo "\t\t\t\t\tEnabled \n";
            echo "\t\t\t\t\t<input id=\"enabled\" type=\"checkbox\" " . $enabled ."><br>\n";
            echo "\t\t\t\t\t<br><br/>\n";
        }
        echo "\t\t\t\t\t<input type=\"submit\" onclick=\"edituser()\" value=\"Edit\">\n";
        echo "\t\t\t\t</td>\n";
        echo "\t\t\t</tr>\n";
        echo "\t\t</table>\n";
    }
}

/**
 * Create a PBKDF2 hash
 *
 *
 */
function pw_crypt($str,$salt) {
   $algo = 'sha256';
   $iterations = 100000; //approx 0.2s on contemporary hw
   return hash_pbkdf2($algo, $str, $salt, $iterations);
}

/**
 * Get a random salt for PBKDF2 hash generation
 *
 *
 */
function pw_salt() {
   $salt = bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
   return $salt;
}

/**
 * Check if password valid
 *
 */
function check_pwd($recv_pwd, $stor_pwd) {
    $result = false;
    if (isset($recv_pwd) && isset($stor_pwd)) {
        $test_pwd = substr($stor_pwd, 0, 16) . pw_crypt($recv_pwd, substr($stor_pwd, 0, 16)); // The hash is 16 chars salt + 64 chars hash
        $result = hash_equals($stor_pwd, $test_pwd);
    }
    return $result;
}

/**
 * Return a PBKDF2 hash
 *
 */
function get_pwd_hash($pwd) {
    if (isset($pwd)) {
        $salt = pw_salt();
        $hash = $salt . pw_crypt($pwd, $salt);
        return $hash;
    } else {
        die("Password cant be NULL");
    }
}


/**
 * Return a PBKDF2 hash for invites & forgotten passwords
 *
 */
function get_url_hash($data) {
    if (isset($data)) {
        $salt = pw_salt();
        $hash = $salt . pw_crypt($pwd, $salt);
        return substr(str_replace("$", "", $hash), 0, 16);
    } else {
        die("Please provide some data for hashing");
    }
}


/**
 * Send email to given mail address
 *
 */
function send_mail($from, $to, $subject, $body) {
    $headers =  "MIME-Version: 1.0\n" .
                "Content-type: text/html; charset=iso-8859-2\n" .
                "From: " . $from . "\n" .
                "Reply-To: " . $from . "\n" .
                "Date: ".date("r")."\n".
                "Return-Path: <" .$from . ">\n" .
                "User-Agent: PHP v".phpversion(). "\n" .
                "X-Mailer: PHP v".phpversion(). "\n" .
                "X-Priority: 3";

    $result = mail($to, $subject, $body, $headers);
    return $result;
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


function seems_utf8($str) {
    $length = strlen($str);
    for($i = 0;
    $i < $length;
    $i ++ ) {
        $c = ord($str[$i]);
        if($c < 0x80)
            $n = 0;
        # 0bbbbbbb
        elseif(($c &0xE0)== 0xC0)
            $n = 1;
        # 110bbbbb
        elseif(($c &0xF0)== 0xE0)
            $n = 2;
        # 1110bbbb
        elseif(($c &0xF8)== 0xF0)
            $n = 3;
        # 11110bbb
        elseif(($c &0xFC)== 0xF8)
            $n = 4;
        # 111110bb
        elseif(($c &0xFE)== 0xFC)
            $n = 5;
        # 1111110b
        else
            return false;
        # Does not match any model
        for($j = 0;
        $j < $n;
        $j ++ ) {
            # n bytes matching 10bbbbbb follow ?
            if((++ $i == $length)||((ord($str[$i])&0xC0)!= 0x80))
                return false;
        }
    }
    return true;
}

/**
 * Converts all accent characters to ASCII characters.
 *
 * If there are no accent characters, then the string given is just returned.
 *
 * @param string $string Text that might have accent characters
 * @return string Filtered string with replaced "nice" characters.
 */
function remove_accents($string) {
    if(!preg_match('/[\x80-\xff]/', $string))
        return $string;
    if(seems_utf8($string)) {
        $chars = array(// Decompositions for Latin-1 Supplement chr(195). chr(128)=> 'A',
                       chr(195). chr(129)=> 'A',
                       chr(195). chr(130)=> 'A',
                       chr(195). chr(131)=> 'A',
                       chr(195). chr(132)=> 'A',
                       chr(195). chr(133)=> 'A',
                       chr(195). chr(135)=> 'C',
                       chr(195). chr(136)=> 'E',
                       chr(195). chr(137)=> 'E',
                       chr(195). chr(138)=> 'E',
                       chr(195). chr(139)=> 'E',
                       chr(195). chr(140)=> 'I',
                       chr(195). chr(141)=> 'I',
                       chr(195). chr(142)=> 'I',
                       chr(195). chr(143)=> 'I',
                       chr(195). chr(145)=> 'N',
                       chr(195). chr(146)=> 'O',
                       chr(195). chr(147)=> 'O',
                       chr(195). chr(148)=> 'O',
                       chr(195). chr(149)=> 'O',
                       chr(195). chr(150)=> 'O',
                       chr(195). chr(153)=> 'U',
                       chr(195). chr(154)=> 'U',
                       chr(195). chr(155)=> 'U',
                       chr(195). chr(156)=> 'U',
                       chr(195). chr(157)=> 'Y',
                       chr(195). chr(159)=> 's',
                       chr(195). chr(160)=> 'a',
                       chr(195). chr(161)=> 'a',
                       chr(195). chr(162)=> 'a',
                       chr(195). chr(163)=> 'a',
                       chr(195). chr(164)=> 'a',
                       chr(195). chr(165)=> 'a',
                       chr(195). chr(167)=> 'c',
                       chr(195). chr(168)=> 'e',
                       chr(195). chr(169)=> 'e',
                       chr(195). chr(170)=> 'e',
                       chr(195). chr(171)=> 'e',
                       chr(195). chr(172)=> 'i',
                       chr(195). chr(173)=> 'i',
                       chr(195). chr(174)=> 'i',
                       chr(195). chr(175)=> 'i',
                       chr(195). chr(177)=> 'n',
                       chr(195). chr(178)=> 'o',
                       chr(195). chr(179)=> 'o',
                       chr(195). chr(180)=> 'o',
                       chr(195). chr(181)=> 'o',
                       chr(195). chr(182)=> 'o',
                       chr(195). chr(182)=> 'o',
                       chr(195). chr(185)=> 'u',
                       chr(195). chr(186)=> 'u',
                       chr(195). chr(187)=> 'u',
                       chr(195). chr(188)=> 'u',
                       chr(195). chr(189)=> 'y',
                       chr(195). chr(191)=> 'y',
                       // Decompositions for Latin Extended-A chr(196). chr(128)=> 'A',
                       chr(196). chr(129)=> 'a',
                       chr(196). chr(130)=> 'A',
                       chr(196). chr(131)=> 'a',
                       chr(196). chr(132)=> 'A',
                       chr(196). chr(133)=> 'a',
                       chr(196). chr(134)=> 'C',
                       chr(196). chr(135)=> 'c',
                       chr(196). chr(136)=> 'C',
                       chr(196). chr(137)=> 'c',
                       chr(196). chr(138)=> 'C',
                       chr(196). chr(139)=> 'c',
                       chr(196). chr(140)=> 'C',
                       chr(196). chr(141)=> 'c',
                       chr(196). chr(142)=> 'D',
                       chr(196). chr(143)=> 'd',
                       chr(196). chr(144)=> 'D',
                       chr(196). chr(145)=> 'd',
                       chr(196). chr(146)=> 'E',
                       chr(196). chr(147)=> 'e',
                       chr(196). chr(148)=> 'E',
                       chr(196). chr(149)=> 'e',
                       chr(196). chr(150)=> 'E',
                       chr(196). chr(151)=> 'e',
                       chr(196). chr(152)=> 'E',
                       chr(196). chr(153)=> 'e',
                       chr(196). chr(154)=> 'E',
                       chr(196). chr(155)=> 'e',
                       chr(196). chr(156)=> 'G',
                       chr(196). chr(157)=> 'g',
                       chr(196). chr(158)=> 'G',
                       chr(196). chr(159)=> 'g',
                       chr(196). chr(160)=> 'G',
                       chr(196). chr(161)=> 'g',
                       chr(196). chr(162)=> 'G',
                       chr(196). chr(163)=> 'g',
                       chr(196). chr(164)=> 'H',
                       chr(196). chr(165)=> 'h',
                       chr(196). chr(166)=> 'H',
                       chr(196). chr(167)=> 'h',
                       chr(196). chr(168)=> 'I',
                       chr(196). chr(169)=> 'i',
                       chr(196). chr(170)=> 'I',
                       chr(196). chr(171)=> 'i',
                       chr(196). chr(172)=> 'I',
                       chr(196). chr(173)=> 'i',
                       chr(196). chr(174)=> 'I',
                       chr(196). chr(175)=> 'i',
                       chr(196). chr(176)=> 'I',
                       chr(196). chr(177)=> 'i',
                       chr(196). chr(178)=> 'IJ',
                       chr(196). chr(179)=> 'ij',
                       chr(196). chr(180)=> 'J',
                       chr(196). chr(181)=> 'j',
                       chr(196). chr(182)=> 'K',
                       chr(196). chr(183)=> 'k',
                       chr(196). chr(184)=> 'k',
                       chr(196). chr(185)=> 'L',
                       chr(196). chr(186)=> 'l',
                       chr(196). chr(187)=> 'L',
                       chr(196). chr(188)=> 'l',
                       chr(196). chr(189)=> 'L',
                       chr(196). chr(190)=> 'l',
                       chr(196). chr(191)=> 'L',
                       chr(197). chr(128)=> 'l',
                       chr(197). chr(129)=> 'L',
                       chr(197). chr(130)=> 'l',
                       chr(197). chr(131)=> 'N',
                       chr(197). chr(132)=> 'n',
                       chr(197). chr(133)=> 'N',
                       chr(197). chr(134)=> 'n',
                       chr(197). chr(135)=> 'N',
                       chr(197). chr(136)=> 'n',
                       chr(197). chr(137)=> 'N',
                       chr(197). chr(138)=> 'n',
                       chr(197). chr(139)=> 'N',
                       chr(197). chr(140)=> 'O',
                       chr(197). chr(141)=> 'o',
                       chr(197). chr(142)=> 'O',
                       chr(197). chr(143)=> 'o',
                       chr(197). chr(144)=> 'O',
                       chr(197). chr(145)=> 'o',
                       chr(197). chr(146)=> 'OE',
                       chr(197). chr(147)=> 'oe',
                       chr(197). chr(148)=> 'R',
                       chr(197). chr(149)=> 'r',
                       chr(197). chr(150)=> 'R',
                       chr(197). chr(151)=> 'r',
                       chr(197). chr(152)=> 'R',
                       chr(197). chr(153)=> 'r',
                       chr(197). chr(154)=> 'S',
                       chr(197). chr(155)=> 's',
                       chr(197). chr(156)=> 'S',
                       chr(197). chr(157)=> 's',
                       chr(197). chr(158)=> 'S',
                       chr(197). chr(159)=> 's',
                       chr(197). chr(160)=> 'S',
                       chr(197). chr(161)=> 's',
                       chr(197). chr(162)=> 'T',
                       chr(197). chr(163)=> 't',
                       chr(197). chr(164)=> 'T',
                       chr(197). chr(165)=> 't',
                       chr(197). chr(166)=> 'T',
                       chr(197). chr(167)=> 't',
                       chr(197). chr(168)=> 'U',
                       chr(197). chr(169)=> 'u',
                       chr(197). chr(170)=> 'U',
                       chr(197). chr(171)=> 'u',
                       chr(197). chr(172)=> 'U',
                       chr(197). chr(173)=> 'u',
                       chr(197). chr(174)=> 'U',
                       chr(197). chr(175)=> 'u',
                       chr(197). chr(176)=> 'U',
                       chr(197). chr(177)=> 'u',
                       chr(197). chr(178)=> 'U',
                       chr(197). chr(179)=> 'u',
                       chr(197). chr(180)=> 'W',
                       chr(197). chr(181)=> 'w',
                       chr(197). chr(182)=> 'Y',
                       chr(197). chr(183)=> 'y',
                       chr(197). chr(184)=> 'Y',
                       chr(197). chr(185)=> 'Z',
                       chr(197). chr(186)=> 'z',
                       chr(197). chr(187)=> 'Z',
                       chr(197). chr(188)=> 'z',
                       chr(197). chr(189)=> 'Z',
                       chr(197). chr(190)=> 'z',
                       chr(197). chr(191)=> 's',
                       chr(226). chr(130).chr(172)=> 'E',
                       chr(194). chr(163)=> '');
        $string = strtr($string, $chars);
    } else {
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = chr(128). chr(131). chr(138). chr(142). chr(154). chr(158). chr(159). chr(162).
                        chr(165). chr(181). chr(192). chr(193). chr(194). chr(195). chr(196). chr(197).
                        chr(199). chr(200). chr(201). chr(202). chr(203). chr(204). chr(205). chr(206).
                        chr(207). chr(209). chr(210). chr(211). chr(212). chr(213). chr(214). chr(216).
                        chr(217). chr(218). chr(219). chr(220). chr(221). chr(224). chr(225). chr(226).
                        chr(227). chr(228). chr(229). chr(231). chr(232). chr(233). chr(234). chr(235).
                        chr(236). chr(237). chr(238). chr(239). chr(241). chr(242). chr(243). chr(244).
                        chr(245). chr(246). chr(248). chr(249). chr(250). chr(251). chr(252). chr(253).
                        chr(255);
        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars['in'] = array(chr(140),
                                    chr(156),
                                    chr(198),
                                    chr(208),
                                    chr(222),
                                    chr(223),
                                    chr(230),
                                    chr(240),
                                    chr(254));
        $double_chars['out'] = array('OE',
                                     'oe',
                                     'AE',
                                     'DH',
                                     'TH',
                                     'ss',
                                     'ae',
                                     'dh',
                                     'th');
        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }
    return $string;
}

?>
