// verify if numeric
// TODO - can go to javascript functions file
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

// TODO - can go to javascript functions file
// Checks a string for a list of characters
function countContain(strPassword, strCheck) {
    var nCount = 0;
    for (i = 0; i < strPassword.length; i++) {
        if (strCheck.indexOf(strPassword.charAt(i)) > -1) {
                nCount++;
        }
    }
    return nCount;
}

// verify if good pass
// TODO - make this more 'serious'
function good_pass(pass) {
    var m_strUpperCase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var m_strLowerCase = "abcdefghijklmnopqrstuvwxyz";
    var m_strNumber = "0123456789";
    var m_strSpec= "!@#$%^&*?_~"
    var pass_result = [];
    var pass_reason = ""
    var pass_good = false;
    var caps_count = countContain(pass, m_strUpperCase);
    var nums_count = countContain(pass, m_strNumber);
    var spec_count = countContain(pass, m_strSpec);

    if (pass.length < 10) {
        pass_good = false;
        pass_reason = "Password too short";
    }
    else if ((caps_count < 1) && (nums_count < 1) && (spec_count < 1)) {
        pass_good = false;
        pass_reason = "No big letter, number or special character";
    } else {
        pass_good = true;
    }

    pass_result.push(pass_good);
    pass_result.push(pass_reason);

    return pass_result;
}


// verify all paramters correct and POST
function adduser() {

    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {    // IE 5/6
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    // get params
    var user_name = document.getElementById("newname").value;
    var user_mail = document.getElementById("newmail").value;

    // verify if params ok
    verify = false;
    if (user_name in usernames) {
        alert("Please provide a unique username");
    } else {
        if (user_mail not good) {
            alert("Please provide a correct email");
        } else {
            verify = true;
        }
    }

    // if verify, run
    if (verify) {

        // setup params
        var params = "adduser=1";
        params += "&" + "name" + "=" + encodeURIComponent(user_name);
        params += "&" + "mail" + "=" + encodeURIComponent(user_mail);

        // initiate connection
        xhttp.open("POST", "index.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.addEventListener("load", processUserAddResponse);
        xhttp.send(params);
    }
}


// verify all paramters correct and POST
function edituser() {

    if (window.XMLHttpRequest) {
       xhttp = new XMLHttpRequest();
    } else {    // IE 5/6
       xhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    // get params
    var user_id = document.getElementById("uid").value;
    var user_name = document.getElementById("name").value;
    var user_oldpass = document.getElementById("oldpass").value;
    var user_newpass_a = document.getElementById("newpass_1").value;
    var user_newpass_b= document.getElementById("newpass_2").value;
    if (document.getElementById("enabled")) {
        var user_enabled = document.getElementById("enabled").value;
    } else {
        var user_enabled = 666;
    }

    // verify if params ok
    verify = false;
    if (user_newpass_a) {
        passchange = true;
        if (user_newpass_b) {
            if (user_oldpass) {
                if (user_newpass_a == user_newpass_b) {
                    var pass_result = good_pass(user_newpass_a);
                    var pass_good = pass_result[0];
                    var pass_reason = pass_result[1];
                    if (pass_good) {
                        verify = true;
                    } else {
                        alert("Password is weak, reason: " + pass_reason);
                    }
                } else {
                    alert("Passwords dont match");
                }
            } else {
                alert("Please provide old password");
            }
        } else {
            alert("Please provide new password twice");
        }
    } else {
        passchange = false;
        if ((user_enabled == 1) || (user_enabled == 0)) {
            verify = true;
        }
    }

    // if verify, run
    if (verify) {

        // setup params
        var params = "edituser=1";
        params += "&" + "uid" + "=" + encodeURIComponent(user_id);
        params += "&" + "name" + "=" + encodeURIComponent(user_name);
        if (passchange) {
            params += "&" + "oldpass" + "=" + encodeURIComponent(user_oldpass);
            params += "&" + "newpass_1" + "=" + encodeURIComponent(user_newpass_a);
            params += "&" + "newpass_2" + "=" + encodeURIComponent(user_newpass_b);
        }
        params += "&" + "enabled" + "=" + encodeURIComponent(user_enabled);

        // initiate connection
        xhttp.open("POST", "index.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.addEventListener("load", processUserEditResponse);
        xhttp.send(params);
    }
}


function processUserAddResponse() {

    // get result code & reason
    code = xhttp.responseXML.getElementsByTagName("code")[0].textContent;
    reason = xhttp.responseXML.getElementsByTagName("reason")[0].textContent;

    // Output response to user
    if (code == 0) {
        alert("Problem encountered: " + reason);
    } else {
        alert(reason);
    }

    // Clean the form
    document.getElementById("newname").value = '';
    document.getElementById("newmail").value = '';
}


function processUserEditResponse() {

    // get result code & reason
    code = xhttp.responseXML.getElementsByTagName("code")[0].textContent;
    reason = xhttp.responseXML.getElementsByTagName("reason")[0].textContent;

    // Output response to user
    if (code == 0) {
        alert("Problem encountered: " + reason);
    } else {
        alert(reason);
    }

    // Clean the form
    document.getElementById("oldpass").value = '';
    document.getElementById("newpass_1").value = '';
    document.getElementById("newpass_2").value = '';
}
