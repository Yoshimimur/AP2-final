// Entfernt nur die Speichermeldung nach fünf Sekunden
for (const message of document.querySelectorAll('[role="status"]')) {
    if (message.textContent.trim() === 'Die Änderung wurde gespeichert.') {
        setTimeout(function () {
            message.remove();
        }, 5000);
    }
}

// Öffnet den Dialog für die angeklickte Zeile
for (const button of document.querySelectorAll('[data-dialog]')) {
    button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const dialog = document.getElementById(button.dataset.dialog);
        const form = dialog.querySelector('form');
        form.reset();

        // Übernimmt die Nummer für Speichern oder Löschen
        const idField = form.querySelector('[name="id"]');
        if (idField) {
            idField.value = button.dataset.id;
        }

        // Setzt beim Umbenennen den bisherigen Namen ein
        const nameField = form.querySelector('[name="name"]');
        if (nameField && button.dataset.name !== undefined) {
            nameField.value = button.dataset.name;
        }

        // Zeigt den Namen im Hinweis des Dialogs
        for (const label of dialog.querySelectorAll('[data-current-name]')) {
            label.textContent = button.dataset.name;
        }
        dialog.showModal();
    });
}

// Leert die Eingabe und klappt sie beim Abbrechen zu
for (const button of document.querySelectorAll('[data-cancel-destination]')) {
    button.addEventListener('click', function () {
        button.form.reset();
        button.closest('details').open = false;
    });
}

// Speichert die ausgewählte Rolle
for (const select of document.querySelectorAll('[data-role-select]')) {
    select.addEventListener('change', function () {
        select.form.requestSubmit();
    });
}

// Schließt den Dialog ohne zu speichern
for (const button of document.querySelectorAll('[data-close]')) {
    button.addEventListener('click', function () {
        button.closest('dialog').close();
    });
}

// Prüft die Dateigröße vor dem Absenden zusätzlich im Browser
for (const field of document.querySelectorAll('input[type="file"]')) {
    field.addEventListener('change', function () {
        const file = field.files[0];
        field.setCustomValidity('');
        if (file && file.size > 20 * 1024 * 1024) {
            field.setCustomValidity('Die PDF-Datei darf höchstens 20 MB groß sein.');
        }
        field.reportValidity();
    });
}