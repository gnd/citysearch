var seen;   
var cities; // the php $_SESSION["cities"] array created at first login
var found_users;
var search_status;


var DESC_SIZE = 220;
var how = "rank_d"
var show_seen = false;
var max_rank = 0;
var max_tracks = 0;
var max_followers = 0;
var max_listeners = 0;
var max_degree = 0;
var duration = 0;
var search_progress_check;
var search_progress_refresh_period = 500;
var uid = 0;

// autoload seen ids on page load
if(window.attachEvent) {
    window.attachEvent('onload', pageload);
} else {
    if(window.onload) {
        var curronload = window.onload;
        var newonload = function(evt) {
            curronload(evt);
            pageload(evt);
        };
        window.onload = newonload;
    } else {
        window.onload = pageload;
    }
}

// verify if numeric
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

// events to happen on page load
function pageload() {
    
    // load seen artists
    load_seen();
    
    // load cities
    load_cities();
    
    // display correct text on button
    if (show_seen) {
        document.getElementById("switch_seen").value = "hide seen";
    } else {
        document.getElementById("switch_seen").value = "show seen";
    }
    
    // display correct sorting indicator
    indicate_sorting();
    
    // hide results div before we search
    document.getElementById("results").style.display = "none";
    
    uid = document.getElementById("uid").value;
}


function get_time_diff(datetime)
{
    var datetime = new Date( datetime ).getTime();
    var now = new Date().getTime();
    var milisec_diff = now - datetime;    
    var days = Math.floor(milisec_diff / 1000 / 60 / (60 * 24));
    return days;
}

function indicate_sorting() {
    var arr = how.split("_");
    var what = arr[0];
    var dir = arr[1];
    var suffix = "&nbsp;&nbsp;&nbsp;";
    
    // make all sorting indicators default
    document.getElementById("th_name").innerHTML = "name" + suffix;
    document.getElementById("th_rank").innerHTML = "rank" + suffix;
    document.getElementById("th_depth").innerHTML = "depth" + suffix;
    document.getElementById("th_deg").innerHTML = "deg" + suffix;
    document.getElementById("th_tracks").innerHTML = "tracks" + suffix;
    document.getElementById("th_followers").innerHTML = "followers" + suffix;
    document.getElementById("th_mta").innerHTML = "mta" + suffix;
    document.getElementById("th_lta").innerHTML = "lta" + suffix;
    
    // adjust indicator according to current how
    if (dir == "i") {
        document.getElementById("th_" + what).innerHTML = "▲ " + what + suffix;
    } else {
        document.getElementById("th_" + what).innerHTML = "▼ " + what + suffix;
    }
}


// load seen from XML
function load_seen() {
    
    // clean seen array
    seen = new Array();
    
    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {    // IE 5/6
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xhttp.open("GET", "index.php?seenxml=1", false);
    xhttp.send(null);
    var xmlDoc = xhttp.responseXML;

    var seen_ids = xmlDoc.getElementsByTagName("id");
    for (var i = 0; i < seen_ids.length; i++) {
         seen.push(seen_ids[i].childNodes[0].textContent);
    }
}


// load cities from XML
function load_cities() {
    
    // clean seen array
    cities = new Array();
    
    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {    // IE 5/6
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xhttp.open("GET", "index.php?citiesxml=1", false);
    xhttp.send(null);
    var xmlDoc = xhttp.responseXML;

    var cities_loaded = xmlDoc.getElementsByTagName("city");
    for (var i = 0; i < cities_loaded.length; i++) {
         cities.push(cities_loaded[i].childNodes[0].textContent);
    }
}

// periodically check on search status once underway
function search_progress_check() {
    
    if (window.XMLHttpRequest) {
       status_xhttp = new XMLHttpRequest();
    } else {    // IE 5/6
       status_xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    // initiate connection
    status_xhttp.open("GET", "status.php?uid="+uid, true);
    status_xhttp.addEventListener("load", processStatus);
    status_xhttp.send(null);
}


// process and show search status data
function processStatus() {
    try {
        process_status_xml(status_xhttp.responseXML);
        if (search_status["status"] == "initial") {
            document.getElementById("search_status").innerHTML = "finalizing: " + search_status["search_progress"] + " %";
            document.getElementById("search_status").style.backgroundColor = "#fcf800";
        } else {
            document.getElementById("search_status").innerHTML = "searching: depth " + search_status["curr_depth"] + "/" + search_status["max_depth"] + ": " + search_status["search_progress"] + " %";
            document.getElementById("search_status").style.backgroundColor = "#fcc900";
        }
    } 
    catch(err) {
        console.log("Reading search status: Oops ..");
    }
}


// load search status into a array
function process_status_xml(xmlDoc) {
    
    // clean search_status array
    search_status = new Array();
    
    // get data
    search_status["status"] = xmlDoc.getElementsByTagName("searching")[0].textContent;
    search_status["curr_depth"] = xmlDoc.getElementsByTagName("curr_depth")[0].textContent;
    search_status["max_depth"] = xmlDoc.getElementsByTagName("max_depth")[0].textContent;
    search_status["search_progress"] = xmlDoc.getElementsByTagName("search_progress")[0].textContent;
}


// load citysearch (seek) from XML
function seekxml() {
    
    // indicate search status
    document.getElementById("search_status").innerHTML = "searching ..";
    document.getElementById("search_status").style.backgroundColor = "#ed6359";
    
    // prepare a clean tbody
    var tbody = document.getElementById("results_body");
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }    
    
    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {    // IE 5/6
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    // check for params
    var city = document.getElementById("seek_city").value;
    var depth = document.getElementById("seek_depth").value;
    
    // verify if params ok
    verify = false;
    if (city) {
        if (cities.indexOf(city) != -1) {
            if (depth) {
                if (isNumeric(depth)) {
                    if (depth < 5) {
                        verify = true;
                    } else {
                        alert("Depth " + depth + " too big");
                    }
                } else {
                    alert("Depth not a number");
                }
            } else {
                alert("Depth empty");
            }
        } else {
            alert("You follow no users from " + city);
        }
    } else {
        alert("City empty");
    }
    
    // if verify, run
    if (verify) {
        // setup params
        var params = "seekxml=1";
        params += "&" + "seek_city" + "=" + encodeURIComponent(city);
        params += "&" + "seek_depth" + "=" + encodeURIComponent(depth);
        
        // initiate connection
        xhttp.open("POST", "index.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.addEventListener("load", processResponse);
        xhttp.send(params);
        
        // starts progress reporting
        search_progress_loop = self.setInterval(search_progress_check, search_progress_refresh_period);
    }
}


function processResponse() {
    
    // process xml
    process_xml(xhttp.responseXML);
        
    // determine rank
    compute_rank();
    
    // stop progress reporting
    window.clearInterval(search_progress_loop);
        
    // show results
    show(how);
        
    // update search status
    document.getElementById("search_status").innerHTML = found_users.length + " users found in " + Number(duration).toFixed(2) + " seconds";
    document.getElementById("search_status").style.backgroundColor = "#60fd6b";
}

// rank by distribution
function anti_pareto_linear(amount, max) {
    var result = 1;
    amount = amount / max;
    if (amount < 0.2) {
        result = (amount*2.5)+0.5;
    } else if (amount < 0.8) {
        result = 1 - (amount - 0.2)/6*10*0.9;
    } else {
        result = 0.1;
    }
    return result;
}


// rank by Age
function rank_by_age(age) {
    // where does the sigmod hit 0
    var zero = 730;
    
    //return 2/(1+Math.exp(0.033*(age-zero)))-1;
    return 1/(1+Math.exp(0.005*(age-365)));
}

// rank by Age
function rank_by_last(age) {
    // where does the sigmod hit 0
    var zero = 360;
    
    return 2/(1+Math.exp(0.033*(age-zero)))-1;
}



// compute user rank
function compute_rank() {
    for (var i = 0; i < found_users.length; i++) {

        // tracks
        var track_rank = anti_pareto_linear(found_users[i]["tracks"], max_tracks);
        var track_age_rank = rank_by_age(found_users[i]["mta"]);
        var last_track_rank = rank_by_last(found_users[i]["lta"]);
        
        // followers
        var followers_rank = anti_pareto_linear(found_users[i]["followers"], max_followers);
        
        // listeners
        var listeners_rank = anti_pareto_linear(found_users[i]["listeners"], max_listeners);
        
        // degree
        var degree_rank = (found_users[i]["degree"] + 1) / (found_users[i]["degree"] + 2)
        
        // depth
        var depth_rank = (found_users[i]["depth"] / 10) + 1;
           
        found_users[i]["rank"] = (track_rank * track_age_rank * followers_rank * listeners_rank * degree_rank * depth_rank)*10 + last_track_rank;
    }
}


// load search results into a array
function process_xml(xmlDoc) {
    
    // clean found_users array
    found_users = new Array();
    
    // get duration
    duration = xmlDoc.getElementsByTagName("duration")[0].textContent;
    
    // populate found users array
    var users = xmlDoc.getElementsByTagName("user");
    for (var i = 0; i < users.length; i++) {
        var user = new Array();
        var user_genres = new Array();
        var user_tags = new Array();
        user["id"] = users[i].getElementsByTagName("id")[0].textContent;
        user["name"] = users[i].getElementsByTagName("name")[0].textContent;
        user["link"] = users[i].getElementsByTagName("link")[0].textContent;
        user["tracks"] = users[i].getElementsByTagName("tracks")[0].textContent;
        user["followers"] = users[i].getElementsByTagName("followers")[0].textContent;
        var genres = users[i].getElementsByTagName("genre");
        for (var j = 0; j < genres.length; j++) {
            user_genres.push(genres[j].textContent);
        }
        user["genres"] = user_genres;
        user["mta"] = get_time_diff(users[i].getElementsByTagName("median_age")[0].textContent);
        user["lta"] = get_time_diff(users[i].getElementsByTagName("last_age")[0].textContent);
        user["listeners"] = users[i].getElementsByTagName("listeners")[0].textContent;
        user["description"] = users[i].getElementsByTagName("description")[0].textContent;
        user["degree"] = users[i].getElementsByTagName("degree")[0].textContent;
        user["depth"] = users[i].getElementsByTagName("depth")[0].textContent;
        user["rank"] = 0;
        
        // find maximum tracks
        if (user["tracks"] > max_tracks) {
            max_tracks = user["tracks"];
        }
        
        // find maximum followers
        if (user["followers"] > max_followers) {
            max_followers = user["followers"];
        }
        
        // find maximum listeners
        if (user["listeners"] > max_listeners) {
            max_listeners = user["listeners"];
        }
        
        // find maximum degree
        if (user["degree"] > max_degree) {
            max_degree = user["degree"];
        }
        
        found_users.push(user); 
    }
}

function change_sorting(what) {
    if (how == what + "_i") {
        how = what + "_d";
    } else {
        how = what + "_i";
    }
    
    // indicate sorting
    indicate_sorting();
    
    // show
    show(how);
}

function sort_results(results, how) {
    
    if (how == "name_i") {
        results.sort(function(a,b) {
            if(a.name < b.name) return -1;
            if(a.name > b.name) return 1;
            return 0;
        });
    }
    if (how == "name_d") {
        results.sort(function(b,a) {
            if(a.name < b.name) return -1;
            if(a.name > b.name) return 1;
            return 0;
        });
    }
    if (how == "rank_i") {
        results.sort(function(a,b) {
            return a.rank - b.rank;
        });
    }
    if (how == "rank_d") {
        results.sort(function(b,a) {
            return a.rank - b.rank;
        });
    }
    if (how == "depth_i") {
        results.sort(function(a,b) {
            return a.depth - b.depth;
        });
    }
    if (how == "depth_d") {
        results.sort(function(b,a) {
            return a.depth - b.depth;
        });
    }
    if (how == "deg_i") {
        results.sort(function(a,b) {
            return a.degree - b.degree;
        });
    }
    if (how == "deg_d") {
        results.sort(function(b,a) {
            return a.degree - b.degree;
        });
    }
    if (how == "tracks_i") {
        results.sort(function(a,b) {
            return a.tracks - b.tracks;
        });
    }
    if (how == "tracks_d") {
        results.sort(function(b,a) {
            return a.tracks - b.tracks;
        });
    }
    if (how == "followers_i") {
        results.sort(function(a,b) {
            return a.followers - b.followers;
        });
    }
    if (how == "followers_d") {
        results.sort(function(b,a) {
            return a.followers - b.followers;
        });
    }
    if (how == "mta_i") {
        results.sort(function(a,b) {
            return a.mta- b.mta;
        });
    }
    if (how == "mta_d") {
        results.sort(function(b,a) {
            return a.mta - b.mta;
        });
    }
    if (how == "lta_i") {
        results.sort(function(a,b) {
            return a.lta- b.lta;
        });
    }
    if (how == "lta_d") {
        results.sort(function(b,a) {
            return a.lta - b.lta;
        });
    }
}


// show found_users on the page
function show(how) {
    
    // prepare a clean tbody
    var tbody = document.getElementById("results_body");
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    // show results div 
    document.getElementById("results").style.display = "initial";
    
    // sort results
    sort_results(found_users, how);
    
    // populate with results and honour show_seen
    for (var i = 0; i < found_users.length; i++) {
        if ((show_seen) || (seen.indexOf(found_users[i]["id"]) == -1)) {
            // create a row for individual artisr
            var row = document.createElement('tr');
            row.setAttribute("id", found_users[i]["id"]);
            if (seen.indexOf(found_users[i]["id"]) != -1) { // if seen
                row.setAttribute("class", "seen");
            }
             
            var td = document.createElement('td');
            if (seen.indexOf(found_users[i]["id"]) != -1) { // if seen
                td.setAttribute("class", "unhide");
                td.innerHTML = "<span onclick=\"unhide(" + found_users[i]["id"] + ")\">unhide</span>";
            } else {
                td.setAttribute("class", "hide");
                td.innerHTML = "<span onclick=\"hide(" + found_users[i]["id"] + ")\">hide</span>";
            }
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_name");
            td.setAttribute("title", found_users[i]["genres"].join(", "));
            td.innerHTML = "<a class=\"artist\" target=\"_blank\" href=\"" + found_users[i]["link"] + "\">" + found_users[i]["name"] + "</a>";
            row.appendChild(td);
            
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.innerHTML = found_users[i]["rank"].toFixed(5);
            row.appendChild(td);
            
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.innerHTML = found_users[i]["depth"];
            row.appendChild(td);
            
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.innerHTML = found_users[i]["degree"];
            row.appendChild(td);
            
            var td = document.createElement('td');
            td.setAttribute("class", "artist_followers");
            td.innerHTML = found_users[i]["followers"];
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.innerHTML = found_users[i]["tracks"];
            row.appendChild(td);
            
            var td = document.createElement('td');
            td.setAttribute("class", "mta");
            td.innerHTML = found_users[i]["mta"];
            row.appendChild(td);
            
            var td = document.createElement('td');
            td.setAttribute("class", "lta");
            td.innerHTML = found_users[i]["lta"];
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.setAttribute("title", found_users[i]["description"]);
            td.innerHTML = found_users[i]["description"].substring(0, DESC_SIZE);
            row.appendChild(td);

            // append row to results
            tbody.appendChild(row);
        }
    }
}

// post seen
function hide(id) {
    
    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    // post seen id
    xhttp.open("POST", "index.php", false);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("seen=" + id);
    
    // reload seen
    load_seen();
    
    // show results
    show(how);
}


// post unsee
function unhide(id) {
    
    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    // post seen id
    xhttp.open("POST", "index.php", false);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("unsee=" + id);
    
    // reload seen
    load_seen();
    
    // show results
    show(how);
}


// switch show_seen state
function switch_seen() {
    if (show_seen) {
        document.getElementById("switch_seen").value = "show seen";
        show_seen = false;
        show(how);
    } else {
        document.getElementById("switch_seen").value = "hide seen";
        show_seen = true;
        show(how);
    }
}