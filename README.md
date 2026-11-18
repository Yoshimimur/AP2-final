
## Anwendung starten
Projektverzeichnis: cd "C:\Users\VSO\OneDrive - VOK DAMS\Desktop\ABSCHLUSSPROJEKT\02_Anwendung\QR-DocManager"
- Prüfen, ob es bereits läuft: Get-Process httpd -ErrorAction SilentlyContinue

1. Apache im XAMPP-Control-Panel starten: 
& "C:\xampp\apache\bin\httpd.exe" 
Beenden: Mit Strg + C

2. Das erste vorläufige Passwort: beim Einrichtung der Datenbank erzeugt und einmal im Terminal angezeigt; 
Database neu einrichten: database/setup.php

3. Anwendung im Browser öffnen: Start-Process "http://localhost:8081/login"


## Aufteilung

- [Benutzerhandbuch]: Bedienung der Anwendung
- [Entwicklerdokumentation]: Einrichtung, Dateien und technische Abläufe
- [Tests und Anforderungsabgleich]: Prüfergebnisse und offene manuelle Prüfungen
- [Lizenzhinweise]: Quellen und Original-Lizenztexte


Nur `public/` ist über Apache erreichbar. Datenbank und PDFs liegen lokal in dem Projektverzeichnis und werden direkt aufgerufen 

Die lokale Projektadresse verwendet HTTP. Vor dem späteren Einsatz müssen die endgültige Domain
und HTTPS für diesen VirtualHost eingerichtet sein


## Codeaufbau

- `public/index.php` startet die Anwendung
- `src/app.php` prüft Anmeldung, Rollen und Formulare zentral und legt die URLs fest
- Controller verarbeiten die Aktionen und wählen die passende View
- Models lesen und speichern die Daten, Services übernehmen Anmeldung, Dateien und Protokolle
- `views/` enthält die Seiten, Formulare und Dialoge

Der Formularprüfwert steht direkt im jeweiligen Formular; eine eigene `csrf.php` ist nicht nötig.


