<?php
function baps_admin_page() {
    global $wpdb;
    $wp = $wpdb->prefix;

    // table: id, name, email, student_id, study_field, semester, timeslots, waiting_list (??)
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
            $occupied_slots = $occupied_slots.$slot_results[0]->name." ".$slot_results[1]->name;
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
    $html = "<h1>Applications</h1>".$table;
    
    echo $html;
}

function baps_settings_page() {

}

?>
