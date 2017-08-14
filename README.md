# quizTool
Tool to display and edit customizable quiz games.
Availabe in german only.

Zum Start ist dieses Tool vorläufig nur in deutsch verfügbar.

## Version
0.0.6-SNAPSHOT

## Installation

### Voraussetzungen
* Internet-Browser mit freigeschaltetem JavaScript
* Webspace mit funktionierendem PHP
  
Das Spiel ist ein Internet-Spiel für HTML-Clients. Die Browser müssen 
JavaScript beherrschen und zulassen. Das Spiel wird auf einem Web-Server 
installiert. Der Web-Server muss PHP-Scripte korrekt ausführen. 

### Installieren  
Zum Installieren einfach den Inhalt dieses Repositories irgendwo
auf dem Webspace ablegen. 

### Konfigurieren
Die Datei data/users-example.json nach data/users.json kopieren und die 
gewünschten Benutzer konfigurieren. Die Syntax entspricht JSON. 

VORSICHT: Falsche Konfigurationen können verhindern, dass das Login funktioniert. 

## Nutzung
Die Nutzung erfolgt ausschließlich über den Internet-Browser.
Getestet wird derzeit nur für den jeweils aktuellen Firefox-Browser.
Im Aufruf-Parameter wird mitgegeben, welches Quiz genutzt werden soll
und ob der Bearbeitungs-Modus benutzt werden soll.

* Spielen:     http://domain.tld/pfadZumSpiel/?quiz=quizname
* Bearbeiten:  http://domain.tld/pfadZumSpiel/?quiz=quizname&edit=yes 
 
### Backup
Ein Vollbackup kann vom Server abgefragt werden. Dafür ist der entsprechende
Benutzer mit Admin-Rechten zu versehen. Die Datei __./data/users.json__ muss 
dafür manuell bearbeitet werden, der zu berechtigende Nutzer muss zusätzlich 
die folgende Zeile in sein Profil bekommen:
' "isAdmin": 1, '  
Bei der Plazierung ist auf die Kommasetzung zu achten. 

## Changelog

### 0.0.6
* auf lokale jquery 3.2.1-Kopie migriert

### 0.0.5
* separate PHP-Konfigurationsdatei
* Selbstregistrierung möglich
* Zugriffsbeschränkung pro Spiel
* automatische Backup-Dateien
* automatisches Löschen unbenutzter Bilder
* Vollbackup mit ZIP-Download erzeugen 
* Fehler bei unteren Editor-Buttons behoben
* Logs im Server verbessert 

### 0.0.4
* JS-Datei einbinden reicht in html-Datei zur Integration des Spiels
* Farben (Vorder- und Hintergrund) sind konfigurierbar pro Quiz
* Das Logo ist optional konfigurierbar
* Der URL-Parameter 'edit' muss nicht mehr mit einem Wert gefüllt werden
* Meldungen nutzen den Anzeigebereich im Fenster (Modal)
* Das Spiel hat ein allgemeines FavIcon
* Der Bild-Upload lässt nur zugelassene Dateitypen zur Auswahl zu.
* Der Seiten-Titel wird aus dem Quiz-Namen dynamisch bestimmt

### 0.0.3
* Verwaltung im github
* Verbesserte Pfade auf Server (#1)
* index.html als Startseite
* Passwörter als Hash gespeichert (#11)
* Problem mit Bildern nach dem Löschen von Fragen behoben (#10)
* Layoutproblem mit Bild behoben (#17, #3)
 
