// verify if numeric
// TODO - can go to javascript functions file
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
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
    var pass_length = length(pass);

    if (pass_length < 10) {
        pass_good = false;
        pass_reason = "Password too short";
    }
    else if ((caps_count < 1) || (nums_count < 1) || (spec_count < 1)) {
        pass_good = false;
        pass_reason = "No big letter, number or special character";
    } else {
        pass_good = true;
    }

    pass_result.append(pass_good);
    pass_result.append(pass_reason);

    return pass_result;
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
    var user_newpass1 = document.getElementById("newpass_1").value;
    var user_newpass2 = document.getElementById("newpass_2").value;
    var user_enabled = document.getElementById("enabled").value;

    // verify if params ok
    verify = false;
    if (user_newpass1) {
        passchange = true;
        if (user_newpass2) {
            if (user_oldpass) {
                if (user_newpass2 == user_newpass1) {
                    var pass_result = good_pass(user_newpass1);
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
        if (user_enabled) {
            verify = true;
        } else {
            alert('No changes');
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
            params += "&" + "newpass_1" + "=" + encodeURIComponent(user_newpass1);
            params += "&" + "newpass_2" + "=" + encodeURIComponent(user_newpass2);
        }
        params += "&" + "enabled" + "=" + encodeURIComponent(user_enabled);

        // initiate connection
        xhttp.open("POST", "index.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.addEventListener("load", processUserEditResponse);
        xhttp.send(params);
    }
}
