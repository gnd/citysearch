var seen;   
var cities; // the php $_SESSION["cities"] array created at first login
var found_users;

var how = "rank_d"
var show_seen = false;

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
}

function indicate_sorting() {
    var arr = how.split("_");
    var what = arr[0];
    var dir = arr[1];
    var suffix = "&nbsp;&nbsp;&nbsp;";
    
    // make all sorting indicators default
    document.getElementById("th_name").innerHTML = "name" + suffix;
    document.getElementById("th_tracks").innerHTML = "tracks" + suffix;
    document.getElementById("th_rank").innerHTML = "rank" + suffix;
    document.getElementById("th_followers").innerHTML = "followers" + suffix;
    
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
        xhttp.open("POST", "index.php", false);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(params);
        
        // process response
        var xmlDoc = xhttp.responseXML;
        process_xml(xmlDoc);
        
        // show results
        show(how);
        
        // indicate search status
        document.getElementById("search_status").innerHTML = "done !";
        document.getElementById("search_status").style.backgroundColor = "#60fd6b";
    }
}

// load results into a array
function process_xml(xmlDoc) {
    
    // clean found_users array
    found_users = new Array();
    
    // populate found users array
    var users = xmlDoc.getElementsByTagName("user");
    for (var i = 0; i < users.length; i++) {
        var user = new Array();
        user["id"] = users[i].getElementsByTagName("id")[0].textContent;
        user["name"] = users[i].getElementsByTagName("name")[0].textContent;
        user["link"] = users[i].getElementsByTagName("link")[0].textContent;
        user["tracks"] = users[i].getElementsByTagName("tracks")[0].textContent;
        user["followers"] = users[i].getElementsByTagName("followers")[0].textContent;
        user["description"] = users[i].getElementsByTagName("description")[0].textContent;
        user["rank"] = users[i].getElementsByTagName("rank")[0].textContent;
        
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
            td.innerHTML = "<a class=\"artist\" target=\"_blank\" href=\"" + found_users[i]["link"] + "\">" + found_users[i]["name"] + "</a>";
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.innerHTML = found_users[i]["tracks"];
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.innerHTML = found_users[i]["rank"];
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_followers");
            td.innerHTML = found_users[i]["followers"];
            row.appendChild(td);
             
            var td = document.createElement('td');
            td.setAttribute("class", "artist_info");
            td.setAttribute("title", found_users[i]["description"]);
            td.innerHTML = found_users[i]["description"].substring(0, 250);
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