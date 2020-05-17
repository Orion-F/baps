function check() {
    var form = document.forms['form'];
    var fields = ['full_name', 'email', 'student_id', 'study_field', 'semester'];

    for (i=0; i<fields.length; i++) {
        value = document.getElementsByName(fields[i])[0].value;
        if (!value || !value.trim().length) {
            alert('Bitte fülle alle Felder aus.');
            return false;
        }
    }

    file_uploaded = document.getElementsByName('file_uploaded')[0].value;
    cv = document.getElementsByName('cv')[0].value;

    if (file_uploaded == 0 && !cv) {
        alert('Bitte fülle alle Felder aus.');
        return false;
    }
}
