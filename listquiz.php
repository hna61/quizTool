 <?php
// Mit den folgenden Zeilen lassen sich
// alle Dateien in einem Verzeichnis auslesen
$handle=opendir ("./quizes/");
echo "Verzeichnisinhalt:<br><ul>";
while ($datei = readdir ($handle)) {
 echo "<li>$datei</li>";
}
closedir($handle);
?> 
</ul>