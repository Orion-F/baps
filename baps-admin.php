<?php

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
        $ts_query = "SELECT company_id, timeslot_id FROM {$wp}baps_timeslots_companies WHERE 
            id IN (SELECT timeslot_id FROM {$wp}baps_timeslots_applicants WHERE applicant_id = {$applicant_id})";
        $ts_results = $wpdb->get_results($ts_query);

        $occupied_slots = "";
        foreach ($ts_results as $ts_r) {
            $slot_query = "SELECT name FROM {$wp}baps_companies WHERE id = {$ts_r->company_id} UNION SELECT slot FROM {$wp}baps_timeslots WHERE id = {$ts_r->timeslot_id}";
            $slot_results = $wpdb->get_results($slot_query);
            $occupied_slots = $occupied_slots.$slot_results[0]->name." <i>".$slot_results[1]->name."</i><br/>";
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

function baps_settings_page() {

}

?>
