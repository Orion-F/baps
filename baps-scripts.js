//TODO: make list dynamic, add file-upload check

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
}
