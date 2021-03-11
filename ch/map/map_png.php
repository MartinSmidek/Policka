<?php # (c) 2006-2009 Martin Smidek

// vykreslí a upraví PNG-obrázek
// GET['mapa']=id mapy v SESSION
// SESSION['mapy'][GET['mapa'] = array (
//   "template" => cesta_png_vzoru, "xy" => array ("x,y" => <oblast>, ...), scale => měřítko )
// <oblast> = array ( 'rgb' => 'r,g,b', 'text' => text_v_xy )

  require_once('../../ezer2/server/session.php');
  sess_start(true); // obsahuje volání session_start() ale USER nebude naplněno
  $test= empty($_GET['mapa']);
  if ( $test ) {
    // pokud není definovaná mapka jde o testování - použij zkušební mapku
    $mapa= 'test';
    $xy['100,100']= array ('rgb'=>'100,250,50','text'=>'ahoj'); 
    $_SESSION['mapy'][$mapa]['template']= 'img/okresy.png';
    $_SESSION['mapy'][$mapa]['xy']= $xy;
  }
  else {
    $mapa= $_GET['mapa'];
  }
  
  Header("Content-Type: image/png");
  // otevři mapu
  $template= $_SESSION['mapy'][$mapa]['template'];
  $img= ImageCreateFromPNG($template);
  
  // zobraz informace
  $textcolor= ImageColorAllocateAlpha($img, 41, 74, 148, 0);
  foreach ($_SESSION['mapy'][$mapa]['xy'] as $xy => $desc) {
    list($x,$y)= explode(',',$xy);
    // vyplnění barvou
    if ( $desc['rgb'] ) {
      list($r,$g,$b)= explode(',',$desc['rgb']);
      $color= ImageColorAllocate($img,$r,$g,$b);
      ImageFill($img,$x,$y,$color);
    }
    // zakreslení textu
    $text= $desc['text'];
    ImageString($img, 3, $x, $y, $text, $textcolor);
  }
  
  // trasování  
  $grey= ImageColorAllocate($img, 128, 128, 128);
  ImageString($img, 1, 10,  5, "$template $newWidth, $newHeight, $origWidth, $origHeight, ok=$ok", $grey);
  
  // resampling pokud je definováno 'scale'
  if ( $scale= $_SESSION['mapy'][$mapa]['scale'] ) {
    list($origWidth, $origHeight)= getimagesize($template);
    $newWidth= round($scale * $origWidth);
    $newHeight= round($scale * $origHeight);
    $new= ImageCreateTrueColor($newWidth, $newHeight); 
    if ( $bgcolor= $_SESSION['mapy'][$mapa]['bgcolor'] ) {
      // alokuj zadanou barvu
      list($r,$g,$b)= explode(',',$bgcolor);
      $color= ImageColorAllocate($new,$r,$g,$b);
    }
    else {
      // bude pozadí bílé 
      $color= ImageColorAllocate($new,255,255,255);
      //ImageColorTransparent($new,$color);
    }
    ImageFill($new,1,1,$color);
    ImageCopyResampled($new, $img, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    if ( !$test ) ImagePNG($new);
  }
  else 
    ImagePNG($img);
?>
