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

include "db_class.php";
include "sttngs.php";

$mydb = new db();
$mydb->connect();


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


// Output XML
header('Content-Type: application/xml');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<status>\n";

if (isset($_REQUEST["uid"])) {
    $uid = validate_int($_REQUEST["uid"], $mydb->db);
    
    // get status
    $data = $mydb->getSessionStatus($uid);
    $line = mysqli_fetch_array($data);
    $status = $line["status"];
    $max_depth = $line["max_dep"];
    $curr_depth = $line["curr_dep"];

    if (($status == 1) || ($status == "initial")) {
        $data = $mydb->getSessionProgress($uid);
        $line = mysqli_fetch_array($data);
        echo "\t<searching>" . $status . "</searching>\n";
        echo "\t<max_depth>" . $max_depth . "</max_depth>\n";
        echo "\t<curr_depth>" . $curr_depth . "</curr_depth>\n";
        echo "\t<search_progress>" . $line["progress"] . "</search_progress>\n";
    } else {
        echo "\t<searching>0</searching>\n";
    }
}

echo "</status>";
die();
    
?>