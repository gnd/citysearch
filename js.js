var seen;
var found_users;
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

// events to happen on page load
function pageload() {
    
    // load seen artists
    loadxml();
    
    // display correct text on button
    if (show_seen) {
        document.getElementById("switch_seen").textContent = "hide seen";
    } else {
        document.getElementById("switch_seen").textContent = "show seen";
    }
}

// load seen from XML
function loadxml() {
    
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


// load citysearch (seek) from XML
function seekxml(city, depth) {
    
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
    show();
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


// show found_users on the page
function show() {
    
    // prepare a clean tbody
    var tbody = document.getElementById("results_body");
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    // sort results
    
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
    loadxml();
    
    // show results
    show();
}


// switch show_seen state
function switch_seen() {
    if (show_seen) {
        document.getElementById("switch_seen").textContent = "show seen";
        show_seen = false;
        show();
    } else {
        document.getElementById("switch_seen").textContent = "hide seen";
        show_seen = true;
        show();
    }
}