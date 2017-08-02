<?php
/*
 * Quiz - Server
 *  
 *  Author(s): Andreas Heidemann (2017-), 
 *  License: MIT  
 */
 
ini_set('display_errors', 1);

function getUser($user)
{
	$userFile = '../data/users.json';
	$userListJson = file_get_contents ($userFile);
	$userList = json_decode($userListJson, true);
	return $userList[$user];
}

function logMe($logtext){
  $logfile = "../data/usage.log";
  file_put_contents($logfile, date("Y-m-d H:i:s") .", ". $_SERVER['REMOTE_ADDR']. " : ". $logtext . "\r\n", FILE_APPEND );  
}

function isGranted(){  
  $user =  $_REQUEST['user'];
  $pwd =   $_REQUEST['passwd']; 
  $UserObj = getUser($user);
  return ($UserObj && $pwd == $UserObj["passwd"]);
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
      echo "access for ";
      echo $user; 
      echo " granted.";
    } else {    
      logMe($user . ": erfolgloser Anmeldeversuch.") ;
        echo "access for ";
        echo  $user;
        echo " rejected!";
    }
    
  } else if ($method == 'storequiz'){
    if(!empty($dataToStore) )
    {
      $fileToStore = "../quizes/" . $_REQUEST['quiz'] . ".json";
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
    $fileToStore = "../quizes/" . $_REQUEST['quiz'] . ".json";
    $content = file_get_contents ($fileToStore);
    if ($content){   
      logMe ("Spiel ".$fileToStore." geladen.");
      echo $content;
    } else {
      logMe ("Spiel ".$fileToStore." unbekannt.");
      echo '{"name":"neues Quiz", "email":"ah@in-howi.de", "questions": [{"question": "", "img": "", "desc":"", "url":"", "answers": ["","","",""]}]}';
    }
    
  } else if ($method == 'uploadImage'){
    // upload and resize
    $newName = $_REQUEST['newName'];
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
        logMe("cannot do horizontal flip on ". $newName);
        break;

        case 3: // 180 rotate left
        $dstimg = imagerotate($dstimg, 180, 0); 
        logMe(" do 180 rotate left on ". $newName);
        break;

        case 4: // vertical flip
        //$resizeObj->flipImage($path,2);  
        logMe("cannot do vertical flip on ". $newName);
        break;

        case 5: // vertical flip + 90 rotate right
        //$resizeObj->flipImage($path, 2);
        //$resizeObj->rotateImage($path, -90);  
        logMe("cannot do vertical flip + 90 rotate right on ". $newName);
        break;

        case 6: // 90 rotate right
        $dstimg = imagerotate($dstimg, -90, 0); 
        logMe(" do 90 rotate right on ". $newName);
        break;

        case 7: // horizontal flip + 90 rotate right
        //$resizeObj->flipImage($path,1);    
        //$resizeObj->rotateImage($path, -90); 
        logMe("cannot do horizontal flip + 90 rotate right on ". $newName);
        break;

        case 8:    // 90 rotate left
        $dstimg = imagerotate($dstimg, 90, 0); 
        logMe(" do 90 rotate left on ". $newName);
        break;
      }
      imagejpeg($dstimg, "../img/".$newName);
      echo "OK";
    }  else {
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
    
  }
} 
