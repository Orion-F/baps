function check() {
    let fields = ['full_name', 'email', 'student_id', 'study_field', 'semester'];

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

function removeSlot(id) {
    var buttonRemove = document.getElementById(id);
    var parent = buttonRemove.parentElement;
    parent.remove(parent)
}

function  moveSlotUp(id) {
    var buttonUp = document.getElementById(id);
    var parent = buttonUp.parentElement;

    slotName = parent.firstChild.name;
    oldSlotPosition = slotName.split("_")[1];
    newSlotPosition = parseInt(slotName.split("_")[2]) - 1;
    newSlotName = "slot_" + oldSlotPosition + "_" + newSlotPosition;
    document.getElementsByName(slotName)[0].setAttribute("name", newSlotName);

    previousElement = parent.previousElementSibling;
    if (previousElement != null)
        parent.parentElement.insertBefore(parent, previousElement);
}

function  moveSlotDown(id) {
    var buttonDown = document.getElementById(id);
    var parent = buttonDown.parentElement;

    slotName = parent.firstChild.name;
    oldSlotPosition = slotName.split("_")[1];
    newSlotPosition = parseInt(slotName.split("_")[2]) + 1;
    newSlotName = "slot_" + oldSlotPosition + "_" + newSlotPosition;
    document.getElementsByName(slotName)[0].setAttribute("name", newSlotName);

    nextElement = parent.nextElementSibling.nextElementSibling;
    if (nextElement != null)
        parent.parentElement.insertBefore(parent, nextElement);
}