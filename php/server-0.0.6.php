<?php
/*
 * Quiz - Server
 *  
 *  Author(s): Andreas Heidemann (2017-), 
 *  License: MIT  
 */
 
define ("VERSION", "0.0.6"); 
 
ini_set('display_errors', 1);
if (file_exists ("config-local.php")){
  require_once "config-local.php";
} 
require_once "config-base.php";

class Zipper extends ZipArchive {  
   
   private function addSubDir($path, $baselen) {
      if (!is_dir($path)){
          return;
      }
      $this->addEmptyDir(substr($path, $baselen));
      
      $nodes=opendir ($path);
      while ($node = readdir ($nodes)) {
        if ($node != "." && $node != ".."){
          $node = $path . "/" . $node;
          if (is_dir($node)) {
            $this->addSubDir($node, $baselen);
          } else if (is_file($node))  {
            $this->addFile($node, substr($node, $baselen));
          }
        }
      }
      closedir($nodes);
   }
   
   public function addDir($path) {
      $path = realpath($path);
      $baselen = strlen($path) - strlen(basename($path)); 
      $this->addSubDir($path, $baselen);
   }
} 




/*
 *  Hilfsfunktionen
 */ 

function logMe($logtext){
  $logfile = "../data/usage.log";
  file_put_contents($logfile, date("Y-m-d H:i:s") 
                              .", ". $_SERVER['REMOTE_ADDR']
                              . " : ". $logtext . "\r\n"
                    , FILE_APPEND );  
}

function startsWith($haystack, $needle){
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle){
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function getAcceptedImageTypes(){
  return "image/jpeg" ;
}

function getUser($user)
{
	$userFile = '../data/users.json';
	$userListJson = file_get_contents ($userFile);
	$userList = json_decode($userListJson, true);
	return $userList[$user];
}

function setUser($username, $User)
{
	$userFile = '../data/users.json';
	$userListJson = file_get_contents ($userFile);
	$userList = json_decode($userListJson, true);
	$userList[$username] = $User;
  file_put_contents($userFile, json_encode ($userList,JSON_PRETTY_PRINT));
}

function addUser($username, $passwd, $email){
	$newUser = (object) ['passwd' => $passwd, 'email'=> $email];
	setUser($username,$newUser);
}

function verifyUser($username, $pwd, $pin){
	$newUser = getUser($username);
  if (("x".$newUser['pin']) == ("x".$pin)){
    logMe("PIN für ". $username . " akzeptiert.");
    $newUser['pin'] = 0;  
	  setUser($username,$newUser);
    return true;
  }
  logMe("PIN für ". $username . " nicht akzeptiert. ".$pin. " <> ". $newUser['pin']);
	return false;
}

function registerUser($username, $hash, $email){ 
	$oldUser = getUser($username);
  if ($oldUser){
    logMe("Benutzer ".$username." darf nicht überschrieben werden.");
  } else {
  	$pin = rand(1000, 9999);
  	$newUser = (object) ['passwd' => $hash, 'email'=> $email, 'pin'=>$pin];
  	setUser($username,$newUser);
  	return mailPin($email, $pin);
  }
}

function isGranted(){  
  $user =  $_REQUEST['user'];
  $pwd =   $_REQUEST['passwd'];  
  $quiz =   $_REQUEST['quiz']; 
  $UserObj = getUser($user);
  return ($UserObj 
          && password_verify($pwd,$UserObj["passwd"]) 
          && (!$quiz || editAllowed($user, $quiz))
          );
}

function isAdmin(){  
  $user =  $_REQUEST['user'];
  $pwd =   $_REQUEST['passwd']; 
  $UserObj = getUser($user);
  logMe("admin angefragt für ". $user);
  return ($UserObj 
          && password_verify($pwd,$UserObj["passwd"]) 
          && ($UserObj["isAdmin"])
          );
}

function editAllowed($user, $quiz){
  $quizDom = json_decode (file_get_contents (QUIZDIR . $quiz . ".json"));
  if ($quizDom && $quizDom->users && !in_array($user, $quizDom->users)){
    return false;
  } else {
    return true;
  }
}

function isVerified(){  
  $user =  $_REQUEST['user'];
  $pwd =   $_REQUEST['passwd']; 
  $quiz =   $_REQUEST['quiz']; 
  $UserObj = getUser($user);
  return ($UserObj 
          && password_verify($pwd,$UserObj["passwd"]) 
          && (!$UserObj["pin"] || $UserObj["pin"]==0)
          && (!$quiz || editAllowed($user, $quiz))
          );
}


function mailPin($email, $pin){
  logMe("erzeuge mail, von ".QZ_ABSENDER_NAME." <".QZ_ABSENDER_EMAIL.">" );
  logMe("erzeuge mail, quelle: ".$_SERVER['HTTP_REFERER'] );
	$subject = "Zugang zum Quiz-Server";
	$body = "Hallo \r\n\r\n"
		. "du musst den Zugang zum Quiz-Server noch mit einer"
		. " PIN bestätigen.\r\n\r\n"
		. "Die PIN ist    "
		. $pin . ".\r\n\r\n"
		. "Bitte nutze den folgenden Link dazu:\r\n"
		. "* ".$_SERVER['HTTP_REFERER']."?pin="
		. $pin
		. "&edit\r\n\r\n-Dein ".QZ_ABSENDER_NAME."-";
  
  $headers = "From: ".QZ_ABSENDER_NAME." <".QZ_ABSENDER_EMAIL.">";

  return mail($email, $subject, $body, $headers);
}

function sendImg($image){
  $filePath = "../data/img/" . basename($image);
  $info = getimagesize($filePath);
  if ($info && $info['mime']){
    logMe("lade " . $filePath);
    header("Content-Type: " . $info['mime']);  
    readfile ($filePath);
  } else {                     
    logMe("verzweifle an " . $filePath);
    http_response_code(404);
    echo ("Datei ". $filePath ." nicht vorhanden.");
  }   
}

function getImageFiles(){ 
  $result = array();
  $handle=opendir (QZ_IMGDIR);
  while ($datei = readdir ($handle)) {
    if (! startsWith($datei, ".")){
      $result[] = $datei;
    }
  }
  closedir($handle);
  
  return $result;
}

/*
 *  Lies die verfügbaren Quiz-Dateien
 */
function getQuizes(){
  $result = array();
  $handle=opendir (QUIZDIR);
  while ($datei = readdir ($handle)) {
    if (endsWith($datei, ".json")){
      $result[] = $datei;
    }
  }
  closedir($handle);
  
  return $result;
}    

// Liefert alle im Quiz verwendeten Image-Dateien im Array
function getQuizImages($quiz){
  $result = array();
  $quizDom = json_decode (file_get_contents (QUIZDIR . $quiz));   
  if (strlen($quizDom->logo) > 0){
    $result[] = basename($quizDom->logo);
  }
  foreach($quizDom->questions as $q){
    if (strlen($q->img) > 0){
      $result[] = basename($q->img);
    }
  } 
  return $result;
}

function getReferencedImages(){
  $result = array(); 
  foreach(getQuizes() as $quiz){
    $result = array_merge($result, getQuizImages($quiz));    
  }
  return $result;
}

function getOrphanedImages(){
  $unused = array();
  $used = getReferencedImages();
  $avail = getImageFiles();
  foreach ($avail as $file){
    if (!in_array($file, $used)){
      $unused[] = $file;      
    } 
  }
  return $unused;
}

function deleteOrphanedImages(){
  $unused = getOrphanedImages();
  foreach ($unused as $file){
    if (unlink(QZ_IMGDIR . $file)){
      logMe("Gelöscht: " . QZ_IMGDIR . $file);
    } else {                               
      logMe("Fehler beim Löschen: " . QZ_IMGDIR . $file);
    } 
  }
}

function createBackups($fileToStore){
  if (QZ_NUMBACKUPS){
    $dir = dirname($fileToStore);
    $file = basename($fileToStore);
  }
  
  for ($i = QZ_NUMBACKUPS; $i > 0 ; $i--){ //TODO Schleife an PHP Code anpassen
    $newname = $dir . "/" . "bak-" . $i ."-" . $file;
    $oldname = $dir . "/" . "bak-" . ($i-1) ."-" . $file;
    rename ($oldname, $newname);
  }

  $newname = $dir . "/" . "bak-" . 0 ."-" . $file;
  $oldname = $fileToStore;
  rename ($oldname, $newname);
}



/*
 *  Aufrufbare Server-Funktionen
 */ 
function do_login(){
    $user =  $_REQUEST['user'];
    if (isVerified()){
      logMe($user . ": erfolgreich angemeldet.");
      echo "access for " . $user . " granted.";
    } else if (isGranted ()){    
      logMe($user . ": PIN-Verifikation fehlt.") ;
      echo "PIN for ". $user . " requested!";
    }   else {    
      logMe($user . ": erfolgloser Anmeldeversuch.") ;
      echo "access for ". $user . " rejected!";
    }    
}
$server['login'] = do_login;    
    
function do_getuser(){
    $user =  $_REQUEST['user'];
    $UserObj = getUser($user);
    echo "requested ";
    echo $user;
    echo ", pwd: ";
    echo $UserObj["passwd"];
    echo "\r\n";
    logMe ("User-daten abgefragt für: ". $user);
}
$server['getuser'] = do_getuser;

function do_adduser(){
    $user =  $_REQUEST['user'];
    $pwd =  $_REQUEST['passwd'];
    $hash =  password_hash($pwd, PASSWORD_DEFAULT);
    $email =  $_REQUEST['email'];
    logMe("registriere ". $user .", ". $email);
    if (registerUser($user,$hash,$email)){
      logMe ("registriere ". $user. ", ". $email);
      echo "OK: PIN-Mail geschickt an ". $email;
    } else {                                      
      logMe ("FEHLER bei: registriere ". $user. ", ". $email);
      echo "FEHLER: Registrierung fehlgeschlagen für ". $user;
    }
}
$server['adduser'] = do_adduser;

function do_verifyuser(){
    $user =  $_REQUEST['user'];
    $pwd =  $_REQUEST['passwd'];
    $pin =  $_REQUEST['pin'];
    $email =  $_REQUEST['email'];
    if (verifyUser($user,$pwd,$pin)){
      logMe ("PIN-Verifikation für ". $user. ", ". $email);
      echo "OK: PIN-Verfikation für ". $email;
    } else {                                      
      logMe ("FEHLER bei: PIN-Verfikation für ". $user. ", ". $email);
      echo "FEHLER: keine PIN-Verfikation für ". $email;
    }
}
$server['verifyuser'] = do_verifyuser;
    
function do_storequiz(){
    $dataToStore = $_REQUEST['store_me'];
    $fileToStore = $_REQUEST['filename'];
    
    if(!empty($dataToStore) )
    {
      $fileToStore = QZ_QUIZDIR. $_REQUEST['quiz'] . ".json";
      // write file
      if (isVerified()){
        createBackups($fileToStore);
        file_put_contents($fileToStore, json_encode (json_decode ($dataToStore),JSON_PRETTY_PRINT));
        deleteOrphanedImages();  
        logMe ("Spiel ".$fileToStore." gespeichert.");
      	echo "OK, quiz saved to ";
        echo $fileToStore  ;
      } else {   
        logMe ("Spiel ".$fileToStore." keine Speicherberechtigung.");
        echo "FEHLER: keine Berechtigung zum Speichern von ".$fileToStore;
      }
    } else {
      logMe ("FEHLER: storequiz ohne Daten");
      echo "FEHLER: keine Daten zum Speichern";
    }     
}
$server['storequiz'] = do_storequiz;
   
function do_getquiz(){
    $fileToStore = QZ_QUIZDIR . $_REQUEST['quiz'] . ".json";
    $content = file_get_contents ($fileToStore);
    if ($content){   
      logMe ("Spiel ".$fileToStore." geladen.");
      echo $content;
    } else {
      logMe ("Spiel ".$fileToStore." unbekannt.");
      echo '{"name":"neues Quiz", "email":"ah@in-howi.de", "questions": [{"question": "", "img": "", "desc":"", "url":"", "answers": ["","","",""]}]}';
    }    
}
$server['getquiz'] = do_getquiz;


function do_getimage(){
    $imageFile =  $_REQUEST['image'];
    logMe ("Lade Bild: ". $imageFile);
    sendImg($imageFile);
}
$server['getimage'] = do_getimage;

function do_uploadImage(){
    // upload and resize
    $quiz = $_REQUEST['quiz'];
    $tmpFile = $_FILES['file']['tmp_name'];
    
    $exif = exif_read_data($tmpFile);
    if ($exif){
      $orientation = $exif["Orientation"];
    }
    
    list ($width,$height,$type) = getimagesize($tmpFile);
    if ($width > $height){
      $x = floor(($width-$height)/2);
      $y = 0;
      $width = $height;
    } else {
      $x = 0;
      $y = floor(($height - $width)/2);
      $height = $width;
    }
    $resize = 300 / $height;
    
    $srcimg = imagecreatefromjpeg($tmpFile);
    $dstimg = imagecreatetruecolor (300,300);
    if ($srcimg && $dstimg && imagecopyresampled ($dstimg, $srcimg, 0, 0, $x, $y, 300, 300, $height, $width)){
      switch($orientation){
        case 1: // nothing
        break;

        case 2: // horizontal flip
        //$resizeObj->flipImage($path,1);
        logMe("cannot do horizontal flip on ". $tmpFile);
        break;

        case 3: // 180 rotate left
        $dstimg = imagerotate($dstimg, 180, 0); 
        logMe(" do 180 rotate left on ". $tmpFile);
        break;

        case 4: // vertical flip
        //$resizeObj->flipImage($path,2);  
        logMe("cannot do vertical flip on ". $tmpFile);
        break;

        case 5: // vertical flip + 90 rotate right
        //$resizeObj->flipImage($path, 2);
        //$resizeObj->rotateImage($path, -90);  
        logMe("cannot do vertical flip + 90 rotate right on ". $tmpFile);
        break;

        case 6: // 90 rotate right
        $dstimg = imagerotate($dstimg, -90, 0); 
        logMe(" do 90 rotate right on ". $tmpFile);
        break;

        case 7: // horizontal flip + 90 rotate right
        //$resizeObj->flipImage($path,1);    
        //$resizeObj->rotateImage($path, -90); 
        logMe("cannot do horizontal flip + 90 rotate right on ". $tmpFile);
        break;

        case 8:    // 90 rotate left
        $dstimg = imagerotate($dstimg, 90, 0); 
        logMe(" do 90 rotate left on ". $tmpFile);
        break;
      }
      $newName = uniqid($quiz . '_') . '.jpg';
      while (file_exists(QZ_IMGDIR . $newName)) {
         $newName = uniqid($quiz . '_') . '.jpg';
      }
      if (imagejpeg($dstimg, QZ_IMGDIR . $newName)){
        echo "OK " .$newName;
        logMe ("Bild-Upload ". $newName);
      } else {  
        logMe ("FEHLER beim Speichern unter " .QZ_IMGDIR .  $newName);
        echo "FEHLER beim Speichern unter " .QZ_IMGDIR . $newName;
      }
    }  else {
      logMe ("FEHLER beim Speichern von " . $newName);
      echo "FEHLER beim Speichern von " . $newName;
    }
}
$server['uploadImage'] = do_uploadImage;
    
function do_uploadLogo(){
    // upload and resize
    $quiz = $_REQUEST['quiz'];
    $tmpFile = $_FILES['file']['tmp_name'];
    
    $exif = exif_read_data($tmpFile);
    if ($exif){
      $orientation = $exif["Orientation"];
      logMe("Orientation of " . $tmpFile . " is " . $orientation);
    }
    
    list ($width,$height,$type) = getimagesize($tmpFile);
    if ($width > ($height * 6)){
      $x = floor(($width-($height * 6))/2);
      $y = 0;
      $width = $height * 6;
    } else {
      $x = 0;
      $y = floor((($height) - floor($width / 6))/2);
      $height = floor($width / 6);
    }
    $resize = 50 / $height;
    
    $srcimg = imagecreatefromjpeg($tmpFile);
    $dstimg = imagecreatetruecolor (300, 50);
    if ($srcimg && $dstimg && imagecopyresampled ($dstimg, $srcimg, 0, 0, $x, $y, 300, 50, $width, $height)){
      switch($orientation){
        case 1: // nothing
        break;

        case 2: // horizontal flip
        //$resizeObj->flipImage($path,1);
        logMe("cannot do horizontal flip on ". $tmpFile);
        break;

        case 3: // 180 rotate left
        $dstimg = imagerotate($dstimg, 180, 0); 
        logMe(" do 180 rotate left on ". $tmpFile);
        break;

        case 4: // vertical flip
        //$resizeObj->flipImage($path,2);  
        logMe("cannot do vertical flip on ". $tmpFile);
        break;

        case 5: // vertical flip + 90 rotate right
        //$resizeObj->flipImage($path, 2);
        //$resizeObj->rotateImage($path, -90);  
        logMe("cannot do vertical flip + 90 rotate right on ". $tmpFile);
        break;

        case 6: // 90 rotate right
        $dstimg = imagerotate($dstimg, -90, 0); 
        logMe(" do 90 rotate right on ". $tmpFile);
        break;

        case 7: // horizontal flip + 90 rotate right
        //$resizeObj->flipImage($path,1);    
        //$resizeObj->rotateImage($path, -90); 
        logMe("cannot do horizontal flip + 90 rotate right on ". $tmpFile);
        break;

        case 8:    // 90 rotate left
        $dstimg = imagerotate($dstimg, 90, 0); 
        logMe(" do 90 rotate left on ". $tmpFile);
        break;
      }
      $newName = $quiz . '_logo.jpg';
      if (imagejpeg($dstimg, QZ_IMGDIR . $newName)){
        echo "OK " .$newName;  
        logMe ("Logo-Upload ". $newName);
      } else {  
        logMe ("FEHLER beim Speichern unter " .QZ_IMGDIR .  $newName);
        echo "FEHLER beim Speichern unter " .QZ_IMGDIR . $newName;
      }
    }  else {
      logMe ("FEHLER beim Speichern von " . $newName);
      echo "FEHLER beim Speichern von " . $newName;
    }
}
$server['uploadLogo'] = do_uploadLogo;
       
function do_getimagetypes(){ 
    echo getAcceptedImageTypes();
    logMe("liefere zulässige ImageTypes");
}       
$server['getimagetypes'] = do_getimagetypes;
       
function do_backup(){
  if (isAdmin()){
    $zipfile = tempnam("/tmp", "QZZ-");
    $zip = new Zipper();
    $ret = $zip->open($zipfile, ZipArchive::CREATE |ZipArchive::OVERWRITE);
    $zip->addDir("..");
    $zip->close();
                
    header("Content-Type: " . "application/zip"); 
    header ('Content-Disposition: attachment; filename="quizbackup.zip"'); 
    readfile ($zipfile);
    logMe("Backup erzeugt");
  } else {
    logMe("unerlaubtes Backup angefragt");
    echo  "unerlaubtes Backup angefragt\r\n";
  }
}       
$server['backup'] = do_backup;

function do_test(){
  logMe ("aufgerufen: test");
  echo "TEST aufgerufen\r\n";
}       
$server['test'] = do_test;


/*
 *  Verteile Aufruf auf die implementierenden Funktionen
 */
$method      = $_REQUEST['method'];

if ($method && $server[$method]){
     $server[$method](); 
}  else {
  logMe("FEHLER: falscher Aufruf des Servers, method='".$method."'");
  "FEHLER: falscher Aufruf des Servers, method='".$method."'";
}

?>
