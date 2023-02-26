<?php

function register_baps_admin_scripts() {
    wp_enqueue_script('baps-scripts', plugins_url('/baps/baps-scripts.js'), false);
}
add_action("admin_enqueue_scripts", "register_baps_admin_scripts");

// TODO: add company edit
// TODO: add slot edit
// TODO: add slot-company assignement

// TODO: Link auf PDF
function baps_admin_page() {
    global $wpdb;
    $wp = $wpdb->prefix;

    if(isset($_POST['export'])) { 
        export(); 
    } 

    $table = "<table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Student ID</th>
                    <th>Field of Study</th>
                    <th>Semester</th>
                    <th>Applied Timeslots</th>
                </tr>";

    // TODO: proper display of study field
    $query = "SELECT id, uuid, name, email, student_id, study_field, semester FROM {$wp}baps_applicants";
    $results = $wpdb->get_results($query);
    foreach ($results as $r) {
        $applicant_id = $r->id;
        # For a given applicant, the query below gives a table of company_ids, and appropriate slot for each one.
        $ts_query = "SELECT DISTINCT wien_baps_timeslots_applicants.company_id, wien_baps_timeslots.slot FROM wien_baps_timeslots_applicants JOIN wien_baps_timeslots_companies ON wien_baps_timeslots_applicants.company_id = wien_baps_timeslots_companies.company_id JOIN wien_baps_timeslots ON wien_baps_timeslots_applicants.timeslot_id = wien_baps_timeslots.id WHERE wien_baps_timeslots_applicants.applicant_id = {$applicant_id}";
        $ts_results = $wpdb->get_results($ts_query);

// TODO: UNION ist nicht gut!        
        $occupied_slots = "";
        foreach ($ts_results as $ts_r) {
            $company_name_query = "SELECT name FROM {$wp}baps_companies WHERE id = {$ts_r->company_id}";
            $company_name = $wpdb->get_results($company_name_query);
            $occupied_slots = $occupied_slots.$company_name[0]->name." <i>".$ts_r->slot."</i><br/>";
        }
        $table = $table."<tr>
                            <td>{$r->uuid}</td>
                            <td>{$r->name}</td>
                            <td>{$r->email}</td>
                            <td>{$r->student_id}</td>
                            <td>{$r->study_field}</td>
                            <td>{$r->semester}</td>
                            <td>$occupied_slots</td>
                        </tr>";
    }
    $table = $table."</table>";
    $button = "<form method='post'><input type='submit' name='export' value='Export Applications'/></form>";
    $html = "<h1>Applications</h1>".$table;
    
    echo $html;
}

function baps_export_page() {
    global $wpdb;
    $wp = $wpdb->prefix;

    $html = "<ul>";

    $query = "SELECT id, name FROM {$wp}baps_companies WHERE 1";
    $results = $wpdb->get_results($query);
    foreach ($results as $r) {
        $application_query = "SELECT {$wp}baps_applicants.name, {$wp}baps_study_fields.field, {$wp}baps_applicants.semester, 
                {$wp}baps_applicants.email, {$wp}baps_applicants.uuid, {$wp}baps_timeslots.slot
            FROM {$wp}baps_timeslots_applicants
            LEFT JOIN {$wp}baps_applicants ON {$wp}baps_applicants.id = {$wp}baps_timeslots_applicants.applicant_id
            LEFT JOIN {$wp}baps_timeslots ON {$wp}baps_timeslots.id = {$wp}baps_timeslots_applicants.timeslot_id
            LEFT JOIN {$wp}baps_study_fields ON {$wp}baps_study_fields.id = {$wp}baps_applicants.study_field
            WHERE timeslot_id IN (SELECT timeslot_id FROM {$wp}baps_timeslots_companies WHERE company_id = {$r->id}) AND company_id = {$r->id} ORDER BY timestamp";
        $application_data = $wpdb->get_results($application_query);

        $csv = fopen("php://memory", "w");
        $zip_file = new ZipArchive();
        $zip_fn = BAPS_UPLOAD_DIR."export/".$r->name.".zip";
        $zip_file->open($zip_fn, ZipArchive::CREATE);

        $cnt = 1;
        fputcsv($csv, array("Zeit", "Name", "Studienrichtung", "Semester", "E-Mail"));
        foreach ($application_data as $application) {
            $ext = pathinfo(glob(BAPS_UPLOAD_DIR.$application->uuid."*")[0], PATHINFO_EXTENSION);
            $cv_file = BAPS_UPLOAD_DIR.$application->uuid.".".$ext;

            $line = array($application->slot, $application->name, $application->field, $application->semester, $application->email);
            fputcsv($csv, $line);

            $zip_file->addFile($cv_file, str_pad($cnt, 2,"0", STR_PAD_LEFT)."_".str_replace(" ", "", $application->name).".".$ext);
            $cnt++;
        }
        rewind($csv);
        $zip_file->addFromString("00_Applications.csv", stream_get_contents($csv));

        $zip_fn = home_url()."/wp-content/plugins/baps/uploads/export/".basename($zip_fn);
        $html = $html."<li><a href=\"{$zip_fn}\">{$r->name}</a></li>";
    }
    $html = "<h1>Download CVs</h1><p>Download a Zip file with all CVs and a list of applicants.</p>
            <p>Applicants are ordered by their application time.</p>".$html."</ul>";
    echo $html;

}

function action_button ($action_name, $title, $id) {
    $html = '<form method="post" style="display: inline-block;">';
    $html = $html.sprintf('<input type="hidden" name="id" value="%s">', $id);
    $html = $html.sprintf('<button name="%s" type="submit">%s</button>', $action_name, $title);
    $html = $html.'</form>';
    return $html;
}

function company_input($company_name, $action, $id = NULL) {
    $html = '<form method="post">';
    $html = $html.sprintf('<input type="text" name="company_name" value="%s">', $company_name);
    if (isset($id)) {
        $html = $html.sprintf('<input type="hidden" name="company_id" value="%s"/>', $id);
    }
    $html = $html.sprintf('<button name="%s" type="submit">%s</button>', $action, $action);
    $html = $html.'</input>';
    return $html;
}

function baps_edit_companies() {
    global $wpdb;
    $wp = $wpdb->prefix;

    if (array_key_exists("Add", $_POST)) {
        $query = "INSERT INTO `{$wp}baps_companies` (`id`, `name`) VALUES (NULL, '{$_POST["company_name"]}')";
        $wpdb->query($query);
    }
    elseif (array_key_exists("Edit", $_POST)) {
        $query = "UPDATE `{$wp}baps_companies` SET `name` = '{$_POST["company_name"]}' WHERE `id` = {$_POST["company_id"]}";
        $wpdb->query($query);
    }
    elseif (array_key_exists("Delete", $_POST)) {
        $query = "DELETE FROM `{$wp}baps_companies` WHERE id = {$_POST["id"]}";
        $wpdb->query($query);
    }

    $query = "SELECT id, name FROM {$wp}baps_companies WHERE 1";
    $results = $wpdb->get_results($query);

    $table = "<table>
                <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Action</th>
                </tr>";

    foreach ($results as $r) {
        $edit = action_button("action_button_edit", "edit", $r->id);
        $delete = action_button("Delete", "delete" ,$r->id);
        $table = $table."<tr>
                            <td>{$r->id}</td>
                            <td>{$r->name}</td>
                            <td>{$edit}{$delete}</td>
                         </tr>";
    }
    $table = $table."</table>";

    if (array_key_exists("action_button_add", $_POST)) {
        $edit_form = company_input("", "Add");
    }
    elseif (array_key_exists("action_button_edit", $_POST)) {
        $query = "SELECT id, name FROM `{$wp}baps_companies` WHERE id = {$_POST['id']}";
        $result = $wpdb->get_results($query);
        foreach ($result as $r) {
            $company_name = $r->name;
            $company_id = $r->id;
            break;
        }
        $edit_form = company_input($company_name, "Edit", $company_id);
    }
    else {
        $edit_form = "";
    }

    $button_add = action_button("action_button_add", "Add new", -1);
    $html = "<h1>Companies</h1>".$button_add.$table.$edit_form;
    echo $html;
}

function baps_edit_timeslots() {
    global $wpdb;
    $wp = $wpdb->prefix;

    if (!is_null($_POST)) {
        $query = "SELECT id FROM {$wp}baps_timeslots";
        $oldSlots = $wpdb->get_results($query);
        var_dump($oldSlots);
        echo "<br>";
    }
    foreach ($_POST as $slot) {


// TODO: check new slots
// TODO: check removed slots
// TODO: update / re-arrange slots
/*
UPDATE fruit SET id = 0 WHERE id = 5;
UPDATE fruit SET id = 5 WHERE id = 2;
UPDATE fruit SET id = 2 WHERE id = 0;
 */
# update test a inner join test b on a.id <> b.id set a.text = b.text where a.id in (1,2) and b.id in (1,2)
// slot_oldID_newID
    }
    var_dump($_POST);

    $query = "SELECT * FROM {$wp}baps_timeslots";
    $result = $wpdb->get_results($query);

    $form = '<form method="post">';
    foreach ($result as $r) {
        $form = $form.sprintf('<div id="slot_%s">', $r->id);
        $form = $form.sprintf('<input type="text" name="slot_%s_%s" value="%s"/>', $r->id, $r->id, $r->slot);
        $form = $form.sprintf('<button id="up_%s" onclick="moveSlotUp(this.id);return false;">⬆</button>', $r->id);
        $form = $form.sprintf('<button id="down_%s" onclick="moveSlotDown(this.id);return false;">⬇</button>', $r->id);
        $form = $form.sprintf('<button id="remove_%s" onclick="removeSlot(this.id);return false;">remove</button><br/>', $r->id);
        $form = $form."</div>";
    }
    $form = $form.'<button name="save" type="submit">Save</button>';
    $form = $form.'</form>';
    $html = '<h1>Timeslots</h1><h2>Available Timeslots</h2>'.$form;
    echo $html;
}

function baps_settings_page() {

}

?>