<?php
/*
 *  Beispiel-Konfiguration
 *    Datei kopieren, zu config.php umbenennen und ggf. anpassen.
 */    
define ("QZ_QUIZDIR", "../data/quizes/");
define ("QZ_IMGDIR", "../data/img/"); 
define ("QZ_NUMBACKUPS", 5);
define ("QZ_EMPTYQUIZ", '{"name":"neues Quiz", "email":"ah@in-howi.de", "questions": [{"question": "", "img": "", "desc":"", "url":"", "answers": ["","","",""]}]}');
define ("QZ_BILDGROESSE", 300);
define ("QZ_ABSENDER_EMAIL", "name@beispiel.tld");
define ("QZ_ABSENDER_NAME", "Quiz-Server");
?>