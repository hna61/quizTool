# quizTool
Tool to display and edit customizable quiz games.
Availabe in german only.

Zum Start ist dieses Tool vorläufig nur in deutsch verfügbar.

## Version
0.0.4 - SNAPSHOT 

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

## Changelog

### 0.0.4
* ...

### 0.0.3
* Verwaltung im github
* Verbesserte Pfade auf Server (#1)
* index.html als Startseite
* Passwörter als Hash gespeichert (#11)
* Problem mit Bildern nach dem Löschen von Fragen behoben (#10)
* Layoutproblem mit Bild behoben (#17, #3)
 