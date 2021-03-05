<?php # (c) 2011 Martin Smidek <martin@smidek.eu>
/** =======================================================================================> IMPORTY */
# importní filtry pro formáty
# Komerční banka:       GPC, KPC
# Volksbanka:           GEM (ACE), KPC
if (!function_exists('fnmatch')) {
  function fnmatch($pattern, $string) {
    return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|')
      , array('*' => '.*', '?' => '.?')) . '$/i', $string);
  }
}
# -------------------------------------------------------------------------------------------------- bank_pub
# rozklad textového zápisu účtu na složky
# $padding -- true přidá levostranné nuly, false odstraní nuly případně i předčíslí
function bank_pub($pub,&$p,&$u,&$b,$padding=true) {
  list($pu,$b)= explode('/',$pub);
  list($p,$u)= explode('-',$pu);
  if ( $padding ) {
    if ( $u ) {
      $u= str_pad($u,10,'0',STR_PAD_LEFT);
      $p= str_pad($p,6,'0',STR_PAD_LEFT);
    }
    else {
      $u= str_pad($p,10,'0',STR_PAD_LEFT);
      $p= '000000';
    }
  }
  else {
    if ( !$u ) {
      $u= $p;
      $p= '';
    }
    $u= ltrim($u,'0');
    $p= ltrim($p,'0');
  }
}
# -------------------------------------------------------------------------------------------------- bank_load_ucty
# $bank_nase_banky = array ('0100',...)
# $bank_nase_ucty  = array ('0100'=> array('000000-1234567890'=>'X'),...)
# $bank_nase_nucty = array ('X'=> n) -- n je data účtu v _cis.druh=='k_ucty'
function bank_load_ucty () {
  global $bank_nase_banky, $bank_nase_ucty, $bank_nase_nucty;
  if ( !isset($bank_nase_banky) ) {
    $bank_nase_ucty= array();
    $bank_nase_banky= array();
    $qry= "SELECT * FROM _cis WHERE druh='k_ucet' AND ikona!='' ";
    $res= pdo_qry($qry);
    while ( $res && $c= pdo_fetch_object($res) ) {
      bank_pub($c->ikona,$p,$u,$b);
      if ( !in_array($b,$bank_nase_banky) )
        $bank_nase_banky[]= $b;
      $bank_nase_ucty[$b]["$p-$u"]= $c->zkratka;
      $bank_nase_nucty[$c->zkratka]= $c->data;
    }
  }
}
# -------------------------------------------------------------------------------------------------- bank_kpc
# přečte soubor formátu KPC
// function bank_kpc($patt='*') {
//                                                 display("banka_kpc($patt)");
//   global $path_banka, $ezer_root;
//   $result= '';
//   $path= $path_banka['kpc'];
//   if ( !$path ) return fce_error("Soubor $ezer_root.php neobsahuje cestu pro příkazy 'kpc'");
//   $handle= @opendir($path);
//   while ( $handle && false !== ($file= readdir($handle))) {
//     $info= pathinfo($path.$file);
//     $typ= $info['extension'];
//     $soubor= $info['filename'];
//     $qry1= "SELECT * FROM prikaz WHERE soubor='$soubor' AND year(datum)=year(now())";
//     $res1= pdo_qry($qry1);
//     $rows1= pdo_num_rows($res1);
//     if ( !$rows1 && strtoupper($typ)=='KPC' && fnmatch($patt,$soubor) ) {
//       $result.= " $soubor.$typ";
//       $f= fopen("$path$soubor.$typ", "r");
//       if ( $f ) {
//         $prikaz= array();
//         // čtení záhlaví
//         $buf= fgets($f,4096);
//                                                   display("$soubor,$typ,$buf");
//         $s= substr($buf,4,6);
//         $prikaz['soubor']= $soubor;
//         $prikaz['datum']= '20'.substr($s,4,2).'-'.substr($s,2,2).'-'.substr($s,0,2);
//         $prikaz['org']= iconv("CP1250","UTF-8",trim(substr($buf,10,20)));
//         $prikaz['item']= array();
//         $n= 0;
//         $splatnost= '';
//         while ( !feof($f) ) {
//           $n++;
//           $buf= fgets($f,4096);
//           $buf= str_replace("\r\n","",$buf);
//           $skup= explode(' ',$buf);
//           switch ( substr($buf,0,2) ) {
//           case '1 ':                 // hlavička účetního souboru
//             $prikaz['poradi']= substr($skup[2],0,3);
//             break;
//           case '2 ':                 // hlavička skupiny
//             $s= $skup[3];
//             if ( $splatnost )
//               return fce_error("banka_import: příkazový soubor $soubor je příliš složitý");
//             $splatnost= '20'.substr($s,4,2).'-'.substr($s,2,2).'-'.substr($s,0,2);
//             $ksym= $skup[4];
//             break;
//           case '3 ':                 // konec skupiny
//             break;
//           case '5 ':                 // konec účetního souboru
//             break 2;
//           default:                   // položka
//             $item= array();
//             $item['ucet']= $ucet= $skup[0];
//             $ucet0= explode('-',$ucet);
//             $item['ucet0']= count($ucet0)==2
//               ? str_pad($ucet0[0],6,'0',STR_PAD_LEFT).'-'.str_pad($ucet0[1],10,'0',STR_PAD_LEFT)
//               : '000000-'.str_pad($ucet0[0],10,'0',STR_PAD_LEFT);
//             $item['banka']= substr($skup[3],0,4);
//             $item['castka']= substr($skup[1],0,-2).'.'.substr($skup[1],-2);
//             $item['vsym']= $skup[2];
//             $item['ksym']= substr($skup[3],4,4);
//             $item['ssym']= $skup[4];
//             $item['splatnost']= $splatnost;
//             $prikaz['item'][]= $item;
//             break;
//           }
//         }
//                                                   debug($prikaz,$soubor);
//         // vložení do tabulek
//         $qry= bank_insert_qry('prikaz','org,datum,soubor,poradi',$prikaz);
//                                                         display("qry:$qry");
//         $res= pdo_qry($qry);
//         if ( !$res ) fce_error("banka_import: přidání příkazu $soubor selhalo");
//         $id_prikaz= pdo_insert_id();
//         foreach ( $prikaz['item'] as $item ) {
//           $item['id_prikaz']= $id_prikaz;
//           $qry= bank_insert_qry('polozka','id_prikaz,ucet,ucet0,banka,castka,vsym,ksym,ssym,splatnost',$item);
//                                         display("qry:$qry");
//           $res= pdo_qry($qry);
//           if ( !$res ) fce_error("banka_import: přidání položky pro {$item['ucet']} příkazu $soubor selhalo");
//         }
//       }
//       else return fce_error("banka_import: příkazový soubor $soubor nelze otevřít");
//     }
//   }
//   return $result ? $result : '---';
// }
# -------------------------------------------------------------------------------------------------- bank_import0
# ASK - Zjistit výpisy
# porovná složku $path s tabulkou vypis a vrátí seznam bankovních převodů typu $type (ACE|GEM|GPC)
# vyhovujících masce $patt
# do pole $bank_soubory=>banka vloží pole jmen dosud neimportovaných souborů
function bank_import0($patt='*') {
//                                                 display("banka_import0($patt)");
  global $path_banka, $bank_soubory, $y, $vypisy, $bank_nase_ucty, $bank_nase_banky, $ezer_root;
  $banka_typ= array ( '6800' => 'GEM', '0100' => 'GPC', '0300' => 'GPC|GP_' );
  bank_load_ucty();                             // zajisti naplnění $bank_nase_ucty,$bank_nase_banky
  $result= '';
  $err= 0;
  $bank_soubory= array();
  if (!function_exists('fnmatch')) {
    function fnmatch($pattern, $string) {
      return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|')
        , array('*' => '.*', '?' => '.?')) . '$/i', $string);
    }
  }
//                                                 debug($bank_nase_ucty,'$bank_nase_ucty');
  foreach ($bank_nase_banky as $banka) {
    $banka_kody= "";
    foreach ($bank_nase_ucty[$banka] as $kod) {
      $banka_kody.= $kod;
    }
    $bank_soubory[$banka]= array();
    $path= $path_banka[$banka];
    if ( !$path ) {
      fce_error("Soubor $ezer_root.php neobsahuje cestu pro výpisy banky '$banka'");
      continue;
    }
    $handle= @opendir($path);
    while ($handle && false !== ($file= readdir($handle))) {
      $info= pathinfo($path.$file);
      $typ= strtoupper($info['extension']);
      $soubor= $info['filename'];
//                                                 display("-- $path $soubor.$typ");
      if ( in_array($typ,explode('|',$banka_typ[$banka])) && fnmatch($patt,$soubor) ) {
        if ( $banka=='0300' ) {
          if ( $typ=='GPC' ) {
            // pokud obsahuje aspoň 1 výpis z účtu, tak
            // přejmenovat                // 0     1    2         3  4
            $part= explode('_',$soubor);  //'bb'IČ_účet_yyyymmdd_'d'_n
            $ucet= "000000-".str_pad($part[1],10,'0',STR_PAD_LEFT);
            $date= substr($part[2],0,8);
            $year= substr($date,0,4);
                                                 debug($part,"CSOB $ucet|$date|$year -- $path $soubor.$typ");
          }
          else {
            fce_error("vypis z banky 0300 ma koncovku '$typ'");
          }
          $qry1= "SELECT * FROM vypis WHERE soubor='$soubor' ";
          $res1= pdo_qry($qry1);
          if ( $res1 ) {
//                                                   display("$soubor:".pdo_num_rows($res1));
            $rows1= pdo_num_rows($res1);
            $row1= pdo_fetch_assoc($res1);
            $v_ident= $row1['ident'];
            if ( !$rows1 ) {
              $result.= " $banka:$soubor.$typ";
              $bank_soubory[$banka][]= $soubor;
            }
          }
        }
        else {
          $year= $banka=='0100' ? substr($soubor,0,4) : '20'.substr($soubor,1,2);
          $qry1= "SELECT * FROM vypis
                  WHERE soubor='$soubor' AND LOCATE(ucet,'$banka_kody') AND year(datum)=$year";
          $res1= pdo_qry($qry1);
          if ( $res1 ) {
//                                                   display("$soubor:".pdo_num_rows($res1));
            $rows1= pdo_num_rows($res1);
            $row1= pdo_fetch_assoc($res1);
            $v_ident= $row1['ident'];
            if ( !$rows1 ) {
              $result.= " $banka:$soubor.$typ";
              $bank_soubory[$banka][]= $soubor;
            }
          }
        }
      }
    }
    if ( $handle ) closedir($handle);
    else fce_error("Cesta '$path' pro výpisy banky '$banka' v $ezer_root.php není platná");
  }
//                                                         debug($bank_soubory);
  return (object)array('html'=>$result ? $result : '---','err'=>$err);
}
# -------------------------------------------------------------------------------------------------- bank_import1
# zjistí dostupné výpisy a vybere nejstarší dosud nevložený z banky $banka
# pokud je $to_move==1 tak naimportované soubory přesune do $path/yyyy
function bank_import1($bank,$to_move=0) {
                                                display("banka_import1($banka,$to_move)");
  global $bank_soubory;
  $soubor= '';
  bank_import0();
  if ( count($bank_soubory[$bank]) ) {
    sort($bank_soubory[$bank]);
    $soubor= $bank_soubory[$bank][0];
    $one= bank_import($soubor,0,$to_move);
  }
//                                                 debug($bank_soubory,$soubor);
  return $soubor;
}
# -------------------------------------------------------------------------------------------------- bank_imported
# zjistí zda nejstarší výpis v importní složce banky $banka je už vložený
# POZOR 6800 neliší názvy mezi roky
function bank_imported($bank) {
                                                display("banka_imported($banka)");
  global $bank_soubory;
  $imported= '';
  bank_import0();
  if ( count($bank_soubory[$bank]) ) {
    sort($bank_soubory[$bank]);
    $soubor= $bank_soubory[$bank][0];
    $year= $bank='0100' || $bank='0300' ? substr($soubor,0,4) : date('Y');
    $qry1= "SELECT * FROM vypis WHERE soubor='$soubor' AND year(datum)=$year";
    $res1= pdo_qry($qry1);
    if ( $res1 && pdo_num_rows($res1) ) $imported= $soubor;
  }
//                                                 debug($bank_soubory,$soubor);
  return $imported;
}
# -------------------------------------------------------------------------------------------------- bank_import_remove
# provede smazání vypis a prevod vzniklých z importu daného souboru z daného data
function bank_import_remove($soubor,$datum) {
//                                                 display("bank_import_remove($soubor,$datum)");
  global $path_banka, $y, $vypisy;
  // projdi výpisy
  $p= 0;
  $sql_datum= sql_date($datum,1);
  $qry1= "SELECT * FROM vypis WHERE soubor='$soubor' AND datum='$sql_datum' GROUP BY ident";
  $res1= pdo_qry($qry1);
  while ( $res1 && $row1= pdo_fetch_assoc($res1) ) {
    // odstraň převody
    $v_ident= $row1['ident'];
    $qry2= "DELETE FROM prevod WHERE left(prevod.ident,7)='$v_ident' ";
    $res2= pdo_qry($qry2);
    $p+= pdo_affected_rows();
  }
  // odstraň výpisy
  $qry3= "DELETE FROM vypis WHERE soubor='$soubor' AND datum='$sql_datum'";
  $res3= pdo_qry($qry3);
  $v= pdo_affected_rows();
  // redakce zprávy
  $text.= "$soubor z $datum: odstraněno $v výpisů a $p převodů";
  return $text;
}
# -------------------------------------------------------------------------------------------------- bank_import
# ASK - Přidej výpisy
# porovná složku $path s tabulkou vypis a provede import bankovních převodů typu $type (ACE|GEM|GPC)
# vyhovujících masce $patt
# pokud je $reimport==1 provede smazání položek v vypis a prevod a potom provede import
# pokud je $to_move==1 tak naimportované soubory přesune do $path/yyyy
# change= 'vypisy'|'prevody' určuje (pokud je reimport) zda se mení jen výpis nebo i jeho převody
function bank_import($patt='*',$reimport=0,$to_move=0,$change='vypisy') {
                                                display("banka_import($patt,$reimport,$to_move,$change)");
  global $path_banka, $y, $vypisy, $bank_nase_ucty, $bank_nase_banky, $ezer_root;
  $varsyms= map_cis('varsym','zkratka');
  $kontrola= $change=='missing';
  $msg= '';
  $n_vypisu= 0;
  $ids_vypisu= '';
  if (!function_exists('fnmatch')) {
    function fnmatch($pattern, $string) {
      return @preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|')
        , array('*' => '.*', '?' => '.?')) . '$/i', $string);
    }
  }
  // projdeme všechny naše účty
  $banka_typ= array ( '6800' => 'GEM', '0100' => 'GPC', '0300' => 'GPC' );
  bank_load_ucty();                             // zajisti naplnění $bank_nase_ucty,$bank_nase_banky
//                                         debug($bank_nase_banky,'$bank_nase_banky');
//                                         debug($bank_nase_ucty,'$bank_nase_ucty');
//                                         debug($path_banka,'$path_banka');
  foreach ($bank_nase_banky as $banka) {
    $banka_kody= "";
    foreach ($bank_nase_ucty[$banka] as $kod) {
      $banka_kody.= $kod;
    }
    $path= $path_banka[$banka];
    if ( !$path ) {
      fce_error("Soubor $ezer_root.php neobsahuje cestu pro výpisy banky '$banka'");
      continue;
    }
    $handle= @opendir($path);
    while ($handle && false !== ($file= readdir($handle))) {
      $info= pathinfo($path.$file);
      $typ= strtoupper($info['extension']);
      $soubor= $info['filename'];
      $vypisy= array();
      if ( $typ==$banka_typ[$banka] && fnmatch($patt,$soubor) ) {
//                                         display("$soubor: reimport=$reimport, fnmatch($patt,$soubor)=".fnmatch($patt,$soubor));
        $year= $banka=='0100' || $banka=='0300' ? substr($soubor,0,4) : date('Y');
        $wh= $banka=='0300'
           ? "soubor='$soubor'"
           : "soubor='$soubor' AND LOCATE(ucet,'$banka_kody') AND year(datum)=$year";
        $qry1= "SELECT * FROM vypis WHERE $wh";
//                   WHERE soubor='$soubor' AND LOCATE(ucet,'$banka_kody') AND year(datum)=$year";
        $res1= pdo_qry($qry1);
        $rows1= $res1 ? pdo_num_rows($res1) : 0;
        if ( $rows1==0 || $reimport ) {
//                                         display("$soubor/$rows1: prošlo testem");
          $row1= pdo_fetch_assoc($res1);
          $v_ident= $row1['ident'];
          if ( $rows1 && !$kontrola ) {
            // pokud je soubor v tabulkách vypis a prevod, vymažeme jej
            if ( $reimport==0 || $change=='prevody' ) {
              // ale převody jen pokud se to chce
              $qry3= "DELETE FROM prevod USING prevod,vypis WHERE soubor='$soubor' AND left(prevod.ident,7)=vypis.ident ";
              $res3= pdo_qry($qry3);
            }
            $qry2= "DELETE FROM vypis WHERE soubor='$soubor' ";
            $res2= pdo_qry($qry2);
          }
          // provedeme vlastní import do polí vypisy, prevody
          switch ( $typ ) {
          case 'ACE':                                   // 6800 old
            $buf= file_get_contents($path.$file);
            bank_ace($buf,$soubor,$yyyy);
            break;
          case 'GEM':                                   // 6800
            $buf= file_get_contents($path.$file);
            bank_gem($path.$file,$soubor,$yyyy);
            break;
          case 'GPC':                                   // 0100, 0300
            if ( $banka=='0100' ) {
              bank_gpc_kb($path.$file,$soubor,$yyyy);
                                                                         display("bank_gpc_kb($path.$file,$soubor,$yyyy)");
//                                                                       debug($vypisy,"0100");
            }
            else if ( $banka=='0300' ) {
              bank_gpc_csob($path.$file,$soubor,$yyyy);
                                                                         display("bank_gpc_csob($path.$file,$soubor,$yyyy)");
//                                                                       debug($vypisy,"0300");
            }
            break;
          }
          // provedeme záměny čísel účtů symboly a vyrobíme ident
          foreach ( $vypisy as $v => $vypis ) {
            $ucet= $bank_nase_ucty[$banka][$vypis['ucet']];
            // pokud se má účet ignorovat, jdi na další
            $ignorovat= !isset($bank_nase_ucty[$banka][$vypis['ucet']]);
            if ( $ignorovat ) {
                                                display("vypis $v z uctu {$vypis['ucet']} byl ignorovan (1)");
              continue;
            }
//             if ( !$ucet ) return fce_error("Výpis $soubor: účet '{$vypis['ucet']}' není zapsán v číselníku ".
//               "vlastních účtů banky $banka");
            if ( $typ=='GEM' ) {
              // pro formát GEM provedeme kontrolu konzistence jména souboru a obsahu
              if ( $soubor[0]!=$ucet )
                return fce_error("soubor $soubor obsahuje výpis účtu {$vypis['ucet']} - to je špatně vytvořené jméno");
              if ( substr($soubor,3,3)!=$vypis['vypis'] )
                return fce_error("soubor $soubor obsahuje výpis číslo {$vypis['vypis']} - to je špatně vytvořené jméno");
            }
            $vypisy[$v]['ucet']= $ucet;
            $vypisy[$v]['prijmu']= 0;
            $v_ident= $ucet.substr($vypis['datum'],2,2)."_".$vypis['vypis'];
            $vypisy[$v]['ident']= $v_ident;
            if ( $vypis['prevody'] ) foreach ( $vypis['prevody'] as $p => $prevod ) {
              $metoda= '';
              if ( $prevod['typ']>=5 ) {
                $vypisy[$v]['prijmu']++;
              }
              $vypisy[$v]['prevody'][$p]['metoda']= '';
              $vypisy[$v]['prevody'][$p]['ucet']= $ucet;
              $vypisy[$v]['prevody'][$p]['ident']= $v_ident.$prevod['ident'];
              // přesuny mezi vlastními účty
              if ( $bank_nase_ucty[$prevod['banka']][$prevod['protiucet']] ) {
                $vypisy[$v]['prevody'][$p]['kat']= "#u";
                $vypisy[$v]['prevody'][$p]['metoda'].= ', vlastní účet';
              }
              // příjem s ksym=0998 je dar - soupis složenek
              else if ( $prevod['typ']==5 && $prevod['ksym']=='0998' ) {
                $vypisy[$v]['prevody'][$p]['typ']= 8;
                $vypisy[$v]['prevody'][$p]['kat']= "+d";
                $vypisy[$v]['prevody'][$p]['metoda'].= ' je třeba rozepsat z poukázek';
              }
              // může být příjem darem?
              else if ( $prevod['typ']==5 ) {
//                                                 display("{$vypisy[$v]['prevody'][$p]['ident']} ... ");
                $clen= 0;
                $vsym= ltrim($prevod['vsym'],'0');
                if ( !isset($varsyms[$vsym]) && $prevod['typ']==5 ) {
                  // neznámý variabilní symbol není darem
                  $vypisy[$v]['prevody'][$p]['typ']= 6;
                  $vypisy[$v]['prevody'][$p]['metoda'].= 'neurčen, varsym je mimo číselník';
                }
                else {
                  if ( !$clen && $prevod['typ']==5 && $prevod['protiucet']!='000000-0000000000' ) {
                    // pokud je účet nenulový
                    // zhoduje se číslo účtu s nějakým uvedeným v kontaktu?
                    $clen= bank_ucet2clen($prevod['protiucet']);
                    if ( $clen) {
                      $vypisy[$v]['prevody'][$p]['metoda'].= ' podle účtu zapsaného u&nbsp;kontaktu';
//                                                               display("... U $clen");
                    }
                    else {
                      // přišel z něj někdy dar, který jsme určili?
                      $cond= "protiucet='{$prevod['protiucet']}' AND banka='{$prevod['banka']}' AND clen!=0 ";
                      $qry4= "SELECT clen FROM prevod WHERE $cond ORDER BY splatnost DESC LIMIT 1";
                      $res4= pdo_qry($qry4);
                      $rows4= pdo_num_rows($res4);
                      if ( $rows4 ) {
                        $row4= pdo_fetch_assoc($res4);
                        $clen= $row4['clen'];
                      }
                      if ( $clen) {
                        $vypisy[$v]['prevody'][$p]['metoda'].= ' podle obdobného převodu dříve';
//                                                                 display("... U $clen");
                      }
                    }
                  }
                  if ( !$clen ) {
                    // poznáme člena z POPISu účtu (rozkladem na příjmení a jméno a s požadavkem jednznačnosti
                    $clen= bank_popis2clen($prevod['popis'],$metoda);
                    if ( $clen) {
                      $vypisy[$v]['prevody'][$p]['metoda'].= ' podle jednoznačnosti jména';
//                                                                 display("... N $clen");
                    }
                  }
                  if ( $clen ) {
                    $vypisy[$v]['prevody'][$p]['clen']= $clen;
                    $vypisy[$v]['prevody'][$p]['typ']= 7;
                    $vypisy[$v]['prevody'][$p]['kat']= "+d";
                  }
                }
              }
              // zjistíme kat
//             $vypisy[$v]['prevody'][$p]['kat']= "+-#";
            }
          }
//                                         debug($vypisy,"vypisy $soubor");
          // zkontrolujeme obrat
          foreach ( $vypisy as $vypis ) {
            // pokud se má účet ignorovat, jdi na další
            $ignorovat= !isset($bank_nase_ucty[$banka][$vypis['ucetcislo']]);
            if ( $ignorovat ) {
//                                                 display("vypis $v z uctu {$vypis['ucetcislo']} byl ignorovan (2)");
              continue;
            }
            $obrat1= $vypis['stav'] - $vypis['stav_poc'];
            $obrat2= $vypis['*obrat'];
            $rozdil= round($obrat1-$obrat2,2);
//                                         display("$obrat%$obrat2%$rozdil v ".count($vypis['prevody']));
            if ( abs($rozdil)>0.01 ) {
              $vypis['_error']= "inkonsistence obratu: $obrat1 x $obrat2";
//                                         debug($vypis,"vypis ze $soubor");
              fce_error("Při importu $soubor byla zjištěna inkonsistence obratu: $obrat1 x $obrat2");
            }
          }
          // zapíšeme do tabulek
          foreach ( $vypisy as $vypis ) if ( !$vypis['_error'] ) {
            // pokud se má účet ignorovat, jdi na další
            $ignorovat= !isset($bank_nase_ucty[$banka][$vypis['ucetcislo']]);
            if ( $ignorovat ) {
//                                                 display("vypis $v z uctu {$vypis['ucetcislo']} byl ignorovan (3)");
              continue;
            }
            // kontrola existence výpisu
            $importovan= select("count(*)","vypis","ident='{$vypis['ident']}'");
                                        display("soubor={$vypis['soubor']}, vypis={$vypis['ident']},
                                        importovan=$importovan, ignorovat=$ignorovat, kontrola=$kontrola, change=$change");
//                                         debug($vypis,"vypis ze $soubor");
            if ( $importovan && $kontrola && $change=='missing' ) {
              // kontrola položek výpisu a jeho převodů a případné doplnění převodu
              $qry= bank_check_qry('vypis','ident,vypis,ucet,datum,soubor,stav_poc,stav',$vypis);
//               $qry= bank_check_qry('vypis','ident,vypis,ucet,datum,soubor,stav',$vypis);
//                                         display("qry:$qry");
              $res= pdo_qry($qry);
              if ( !$res ) fce_error("banka_import: kontrola výpisu {$vypis['ident']} selhala");
              if ( $res && $row= pdo_fetch_assoc($res) ) {
                if ( $er= $row['err_list'] ) {
                  fce_error("Při kontrole $soubor byla zjištěna změna položek vypisu: $er");
                }
                else {
                  // vypis je stejný jako byl
                  if ( $vypis['prevody'] )
                  foreach ( $vypis['prevody'] as $prevod )
                  if ( $prevod['typ']>=5 ) {
                    // doplň údaje do převodů - pro Hospic jen příjmy, výdaje ignoruj
                    $qry= bank_check_qry('prevod'
                      ,'ident,vypis,ucet,popis,clen,typ,castka,protiucet,banka,splatnost,'
                        . 'ksym,vsym,ssym,kat,poznamka'
//                         . 'ksym,vsym,ssym,kat,poznamka,metoda' -- metoda se nemusí porovnávat
                      ,$prevod,'typ,kat,clen,dar');
//                                         display("qry:$qry");
                    $res= pdo_qry($qry);
                    $rows= pdo_num_rows($res);
                    if ( $rows ) {
                      if ( $res && $row= pdo_fetch_assoc($res) ) {
                        if ( $er= $row['err_list'] ) {
                          fce_error("Při kontrole {$prevod['ident']} byla zjištěna změna položek převodu: $er");
                        }
                      }
                    }
                    else {
                      $qry= bank_insert_qry('prevod'
                        ,'ident,vypis,ucet,popis,clen,typ,castka,protiucet,banka,splatnost,'
                          . 'ksym,vsym,ssym,kat,poznamka,metoda',$prevod);
//                                         display("qry:$qry");
                      $res= pdo_qry($qry);
                      if ( !$res ) fce_error("banka_import: přidání převodu {$prevod['ident']} selhalo");
                      else {
                        $n_vypisu++;
                        $ids_vypisu.= "{$prevod['ident']} ";
                      }
                    }
                  }
                }
              }
            }
            else /*if ( $vypis['prijmu']>0 ) if ( $kontrola && $change=='missing' )*/ {
              // doplnění nového výpisu nebo oprava starého znovunačtením
//               $existuje= select("count(*)","vypis","ident='{$vypis['ident']}'");
//               if ( $existuje )
//                 fce_warning("výpis {$vypis['ident']} již byl importován ze souboru {$vypis['soubor']},
//                   převody nebyly přepsány");
//               else {
                $n_vypisu++;
                $ids_vypisu.= " {$vypis['ident']} ";
                $qry= bank_insert_qry('vypis','ident,vypis,ucet,datum,soubor,stav_poc,stav',$vypis);
                $res= pdo_qry($qry);
                if ( !$res ) fce_warning("banka_ace: zápis výpisu {$vypis['ident']} selhal");
                else if ( $reimport==0 || $change=='prevody' || ($change=='missing' && !$importovan)) {
                  if ( $vypis['prevody'] )
                  foreach ( $vypis['prevody'] as $prevod )
                  if ( $prevod['typ']>=5 ) {
                    // doplň údaje do převodů - pro Hospic jen příjmy, výdaje ignoruj
                    $qry= bank_insert_qry('prevod'
                      ,'ident,vypis,ucet,popis,clen,typ,castka,protiucet,banka,splatnost,'
                        . 'ksym,vsym,ssym,kat,poznamka,metoda',$prevod);
                    $msg.= "{$prevod['ident']} ";
                    $res= pdo_qry($qry);
                    if ( !$res ) fce_error("banka_import: zápis převodu {$prevod['ident']} selhal");
                  }
                }
//               }
            }
          }
          // přesuneme soubor do podsložky s názvem roku
          if ( $to_move && $yyyy && !$kontrola ) {
            if ( !is_dir($path.$yyyy) ) mkdir($path.$yyyy);
            $file1= $path.$yyyy.'/'.$file;
            $ok= 1;
            if ( file_exists($file1) && $to_move ) {
              $ok= @unlink($file1);
              if ( !$ok )
                fce_error("Soubor $file nelze odstranit z FTP složky banky $banka");
            }
            if ( $ok ) {
              $ok= @rename($path.$file,$file1);
              if ( !$ok )
                fce_error("Soubor $file nelze přesunout FTP složky banky $banka/rok");
            }
          }
        }
      }
    }
    if ( $handle ) closedir($handle);
    else fce_error("Cesta '$path' pro výpisy banky '$banka' v $ezer_root.php není platná");
  }
//                                         debug($vypisy,'vypisy 2');
  if ( $n_vypisu ) $n_vypisu.=" (".trim($ids_vypisu).")";
  return $n_vypisu; //$msg;
}
# -------------------------------------------------------------------------------------------------- bank_popis2clen
# zkusíme z popisu účtu uhodnout člena
# předpokládáme, že popis= příjmení jméno ...
function bank_popis2clen($popis,&$metoda) {
  $clen= 0;
  // rozklad popisu na jméno
  $popis= str_replace('  ',' ',trim($popis));
  $jm= array();
  foreach(explode(' ',$popis) as $x) {
    if ( substr($x,-1)!='.' ) {
      $jm[]= $x;
    }
  }
  $jm0= pdo_real_escape_string($jm[0]);
  $jm1= pdo_real_escape_string($jm[1]);
  list($prijmeni,$jmeno)= explode(' ',trim($popis));
  if ( $jm0 && $jm1 ) {
    $qry= "SELECT id_clen FROM clen WHERE
             (  (jmeno LIKE '$jm0' COLLATE utf8_general_ci
                 AND prijmeni LIKE '$jm1' COLLATE utf8_general_ci)
             OR (jmeno LIKE '$jm1' COLLATE utf8_general_ci
                 AND prijmeni LIKE '$jm0' COLLATE utf8_general_ci))
             AND left(deleted,1)!='D' ";
    $res= pdo_qry($qry);
    if ( $res && pdo_num_rows($res)==1 ) {
      $row= pdo_fetch_assoc($res);
      $clen= $row['id_clen'];
      $metoda.= ", podle jednoznačnosti jména";
    }
  }
  return $clen;
}
# -------------------------------------------------------------------------------------------------- bank_ucet2clen
# zkusíme z účtu uhodnout člena mezi těmi, kteří mají tuto položku uvedenu
function bank_ucet2clen($pub) {
  bank_pub($pub,$p,$u,$b,false);     // rozklad bez doplňování nul
  $match= ($p ? "0*$p-" : '')."0*$u/$b";
  $clen= 0;
  $qry= "SELECT id_clen FROM clen WHERE ucet REGEXP '$match' AND LEFT(deleted,1)!='D' ";
  $res= pdo_qry($qry);
  if ( $res && pdo_num_rows($res)==1 ) {
    $c= pdo_fetch_object($res);
    $clen= $c->id_clen;
  }
  return $clen;
}
# -------------------------------------------------------------------------------------------------- bank_vsym2clen
# zkusíme z variabilního symbolu uhodnout člena
# predanych 10 cislic je bud clenske nebo rodne cislo nebo nic
#   0000nnnnnn - členské číslo, je-li n>12
#   rrmmddxxxx - rodné číslo
#   9999nnnnnn - hromadný dar
function bank_vsym2clen($vsym,&$metoda) {
  $clen= 0;
  $metoda= '';
  if ( verify_rodcis($vsym) ) {
    $metoda= "$vsym je rc";
    // rodne cislo - rrmmddxxx(x)
    $qry= "SELECT id_clen FROM clen WHERE rodcis='$vsym' AND left(deleted,1)!='D' LIMIT 1";
    $res= pdo_qry($qry);
    if ( $res && $row= pdo_fetch_assoc($res) ) {
      // rodné číslo existuje Klubu?
      $clen= $row['id_clen'];
      $metoda.= ", v Klubu je";
    }
    else
      $metoda.= ", v Klubu není";
  }
  else {
    if ( substr($vsym,0,4)=="9999" ) {
      // hromadny dar=9999nnnnn
      $clen= intval(substr($vsym,5));
      $metoda.= "$vsym určuje hromadný dar člena $clen";
    }
    else {
      $clen= intval($vsym);
      if ( $clen>12 && $clen<999999 ) {
        // členské číslo=0000nnnnnn
        $metoda.= "$vsym může být členské číslo $clen";
      }
      else $clen= 0;
    }
    if ( $clen ) {
      // existuje číslo v Klubu?
      $qry= "SELECT id_clen FROM clen WHERE id_clen=$clen AND left(deleted,1)!='D' LIMIT 1";
      $res= pdo_qry($qry);
      if ( pdo_num_rows($res) ) {
        $metoda.= ", v Klubu je";
      }
      else {
        $metoda.= ", v Klubu není";
        $clen= 0;
      }
    }
  }
  if ( NOE && !$clen && $vsym>100000 ) {
    // i chybné formáty rodného čísla
    $qry= "SELECT id_clen FROM clen WHERE rodcis=$vsym AND left(deleted,1)!='D' LIMIT 1";
    $res= pdo_qry($qry);
    if ( $res && $row= pdo_fetch_assoc($res) ) {
      // rodné číslo existuje Klubu?
      $clen= $row['id_clen'];
      $metoda.= ", v Klubu je 2";
    }
    else
      $metoda.= ", v Klubu není 2, protože $qry";
  }
//                                         display("bank_vsym2clen($vsym) - $clen $metoda");
  return $clen;
}
# -------------------------------------------------------------------------------------------------- bank_check_qry
# zjistí shodu všech položek pro "ident={$values['ident']}"
function bank_check_qry($table,$items,$values,$buts='') { #trace();
  $cond= "ident='{$values['ident']}'";
  $del= '';
  $list= '';
  $but= explode(',',$buts);
  foreach (explode(',',$items) as $item) {
    if ( !in_array($item,$but) ) {
      $value= pdo_real_escape_string($values[$item]);
      $list.= "{$del}if($item='$value','',concat('$item:','/','$value,$item'))";
      $del= ',';
    }
  }
  $qry= "SELECT CONCAT($list) as err_list FROM $table WHERE $cond";
  return $qry;
}
# -------------------------------------------------------------------------------------------------- bank_insert_qry
function bank_insert_qry($table,$items,$values) { //trace();
  $qry= "INSERT INTO $table ($items) VALUES (";
  $del= '';
  foreach (explode(',',$items) as $item) {
    $value= pdo_real_escape_string($values[$item]);
    $qry.= "$del'$value'";
    $del= ',';
  }
  return "$qry);";
}
# -------------------------------------------------------------------------------------------------- bank_ace_kod
# překóduje řetězec do UTF-8
function bank_ace_kod($val) {
  $val= iconv("CP852","UTF-8",$val);
  return $val;
}
# -------------------------------------------------------------------------------------------------- bank_gem_kod
# překóduje řetězec do UTF-8
function bank_gem_kod($val) {
  $val= iconv("CP1250","UTF-8",$val);
  return $val;
}
# -------------------------------------------------------------------------------------------------- bank_gem
# rozkóduje text se strukturou GEMINI 4.1
function bank_gem($gpc,$soubor,&$yyyy) {
  global $y, $vypisy;
                                                display("<b>bank_gem($soubor)</b>");
  $yyyy= "20".substr($soubor,1,2);
//  $yyyy= ((substr($soubor,1,1)=="0"||substr($soubor,1,1)=="1") ? "20" : "19").substr($soubor,1,2);
  $msg= '';
  $f= fopen($gpc, "r");
  $vypis= array();
  $nprikaz= 0;
  while ( !feof($f) ) {
    $b= fgets($f,4096);
    if ( strlen($b) ) {
      // zjištění a kontrola informací o výpise
      $nprikaz++;
      $nvypis= str_pad(trim(substr($b,38,5)),3,'0',STR_PAD_LEFT);
      $smer= trim(substr($b,72,1));
      $castka= trim(substr($b,74,15));
      $sign_castka= $castka*($smer=='D' ? -1 : 1);
      if ( !$vypis['vypis'] ) {
        $vypis['ucetcislo']= $vypis['ucet']= '000000-'.substr($b,0,10);
        $vypis['soubor']= $soubor;
        $vypis['vypis']= $nvypis;
        $vypis['datum']= substr($b,48,4).'-'.substr($b,52,2).'-'.substr($b,54,2);
        $stav= trim(substr($b,89,15));
        $vypis['stav_poc']= $stav - $sign_castka;
//                                                         display("$stav - $castka = {$vypis['stav_poc']}");
        $vypis['*obrat']+= 0;
      }
      else if ( $vypis['vypis']!=$nvypis )
        return fce_error("bank_ace: v jednom souboru smí být jen jeden výpis, je tam $nvypis a {$vypis['vypis']}");
      // zpracování dat převodu
      $prevod= array();
      $prevod['ident']= str_pad($nprikaz,3,'0',STR_PAD_LEFT);
      $prevod['splatnost']= substr($b,64,4).'-'.substr($b,68,2).'-'.substr($b,70,2);
      $prevod['typ']= $smer=='C' ? 5 : 1;
      $prevod['castka']= $castka;
      $vypis['*obrat']+= $sign_castka;
      $prevod['vypis']= $nvypis;
      $posting= trim(substr($b,125,16));
      $info= substr($b,175);
      // obsah proměnných polí
      $infoc= preg_match_all("/[\4]?([^\4]*)/",$info,$aa);
      $a= $aa[1];
//                                                           debug($a,$posting);
      switch ( $posting ) {
      case 'lib.':              // nepopsáno - úrok
        $prevod['popis']= bank_gem_kod(substr($b,141));
        if ( $smer=='C' ) $prevod['typ']= 6;
//                                                           debug($a,$posting);
        break;
      case 'Z8-GEFE':           // nepopsáno - poplatek
      case 'GE-IC':             // Úrok / Poplatek
      case 'PK-ACTR':           // Transakce přes platební karty
        $prevod['popis']= bank_gem_kod($info);
        $prevod['poznamka']= '';
        if ( $smer=='C' && $posting=='GE-IC' ) $prevod['typ']= 6;
  //                                                 debug($prevod,'úrok');
        break;
      case 'GE-FT':             // Zahraniční transakce
        # Příklad výpisu: 12.11.08 Reference: P0811120001OP08  5,613.51
        #                 Částka platby: 159.60 EUR Kurz: 25.77389500 Poplatky: 1,050.00 CZK
      case 'GE-TT':             // Transakce na přepážce -- bývá bohužel označena i jako GE-FT (V09028)
        if ( count($a)>15 ) {
          // Zahraniční transakce
          $prevod['popis']= "zahraniční {$a[0]}";
          $prevod['poznamka']= "částka:{$a[4]}{$a[3]} kurz:{$a[2]} poplatky:{$a[15]}";
        }
        else {
          // Transakce na přepážce
          $prevod['popis']= bank_gem_kod(trim(substr($b,141,34)));
        }
        break;
      case 'I-GE-CC':           // Clearingové (domácí) transakce – příchozí
      case 'O-GE-CC':           // Clearingové (domácí) transakce – odchozí
        $prevod['ksym']= str_pad($a[5],4,"0",STR_PAD_LEFT);
        $poznamka= trim($a[16]) ? bank_gem_kod("B: {$a[16]}") : '';
        $poznamka.= trim($a[12]) ? bank_gem_kod(" D: {$a[12]}") : '';
        $poznamka.= trim($a[13]) ? bank_gem_kod(" K: {$a[13]}") : '';
        $poznamka= str_replace('        ',' ',$poznamka);
        $poznamka= str_replace('    ',' ',$poznamka);
        $prevod['poznamka']= str_replace('  ',' ',$poznamka);
        if ( $posting[0]=='I' ) {         // kreditní platby
          $protiucet= $a[6];
          $prevod['vsym']= str_pad($a[14],10,"0",STR_PAD_LEFT);
          $prevod['ssym']= str_pad($a[10],10,"0",STR_PAD_LEFT);
          $prevod['banka']= $a[2];
          $prevod['popis']= bank_gem_kod($a[7]);
        }
        else {                            // debetní platby
          $protiucet= $a[8];
          $prevod['vsym']= str_pad($a[15],10,"0",STR_PAD_LEFT);
          $prevod['ssym']= str_pad($a[11],10,"0",STR_PAD_LEFT);
          $prevod['banka']= $a[3];
          $prevod['popis']= bank_gem_kod($a[9]);
        }
        $protiucet= str_pad($protiucet,16,'0',STR_PAD_LEFT);
        $protiucet= substr($protiucet,0,6).'-'.substr($protiucet,6,10);
        $prevod['protiucet']= $protiucet;
        break;
      default:
                                            fce_error("$soubor/$nvypis/$nprikaz :61: $posting -- neošetřeno");
        break;
      }
      $vypis['prevody'][$nprikaz]= $prevod;
      $vypis['stav']= $vypis['stav_poc'] + $vypis['*obrat'];

  //     if ( $nprikaz==2 ) break;
    }
  }
//                                                           debug($vypis,$soubor);
  fclose($f);
  if ( count($vypis) ) $vypisy[]= $vypis;
  return $msg;
}
# -------------------------------------------------------------------------------------------------- bank_ace
# rozkóduje text se strukturou MT940
function bank_ace($buf,$soubor,&$yyyy) {
  global $y, $vypisy;
//                                                 display("<b>bank_ace($soubor)</b>");
  $msg= '';
//   $mc= preg_match_all("/:(86):((?:.*\r\n){2})|:([\dF]+):([^:]*)/",$buf,$m);
  $mc= preg_match_all("/:(86):([IO](?:\4[^\4]*){16})|:([\dF]+):([^:]*)/",$buf,$m);
//                                                         debug($m);
  $vypis= array();
  for ($i= 0; $i<$mc; $i++) {
    $kod= $m[1][$i] ? $m[1][$i] : $m[3][$i];
    $x= $m[1][$i] ? $m[2][$i] : $m[4][$i] ;
    $x= str_replace("\r\n","",$x);
//                                                 display("položka kod=$kod");
    switch ( $kod ) {
    // VYPIS I.
    case '20':	// -- referenční číslo transakce datum+účet
      break;
    case '25':	// identifikace účtu
      $vypis= array();
      $vypis['ucetcislo']= $vypis['ucet']= $ucet= '000000-'.substr($x,0,10);
//                                                 display("25 účet:$ucet");
      break;
    case '28':	// číslo výpisu
      $vypis['soubor']= $soubor;
      $vypis['vypis']= $nvypis= substr($x,0,3);
      $prevod= array();
      $nprikaz= 0;
      $vypis['*obrat']= 0;
      break;
    case '60F':	// výchozí (opening) balance
      $vyp_cd= substr($x,0,1);
      $yy= substr($x,1,2);
      $vypis['datum']= "20$yy-".substr($x,3,2)."-".substr($x,5,2);
      $yyyy= "20$yy";
      $balance= substr($x,10);
      $vypis['stav_poc']= $stav= str_replace(',','.',$balance) * ($vyp_cd=='D' ? -1 : 1);
//                                                 display("60F (opening) $stav $x");
      $prevod= array();
      break;
    // PŘEVOD
    case '61':	// detail  o transakci
      $nprikaz++;
      $c61= preg_match("/(\d+)([CD])([\d,]+)(NCHK|NMSC)([^\/]+|NONREF)(?:\/\/)(.{7})(.+)/",$x,$i61);
      list($filler,$datum,$cd,$castka,$filler,$filler,$posting,$info)= $i61;
      $posting= trim($posting);
      $prevod['splatnost']= "20".substr($datum,0,2)."-".substr($datum,2,2)."-".substr($datum,4,2);
      $prevod['castka']= str_replace(',','.',$castka) ; // * ($cd=='D' ? -1 : 1);
      $prevod['ucet']= $ucet;
      $prevod['typ']= $cd=='D' ? 1 : 5;
      $prevod['vypis']= $nvypis;
      $prevod['ident']= str_pad($nprikaz,3,'0',STR_PAD_LEFT);
      switch ( $posting ) {
      case 'Z8-GEFE':           // nepopsáno - poplatek
      case 'GE-IC':             //  Úrok / Poplatek
      case 'PK-ACTR':           // Transakce přes platební karty
        // k tomuto typu nebude :86:
        $vypis['*obrat']+= ($cd=='D' ? -1 : 1) * $prevod['castka'];
        $prevod['popis']= bank_ace_kod($info);
        $prevod['poznamka']= '';
        $vypis['prevody'][$nprikaz]= $prevod;
        if ( $cd=='C' && $posting=='GE-IC' ) $prevod['typ']= 6;
//                                                 debug($prevod,'úrok');
        $prevod= array();
        break;
      case 'GE-TT':             // Transakce na přepážce
        $c61a= preg_match("/(.{10})(.+)/",$info,$i61i);
//                                             display("GE-TT $nprikaz:61:$x"); // debug($i61); debug($i61i);
        list($filler,$info1,$info2)= $i61i;
        $vypis['*obrat']+= ($cd=='D' ? -1 : 1) * $prevod['castka'];
        $prevod['popis']= bank_ace_kod($info2);
        $prevod['poznamka']= "přepážka $info1";
        // k tomuto typu nebude :86:
        $vypis['prevody'][$nprikaz]= $prevod;
        $prevod= array();
        break;
      case 'GE-FT':             // Zahraniční transakce
      case 'I-GE-CC':           // Clearingové (domácí) transakce – příchozí
      case 'O-GE-CC':           // Clearingové (domácí) transakce – odchozí
        // musí následovat :68:
//                                             display("GE-FT $nprikaz:61:$x"); debug($i61); debug($i61i); debug($prevod);
        break;
      default:
                                            fce_error("$soubor/$nvypis/$nprikaz :61: $posting -- neošetřeno");
        break;
      }
      break;
    case '86':	// informace o platbě -v případě že
      $infoc= preg_match_all("/[\4]?([^\4]*)/",$x,$info);
//                                                 display_(" . ");
//                                                         debug($info[1]);
      if ( $posting=='GE-FT' ) {          // původ postingu = “GE-FT“ detaily zahraniční platby;
        # Příklad výpisu: 12.11.08 Reference: P0811120001OP08  5,613.51
        #                 Částka platby: 159.60 EUR Kurz: 25.77389500 Poplatky: 1,050.00 CZK
        $prevod['popis']= "zahraniční {$info[1][0]}";
        $prevod['poznamka']= "částka:{$info[1][4]}{$info[1][3]} kurz:{$info[1][2]} poplatky:{$info[1][15]}";
      }
      else {                                    // původ postingu  = “O-GE-CC“ nebo „I-GE-CC“ detaily domácí platby
        $prevod['ksym']= str_pad($info[1][5],4,"0",STR_PAD_LEFT);
        $prevod['ssym']= str_pad($info[1][10],10,"0",STR_PAD_LEFT);
        $prevod['vsym']= str_pad($info[1][14],10,"0",STR_PAD_LEFT);
        $poznamka= $info[1][16] ? bank_ace_kod("B: {$info[1][16]}") : '';
        $poznamka.= $info[1][12] ? bank_ace_kod(" D: {$info[1][12]}") : '';
        $poznamka.= $info[1][13] ? bank_ace_kod(" K: {$info[1][13]}") : '';
  //       $poznamka= bank_ace_kod("D: {$info[1][12]} K:{$info[1][13]} B:{$info[1][16]}");
        $poznamka= str_replace('        ',' ',$poznamka);
        $poznamka= str_replace('    ',' ',$poznamka);
        $prevod['poznamka']= str_replace('  ',' ',$poznamka);
        if ( $info[1][0]=='I' ) {         // příchozí platby
          $protiucet= $info[1][6];
          $prevod['banka']= $info[1][2];
          $prevod['popis']= bank_ace_kod($info[1][7]);
          $prevod['typ']= 5;
        }
        else {                            // odchozí platby
          $protiucet= $info[1][8];
          $prevod['banka']= $info[1][3];
          $prevod['popis']= bank_ace_kod($info[1][9]);
          $prevod['typ']= 1;
        }
        $protiucet= str_pad($protiucet,16,'0',STR_PAD_LEFT);
        $protiucet= substr($protiucet,0,6).'-'.substr($protiucet,6,10);
        $prevod['protiucet']= $protiucet;
      }
      $vypis['*obrat']+= ($cd=='D' ? -1 : 1) * $prevod['castka'];
//                       if ( $posting=='GE-FT' ){           /*display("$nprikaz:86:$x"); debug($info);*/  debug($vypis); debug($prevod);  }
      $vypis['prevody'][$nprikaz]= $prevod;
      $prevod= array();
      break ;
    // VYPIS II.
    case '62F':	// konečná (booked) balance
      $vyp_cd= substr($x,0,1);
      $balance= substr($x,10);
      $vypis['stav']= $stav= str_replace(',','.',$balance) * ($vyp_cd=='D' ? -1 : 1);
      $vypisy[]= $vypis;
//                                                 display("62F (booked) $stav");
      break;
    case '64':	// konečná (available) balance
//       $vyp_cd= substr($x,0,1);
//       $balance= substr($x,10,-1);
//       $stav= str_replace(',','.',$balance) * ($vyp_cd=='D' ? -1 : 1);
//                                                 display("64 (available) $stav");
      break;
    case '65':	// balance pro datum (Ledger balance pro datum)
//       $vypisy[]= $vypis;
      break;
    default:          // chyba
      fce_error("bank_ace: neznámý kód :$kod:");
      break;
    }
  }
  return $msg;
}
# -------------------------------------------------------------------------------------------------- bank gpc_csob
# rozkóduje soubor se strukturou GPC pro ČSOB
function bank_gpc_csob($gpc,$soubor,&$yyyy) { trace();
  global $y, $vypisy;
  $f= fopen($gpc, "r");
                                                display("$gpc - ".($f?'ok':'ko'));
  $vypis= array();
  while ( !feof($f) ) {
    $buf= fgets($f,4096);
    if ( strlen($buf) ) {
      $druh= substr($buf,0,3);
      switch ( $druh ) {
      case '074':    // věta obratová
        // hlavička našeho účtu
        $nprikaz= 0;
        if ( count($vypis) ) {
          $vypisy[]= $vypis;
          $vypis= array();
        }
        $vypis['soubor']= $soubor;
        $vypis['ucetcislo']= $vypis['ucet']= $ucet= gpc_kod2ucet_csob(substr($buf,3,16));
        $prevod= array();
        $vypis['vypis']= $nvypis= substr($buf,105,3);
        $dat0= substr($buf,108,6);
        $_yy= substr($dat0,4,2);
        $yyyy= "20$_yy";
//        $yyyy= ((substr($_yy,0,1)=="0"||substr($_yy,0,1)=="1") ? "20" : "19").$_yy;
//                                                 display("GPC rec 074, dat0=$dat0 dá $yyyy");
        $vypis['datum']= "$yyyy-" . substr($dat0,2,2) . "-" . substr($dat0,0,2);
        $sta= substr($buf,60,14)/100;
        $vypis['stav']= $sta * (substr($buf,74,1)=="-" ? -1 : 1);
        $sta_poc= substr($buf,45,14)/100;
        $vypis['stav_poc']= $sta_poc * (substr($buf,59,1)=="-" ? -1 : 1);
        $vypis['*obrat']= 0;
        break;
      case '075':         // věta transakční
        // řádek výpisu
        $_kat= '';
        $nprikaz++;
        $prevod['popis']= win2utf(substr($buf,97,20),true);
        $_inkaso= substr($buf,121,1);
        $prevod['typ']= substr($buf,60,1)=="1" || substr($buf,60,1)=="5"
          ? ($_inkaso%2==0 ? 3 : 1) : 5;
        $prevod['castka']= substr($buf,48,12)/100;
        $prevod['protiucet']= gpc_kod2ucet_csob(substr($buf,19,16));
        $prevod['banka']= substr($buf,73,4);
        $dat0= substr($buf,122,6);
        $yyyy= "20".substr($dat0,4,2);
//        $yyyy= ((substr($dat0,4,1)=="0"||substr($dat0,4,1)=="1") ? "20" : "19").substr($dat0,4,2);
//         $yyyy= (substr($dat0,4,1)=="0" ? "20" : "19").substr($dat0,4,2);
        $prevod['splatnost']= "$yyyy-" . substr($dat0,2,2) . "-" . substr($dat0,0,2);
        $prevod['ksym']= substr($buf,77,4);
        $prevod['vsym']= substr($buf,61,10);
        $prevod['ssym']= substr($buf,81,10);
        $cle= 0;
        $prevod['ucet']= $ucet;
        $prevod['vypis']= $nvypis;
        $prevod['ident']= str_pad($nprikaz,3,'0',STR_PAD_LEFT);
        $vypis['prevody'][$nprikaz]= $prevod;
        $vypis['*obrat']+= ($prevod['typ']<5 ? -1 : 1) * $prevod['castka'];
        $prevod= array();
        break;
      case '076':         // Sekv. Odúčt.
        break;
      case '079':         // poznámka ?
      case '078':         // poznámka
        $pozn= win2utf(substr($buf,3),true);
        $vypis['prevody'][$nprikaz]['poznamka']= $pozn;
                                                display("POZNAMKA:$pozn");
        break;
      default:
        fce_error("importování GPC/CSOB výpisu: neznámý kód věty: '$buf'");
      }
    }
  }
  fclose($f);
  if ( count($vypis) ) $vypisy[]= $vypis;
}
# -------------------------------------------------------------------------------- gpc_kod2ucet_csob
function gpc_kod2ucet_csob ($kod) {
  $kod2= substr($kod,0,6)."-".substr($kod,6,10);
  return $kod2;
}
# -------------------------------------------------------------------------------------------------- bank_gpc_kb
# rozkóduje soubor se strukturou GPC pro KB
# ( 074 075* )*
function bank_gpc_kb($gpc,$soubor,&$yyyy) {
  global $y, $vypisy;
  $f= fopen($gpc, "r");
  $vypis= array();
  while ( !feof($f) ) {
    $buf= fgets($f,4096);
    if ( strlen($buf) ) {
      $druh= substr($buf,0,3);
      switch ( $druh ) {
      case '074':    // věta obratová
        // hlavička našeho účtu
        $nprikaz= 0;
        if ( count($vypis) ) {
          $vypisy[]= $vypis;
          $vypis= array();
        }
        $vypis['soubor']= $soubor;
        $vypis['ucetcislo']= $vypis['ucet']= $ucet= gpc_kod2ucet(substr($buf,3,16));
        $prevod= array();
        $vypis['vypis']= $nvypis= substr($buf,105,3);
        $dat0= substr($buf,108,6);
        $_yy= substr($dat0,4,2);
        $yyyy= "20$_yy";
//        $yyyy= ((substr($_yy,0,1)=="0"||substr($_yy,0,1)=="1") ? "20" : "19").$_yy;
//                                                 display("GPC rec 074, dat0=$dat0 dá $yyyy");
        $vypis['datum']= "$yyyy-" . substr($dat0,2,2) . "-" . substr($dat0,0,2);
        $sta= substr($buf,60,14)/100;
        $vypis['stav']= $sta * (substr($buf,74,1)=="-" ? -1 : 1);
        $sta_poc= substr($buf,45,14)/100;
        $vypis['stav_poc']= $sta_poc * (substr($buf,59,1)=="-" ? -1 : 1);
        $vypis['*obrat']= 0;
        break;
      case '075':         // věta transakční
        // řádek výpisu
        $_kat= '';
        $nprikaz++;
        $prevod['popis']= substr($buf,97,20);
        $_inkaso= substr($buf,121,1);
        $prevod['typ']= substr($buf,60,1)=="1" || substr($buf,60,1)=="5"
          ? ($_inkaso%2==0 ? 3 : 1) : 5;
        $prevod['castka']= substr($buf,48,12)/100;
        $prevod['protiucet']= gpc_kod2ucet(substr($buf,19,16));
        $prevod['banka']= substr($buf,73,4);
        $dat0= substr($buf,122,6);
        $yyyy= "20".substr($dat0,4,2);
//        $yyyy= ((substr($dat0,4,1)=="0"||substr($dat0,4,1)=="1") ? "20" : "19").substr($dat0,4,2);
//         $yyyy= (substr($dat0,4,1)=="0" ? "20" : "19").substr($dat0,4,2);
        $prevod['splatnost']= "$yyyy-" . substr($dat0,2,2) . "-" . substr($dat0,0,2);
        $prevod['ksym']= substr($buf,77,4);
        $prevod['vsym']= substr($buf,61,10);
        $prevod['ssym']= substr($buf,81,10);
        $cle= 0;
        $prevod['ucet']= $ucet;
        $prevod['vypis']= $nvypis;
        $prevod['ident']= str_pad($nprikaz,3,'0',STR_PAD_LEFT);
        $vypis['prevody'][$nprikaz]= $prevod;
        $vypis['*obrat']+= ($prevod['typ']<5 ? -1 : 1) * $prevod['castka'];
        $prevod= array();
        break;
      default:
        fce_error("importování GPC/KB výpisu: neznámý kód věty: '$buf'");
      }
    }
  }
  fclose($f);
  if ( count($vypis) ) $vypisy[]= $vypis;
}
# -------------------------------------------------------------------------------------------------- gpc_kod2ucet
function gpc_kod2ucet ($kod) {
  $kod2= substr($kod,10,6)."-"
   .substr($kod,4,5).substr($kod,3,1).substr($kod,9,1).substr($kod,1,2).substr($kod,0,1);
  return $kod2;
}
?>
