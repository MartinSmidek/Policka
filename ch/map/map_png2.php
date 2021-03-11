<?php # (c) 2006-2009 Martin Smidek
# vykreslí a upraví PNG-obrázek, parametry jsou předávány pomocí GET takto:
# mapa=cesta            -- mapa např. img/okresy.png
# bg=r,g,b              -- případná varba pozadí
# scal=n                -- měřítko 1 je 1:1 0,5 je poloviční
# parm=par;par;... kde  -- x,y jsou souřadnice r,g,b je barva a t je text zobrazený v x,y
#   par=x,y,r,g,b,t
  Header("Content-Type: image/png");
  $mapa= $_GET['mapa'];
  // otevři mapu
  $img= ImageCreateFromPNG($mapa);
  $textcolor= ImageColorAllocateAlpha($img, 41, 74, 148, 0);
  // rozeber parm
  $n= 0;
  foreach(explode(';',$_GET['parm']) as $par) {
    $n++;
    list($x,$y,$r,$g,$b,$t)= explode(',',$par);
    // vyplnění barvou
    $color= ImageColorAllocate($img,$r,$g,$b);
    ImageFill($img,$x,$y,$color);
    // zakreslení textu
    ImageString($img, 3, $x, $y, $t, $textcolor);
//     if ( $n>6 ) break;
  }
  // trasování
//   $grey= ImageColorAllocate($img, 128, 128, 128);
//   ImageString($img, 1, 10,  5, "$mapa ($x,$y,$r,$g,$b,$t)", $grey);
  // resampling pokud je definováno 'scale'
  if ( $scale= $_GET['scal'] ) {
    list($origWidth, $origHeight)= getimagesize($mapa);
    $newWidth= round($scale * $origWidth);
    $newHeight= round($scale * $origHeight);
    $new= ImageCreateTrueColor($newWidth, $newHeight); 
    if ( $bgcolor= $_GET['bg'] ) {
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
    ImagePNG($new);
  }
  else 
    ImagePNG($img);
?>
