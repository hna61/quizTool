<?php
/*
 * Quiz - Server
 *  
 *  Author(s): Andreas Heidemann (2017-), 
 *  License: MIT  
 */
 
ini_set('display_errors', 1);

$VERSION="0.0.3";
$QUIZDIR="../data/quizes/";
$IMGDIR="../data/img/";

logMe ($VERSION);

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

function registerUser($username, $passwd, $email){
	$pin = "4711"; /* TODO: richtiger Zufallswert */
	$newUser = (object) ['passwd' => $passwd, 'email'=> $email];
	setUser($username,$newUser);
	mailPin($newUser['email'], $newUser['pin']);
}

function mailPin($email, $pin){
	$subject = "Zugang zum Quiz-Server";
	$body = "Hallo \r\n\r\n"
		. "du musst den Zugang zum Quiz-Server noch mit einer"
		. " PIN bestätigen.\r\n\r\n"
		. "Die PIN ist    "
		. $pin . ".\r\n\r\n"
		. "Bitte nutze den folgenden Link dazu:\r\n"
		. "* http://p.in-howi.de/kobel/quiztest/index.php?pin="
		. $pin
		. "\r\n\r\n-Dein Server-";
	mailOut($email, $subject, $body);
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
	

function logMe($logtext){
  $logfile = "../data/usage.log";
  file_put_contents($logfile, date("Y-m-d H:i:s") 
                              .", ". $_SERVER['REMOTE_ADDR']
                              . " : ". $logtext . "\r\n"
                    , FILE_APPEND );  
}

function isGranted(){  
  $user =  $_REQUEST['user'];
  $pwd =   $_REQUEST['passwd']; 
  $UserObj = getUser($user);
  return ($UserObj && password_verify($pwd,$UserObj["passwd"]));
}

$dataToStore = $_REQUEST['store_me'];
$fileToStore = $_REQUEST['filename'];
$method      = $_REQUEST['method'];
$grant       = $_REQUEST['grant'];

if (!empty($method)){
  if ($method == 'login'){
    $user =  $_REQUEST['user'];
    if (isGranted()){
      logMe($user . ": erfolgreich angemeldet.");
      echo "access for " . $user . " granted.";
    } else {    
      logMe($user . ": erfolgloser Anmeldeversuch.") ;
      echo "access for ". $user . " rejected!";
    }
    
  } else if ($method == 'gethash'){
    $user =  $_REQUEST['user'];
    $pwd  =  $_REQUEST['passwd']; 
    $hash =  password_hash($pwd, PASSWORD_DEFAULT);
    logMe($user . ", " . $hash) ;
    echo "passwordhash for ". $user." generated and ";
    if (password_verify($pwd, $hash)){
      echo "verified";
    } else {
      echo "failed";
    }
    
    
  } else if ($method == 'storequiz'){
    if(!empty($dataToStore) )
    {
      $fileToStore = $QUIZDIR. $_REQUEST['quiz'] . ".json";
      // write file
      if (isGranted()){
        logMe ("Spiel ".$fileToStore." gespeichert.");
        file_put_contents($fileToStore, json_encode (json_decode ($dataToStore),JSON_PRETTY_PRINT));
      	echo "OK, quiz saved to ";
        echo $fileToStore  ;
      } else {   
        logMe ("Spiel ".$fileToStore." keine Speicherberechtigung.");
        echo "FEHLER: keine Berechtigung zum Speichern von ".$fileToStore;
      }
    } else {
      echo "FEHLER: keine Daten zum Speichern";
    }  
    
  } else if ($method == 'getquiz'){
    $fileToStore = $QUIZDIR . $_REQUEST['quiz'] . ".json";
    $content = file_get_contents ($fileToStore);
    if ($content){   
      logMe ("Spiel ".$fileToStore." geladen.");
      echo $content;
    } else {
      logMe ("Spiel ".$fileToStore." unbekannt.");
      echo '{"name":"neues Quiz", "email":"ah@in-howi.de", "questions": [{"question": "", "img": "", "desc":"", "url":"", "answers": ["","","",""]}]}';
    }
    
  } else if ($method == 'getimage'){
    $imageFile =  $_REQUEST['image'];
    logMe ("Lade Bild: ". $imageFile);
    sendImg($imageFile);
    
  } else if ($method == 'uploadImage'){
    // upload and resize
    $quiz = $_REQUEST['quiz'];
    $tmpFile = $_FILES['file']['tmp_name'];
    
    $exif = exif_read_data($tmpFile);
    if ($exif){
      $orientation = $exif["Orientation"];
      logMe("Orientation of " . $tmpFile . " is " . $orientation);
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
      while (file_exists($IMGDIR . $newName)) {
         $newName = uniqid($quiz . '_') . '.jpg';
      }
      if (imagejpeg($dstimg, $IMGDIR . $newName)){
        echo "OK " .$newName;
      } else {  
        logMe ("FEHLER beim Speichern unter " .$IMGDIR .  $newName);
        echo "FEHLER beim Speichern unter " .$IMGDIR . $newName;
      }
    }  else {
      logMe ("FEHLER beim Speichern von " . $newName);
      echo "FEHLER beim Speichern von " . $newName;
    }
    
  } else if ($method == 'getuser'){
    $user =  $_REQUEST['user'];
    $UserObj = getUser($user);
    echo "requested ";
    echo $user;
    echo ", pwd: ";
    echo $UserObj["passwd"];
    echo "\r\n";
    
  } else if ($method == 'adduser'){
    $user =  $_REQUEST['user'];
    $pwd =  $_REQUEST['passwd'];
    $hash =  password_hash($pwd, PASSWORD_DEFAULT);
    $email =  $_REQUEST['email'];
    addUser($user,$hash,$email);
    echo "fertig\r\n";
    
  } else if ($method == 'picinfo'){ 
    $filename =  $_REQUEST['filename'];     
    logMe("picinfo for: " . filename);
    list ($width,$height,$type) = getimagesize("../img/".$filename);
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
    
    
    echo "Änderung für ";
    echo $filename;
    echo ": x=" . $x .", y=" . $y . ", height=" . $height . ", width=" . $width . ", faktor=" . $resize;
    echo "\r\n";
    
  }else if ($method == 'testmail'){ 
    $to =  $_REQUEST['to'];     
    $subject =  $_REQUEST['subject']; 
    $body = "Dies ist eine einfache Testmail.\r\nBitte ignorieren";
    $headers = "From: Andreas Heidemann <ah@in-howi.de>";

    if (mail ($to, $subject, $body, $headers)) {
      echo "Mail verschickt an " . $to;
    } else {
      echo "Mail nicht verschickt an " . $to;
    }    
  }
} 

?>
