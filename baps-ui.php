<?php

# https://stackoverflow.com/questions/293601/php-and-concurrent-file-access
# http://www.php.net/flock
# https://www.w3schools.com/js/js_json_php.asp

// INSERT INTO `timeslots_applicants` (`id`, `applicant_id`, `timeslot_id`, `timestamp`) VALUES (NULL, '3', '1', CURRENT_TIMESTAMP)
// SELECT * FROM applicants INNER JOIN timeslots_applicants ON timeslots_applicants.applicant_id=applicants.id ORDER BY timeslots_applicants.timestamp ASC
// SELECT * FROM wp_baps_applicants INNER JOIN wp_baps_timeslots_applicants ON wp_baps_timeslots_applicants.applicant_id=wp_baps_applicants.id ORDER BY wp_baps_timeslots_applicants.timestamp ASC
// SELECT wp_baps_timeslots_companies.timeslot_id FROM wp_baps_timeslots_companies INNER JOIN wp_baps_companies ON wp_baps_timeslots_companies.company_id=wp_baps_companies.id WHERE wp_baps_companies.id = \ändern\ ORDER BY wp_baps_timeslots_companies.timeslot_id ASC


/*
Firmen in Datenbank spielen
Timeslots zu Firmen zuordnen

1. Schauen ob UUID von Link existiert (vorhandene Daten ändern):
    Werte abfragen und anzeigen
2. Wenn Werte in $_POST (neue Werte eingespielt / update):
    Werte in Datenbank einspielen
3. Wenn keine Werte vorhanden sind (neue Anmeldung):
    Leeres Formular anzeigen

*/

add_shortcode("baps", "baps_application_page");

define("BAPS_UPLOAD_DIR", dirname(__FILE__) . "/uploads/");

function baps_application_page() {
    forms();
}

// TODO: Warteliste verbessern
// TODO: persönliches Email
// TODO: Backend für Timeslots von Firmen
// TODO: Matrikelnummer eindeutig machen
// TODO: add MySQL sanitizer

function forms() {
    global $wpdb;
    $wp = $wpdb->prefix;

    $app_slot_ids = array();
    $registered_message = "";

    if (!empty($_POST)) {
        $full_name = $_POST["full_name"];
        $email = $_POST["email"];
        $student_id = $_POST["student_id"];
        $study_field = $_POST["study_field"];
        $semester = $_POST["semester"];
        
        foreach ($_POST as $key=>$value) {
            if (substr( $key, 0, 4 ) === "com_" && $value != "") {
                array_push($app_slot_ids, $value);
            }
        }

        $uuid = $_GET["id"];
        $query = "SELECT * FROM {$wp}baps_applicants WHERE uuid = '$uuid'";
        $app_id = $wpdb->get_var($query);
        if (!$app_id)
            $app_id = "NULL";
        
        upload_file($uuid);
        send_mail($email, $uuid);

        $query = "REPLACE INTO {$wp}baps_applicants (id, name, email, student_id, uuid, study_field, semester) 
            VALUES ($app_id, '$full_name', '$email', '$student_id', '$uuid', '$study_field', '$semester')";
        $wpdb->query($query);

        $query = "SELECT id FROM {$wp}baps_applicants WHERE uuid = '{$uuid}'";
        $applicant_id = $wpdb->get_var($query);

// TODO: add company_id (?)

        $query = "SELECT timeslot_id FROM {$wp}baps_timeslots_applicants WHERE applicant_id = '$applicant_id'";
        $old_occupied = $wpdb->get_results($query);
        $tmp = array();
        foreach($old_occupied as $old) {
            array_push($tmp, $old->timeslot_id);
        }
        $old_occupied = $tmp;
        $removed = array_diff($old_occupied, $app_slot_ids);
        $added = array_diff($app_slot_ids, $old_occupied);

        foreach ($added as $slot_id) {
            $query = "INSERT INTO {$wp}baps_timeslots_applicants (id, applicant_id, timeslot_id, timestamp)
                VALUES (NULL, '{$applicant_id}','{$slot_id}', CURRENT_TIMESTAMP)";
            $wpdb->query($query);
        }
        foreach ($removed as $rm) {
            $query = "DELETE FROM {$wp}baps_timeslots_applicants WHERE timeslot_id = {$rm} AND applicant_id = {$applicant_id}";
            $wpdb->query($query);
        }

        $link = get_permalink()."?id=$uuid";
        $registered_message = "<h2>Du hast dich erfolgreich für beWANTED angemeldet!</h2>
        <p>Um Details deiner Anmeldung zu sehen, oder um nachträchlich etwas zu ändern klicke auf diesen Link:
        <a href=$link>$link</a></p><p>Bitte schreibe ihn auf oder speichere diese Seite als Lesezeichen.</p>";
    }

    if (isset($_GET["id"])) {
        $uuid = $_GET["id"];

        $query = "SELECT * FROM {$wp}baps_applicants WHERE uuid = '{$uuid}'";
        $filled = $wpdb->get_results($query)[0];

        $full_name = $filled->name;
        $email = $filled->email;
        $student_id = $filled->student_id;
        $study_field = $filled->study_field;
        $semester = $filled->semester;
        $app_id = $filled->id;

        if (!$app_slot_ids) {
            $query = "SELECT timeslot_id FROM {$wp}baps_timeslots_applicants WHERE applicant_id={$app_id}";
            $response = $wpdb->get_results($query);
            
            foreach ($response as $r)
                array_push($app_slot_ids, $r->timeslot_id);
        }
    }
    else {
        $uuid = substr(md5(rand(1000, 100000)."+".rand(0, 100000)."+".rand(0, 1000000)), 0, 32);

        $full_name = "";
        $email = "";
        $student_id = "";
        $study_field = "";
        $semester = "";
    }

//TODO: in eigene CSS Datei auslagern
//TODO: eigene Namen verwenden (baps-...)
    $style = '<style type="text/css">
        .form-style{
            max-width:400px;
            margin:50px auto;
            background:#fff;
            border-radius:2px;
            padding:20px;
            font-family: Arial, Helvetica, sans-serif;
        }
        .form-style ul{
            padding:0;	
        }
        .form-style li{
            display: block;
            padding: 9px;
            border:1px solid #DDDDDD;
            margin-bottom: 30px;
            border-radius: 3px;
        }
        .form-style li:last-child{
            border:none;
            margin-bottom: 0px;
            text-align: center;
                height: 30px;
        }
        .form-style li > label{
            display: block;
            float: left;
            margin-top: -19px;
            background: #FFFFFF;
            padding: 2px 5px 2px 5px;
            color: #B9B9B9;
            font-size: 14px;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
        }
        .form-style input[type="text"],
        .form-style input[type="email"],
        .form-style select 
        {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            width: 100%;
            display: block;
            outline: none;
            border: none;
            height: 25px;
            line-height: 25px;
            font-size: 16px;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .form-style li > span{
            background: #F3F3F3;
            display: block;
            padding: 3px;
            margin: 0 -9px -9px -9px;
            text-align: center;
            color: #C0C0C0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }
        .form-style input[type="submit"]{
            background: #1d9188;
            border: none;
            padding: 10px 20px 10px 20px;
            border-bottom: 3px solid #1d9188;
            border-radius: 3px;
            color: #FFFFFF;
        }
        .form-style input[type="submit"]:hover{
            background: #dea514;
            border-bottom: 3px solid #dea514;
            color:#FFFFFF;
        }
        .box {
            width: fit-content;
            height: 30px;
        }
        #sel {
            margin-top:15px;
        }
        </style>';


//TODO: make list dynamic, add file-upload check
    $script = "<script>
        function check() {
            var form = document.forms['form'];
            var fields = ['full_name', 'email', 'student_id', 'study_field', 'semester', 'cv'];

            for (i=0; i<fields.length; i++) {
                value = document.getElementsByName(fields[i])[0].value;
                if (!value || !value.trim().length) {
                    alert('Bitte fülle alle Felder aus.');
                    return false;
                }
            }
        };
        </script>";

    $html = $style.$script;
    $html = $html.$registered_message;
    $html = $html.sprintf('<form action="?id=%s" method="post" name="form" id="baps-form" enctype="multipart/form-data" onsubmit="return check()" class="form-style">', $uuid);
    $html = $html.'<ul>';
    $html = $html.'<li>';
    $html = $html.'<label for="name">Name</label>';
    $html = $html.sprintf('<input type="text" name="full_name" value="%s" maxlength="100">', $full_name);
    $html = $html.'<span>Dein Name</span>';
    $html = $html.'</li>';
    $html = $html.'<li>';
    $html = $html.'<label for="email">E-mail</label>';
    $html = $html.sprintf('<input type="email" name="email" value="%s" maxlength="100">', $email);
    $htl = $html.'<span>Deine E-Mail Adresse</span>';
    $html = $html.'</li>';
    $html = $html.'<li>';
    $html = $html.'<label for="student_id">Matrikelnummer</label>';
    $html = $html.sprintf('<input type="text" name="student_id" value="%s" maxlength="8">', $student_id);
    $html = $html.'<span>Deine Matrikelnummer</span>';
    $html = $html.'</li>';
    $html = $html.'<li>';
    $html = $html.'<label for="study_field">Studienrichtung</label>';
    $html = $html.sprintf('<select name="study_field" value="%s">', $study_field);

    $query = "SELECT name FROM {$wp}baps_study_fields ORDER BY {$wp}baps_study_fields.id ASC";
    $response = $wpdb->get_results($query);
    foreach ($response as $r) {
        if ($r->name == $study_field)
            $html = $html.sprintf('<option selected>%s</option>', $r->name);
        else
            $html = $html.sprintf('<option>%s</option>', $r->name);

    }
    $html = $html.'</select>';
    $html = $html.'</li>';
    $html = $html.'<li>';
    $html = $html.'<span>Aktuelles Semester:</span>';
    $html = $html.'<select name="semester" id="sel">';
    $semesters = ["1-4", "5-8", "9-12", "13+"];
    foreach ($semesters as $s) {
        if ($s == $semester)
            $html = $html.'<option selected>'.$s.'</option>';
        else
            $html = $html.'<option>'.$s.'</option>';
    }
    $html = $html.'</select>';
    $html = $html.'</li>';

    $html = $html.'<li>';
    $html = $html.'<span>Lebenslauf hochladen</span>';
    $html = $html.'<input type="file" name="cv" id="sel" /><br />';
    $html = $html.'</li>';
    $html = $html.'<li>';
    //echo $html;

    $query = "SELECT COUNT(*) FROM {$wp}baps_companies";
    $num_companies = $wpdb->get_var($query);
    $query = "SELECT COUNT(*) FROM {$wp}baps_timeslots";
    $num_timeslots = $wpdb->get_var($query);
    
    $timetable = array();
    for ($i=0; $i<$num_timeslots; $i++) {
        for ($j=0; $j<$num_companies; $j++) {
            $arr = array ("id" => ($j * $num_timeslots) + $i, "free" => 2, "blocked" => FALSE);
            $timetable[$i][$j] = $arr;
        }
    }

    //$query = "SELECT {$wp}baps_timeslots_applicants.applicant_id, {$wp}baps_timeslots_applicants.company_id, {$wp}baps_timeslots_applicants.timeslot_id FROM wp_baps_applicants INNER JOIN wp_baps_timeslots_applicants ON wp_baps_timeslots_applicants.applicant_id=wp_baps_applicants.id ORDER BY {$wp}baps_timeslots_applicants.timestamp ASC";
    $query = "SELECT {$wp}baps_timeslots_applicants.applicant_id, {$wp}baps_timeslots_applicants.timeslot_id FROM {$wp}baps_applicants 
        INNER JOIN {$wp}baps_timeslots_applicants ON {$wp}baps_timeslots_applicants.applicant_id={$wp}baps_applicants.id 
        ORDER BY {$wp}baps_timeslots_applicants.timestamp ASC";
    $response = $wpdb->get_results($query);
   
    $ts_query = "SELECT id, slot from {$wp}baps_timeslots";
    $ts_response = $wpdb->get_results($ts_query);

    $c_query = "SELECT id, name from {$wp}baps_companies";
    $c_response = $wpdb->get_results($c_query);

    $selectors = '<div style="display:block;">';
    foreach ($c_response as $c_r) {
        $selectors = $selectors.$c_r->name.'<select name="com_'.$c_r->name.'">';
        $selectors = $selectors."<option></option>";
        foreach ($ts_response as $ts_r) {
            $timeslot = $ts_r->id - 1;
            $company_id = $c_r->id - 1;

            $free_slots = $timetable[$timeslot][$company_id]["free"];
            $app_slot_id = $timeslot + ($company_id * $num_timeslots);

            $query_cs = "SELECT {$wp}baps_timeslots_companies.timeslot_id FROM {$wp}baps_timeslots_companies INNER JOIN {$wp}baps_companies
                ON {$wp}baps_timeslots_companies.company_id={$wp}baps_companies.id WHERE {$wp}baps_companies.id = %d
                ORDER BY {$wp}baps_timeslots_companies.timeslot_id ASC";
            $query_cs = sprintf($query_cs, $company_id+1);
            $available_timeslots = $wpdb->get_results($query_cs);

            //TODO: könnte man eleganter lösen
            $found = FALSE;
            foreach ($available_timeslots as $avl) {
                if ($avl->timeslot_id == $timeslot) {
                    $found = TRUE;
                }
            }
            if (!$found)
                continue;

            foreach ($response as $k => $row) {
                if ($row->timeslot_id == $app_slot_id) {
                    unset($response[$k]);
                    $free_slots--;
                }
            }

            if (in_array($app_slot_id, $app_slot_ids))
                $selected = "selected";
            else
                $selected = "";
            
            $selectors = $selectors.sprintf("<option value='%d' %s>%s (%d)</option>", $app_slot_id, $selected, $ts_r->slot, $free_slots);
        }
        $selectors = $selectors."</select>";
    }
    $selectors = $selectors."</div>";
 
    $html = $html.$selectors."</li>";

    $html = $html.'<li>';
    $html = $html.'<input type="submit" value="Absenden" name="submit" />';
    $html = $html.'<li>';
    $html = $html.'</ul>';
    // <!-- Slots und hidden für Warteliste hinzufügen -->
    $html = $html.'</form>';

    echo $html;

    //echo $selectors;


    /* TODO: this is the code for using tables as selection
    $table_header = "";
    $table_rows = "";

    foreach ($ts_response as $ts_r) {
            $table_rows = $table_rows."<tr>";

            $table_header = "<tr>";
            foreach ($c_response as $c_r) {
                $table_header = $table_header.sprintf("<td><b>%s</b></td>", $c_r->name);
                $app_slot_id = ($ts_r->id) + ($c_r->id * $num_timeslots);
                /$table_rows = $table_rows.sprintf("<td id=%d>%s</td>", $app_slot_id, $ts_r->slot);
        }
        $table_rows = $table_rows."</tr>";
    }

    $script = '<script src="https://code.jquery.com/jquery-1.8.3.min.js"  type="text/javascript"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $("#baps-table td").click(function(){
            $(this).addClass("selected").siblings().removeClass("selected");
            var value=$(this).find("td:first").html();
        });
     });
    </script>';

    $css = "<style>
    td {border: 1px #DDD solid; padding: 5px; cursor: pointer;}
    .selected {
        background-color: brown;
        color: #FFF;
    }
    </style>";

    echo $script;
    echo $css;

    $table = '<table id="baps-table">'.$table_header.$table_rows.'</table>';
    echo $table;
    */
}

function upload_file($filename) {
    if(filter_input(INPUT_POST, "submit", FILTER_SANITIZE_STRING)){
        $ext = pathinfo($_FILES["cv"]['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES["cv"]["tmp_name"], BAPS_UPLOAD_DIR . $filename . "." . $ext);
    }
}

function send_mail($recipient, $uuid) {
    $header = array(
        "From: vienna@best.eu.org",
        "MIME-Version: 1.0",
        "Content-Type: text/html;charset=utf-8"
    );

    $link = get_permalink()."?id=$uuid";

    $msg = "<html><body><h2>Du hast dich erfolgreich für beWANTED angemeldet!</h2>
        <p>Um Details deiner Anmeldung zu sehen, oder um nachträglich etwas zu ändern klicke auf diesen Link:
        <a href=$link>$link</a></p>
        <p>Mit freundlichen Grüßen,<br/>BEST Vienna</p></body></html>";

    $ret = mail(
        $recipient,
        "Deine Anmeldung für beWANTED",
        $msg,
        implode("\r\n", $header)
    );
}
?>
