<?php # Aplikace Polička, (c) 2021 Martin Smidek <martin@smidek.eu>
# --------------------------------------------------------------------------------------==> dop vars
# ASK
# vrátí seznamy proměnných: all=všech, use=použitých
function dop_vars($idd=0) {  trace();
  $html= '';
  $vars= array(
    'adresa'            => 'adresa odběratele',
    'datum'             => 'datum odeslání',
    'osloveni'          => 'oslovení odběratele z karty Odběratelé',
  );
  $all= array_keys($vars);
  $use= array();
  if ( $idd ) {
    $d= select('*','dopis',"id_dopis=$idd");
    $idd= $d->id_dopis;
    $obsah= $d->obsah;
    $list= null;
    $is_vars= preg_match_all("/[\{]([^}]+)[}]/",$obsah,$list);
    $use= $list[1];
  }
  // redakce zobrazení
  $bad= array_diff($use,$all);
//                                                         debug($bad,'bad');
  if ( count($bad) ) {
    $html.= "<h3 class='work'>Neznámé proměnné</h3><div style='color:red'>";
    sort($bad);
    foreach ($bad as $x) {
      $html.= "<div><b>{{$x}}</b></div>";
    }
    $html.= '</div>';
  }
  $html.= "<h3 class='work'>Seznam proměnných</h3><dl>";
  ksort($vars);
  foreach ($vars as $k=>$x) {
    $clr= in_array($k,$use) ? 'green' : 'silver';
    $html.= "<dt style='color:$clr'><b>{{$k}}</b></dt><dd><i>$x</i></dd>";
  }
  $html.= '</dl>';

  return (object)array('html'=>$html,'all'=>$all,'use'=>$use);
}
# --------------------------------------------------------------------------------------==> . ukázka
# ASK
# provede substituce pro daného odběratele
function dop_ukazka($idd,$idc) {  trace();
  global $ezer_path_docs;
  $vars= dop_vars($idd);
  $dopis= select('*','dopis',"id_dopis=$idd");
  $pairs= dop_compute($vars->use,$dopis,$idc);
  if ( isset($pairs['warning']) ) fce_warning($pairs['warning']);
  // pro testování doplň title se jménem proměnné
  if ( $vars ) foreach ($pairs as $var=>$pair) {
    $pairs[$var]= "<span title='$var'>$pair</span>";
  }
  // pokud dopis obsahuje proměnné, personifikuj obsah
  $html= $dopis->obsah;
  if ( $vars ) {
    $html= strtr($html,$pairs);
  }
  // generování PDF a předání odkazu
  $sablona= null;
  $fname= -$idc."_ukazka_".date('ymd_Hi').".pdf";
  $f_abs= "$ezer_path_docs/$fname";
  $f_rel= "docs/$fname";
  tc_html_open();
  tc_html_write($html,'');
  tc_html_close($f_abs);
  $ref= "<a target='dopis' href='$f_rel'>zde</a>";
  return (object)array('html'=>$html,'ref'=>$ref);
}
# ------------------------------------------------------------------------------------==> . proměnné
# spočítá hodnoty proměnných pro id_ctenar - seznam viz od_dop_vars.$vars
# ve warning vrací případné nesrovnalosti
function dop_compute($vars,$dopis,$idc) {  trace();
  $pairs= array();
  $map_osloveni= map_cis('k_osloveni','zkratka');
  $rc= pdo_qry("
    SELECT 
      titul,jmeno,prijmeni,firma,c.ulice,c.psc,obec,osloveni,prijmeni5p
    FROM clen AS c 
    WHERE id_clen=$idc ORDER BY psc DESC LIMIT 1
    ");
  $c= pdo_fetch_object($rc);
  $titul= $c->titul; $jmeno= $c->jmeno; $prijmeni= $c->prijmeni;
  $organizace= $c->firma ? "{$c->firma}<br>" : "";
  $c_ulice= $c->ulice;
  $c_psc=   $c->psc;
  $c_obec=  $c->obec;
  $ulice= $c_ulice ?: $c->f_ulice;
  $psc=   $c_psc   ?: $c->f_psc;
  $obec=  $c_obec  ?: $c->f_obec;
  foreach ($vars as $var) {
    switch ($var) {
    case 'osloveni':                          // kontext: {osloveni}!
      if ( $c->osloveni!=0 && $c->prijmeni5p!='' ) {
        $val= "{$map_osloveni[$c->osloveni]} {$c->prijmeni5p}";
      }
      else
        $val= 'Milí';
//                                         display("osloveni={$c->osloveni}|{$c->prijmeni5p}|{$val->$var}|");
      break;
    // informace
    case 'datum':
      $val= sql_date1($dopis->datum,0,'. ');
      break;
    // osobní údaje
    case 'adresa':
      $val= $organizace.trim("$titul $jmeno")." $prijmeni<br>$c_ulice<br>$c_psc $c_obec";
      break;
    default:
      $val= "<b style='color:red' title='$var'>???</b>";
      break;
    }
    $pairs['{'."$var}"]= $val;
  }
//                                                         debug($pairs);
  return $pairs;
}
# --------------------------------------------------------------------------------------==> . všem
# ASK
# provede substituce pro dané odběratele
function dop_vsem($idd) {  trace();
  global $ezer_path_docs;
  global $odber_ids;  // pro komunikaci s od_adr_gen
  $ref= $err= '';
  $vars= dop_vars($idd);
  $dopis= select('*','dopis',"id_dopis=$idd");
  $cislo= (object)array();
  $ids= "";
//  xx_posledni($cislo,'O');
//                                                         debug($cislo,"číslo");
//  $cenik= od_posta_cenik($cislo->id_casopis);
  // zahájení a získání seznamu ID pro obeslání
  tc_html_open();
  switch ($dopis->komu) {
  case 1: // dlužníci
//    $ids= od_op('dluznici')->dluh_maly; 
    $name= 'dluznici'; 
    break;
  case 2: // budoucí dlužníci -- dostanou se do dluhu před koncem roku
//    od_adr_gen('odber');
//    // $odber_ids[]= (object)array('idc'=>$idc,'konto'=>$konto,'rocni'=>$celkem_rok);
//    $del= '';
//    foreach ($odber_ids as $x) {
//      if ( $x->konto < $x->rocni ) {
//        $ids.= $del.$x->idc;
//        $del= ',';
//      }
//    }
    $name= 'dluznici_za_rok'; 
    break;
  case 3: // všichni odběratelé
//    od_adr_gen('odber');
//    // $odber_ids[]= (object)array('idc'=>$idc,'konto'=>$konto,'rocni'=>$celkem_rok);
//    $del= '';
//    foreach ($odber_ids as $x) {
//      $ids.= $del.$x->idc;
//      $del= ',';
//    }
    $name= 'odberatele'; 
    break;
  default: $err= "Dopis má chybný výběr adresátů"; goto end;
  }
  // projití vygenerovaných
  //$ids= '-8081,-5548';
  foreach ( explode(',',$ids) as $idc) {
    $pairs= dop_compute($vars->use,$dopis,$idc);
    if ( isset($pairs['warning']) ) fce_warning($pairs['warning']);
//                                                         debug($pairs);
    // pokud dopis obsahuje proměnné, personifikuj obsah
    $html= $dopis->obsah;
    if ( $vars ) {
      $html= strtr($html,$pairs);
    }
    tc_html_write($html,'');
  }
  // generování PDF a předání odkazu
  $sablona= null;
  $fname= -$idc."_{$name}_".date('ymd_Hi').".pdf";
  $f_abs= "$ezer_path_docs/$fname";
  $f_rel= "docs/$fname";
  tc_html_close($f_abs);
  $ref= "<a target='dopis' href='$f_rel'>zde</a>";
  $msg= "Vygenerované dopisy jsou ke stažení $ref";
end:
  return (object)array('msg'=>$msg,'err'=>$err);
}
