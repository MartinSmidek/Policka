<?php # Systém CK, (c) 2010 Martin Smidek <martin@smidek.eu>
/** **************************************************************************************** DÁVKOVÉ */
/** ==================================================================================> ZPĚTNÝ ZÁPIS */
# --------------------------------------------------------------------------------- dop pot_hist_can
# zjistí, zda je vše vygenerováno a lze-li zapisovat do historie
#   $obj   -- objekt s informacemi o rozesílání
function dop_pot_hist_can($obj) { trace();
  $ok= 0;
  $d= (object)array('_any_back'=>0,'_all_gen'=>0,'_all_back'=>0);
  // nalezení čerstvého obsahu dávky
  $qry= "SELECT * FROM davka WHERE id_davka='{$obj->id_davka}' " ;
  $res= pdo_qry($qry);
  if ( $res && pdo_num_rows($res) ) {
    $d= pdo_fetch_object($res);
    if ( $d->pro=='Q' ) {
      // varianta pro potvrzení firmám
      $all= $d->PjH  && $d->PjZ && $d->PjC && $d->PsH && $d->PsZ && $d->PsC;
      $d->_all_gen= $all ? 1 : 0;
      $all= $d->ZPjH  && $d->ZPjZ && $d->ZPjC && $d->ZPs;
      $d->_all_back= $all ? 1 : 0;
      $any= $d->ZPjH  || $d->ZPjZ || $d->ZPjC || $d->ZPs;
      $d->_any_back= $any ? 1 : 0;
    }
    else {
      $all= $d->BH && $d->BZ && $d->BC && $d->PjH  && $d->PjZ && $d->PjC && $d->PsH && $d->PsZ && $d->PsC;
      $d->_all_gen= $all ? 1 : 0;
      $all= $d->ZBH && $d->ZBZ && $d->ZBC && $d->ZPjH  && $d->ZPjZ && $d->ZPjC && $d->ZPs;
      $d->_all_back= $all ? 1 : 0;
      $any= $d->ZBH || $d->ZBZ || $d->ZBC || $d->ZPjH  || $d->ZPjZ || $d->ZPjC || $d->ZPs;
      $d->_any_back= $any ? 1 : 0;
    }
  }
                                                        debug($d,"dop_pot_hist_can");
  return $d;
}
# -------------------------------------------------------------------------------- dop pot_hist_canF
# zjistí, zda je vše vygenerováno a lze-li zapisovat do historie - pro firmy
#   $obj   -- objekt s informacemi o rozesílání
function dop_pot_hist_canF($obj) { trace();
  $ok= 0;
  $d= (object)array();
  // nalezení čerstvého obsahu dávky
  $qry= "SELECT * FROM davka WHERE id_davka='{$obj->id_davka}' " ;
  $res= pdo_qry($qry);
  if ( $res ) {
    $d= pdo_fetch_object($res);
    if ($d) {
      $all= $d->BH  && $d->BZ  && $d->BC &&  $d->PjH   && $d->PjZ  && $d->PjC;
      $d->_all_gen= $all ? 1 : 0;
      $all= $d->ZBH && $d->ZBZ && $d->ZBC && $d->ZPjH  && $d->ZPjZ && $d->ZPjC;
      $d->_all_back= $all ? 1 : 0;
      $any= $d->ZBH || $d->ZBZ || $d->ZBC || $d->ZPjH  || $d->ZPjZ || $d->ZPjC;
      $d->_any_back= $any ? 1 : 0;
    }
  }
                                                        debug($d,"dop_pot_hist_canF");
  return $d ?: (object)array('error'=>"nedefinované období");
}
# -------------------------------------------------------------------------------- dop pot_hist_bull
# zápis do historie o odeslání Bulletinu
#   $obj   -- objekt s informacemi o rozesílání
#   $browse_status obsahuje výsledek browse_status obeslaných členů
#   $zasilka = text zapsaný do historie
#   $ys -- ZBH|ZBZ|ZBC
function dop_pot_hist_bull($obj,$browse_status,$zasilka,$ys) { trace();
  $result= (object)array();
  $n= 0;
  $kdy= date('j.n.Y');
  $prefix= "rozesílání $kdy ";
  $y= browse_status($browse_status);
//                                                         debug($y);
  $res= pdo_qry($y->qry);
  while ( $res && ($x= pdo_fetch_object($res)) ) {
    $id_clen= $x->id_clen;
    $historie= pdo_real_escape_string("$prefix|$zasilka|");
    $qryu= "UPDATE clen SET historie=CONCAT('$historie',historie) WHERE id_clen=$id_clen ";
    $resu= pdo_qry($qryu,null,null,1);
    $n+= pdo_affected_rows();
  }
  // poznamenej, že se zapsalo $ys
  $qry= "UPDATE davka SET $ys=1 WHERE id_davka={$obj->id_davka} ";
  $res= pdo_qry($qry);
  $result->_html= " Bylo zapsáno $n záznamů do historie příjemců $zasilka";
  return $result;
}
# -------------------------------------------------------------------------------- dop pot_hist_potv
# zápis do historie o odeslání Bulletinu a zápis data potvrzení
#   $obj   -- objekt s informacemi o rozesílání
#   $browse_status -- obsahuje výsledek browse_status
#   $zasilka -- text zapsaný do historie
#   $xs -- ZPjH|ZPjZ|ZPjC|ZPs
function dop_pot_hist_potv($obj,$browse_status,$zasilka,$ys) { trace();
  global $USER;
  $result= (object)array();
  $n= $nd= 0;
  $kdy= date('j.n.Y');
  $kdy_sql= date('Y-m-d');
  $prefix= "rozesílání $kdy ";
  $obdobi_od= $obj->datum_od;
  $obdobi_do= $obj->datum_do;
  // zápis do historie člena
  $y= browse_status($browse_status);
//                                                         debug($y);
  $res= pdo_qry($y->qry);
  while ( $res && ($x= pdo_fetch_object($res)) ) {
    $id_clen= $x->id_clen;
    $historie= pdo_real_escape_string("$prefix|potvrzení $zasilka|");
    $qryu= "UPDATE clen SET historie=CONCAT('$historie',historie) WHERE id_clen=$id_clen ";
    $resu= pdo_qry($qryu,null,null,1);
    $n+= pdo_affected_rows();
    // zápis potvrzených darů
    $id_clen= $x->id_clen;
    $qryd= "UPDATE dar SET potvrz_txt='',potvrz_kdy='$kdy_sql',potvrz_kdo='{$USER->abbr}'
            WHERE LEFT(deleted,1)!='D' AND zpusob!=4 AND potvrz_kdy='0000-00-00'
              AND id_clen=$id_clen AND castka_kdy BETWEEN '$obdobi_od' AND '$obdobi_do' ";
    $resd= pdo_qry($qryd);
    $nd+= pdo_affected_rows();
  }
  // poznamenej, že se zapsalo $ys
  $qry= "UPDATE davka SET $ys=1 WHERE id_davka={$obj->id_davka} ";
  $res= pdo_qry($qry);
  $result->_html= " Bylo zapsáno $n záznamů do historie příjemců $zasilka a $nd do tabulky darů";
  return $result;
}
/** ========================================================================================> ŠTÍTKY */
# ----------------------------------------------------------------------------------- dop pot_stitky
# vygenerování PDF se samolepkami - adresními štítky
#   $obj   -- objekt s informacemi o rozesílání
#   $browse_status obsahuje výsledek browse_status
#   $the_json obsahuje  title:'{jmeno_postovni}<br>{adresa_postovni}'
#   $xs -- BH|BZ|BC
function dop_pot_stitky($obj,$browse_status,$report_json,$xs) { trace();
  global $ezer_path_docs;
  $obj->_error= 0;
  // sestav dotaz
  $n= 0;
  $parss= array();
  $y= browse_status($browse_status);
//                                                         debug($y);
  $res= pdo_qry($y->qry);
  while ( $res && ($x= pdo_fetch_object($res)) ) {
    // formátované PSČ (tuzemské a slovenské)
    $psc= (!$x->stat||$x->stat=='CZ'||$x->stat=='SK')
      ? substr($x->psc,0,3).' '.substr($x->psc,3,2) : $x->psc;
    $stat= $x->stat=='CZ' ? '' : $x->stat;
    $titul= $x->titul ? "{$x->titul}<br>" : '';
    // definice pole substitucí
    $parss[$n]= (object)array();
    $parss[$n]->jmeno_postovni= $x->osoba==1
      ? "$titul<b>{$x->jmeno} {$x->prijmeni}</b>"
      : "<b>{$x->jmeno} {$x->prijmeni}</b>".($x->firma ? "<br>{$x->firma}" : '');
    $parss[$n]->adresa_postovni= "{$x->ulice}<br/><b>$psc</b>  {$x->obec}"
      .( $stat ? "<br/>        $stat" : "");
    $n++;
  }
  // předání k tisku
  $fname= 'stitky_'.date("Ymd_Hi");
  $fpath= "$ezer_path_docs/$fname.pdf";
  dop_rep_ids($report_json,$parss,$fpath);
  $href= "<a href='docs/$fname.pdf' target='pdf'>$fname.pdf</a>";
  $obj->_html= " Bylo vygenerováno $n adres do $href.";
  // pokud nebyl použit filtr, poznamenej, že se generovalo $xs
  if ( $obj->_query=='' ) {
    $qry= "UPDATE davka SET $xs=\"$href\" WHERE id_davka={$obj->id_davka} ";
    $res= pdo_qry($qry);
  }
  return $obj;
}
# -------------------------------------------------------------------------------------- dop rep_ids
# LOCAL
# vytvoření dopisů se šablonou pomocí TCPDF podle parametrů
# $parss  - pole obsahující substituce parametrů pro $text
# vygenerované dopisy ve tvaru souboru PDF se umístí do ./docs/$fname
# případná chyba se vrátí jako Exception
function dop_rep_ids($report_json,$parss,$fname) { trace();
  global $json;
  $err= 0;
  // transformace $parss pro strtr
  $subst= array();
  for ($i=0; $i<count($parss); $i++) {
    $subst[$i]= array();
    foreach($parss[$i] as $x=>$y) {
      $subst[$i]['{'.$x.'}']= $y;
    }
  }
  $report= $json->decode($report_json);
  // vytvoření $texty - seznam
  $texty= array();
  for ($i=0; $i<count($parss); $i++) {
    $texty[$i]= (object)array();
    foreach($report->boxes as $box) {
      $id= $box->id;
      if ( !$id) fce_error("dop_rep_ids: POZOR: box reportu musí být pojmenován");
      $texty[$i]->$id= strtr($box->txt,$subst[$i]);
    }
  }
  tc_report($report,$texty,$fname);
}
/** =====================================================================================> POTVRZENI */
# ------------------------------------------------------------------------------------- dop pot_potv
# vygeneruje potvrzení podle $par->potv
#   $obj        -- objekt s informacemi o rozesílání
#      .druh    -- r=roční, o=období, p=pololetí (jako období)
#   $stred      -- 1:Hospic, 2:Žireč, 3:Charita
#   $browse_status -- obsahuje výsledek browse_status
#   $xs         -- PjH|PjZ|PjC|PsH|PsZ|PsC
#               -- A pokud bylo generováno opravné potvrzení bez zápisu do dávky
#   $ids        -- seznam id_clen pro $xs=='A'
function dop_pot_potv($obj,$stred,$browse_status,$xs,$ids='') {  #trace();
  global $USER,$ezer_path_docs;
  $obj->_error= 0;
  $obj->_html= 'došlo k chybě';
//                                                         debug($obj,"vstup $xs");
  // test vybraných pro opakovaný tisk
  if ( $xs=='A' && $ids=='' ) {
    $obj->_html= 'nebyl vybrán žádný příjemce potvrzení';
    return $obj;
  }
  // název střediska
  $map_stredisko= map_cis('stredisko','popis');
  $stredisko= $map_stredisko[$stred] ? $map_stredisko[$stred] : $map_stredisko[7];
  // nalezení dopisu
  $druh= $obj->druh=='r' ? 'Pfr' : 'Pfo';
  $qry= "SELECT * FROM dopis WHERE typ='$druh' " ;
  $res= pdo_qry($qry);
  if ( $res ) $dopis= pdo_fetch_object($res);
  else fce_error("nebyl nalezen dopis typu 'hromadné potvrzení za období' (Pfo)");
  $vzor= $dopis->obsah;
  $sablona= $dopis->sablona ? $dopis->sablona : $dopis->druh;
  $texty= array();
  // výpočet proměnných použitých v dopisu
  $is_vars= preg_match_all("/[\{]([^}]+)[}]/",$vzor,$list);
  $vars= $list[1];
//                                                         debug($vars,"vars");
  // sestav dotaz
  $obdobi_od= $obj->datum_od;
  $obdobi_do= $obj->datum_do;
  $cond_stred= $stred<3 ? "stredisko=$stred" : "stredisko>2";
  $n= 0;
  $parss= array();
  if ( $xs=='A' ) {
    // opakovaný tisk je jen pro vybrané
    $browse_status->cond.= " AND id_clen IN ($ids) ";
  }
  $y= browse_status($browse_status);
//                                                         debug($y);
  $res= pdo_qry($y->qry);
  while ( $res && ($x= pdo_fetch_object($res)) ) {
    // zjištění potvrzovaných darů - při opakování se tisknou i již potvrzené
    $id_clen= $x->id_clen;
    $potvrzeno= $xs=='A' ? '' : "AND potvrz_kdy='0000-00-00'";
    $qryd= "SELECT SUM(castka) AS _sum,COUNT(*) AS _count,COUNT(DISTINCT darce) AS _darci,darce
            FROM dar
            WHERE LEFT(deleted,1)!='D' AND zpusob!=4 $potvrzeno
              AND id_clen=$id_clen AND castka_kdy BETWEEN '$obdobi_od' AND '$obdobi_do'
              AND $cond_stred
            GROUP BY id_clen ";
    $resd= pdo_qry($qryd);
    if ( $resd && ($d= pdo_fetch_object($resd)) ) {
      // jen pokud jsou dary
      $parss[$n]= (object)array();
      // definice obdobi
      $parss[$n]->obdobi_od= sql_date1($obdobi_od,0,'. ');
      $parss[$n]->obdobi_do= sql_date1($obdobi_do,0,'. ');
      // informace o darech
      $parss[$n]->dary_pocet=
        $d->_count==1 ? "dar" : ($d->_count<5 ? "{$d->_count}&nbsp;dary" : "{$d->_count}&nbsp;darů");
      $castka= $d->_sum;
      $parss[$n]->dary_castka= ceil($castka)-$castka==0
        ? round($castka).",-"
        : number_format($castka,2,',',' ');
      $parss[$n]->stredisko= $stredisko;
      // definice částí šablony, adresy, vyřizuje ...
      $psc= (!$x->stat||$x->stat=='CZ'||$x->stat=='SK')
        ? substr($x->psc,0,3).' '.substr($x->psc,3,2) : $x->psc;
      $stat= $x->stat=='CZ' ? '' : $x->stat;
      $titul= $x->titul ? "{$x->titul}<br>" : '';
      // zohlednění nastaveného clen.darce
      if ( $x->darce ) {
        $darce= explode(';',$x->darce);
        // ... jméno obvyklého dárce
        $x->jmeno_darce= $darce[0];
//                                                         debug($darce,"dárce:{$x->jmeno_darce}");
        if ( count($darce)!=1 ) {
          $x->adresa_postovni= trim($darce[1])."<br/>".trim($darce[2]);
          if ( count($darce)!=3 )
            fce_warning("adresa dárce u člena č.$id_clen má chybný formát");
        }
      }
      // poštovní jméno je definováno kaskádou: dar.darce|clen.darce|clen.jméno
      if ( $d->_darci>1 || $x->darce && $d->darce && $d->darce!=$x->darce )
        fce_warning("člen $id_clen má dary rozepsány na více dárců, vytiskněte mu prosím potvrzení na kartě Dárci");
      $jmeno_postovni= $x->jmeno_darce ? "<b>{$x->jmeno_darce}</b>"
         :            "$titul<b>{$x->jmeno} {$x->prijmeni}</b>" ;
      $adresa_postovni= $x->adresa_postovni ? $x->adresa_postovni
         :            "{$x->ulice}<br/><b>$psc</b>  {$x->obec}".($stat ? "<br/>        $stat" : "");
      $texty[$n]= (object)array();
      $texty[$n]->adresa= "<div style=\"line-height:1.5\">{$jmeno_postovni}<br/>{$adresa_postovni}";
      $texty[$n]->vyrizuje= $USER->options->vyrizuje;
      $texty[$n]->telefon= $USER->options->telefon;
      $texty[$n]->vyrizeno= date('j. n. Y');
      // substituce v 'text'
      $text= $vzor;
      if ( $is_vars ) foreach ($vars as $var ) {
        $text= str_replace('{'.$var.'}',$parss[$n]->$var,$text);
      }
      // úprava lámání textu kolem jednopísmenných předložek a přilepení Kč k částce
      $text= preg_replace(array('/ ([v|k|s|a|o|u|i]) /u','/ Kč/u'),array(' \1&nbsp;','&nbsp;Kč'),$text);
      $texty[$n]->text= $text;
      $n++;
    }
    elseif ( !$resd ) fce_error("problém ".pdo_error()." při hledání nepotvrzených darů pro č.$id_clen");
  }
  // předání k tisku, je-li co
  if ( $n ) {
    $fname= "potvrzeni_{$stred}_".date("Ymd_Hi");
    $fpath= "$ezer_path_docs/$fname.pdf";
    $dlouhe= tc_dopisy($texty,$fpath,'','_user',$listu);
    if ( $dlouhe ) {
      fce_warning("POZOR: v souboru $fname je $dlouhe dlouhých dopisů");
    }
    $href= "<a href='docs/$fname.pdf' target='pdf'>$fname.pdf</a>";
    $obj->_html= " Bylo vygenerováno $listu potvrzení do $href.";
    // pokud nebyl použit filtr, poznamenej, že se generovalo $xs (mimo opakovaného tisku)
    if ( $xs!='A' && $obj->_query=='' ) {
      $qry= "UPDATE davka SET $xs=\"$href\" WHERE id_davka={$obj->id_davka} ";
      $res= pdo_qry($qry);
    }
  }
  else {
    $obj->_html= " Nebylo vygenerováno žádné potvrzení.";
    // pokud se nebyl použit filtr, poznamenej prázdnost (mimo opakovaného tisku)
    if ( $xs!='A' && $obj->_query=='' ) {
      $qry= "UPDATE davka SET $xs='-' WHERE id_davka={$obj->id_davka} ";
      $res= pdo_qry($qry);
    }
  }
                                                        debug($obj,"výstup");
  return $obj;
}
# ========================================================================================> VERZE II
# ------------------------------------------------------------------------------------ dop pot2_newP
# ASK vytvoření nové dávky pro potvrzení a bulletin osobám
# bude vytvořen i mail k rozesílání (tj. záznam DOPIS)
function dop_pot2_newP($druh,$nazev,$od,$do,$let_zpet,$mailem,$vanocni) {
  global $USER;
  $obj= (object)array('id_davka'=>0,'id_dopis'=>0);
  $ok= query("INSERT INTO davka (druh,pro,stav,nazev,datum_od,datum_do,datum_nw,let_zpet,mailem,vanocni)
              VALUES ('$druh','P',1,'$nazev','$od','$do','0000-00-00','$let_zpet','$mailem','$vanocni')");
  if ( $ok ) {
    $obj->id_davka= pdo_insert_id();
//     $dnes= date("Y-m-d");
//     $d= pdo_object("SELECT id_dopis,obsah FROM dopis WHERE typ='bulletin'");
//     $subj= "Pozdrav z Červeného Kostelce";
//     $body= $d->obsah;
//     $ok= query("INSERT INTO dopis (id_davka,datum,nazev,odesilatel,obsah)
//                 VALUES ($obj->id_davka,'$dnes','$subj','{$USER->options->email}','$body')");
//     $obj->id_dopis= pdo_insert_id();
//     $ok= query("UPDATE davka SET id_dopis={$obj->id_dopis} WHERE id_davka={$obj->id_davka}");
    $obj->id_dopis= dop_pot2_updP($obj->id_davka,0);
    dop_pot2_infoP($obj,(object)array('alg'=>'infoP'));
  }
  return $obj;
}
# ------------------------------------------------------------------------------------ dop pot2_updP
# ASK změna textu mailu podle vzoru 'bulletiny'
function dop_pot2_updP($id_davka,$id_dopis) {
  global $USER;
  $da= pdo_object("SELECT id_dopis FROM davka WHERE id_davka=$id_davka");
  $dnes= date("Y-m-d");
  $subj= "Pozdrav z Červeného Kostelce";
  $do= pdo_object("SELECT obsah FROM dopis WHERE typ='bulletiny' AND id_davka=0");
  $obsah= pdo_real_escape_string($do->obsah);
  if ( $id_dopis ) {
    query("UPDATE dopis SET obsah='$obsah' WHERE id_dopis=$id_dopis");
  }
  else {
    query("INSERT INTO dopis (id_davka,datum,nazev,odesilatel,obsah,prilohy)
           VALUES ($id_davka,'$dnes','$subj','{$USER->options->email}','$obsah','')");
    $id_dopis= pdo_insert_id();
    query("UPDATE davka SET id_dopis=$id_dopis WHERE id_davka=$id_davka");
  }
  return $id_dopis;
}
# ---------------------------------------------------------------------------------- dop pot2_switch
# ASK - sdružená obsluha rozesílání potvrzení a bulletinů
# provede funkci $par->alg a změní stav objektu $obj
# vrací {id_cis,data,query}
function dop_pot2_switch($obj,$par) {  trace();
                                                debug(array('obj'=>$obj,'par'=>$par),"dop_pot2_switch");
  if ( $obj && $obj->id_davka ) {
    switch ($par->alg) {
    case 'read':
      // nalezení dávky
      $qry= "SELECT * FROM davka WHERE id_davka='{$obj->id_davka}' " ;
      $res= pdo_qry($qry);
      if ( $res && ($d= pdo_fetch_object($res))) {
        $obj->ok= 1;
        foreach($d as $di=>$dv) {
          $obj->$di= $dv;
        }
      }
      break;
    case 'infoP': dop_pot2_infoP($obj,$par); break;
    case 'infoF': dop_pot_infoF($obj,$par); break;
    default: $obj->stav= "N.Y.I."; fce_warning("{$par->alg}:{$obj->stav}"); break;
    }
  }
  else {
    $obj->resume= '';
    $obj->stav= "Vyberte nebo založte rozesílací období";
    fce_warning($obj->stav);
  }
//                                                 debug($obj,"dop_pot_switch end");
  return $obj;
}
# ----------------------------------------------------------------------------------- dop pot2_infoP
# vrátí informace o dávce určené id_davka pro potvrzení a buletiny
# - středisko je určeno položkou clen.stredisko
# alg_par:'firmy' pokud nemají být vráceny informace o bulletinech
# vrací {id_cis,data,query}
function dop_pot2_infoP(&$obj,$par) {  trace();
//                                                 debug(array('obj'=>$obj,'par'=>$par),"dop_pot2_infoP");
  $firmy= $par->alg_par=='firmy' ? 1 : 0;
  $id_davka= $obj->id_davka;
  if ( !$id_davka ) {
    $obj->resume= '';
    $obj->stav= "Vyberte nebo založte rozesílací období";
    fce_warning($obj->stav);
    goto end;
  }
  // nalezení dávky
  $qry= "SELECT * FROM davka WHERE id_davka='$id_davka' " ;
  $res= pdo_qry($qry);
  if ( $res && ($d= pdo_fetch_object($res))) {
    foreach($d as $di=>$dv) {
      $obj->$di= $dv;
    }
  }
  if ( $obj->stav==9 ) {
    // pokud se jedná o skončené rozesílání, jen zobraz staré resume bez přepočtu
  }
  else {
//     // nalezení dopisu
//     $qry= "SELECT * FROM dopis WHERE id_dopis='{$d->id_dopis}' " ;
//     $res= pdo_qry($qry);
//     if ( $res && ($l= pdo_fetch_object($res))) {
//     }
    // nalezení počtu tištěných potvrzení (fyzické+inst.s bull.,jen finanční dary zpusob=1..3)
    // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
    // pokud obsahuje 0 má zvláštní chybový řádek
    $potvr= $zasil= array();
    $clen= 0;
    $_stred= '';
    $potvrzeni= $obj->druh=='r' ? "0,1,2" : "0,1";
    $osoba_in= $firmy ? "0" : "1,3";
    $qry= "SELECT id_clen, COUNT(*) AS _pocet,
             -- IF(d.stredisko<3,d.stredisko,3) AS _stred
             GROUP_CONCAT(DISTINCT IF(d.stredisko<3,d.stredisko,3) ORDER BY d.stredisko SEPARATOR '') as _stred
           FROM dar AS d
           JOIN clen AS c USING(id_clen)
           WHERE LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00'
             AND c.neposilat=0 AND c.psc!=''
             AND c.osoba IN ($osoba_in) AND c.potvrzeni IN ($potvrzeni)
             AND LEFT(d.deleted,1)!='D' AND d.zpusob!=4
             AND d.potvrz_kdy='0000-00-00'
             AND d.castka_kdy BETWEEN '{$d->datum_od}' AND '{$d->datum_do}'
           GROUP BY id_clen/*,IF(d.stredisko<3,d.stredisko,3)*/ ORDER BY id_clen ";
    $res= pdo_qry($qry);
    while ( $res && ($p= pdo_fetch_object($res))) {
      $stred= $p->_stred;
      $zasil[$stred]++;
      $potvr[$stred]++;
      $nsdruz++;
    }
    // korekce $potvr
    $potvr['1']+= $potvr['12']+$potvr['13'];
    $potvr['2']+= $potvr['12']+$potvr['23'];
    $potvr['3']+= $potvr['13']+$potvr['23'];
//                                                         debug($zasil,"počty zásilek");
//                                                         debug($potvr,"počty potvrzení");
    // nalezení počtu rozesílaných bulletinů (fyzické+instituce s bulletinem)
    // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
    // pokud obsahuje 0 má zvláštní chybový řádek
    $bull= array();
    $d_od= (substr($d->datum_od,0,4)-($d->let_zpet)).'-01-01';
    if ( !$firmy ) {
      $qry= "SELECT IF(c.stredisko<3,c.stredisko,3) AS _stred,COUNT(*) AS _pocet,
               -- datum nejnovějšího potvrditelného daru
               MAX(IF(c.potvrzeni IN (0,1),d.castka_kdy,'')) AS _max,
               -- počet nepotvrzených darů ve sledovaném období
               SUM(IF(d.potvrz_kdy='0000-00-00'
                 AND d.castka_kdy BETWEEN '{$d->datum_od}' AND '{$d->datum_do}',1,0)) AS _uzp
             FROM dar AS d
             JOIN clen AS c USING(id_clen)
             WHERE LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00' AND c.osoba=1
               AND c.neposilat=0 AND c.psc!=''
               AND LEFT(d.deleted,1)!='D' AND c.jen_mail=0
               AND d.castka_kdy BETWEEN '$d_od' AND '{$d->datum_do}'
             -- ve sledovaném období nebyl potvrditelný dar nebo byl potvrzen
             GROUP BY id_clen HAVING _max<'{$d->datum_od}' OR _uzp=0
             ORDER BY id_clen ";
      $res= pdo_qry($qry);
      while ( $res && ($p= pdo_fetch_object($res))) {
        $barva= $p->_stred;
        $bull[$barva]++;
      }
                                                         debug($bull,"počty bulletinů");
      $obj->isset= 1;
      // redakce Resume
      $bullx= array();
      foreach($bull as $i=>$n) {
        $bullx[$i==1||$i==2||$i==3||$i==4?$i:3]+= $n;
      }
                                                         debug($bullx,"xpočty bulletinů");
      // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
      $html= '';
      $nbull= array();
      $x= $zasil[12]+$zasil[13]+$zasil[23]+$zasil[123];
      $x1= $zasil[12]+$zasil[13]+$zasil[123];
      $x2= $zasil[12]+$zasil[23]+$zasil[123];
      $x3= $zasil[13]+$zasil[23]+$zasil[123];
      $nbull[1]= $bullx[1]+$zasil[1];
      $nbull[2]= $bullx[2]+$zasil[2];
      $nbull[3]= $bullx[3]+$zasil[3]+$x;
      $nbull[0]= '';
                                                         debug($nbull,"npočty bulletinů");
    }
    // přehled součástí
    $html.= "<b>Přehled rozesílaných součástí</b><br>";
    $html.= "<table class='stat' style='width:300px'>";
    $html.= "<tr><th></th><th>počet potvrzení</th>"
         . ( $firmy ? "" : "<th>počet Bulletinů</th>")."</tr>";
    foreach(array(1=>'Hospic','Žireč','Charita',0=>'? NEURČENÉ') as $i=>$title) {
      $style= $i==0 ? " style='background-color:yellow'" : '';
      $html.= "<tr><th$style>$title</th><td align='right'>{$potvr[$i]}</td>"
           . ( $firmy ? "" : "<td align='right'>{$nbull[$i]}</td>")."</tr>";
    }
    $html.= "</table>";
    // přehled zásilek
    $plus= $firmy ? '' : '+ Bulletin';
    $html.= "<br><b>Přehled rozesílaných zásilek</b><br>";
    $html.= "<table class='stat' style='width:540px'>";
    $html.= "<tr><th></th><th>počet obálek</th><th>počet potvrzení</th>
                 <th>vygenerovaný soubor</th><th>zápis</th></tr>";
    $zapis= $zasil[1] ? ($d->ZPjH?'ano':'ne') : '--';
    $html.= "<tr><th>potvrzení Hospic $plus </th>
                 <td align='right'>{$zasil[1]}</td><td align='right'>{$zasil[1]}</td>
                 <td>{$d->PjH}</td><td>$zapis</td></tr>";
    $zapis= $zasil[2] ? ($d->ZPjZ?'ano':'ne') : '--';
    $html.= "<tr><th>potvrzení Žireč $plus </th>
                 <td align='right'>{$zasil[2]}</td><td align='right'>{$zasil[2]}</td>
                 <td>{$d->PjZ}</td><td>$zapis</td></tr>";
    $zapis= $zasil[3] ? ($d->ZPjC?'ano':'ne') : '--';
    $html.= "<tr><th>potvrzení Charita $plus </th>
                 <td align='right'>{$zasil[3]}</td><td align='right'>{$zasil[3]}</td>
                 <td>{$d->PjC}</td><td>$zapis</td></tr>";
    $zapis= $x ? ($d->ZPs?'ano':'ne') : '--';
    $html.= "<tr><th>směs potvrzení $plus </th>
                 <td align='right'>$x</td><td></td>
                 <td></td><td>$zapis</td></tr>";
    $html.= "<tr><th>směs potvrzení = potvrzení Hospic</th>
                 <td></td><td align='right'>$x1</td>
                 <td>{$d->PsH}</td><td></td></tr>";
    $html.= "<tr><th>směs potvrzení = potvrzení Žireč</th>
                 <td></td><td align='right'>$x2</td>
                 <td>{$d->PsZ}</td><td></td></tr>";
    $html.= "<tr><th>směs potvrzení = potvrzení Charita</th>
                 <td></td><td align='right'>$x3</td>
                 <td>{$d->PsC}</td><td></td></tr>";
    if ( !$firmy ) {
      foreach(array(1=>'BH','BZ','BC',0=>'x') as $i=>$t) {
        $title= array(1=>'Hospic','Žireč','Charita',0=>'? (NEURČENÉ DARY)');
        $style= $i==0 ? " style='background-color:yellow'" : '';
        $zt= "Z$t";
        $html.= "<tr><th$style>jen Bulletin {$title[$i]}</th>
                     <td align='right'>{$bull[$i]}</td><td></td>
                     <td>{$d->$t}</td><td>".($t=='x'?'':($d->$zt?'ano':'ne'))."</td></tr>";
      }
    }
    $html.= "</table>";
    $state= dop_pot_hist_can($obj);
    if ( $state->_all_gen ) {
      $html.= "<br>Generování souborů je skončeno, ";
      if ( $state->_all_back ) {
        // vše hotovo - resume bude uloženo pro vyzvednutí jako text
        $html.= "zápisy do historie byly provedeny";
        if ( $obj->stav==1 ) {
          $resume= pdo_real_escape_string("
            <div style='background-color:silver;padding:10px'>$html</div>
          ");
          $qry= "UPDATE davka SET stav=9,resume='$resume'
                 WHERE id_davka={$obj->id_davka} ";
          $res= pdo_qry($qry);
          if ( !$res ) fce_error("selhal zápis do stavu rozesílání");
        }
      }
      else {
        if ( $state->_any_back ) {
          $html.= "je třeba dokončit všechny zápisy do historie";
        }
        else {
          $html.= "lze začít se zápisy do historie";
        }
      }
    }
    else {
      $html.= "<br>Generování souborů ještě nebylo dokončeno ...";
    }
    $obj->resume= $html;
  }
end:
  return;
}
/**# ========================================================================================> FIRMY */
# ---------------------------------------------------------------------------------- dop pot_fy_cond
# ASK - určení filtrovací podmínky obeslání firem
# vrací {cond,_max}
# !!! při změně je třeba modifikovat dop_pot_infoF
function dop_pot_fy_cond($obj,$par) {  #trace();
                                                                debug($obj,'dop_pot_fy_cond');
  $letos= substr($obj->datum_do,-4);
  $od= $obj->datum_od;
  $do= $obj->datum_do;
  $nw= $obj->datum_nw;
  $vanocni= $obj->vanocni;
  $cond= "LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00' "             // dárci: živí nesmazaní
       . "AND c.neposilat=0 AND c.psc!='' "                             // obeslatelní
       . "AND c.osoba IN (0) "                                          // firmy
       . ($vanocni ? ' ' : "AND c.jenvanocni=0 ")                       // nejsou vánoce: vynechat jenvanocni
       . "AND LEFT(d.deleted,1)!='D'  ";                                // dary: nesmazané
  $cond.= $par->bull && !$par->neobeslane || $par->neurcene
       ? "AND d.castka_kdy BETWEEN '$od' AND '$do' " : '';      // posíláme bulletin ... či kontrola
  // podmínka do HAVING
  $having= $par->bull ? (                     // posíláme bulletin
            $par->letos                         // dar v letošním 1.pololetí?
              ? " AND _max>='$nw'"              // je
              : " AND _max<'$nw'" ) :           // není
          ( $par->x
            ?  " AND (_max<'$od' OR _min>'$do')"    // neposíláme nic
            :  " AND _max>='$od' " );               // ... jen zobrazujeme s nedefinovaným příjemcem
    return (object)array('cond'=>$cond,'letos'=>$having);
}
# ----------------------------------------------------------------------------------- dop pot_switch
# ASK - sdružená obsluha rozesílání potvrzení a bulletinů
# provede funkci $par->alg a změní stav objektu $obj
# vrací {id_cis,data,query}
function dop_pot_switch($obj,$par) {  trace();
//                                                 debug(array('obj'=>$obj,'par'=>$par),"dop_pot_switch");
  if ( $obj && $obj->id_davka ) {
    switch ($par->alg) {
    case 'read':
      // nalezení dávky
      $qry= "SELECT * FROM davka WHERE id_davka='{$obj->id_davka}' " ;
      $res= pdo_qry($qry);
      if ( $res && ($d= pdo_fetch_object($res))) {
        foreach($d as $di=>$dv) {
          $obj->$di= $dv;
        }
      }
      break;
    case 'infoP': dop_pot_infoP($obj,$par); break;
    case 'infoF': dop_pot_infoF($obj,$par); break;
//     case 'potv': dop_pot_potv($obj,$par); break;
//     case 'bull': dop_pot_bull($obj,$par); break;
    }
  }
  else {
    $obj->stav= "Vyberte nebo založte rozesílací období";
  }
//                                                 debug($obj,"dop_pot_switch end");
  return $obj;
}
# ------------------------------------------------------------------------------------ dop pot_infoF
# vrátí informace o dávce určené id_davka pro potvrzení a buletiny
# vrací {id_cis,data,query}
function dop_pot_infoF(&$obj,$par) {  trace();
  $id_davka= $obj->id_davka;
  // nalezení dávky
  $qry= "SELECT * FROM davka WHERE id_davka='$id_davka' " ;
  $res= pdo_qry($qry);
  if ( $res && ($d= pdo_fetch_object($res))) {
    $obj->ok= 1;
    foreach($d as $di=>$dv) {
      $obj->$di= $dv;
    }
  }
  if ( $obj->stav==9 ) {
    // pokud se jedná o skončené rozesílání, jen zobraz staré resume bez přepočtu
  }
  else {
    // nalezení počtu rozesílaných bulletinů (fyzické+instituce s bulletinem)
    // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
    // pokud obsahuje 0 má zvláštní chybový řádek
    //   jcd.dcleni._max.set_attrib('expr','MAX(d.castka_kdy)');
    //   jcd.dcleni._min.set_attrib('expr','MIN(d.castka_kdy)');
    //   conds.set(ask('dop_pot_fy_cond',the_davkaF.get,par));
    $bull= array();
    $qry= "SELECT GROUP_CONCAT(DISTINCT IF(d.stredisko<3,d.stredisko,3) ORDER BY d.stredisko) AS _stred,
             COUNT(*) AS _pocet, MAX(d.castka_kdy) AS _max, id_clen
           FROM dar AS d
           JOIN clen AS c USING(id_clen)
           WHERE LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00' AND c.osoba IN (0)
             AND c.neposilat=0 AND c.psc!=''
             AND LEFT(d.deleted,1)!='D'
             AND d.castka_kdy BETWEEN '{$d->datum_od}' AND '{$d->datum_do}'
           GROUP BY id_clen ORDER BY id_clen ";
    $res= pdo_qry($qry);
    while ( $res && ($p= pdo_fetch_object($res))) {
      $cerstve= $p->_max>=$d->datum_nw ? 1 : 0;
      $barva= $p->_stred=="1,2,3" ? 3 : (
              $p->_stred=="1,2"   ? 3 : (
              $p->_stred=="1,3"   ? 1 : (
              $p->_stred[0]=="0"  ? 4 : (
              $p->_stred=="2,3"   ? 2 : $p->_stred ))));
      $bull[$barva==4 ? 0 : $cerstve][$barva]++;
    }
    // přehled zásilek
    $html.= "<br><b>Přehled rozesílaných zásilek</b><br>";
    $html.= "<table class='stat' style='width:540px'>";
    $html.= "<tr><th></th><th>zásilek</th>
                 <th>vygenerovaný soubor</th><th>zápis</th></tr>";
    foreach(array(1=>'H','Z','C','x') as $i=>$t) {
      $title= array(1=>'Hospic','Žireč','Charita','? (NEURČENÉ DARY)');
      $style= $i==4 ? " style='background-color:yellow'" : '';
      $bt= "B$t";
      $zt= "ZB$t";
      $post= $i==4 ? '' : "- staré dary";
      $html.= "<tr><th$style>Bulletin {$title[$i]} $post</th>
                   <td align='right'>{$bull[0][$i]}</td>
                   <td>{$d->$bt}</td><td>".($t=='x'?'':($d->$zt?'ano':'ne'))."</td></tr>";
      if ( $i!=4 ) {
        $bt= "Pj$t";
        $zt= "ZPj$t";
        $html.= "<tr><th$style>Bulletin {$title[$i]} - čerstvé dary</th>
                     <td align='right'>{$bull[1][$i]}</td>
                     <td>{$d->$bt}</td><td>".($t=='x'?'':($d->$zt?'ano':'ne'))."</td></tr>";
      }
    }
    $html.= "</table>";
    $state= dop_pot_hist_canF($obj);
    if ( $state->_all_gen ) {
      $html.= "<br>Generování souborů je skončeno, ";
      if ( $state->_all_back ) {
        // vše hotovo - resume bude uloženo pro vyzvednutí jako text
        $html.= "zápisy do historie byly provedeny";
        if ( $obj->stav==1 ) {
          $resume= pdo_real_escape_string("
            <div style='background-color:silver;padding:10px'>$html</div>
          ");
          $qry= "UPDATE davka SET stav=9,resume='$resume'
                 WHERE id_davka={$obj->id_davka} ";
          $res= pdo_qry($qry);
          if ( !$res ) fce_error("selhal zápis do stavu rozesílání");
        }
      }
      else {
        if ( $state->_any_back ) {
          $html.= "je třeba dokončit všechny zápisy do historie";
        }
        else {
          $html.= "lze začít se zápisy do historie";
        }
      }
    }
    else {
      $html.= "<br>Generování souborů ještě nebylo dokončeno ...";
    }
    $obj->resume= $html;
  }
//                                                 debug($obj,"dop_pot_infoF end");
}
# ------------------------------------------------------------------------------------ dop pot_infoP
# vrátí informace o dávce určené id_davka pro potvrzení a buletiny
# vrací {id_cis,data,query}
function dop_pot_infoP(&$obj,$par) {  trace();
  $id_davka= $obj->id_davka;
  // nalezení dávky
  $qry= "SELECT * FROM davka WHERE id_davka='$id_davka' " ;
  $res= pdo_qry($qry);
  if ( $res && ($d= pdo_fetch_object($res))) {
    foreach($d as $di=>$dv) {
      $obj->$di= $dv;
    }
  }
  if ( $obj->stav==9 ) {
    // pokud se jedná o skončené rozesílání, jen zobraz staré resume bez přepočtu
  }
  else {
//     // nalezení dopisu
//     $qry= "SELECT * FROM dopis WHERE id_dopis='{$d->id_dopis}' " ;
//     $res= pdo_qry($qry);
//     if ( $res && ($l= pdo_fetch_object($res))) {
//     }
    // nalezení počtu tištěných potvrzení (fyzické+inst.s bull.,jen finanční dary zpusob=1..3)
    // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
    // pokud obsahuje 0 má zvláštní chybový řádek
    $potvr= $zasil= array();
    $clen= 0;
    $nsdruz= $nclen= 0;
    $_stred= '';
    $potvrzeni= $obj->druh=='r' ? "0,1,2" : "0,1";
    //$potvrzeni= "0,1";
    $qry= "SELECT id_clen,IF(d.stredisko<3,d.stredisko,3) AS _stred, COUNT(*) AS _pocet
           FROM dar AS d
           JOIN clen AS c USING(id_clen)
           WHERE LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00'
             AND c.neposilat=0 AND c.psc!=''
             AND c.osoba IN (1,3) AND c.potvrzeni IN ($potvrzeni)
             AND LEFT(d.deleted,1)!='D' AND d.zpusob!=4
             AND d.potvrz_kdy='0000-00-00'
             AND d.castka_kdy BETWEEN '{$d->datum_od}' AND '{$d->datum_do}'
           GROUP BY id_clen,IF(d.stredisko<3,d.stredisko,3) ORDER BY id_clen ";
    $res= pdo_qry($qry);
    while ( $res && ($p= pdo_fetch_object($res))) {
      $stred= $p->_stred;
      if ( $clen && $clen!=$p->id_clen ) {
        // uzávěrka zásilek člena
        $nsdruz++;
        $zasil[$_stred]++;
        $_stred= $stred;
      }
      if ( $clen==$p->id_clen ) {
        $nsdruz++;
        $_stred.= $stred;
      }
      $clen= $p->id_clen;
      $potvr[$stred]++;
    }
                                                         debug($zasil,"$nclen,$nsdruz");
  //                                                         debug($potvr,"počty potvrzení");
    // nalezení počtu rozesílaných bulletinů (fyzické+instituce s bulletinem)
    // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
    // pokud obsahuje 0 má zvláštní chybový řádek
    $darci_od= (substr($d->datum_od,0,4)-3)."-01-01";
    $bull= array();
//     $qry= "SELECT GROUP_CONCAT(DISTINCT IF(d.stredisko<3,d.stredisko,3) ORDER BY d.stredisko) AS _stred,
//              COUNT(*) AS _pocet,
//              MAX(IF(c.potvrzeni IN (0,1),d.castka_kdy,'')) AS _max
//            FROM dar AS d
//            JOIN clen AS c USING(id_clen)
//            WHERE LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00' AND c.osoba=1
//              AND c.neposilat=0 AND c.nedorucitelne=0 AND c.psc!=''
//              AND LEFT(d.deleted,1)!='D'
//              AND d.castka_kdy BETWEEN '$darci_od' AND '{$d->datum_do}'
//            GROUP BY id_clen HAVING _max<'{$d->datum_od}'
//            ORDER BY id_clen ";
    $qry= "SELECT GROUP_CONCAT(DISTINCT IF(d.stredisko<3,d.stredisko,3) ORDER BY d.stredisko) AS _stred,
             COUNT(*) AS _pocet,
             MAX(IF(c.potvrzeni IN (0,1),d.castka_kdy,'')) AS _max,
             SUM(IF(d.potvrz_kdy='0000-00-00' AND d.castka_kdy BETWEEN '{$d->datum_od}' AND '{$d->datum_do}',1,0)) AS _uzp
           FROM dar AS d
           JOIN clen AS c USING(id_clen)
           WHERE LEFT(c.deleted,1)!='D' AND c.umrti='0000-00-00' AND c.osoba=1
             AND c.neposilat=0 AND c.psc!=''
             AND LEFT(d.deleted,1)!='D'
             AND d.castka_kdy BETWEEN '$darci_od' AND '{$d->datum_do}'
           GROUP BY id_clen HAVING _max<'{$d->datum_od}' OR _uzp=0
           ORDER BY id_clen ";
    $res= pdo_qry($qry);
    while ( $res && ($p= pdo_fetch_object($res))) {
      $barva= $p->_stred=="1,2,3" ? 3 : (
              $p->_stred=="1,2"   ? 3 : (
              $p->_stred=="1,3"   ? 1 : (
              $p->_stred[0]=="0"  ? 4 : (
              $p->_stred=="2,3"   ? 2 : $p->_stred ))));
      $bull[$barva]++;
    }
  //                                                         debug($bull,"počty bulletinů");
    $obj->isset= 1;
    // redakce Resume
    $bullx= array();
    foreach($bull as $i=>$n) {
      $bullx[$i==1||$i==2||$i==3||$i==4?$i:3]+= $n;
    }
  //                                                         debug($bullx,"xpočty bulletinů");
    // přepočet středisek 1=Hospic, 2=Žíreč, 3=Charita tj. vše ostatní
    $html= '';
    $nbull= array();
    $x= $zasil[12]+$zasil[13]+$zasil[23]+$zasil[123];
    $x1= $zasil[12]+$zasil[13]+$zasil[123];
    $x2= $zasil[12]+$zasil[23]+$zasil[123];
    $x3= $zasil[13]+$zasil[23]+$zasil[123];
    $nbull[1]= $bullx[1]+$zasil[1];
    $nbull[2]= $bullx[2]+$zasil[2];
    $nbull[3]= $bullx[3]+$zasil[3]+$x;
    $nbull[0]= '';
    // přehled součástí
    $html.= "<b>Přehled rozesílaných součástí</b><br>";
    $html.= "<table class='stat' style='width:300px'>";
    $html.= "<tr><th></th><th>počet potvrzení</th><th>počet Bulletinů</th></tr>";
    foreach(array(1=>'Hospic','Žireč','Charita',0=>'? NEURČENÉ') as $i=>$title) {
      $style= $i==0 ? " style='background-color:yellow'" : '';
      $html.= "<tr><th$style>$title</th><td align='right'>{$potvr[$i]}</td><td align='right'>{$nbull[$i]}</td></tr>";
    }
    $html.= "</table>";
    // přehled zásilek
    $html.= "<br><b>Přehled rozesílaných zásilek</b><br>";
    $html.= "<table class='stat' style='width:540px'>";
    $html.= "<tr><th></th><th>počet obálek</th><th>počet potvrzení</th>
                 <th>vygenerovaný soubor</th><th>zápis</th></tr>";
    $html.= "<tr><th>potvrzení Hospic + Bulletin Hospic</th>
                 <td align='right'>{$zasil[1]}</td><td align='right'>{$zasil[1]}</td>
                 <td>{$d->PjH}</td><td>".($d->ZPjH?'ano':'ne')."</td></tr>";
    $html.= "<tr><th>potvrzení Žireč + Bulletin Žireč</th>
                 <td align='right'>{$zasil[2]}</td><td align='right'>{$zasil[2]}</td>
                 <td>{$d->PjZ}</td><td>".($d->ZPjZ?'ano':'ne')."</td></tr>";
    $html.= "<tr><th>potvrzení Charita + Bulletin Charita</th>
                 <td align='right'>{$zasil[3]}</td><td align='right'>{$zasil[3]}</td>
                 <td>{$d->PjC}</td><td>".($d->ZPjC?'ano':'ne')."</td></tr>";
    $html.= "<tr><th>směs potvrzení + Bulletin Charita</th>
                 <td align='right'>$x</td><td></td>
                 <td></td><td>".($d->ZPs?'ano':'ne')."</td></tr>";
    $html.= "<tr><th>směs potvrzení = potvrzení Hospic</th>
                 <td></td><td align='right'>$x1</td>
                 <td>{$d->PsH}</td><td></td></tr>";
    $html.= "<tr><th>směs potvrzení = potvrzení Žireč</th>
                 <td></td><td align='right'>$x2</td>
                 <td>{$d->PsZ}</td><td></td></tr>";
    $html.= "<tr><th>směs potvrzení = potvrzení Charita</th>
                 <td></td><td align='right'>$x3</td>
                 <td>{$d->PsC}</td><td></td></tr>";
    foreach(array(1=>'BH','BZ','BC','x') as $i=>$t) {
      $title= array(1=>'Hospic','Žireč','Charita','? (NEURČENÉ DARY)');
      $style= $i==4 ? " style='background-color:yellow'" : '';
      $zt= "Z$t";
      $html.= "<tr><th$style>jen Bulletin {$title[$i]}</th>
                   <td align='right'>{$bullx[$i]}</td><td></td>
                   <td>{$d->$t}</td><td>".($t=='x'?'':($d->$zt?'ano':'ne'))."</td></tr>";
    }
    $html.= "</table>";
    $state= dop_pot_hist_can($obj);
    if ( $state->_all_gen ) {
      $html.= "<br>Generování souborů je skončeno, ";
      if ( $state->_all_back ) {
        // vše hotovo - resume bude uloženo pro vyzvednutí jako text
        $html.= "zápisy do historie byly provedeny";
        if ( $obj->stav==1 ) {
          $resume= pdo_real_escape_string("
            <div style='background-color:silver;padding:10px'>$html</div>
          ");
          $qry= "UPDATE davka SET stav=9,resume='$resume'
                 WHERE id_davka={$obj->id_davka} ";
          $res= pdo_qry($qry);
          if ( !$res ) fce_error("selhal zápis do stavu rozesílání");
        }
      }
      else {
        if ( $state->_any_back ) {
          $html.= "je třeba dokončit všechny zápisy do historie";
        }
        else {
          $html.= "lze začít se zápisy do historie";
        }
      }
    }
    else {
      $html.= "<br>Generování souborů ještě nebylo dokončeno ...";
    }
    $obj->resume= $html;
  }
}
// # ------------------------------------------------------------------------------------- dop pot_bull
// # vygeneruje adresní štítky pro bulletin podle $par->bull
// # bull= 1..3   -- pro příjemce bulletinu n
// function dop_pot_bull(&$obj,$par) {  #trace();
// }
/** ===================================================================================> SQL EXPORTY */
# ----------------------------------------------------------------------------------- dop sql_struct
# ASK - zobrazení struktury tabulky
function dop_sql_struct($tab,$all=1) {  #trace();
  $html= '';
  $row= 0;
  $max_note= 200;
//   query("SET group_concat_max_len=1000000");
  $res= @pdo_query("SHOW FULL COLUMNS FROM $tab");
  if ( $res ) {
    $db= sql_query("SHOW TABLE STATUS LIKE '$tab'");
    $html.= $db->Comment ? "{$db->Comment}<br><br>" : '';
    $html.= "<table class='stat' style='width:100%'>";
    $joins= 0;
    while ( $res && ($c= pdo_fetch_object($res)) ) {
      if ( !$row ) {
        // záhlaví tabulky
        $html.= "<tr><th></th><th>Sloupec</th><th>Typ</th><th>Komentář</th></tr>";
      }
      // řádek tabulky
      $key= $c->Key ? '*' : '';
      $note= $c->Comment;
      if ( $all || $note[0]!='-' ) {
        if ( $note[0]=='#' ) {
          // číselníková položka
          $joins++;
          $strip= false;
          $zkratka= substr($note,1);
          if ( strstr($note,'...') ) {
            $zkratka= trim(str_replace('...','',$zkratka));
            $strip= true;
          }
          $note= "číselník <b>'$zkratka'</b> <i>";
          $note.= select("popis","_cis","druh='_meta_' AND zkratka='$zkratka'");
          $note.= "</i> (";
          // nelze použít GROUP_CONCAT kvůli omezení v ORDER
          $del= '';
          $resd= pdo_query("SELECT * FROM _cis WHERE druh='$zkratka' ORDER BY LPAD(5,'0',data)");
          while ( (!$strip || strlen($note)<$max_note) && $resd && ($d= pdo_fetch_object($resd))){
            if ( $d->hodnota != '---' ) {
              $note.= "$del{$d->data}:{$d->hodnota}";
              $del= ", ";
            }
          }
          if ( $strip && strlen($note)>$max_note )
            $note= substr($note,0,$max_note).' ...';
          $note.= ")";
        }
        $html.= "<tr><td>$key</td><td>{$c->Field}</td><td>{$c->Type}</td><td>$note</td></tr>";
      }
      $row++;
    }
    $html.= "</table>";
    $html.= "<br>Hvězdička označuje sloupec s indexem<br>";
    if ( $joins ) {
      $html.= "<br>K hodnotám položky 'p' označené v komentáři jako číselník 'x' se lze dostat připojením
      <pre>      SELECT ... x.hodnota ...
      LEFT JOIN _cis AS x ON druh='x' AND data=p</pre>";
    }
  }
  return $html;
}
# ---------------------------------------------------------------------------- dop sql_export_fields
# ASK - export polí tabulky splňujících cond do CSV
function dop_sql_export_fields($tab,$flds,$cond) {
  global $ezer_path_docs;
  $html= "Export polí '$fields' tabulky '$tab' ";
  $fname= "$tab.csv";
  $fpath= "$ezer_path_docs/$fname";
  $href=  "docs/$fname";
  $f= @fopen($fpath,'w');
  if ( !$f ) fce_error("soubor '$fpath' nelze vytvořit");
  fputs($f,chr(0xEF).chr(0xBB).chr(0xBF));
  fputcsv($f,explode(',',$flds),';','"');
  // vyzvednutí polí, indexujeme podle prvního
  list($first)= explode(',',$flds);
  $LIMIT= '';
//  $LIMIT= "LIMIT 1";
  $res= pdo_qry("SELECT $flds FROM $tab WHERE $cond ORDER BY $first $LIMIT");
  while ( $res && ($row= pdo_fetch_row($res)) ) {
    fputcsv($f,$row,';','"');
  }
  fclose($f);
  $html.= " je připraven ke stažení v souboru <a href='$href'>$fname</a>";  
  return $html;
}
# -------------------------------------------------------------------------------------- dop sql_new
# ASK - vytvoření SQL dotazů pro definici exportů
# vrací {id_cis,data,qry}
function dop_sql_new() {  #trace();
  $id= select("MAX(0+id_cis)","_cis","druh='export_sql'");
  $data= select("MAX(0+data)","_cis","druh='export_sql'");
  $result= (object)array('id'=>$id+1, 'data'=>$data+1,'qry'=>"SELECT ...");
  return $result;
}
# ----------------------------------------------------------------------------------- dop sql_export
# ASK - testování SQL dotazů pro definici exportů
# $export = '' pro test | csv | xls
# $name   = název dotazu, použitý pro jméno výstupního souboru
function dop_sql_export($qry,$export='',$name='') {  trace();
  $html= '';
  $qry= trim($qry);
  // povolíme pouze datazy začínající SELECT
  if ( substr($qry,0,6)=="SELECT" ) try {
    $time_start= getmicrotime();
    $res= @pdo_query($qry);
    $time= round(getmicrotime() - $time_start,4);
    if ( !$res ) {
      $html.= "<span style='color:darkred'>ERROR ".pdo_error()."</span>";
    }
    else {
      $nmax= 15;
      $num= pdo_num_rows($res);
      $html.= "dotazem bylo nalezeno <b>$num</b> záznamů, nalezených během $time ms, ";
      $html.= $num>$nmax ? "následuje prvních $nmax záznamů" : "následují všechny záznamy";
      if ( $export ) {
        $fname= ( $name ? utf2ascii($name) : 'export').date("-Ymd_Hi");
        $fname_ext= "$fname.$export";
        $html.= "<br><br>Záznamy byly uloženy do souboru <a href='docs/$fname_ext'>$fname_ext</a>";
      }
      $html.= "<br><br><table class='stat' style='width:100%'>";
      $n= $nmax;
      $row= 0;
      $clmns= '';
      $del= '';
      while ( ($n || $export) && ($c= pdo_fetch_object($res)) ) {
        if ( !$row ) {
          // záhlaví tabulky
          $html.= "<tr>";
          foreach($c as $clmn => $val) {
            $html.= "<th>$clmn</th>";
            $clmns.= "$del$clmn";
            $del= ',';
          }
          $html.= "</tr>";
          if ( $export ) {
            $par= (object)array('file'=>$fname,'type'=>$export);
            export_head($par,$clmns);
          }
        }
        if ( $n>0 ) {
          // řádek tabulky
          $html.= "<tr>";
          $vals= array();
          foreach($c as $clmn => $val) {
            $html.= "<td>$val</td>";
            $vals[]= $val;
          }
          $html.= "</tr>";
          $n--;
        }
        if ( $export ) {
          // řádek exportu
          $vals= array();
          foreach($c as $clmn => $val) {
            $vals[]= $val;
          }
          export_row($vals);
        }
        $row++;
      }
      $html.= "</table>";
      $html.= $num>$nmax ? "..." : "";
      if ( $export ) {
        $html.= export_tail();
      }
    }
  }
  catch (Exception $e) { $html.= "<span style='color:red'>FATAL ".pdo_error()."</span>";  }
  else {
    $html= "Na této kartě je možné používat pouze dotazy začínající klíčovým slovem SELECT";
    fce_warning($html);
    $html= "<span style='color:darkred'>ERROR: $html</span>";
  }
  return $html;
}
// /** ============================================================================ DAVKOVÉ SQL - staré */
// # ---------------------------------------------------------------------------------- dop pot_sql_new
// # ASK - vytvoření SQL dotazů pro definici mailů
// # vrací {id_cis,data,query}
// function dop_pot_sql_new() {  #trace();
//   $id= select("max(id_cis)","_cis","druh='dop_pot_sql'");
//   $data= select("max(data)","_cis","druh='dop_pot_sql'");
//   $result= (object)array(
//     'id'=>$id+1, 'data'=>$data+1,
//     'qry'=>"podmínka s c.položka_CLEN a d.položka_DAR");
//   return $result;
// }
// # ---------------------------------------------------------------------------------- dop pot_sql_try
// # ASK - testování SQL dotazů pro definici mailů
// function dop_pot_sql_try($qry) {  trace();
//   $html= '';
//   try {
//     $time_start= getmicrotime();
//     $res= @pdo_query($qry);
//     $time= round(getmicrotime() - $time_start,4);
//     if ( !$res ) {
//       $html.= "<span style='color:darkred'>ERROR ".pdo_error()."</span>";
//     }
//     else {
//       $nmax= 15;
//       $num= pdo_num_rows($res);
//       $html.= "výběr obsahuje <b>$num</b> emailových adresátů, nalezených během $time ms, ";
//       $html.= $num>$nmax ? "následuje prvních $nmax adresátů" : "následují všichni adresáti";
//       $html.= "<br><br><table>";
//       $n= $nmax;
//       while ( $n && ($c= pdo_fetch_object($res)) ) {
//         $html.= "<tr><td>{$c->email}</td><td>{$c->telefon}</td><td>{$c->prijmeni} {$c->jmeno}
//                  </td><td>{$c->ulice} {$c->psc} {$c->obec}</td></tr>";
//         $n--;
//       }
//       $html.= "</table>";
//       $html.= $num>$nmax ? "..." : "";
//     }
//   }
//   catch (Exception $e) { $html.= "<span style='color:red'>FATAL ".pdo_error()."</span>";  }
//   return $html;
// }
// # -------------------------------------------------------------------------------------- dop pdf_one
// # ASK
// # vytištění sumárního potvrzení za vybrané dary
// function dop_pdf_one($typ,$id_clen,$ids_dar,$kdy) { trace();
// }
/** **********************************************************************************==> JEDNOTLIVE */
# ----------------------------------------------------------------------------------- dop gener_load
# ASK
# vyzvednutí již vygenerovaného dopisu z DAR
function dop_gener_load($typ,$id_dar) { trace();
  $x_kdy= "{$typ}_kdy"; $x_kdo= "{$typ}_kdo"; $x_txt= "{$typ}_txt";
  $qry= "SELECT id_clen,$x_txt,$x_kdo,$x_kdy FROM dar WHERE id_dar=$id_dar";
  $res= pdo_qry($qry,1);
  $d= pdo_fetch_object($res);
  $dop= (object)array();
  $dop->typ= $typ;
  $dop->kdy= sql_date1($d->$x_kdy,0,'. ');
  $dop->kdo= $d->$x_kdo;
  switch ($typ) {
  case 'zadost':
  case 'smlouva':
  case 'potvrz':
    $dop->id_clen= $d->id_clen;
    $dop->id_dar= $id_dar;
    $res= dop_gener_text($typ,$d->id_clen,$id_dar,$dop->kdy);
    $dop->dopis= $res->dopis;
    $dop->dopis->text= $dop->text= $d->$x_txt;
    break;
  }
//                                                         debug($dop,"dop_gener_load/$typ/{$d->$x_kdy}");
  return $dop;
}
# ----------------------------------------------------------------------------------- dop gener_save
# ASK
# zápis vygenerovaného dopisu (id_dar,prefix,kdy,kdo,text) do DAR
function dop_gener_save($typ,$dop) { trace();
  global $USER;
  $id_dar= $dop->id_dar;
  $x_kdy= "{$typ}_kdy"; $x_kdo= "{$typ}_kdo"; $x_txt= "{$typ}_txt";
  $txt= pdo_real_escape_string($dop->text);
  $kdy= sql_date1($dop->kdy,1);
//  $zmena_kdo= $USER->abbr;
//  $zmena_kdy= date('Y-m-d H:i:s');
  $qry= "UPDATE dar SET $x_txt='$txt',$x_kdy='$kdy',$x_kdo='{$dop->kdo}' WHERE id_dar=$id_dar";
//  $qry= "UPDATE dar SET $x_txt='$txt',$x_kdy='$kdy',$x_kdo='{$dop->kdo}',
//         zmena_kdo='$zmena_kdo',zmena_kdy='$zmena_kdy' WHERE id_dar=$id_dar";
  $res= pdo_qry($qry);
  return $res;
}
# ------------------------------------------------------------------------------------ dop gener_new
# ASK
#  pro $typ=='potvrz'
#  - jako adresa se použije (v tomto pořadí):
#    1. adresa kontaktu
#    2. adresa z potvrzení_na
#    3. číslo účtu
function dop_gener_new($typ,$id_clen,$id_dar,$dne,$dne_changed) { trace();
  global $USER;
  $dne= $dne ? $dne : date('j. n. Y');
  $dop= (object)array();
  $dop->typ= $typ;
  $dop->kdy= $dne;
  $dop->kdy_ch= $dne_changed;
  $dop->kdo= $USER->options->vyrizuje;
  switch ($typ) {
  case 'zadost':
  case 'smlouva':
  case 'potvrz':
    $dop->id_clen= $id_clen;
    $dop->id_dar= $id_dar;
    $res= dop_gener_text($typ,$id_clen,$id_dar,$dne);
    $dop->text= $res->_error ? $res->_error : $res->dopis->text;
    $dop->dopis= $res->dopis;
    break;
  }
//                                                         debug($dop,"dop_gener_new");
  return $dop;
}
# ------------------------------------------------------------------------------------ dop gener_pdf
# ASK
# vytvoření připraveného dopisu se šablonou pomocí TCPDF
# $c - kontext vytvořený funkcí dop_subst
function dop_gener_pdf($oprava,$dop) { trace();
  global $ezer_path_root;
//                                                         debug($dop,'dop');
  $text= $dop->dopis;
  $text->text= $oprava;
  $texty= array($text);
  $fname= "docs/".date('ymd_Hi_')."{$dop->typ}.pdf";
  $fpath= "$ezer_path_root/$fname";
//                                                         debug($texty,'texty');
//   $dop_rozesilani= array('vyrizuje'=>$dop->kdo,'dne'=>$dop->kdy);
  tc_dopisy($texty,$fpath,'rozesilani','_user',$listu,'D',$dop->kdy);
  return $fname;
}
# ----------------------------------------------------------------------------------- dop gener_text
# ASK
# parametrizace dopisu se šablonou pomocí TCPDF
# hodnoty proměnných se spočítají přímo z CLEN, DAR přitom se vynechají proměnné ze seznamu $vynech
# {dne} se použije z parametru
# vrátí se JSON výsledek s
#   _error - 0 | text chyby
#   _html  - ve tvaru pro MenuLeft s případným textem chyby
#   _href  - html odkaz na soubor
#  pro $typ=='potvrz' bude místo případně chybějící poštovní adresy uvedeno číslo účtu
function dop_gener_text($typ,$id_clen,$ids_dar,$dne=null) { trace();
  $id_davka= 1;
  $vyrizuje= '_user';    // jméno do proměnné šablony {vyrizuje} se má vzít z tabulky _user.options.vyrizuje
  $dne= $dne ? $dne : date('j. n. Y');
  $html= '';
  $texty= array();
  $result= (object)array('_error'=>0,'_href'=>'','_text'=>'');
  $error= 0;
  if ( $id_clen ) {
    try {
      $qry= "SELECT zpusob FROM dar WHERE id_dar=$ids_dar";
      $res= pdo_qry($qry,1);
      $d= pdo_fetch_object($res);
      $dopis_typ= $d->zpusob==4 ? (
         $typ=='zadost'  ? 'Zv' : (
         $typ=='smlouva' ? 'Sv' : (
         $typ=='potvrz'  ? 'Pv' : '??')))
        : 'Pf';
      // nalezení dopisu
      $qry= "SELECT * FROM dopis WHERE typ='$dopis_typ' AND id_davka=$id_davka ";
      $res= pdo_qry($qry,1,null,1);
      $d= pdo_fetch_object($res);
      $dopis_druh= $d->druh;
      // výpočet proměnných použitých v dopisu
      $is_vars= preg_match_all("/[\{]([^}]+)[}]/",$d->obsah,$list);
      $vars= $list[1];
//       // zohlednění nastavené $adresa_darce
//       if ( $adresa_darce && $tc->darce ) {
//         $darce= explode(';',$tc->darce);
//         // ... jméno obvyklého dárce
//         $x->jmeno_darce= $darce[0];
//     //                                                         debug($darce,"dárce:{$x->jmeno_darce}");
//         if ( count($darce)!=1 ) {
//           $x->jmeno_postovni2= $x->jmeno_darce;
//           $x->adresa_postovni= trim($darce[1])."<br/>".trim($darce[2]);
//           if ( count($darce)!=3 )
//             fce_error("adresa dárce u člena č.$K má chybný formát");
//         }
//       }
      // přidání polí pro adresu - viz definice u funkce dop_gener_new
      $var_array= array_merge($vars,$dopis_druh=='D'
        ? array('cislo','ico','jmeno_postovni','adresa_postovni')
        : array('cislo','ico','jmeno_darce',$typ=='potvrz' ? 'adresa_nebo_ucet' : 'adresa_clena')
        );
//       $var_clen= array_diff($var_array,array('dne'));
      $parss= dop_vars($id_clen,$ids_dar,$var_array,$typ);
      // předání k tisku
      $result= dop_pdf_id($d,$var_array,$parss,$id_clen,$ids_dar,$dne,'',$vyrizuje,$stran);
    }
    catch (Exception $e) {
      $html.= nl2br("Chyba: ".$e->getMessage()." na ř.".$e->getLine());
      display($e); $error= 1;
      $result->_error= ' '.$e->getMessage();
    }
  }
  else
    $html= "Prázdný výběr!";
  $result->_html= $html;
//                                                         debug($result,'dop_subst');
  return $result;
}
// # ------------------------------------------------------------------------------------- dop gener
// # ASK
// # vytvoření připraveného dopisu se šablonou pomocí TCPDF
// # $c - kontext vytvořený funkcí dop_subst
// function dop_gener($oprava,$c) { trace();
//   $text= $c->dopis;
//   $text->text= $oprava;
//   $texty= array($text);
// //                                                         debug($c,'c');
// //                                                         debug($texty,'texty');
//   tc_dopisy($texty,$c->fname,$c->pro,$c->vyrizuje,$c->listu,$c->druh);
//   return 1;
// }
# ----------------------------------------------------------------------------------------- dop vars
# načte data dopisu pro daného člena
# $ids - pole klíčů členů, $vas - pole jmen proměnných, $result - pole objektů hodnot proměnných
function dop_vars($id_clen,$ids_dar,$vars,$typ) { //trace();
//                                                 display("dop_vars($ids,$vars,$typ)");
                                                debug($vars,"dop_vars($typ)");
  $map_osloveni= map_cis('k_osloveni','zkratka');
  $jsons= array(null);          // 0. prvek není definován, $ids začíná od 1
  $vals= array();
  $qry= "SELECT * FROM clen WHERE id_clen=$id_clen";
  $res= pdo_qry($qry);
  if ( !$res || pdo_num_rows($res)!=1 ) return fce_error("dop_vars $id_clen není klíčem člena");
  $c= pdo_fetch_object($res);
  // spočítání proměnných
  $val= (object)array('_dar'=>null);
  foreach ($vars as $var) if ( $var ) {
    $var= trim($var);
    switch ( $var ) {
    // údaje z kontaktu
    case 'osloveni':                          // kontext: {osloveni}!
      if ( $c->osloveni!=0 && $c->prijmeni5p!='' )
        $val->$var= "{$map_osloveni[$c->osloveni]} {$c->prijmeni5p}";
      else
        $val->$var= 'Milí';
      break;
    case 'cislo':                             // členské číslo
      $val->$var= $c->id_clen;
      break;
    case 'ps':                                // P.S:
      $val->$var= clen_data($c,'ps');
      break;
    // údaje z daru
    case 'potvrzeni':                         // potvrzení
      if ( !$val->_dary ) $val->_dary= clen_dary($c->id_clen);
      $val->$var= clen_potvrzeni_text($val->_dary,1);
      break;
    case 'dar_castka':                        // dar: částka
      if ( !$val->_dar ) dop_dar($ids_dar,$val->_dar);
      $castka= $val->_dar->castka;
      $castka= ceil($castka)-$castka==0
        ? round($castka).",-"
        : number_format($castka,2,',',' ');
      $val->$var= $castka;
      break;
    case 'dar_datum':                         // dar: částka_kdy
      if ( !$val->_dar ) dop_dar($ids_dar,$val->_dar);
      $val->$var= $val->_dar->castka_kdy;
      break;
    case 'dar_popis':                         // dar: popis
      if ( !$val->_dar ) dop_dar($ids_dar,$val->_dar);
      $val->$var= $val->_dar->popis;
      break;
//     case 'dary':                              // seznam darů => POST diky
//       if ( !$val->_dary ) $val->_dary= clen_dary($c->id_clen);
//       $val->$var= clen_dary_text($val->_dary);
//       break;
    // jméno a adresa
    case 'adresa_clena':                      // bydliště na obálku
      $val->$var= clen_data($c,'adresa2');
      break;
    case 'adresa_radek':                      // bydliště člena na řádek
      $val->$var= clen_data($c,'adresa1');
      break;
    case 'adresa_postovni':                   // poštovní adresa bez jména
      $val->$var= clen_data($c,'adresa2');
      break;
    case 'adresa_nebo_ucet':                  // bydliště na obálku nebo číslo účtu <= potvrzení
      if ( $typ=='potvrz' ) {
        // poštovní jméno je definováno kaskádou: dar.darce|clen.darce|clen.jméno
        // zohlednění nastaveného clen.darce
        $jmeno_darce= $adresa_postovni= '';
        if ( $c->darce ) {
          $darce= explode(';',$c->darce); // ... jméno a příp.adresa obvyklého dárce
          $jmeno_darce= $darce[0];
          if ( count($darce)!=1 ) {
            $adresa_postovni= trim($darce[1])."<br/>".trim($darce[2]);
            if ( count($darce)!=3 )
              fce_warning("adresa dárce u člena č.$id_clen má chybný formát");
          }
        }
        $val->adresa_clena=
           $adresa_postovni ? $adresa_postovni : (
           $c->psc ? clen_data($c,'adresa2')
           : "účet:".$val->_dar->ucet );
      }
      else if ( $c->psc && $c->obec ) {
        $val->adresa_clena= clen_data($c,'adresa2');
      }
      else {
        if ( !$val->_dar ) dop_dar($ids_dar,$val->_dar);
        $val->adresa_clena= "účet:".$val->_dar->ucet;
      }
      break;
    case 'jmeno_clena':                       // titl. jméno příjmení
      $val->$var= clen_data($c,'jmeno1');
      break;
    case 'jmeno_darce':                       // jméno dárce na potvrzení může být jiné než člena
      if ( !$val->_dar ) dop_dar($ids_dar,$val->_dar);
      $c_darce= '';
      if ( $c->darce ) {
        list($c_darce)= explode(';',$c->darce);
      }
      $val->$var= $val->_dar->darce ? $val->_dar->darce       // jméno z daru
        : ( $c_darce ? $c_darce : clen_data($c,'jmeno1'));    // jméno z člena
      break;
    case 'ico':                             // rodcis obsahuje jen IČ
      $val->$var= $c->rodcis ? "IČ:{$c->rodcis}" : '';
      break;
    case 'jmeno_postovni':                    // jméno na poštovní adresu (třeba přes sardinku)
      $val->$var= clen_data($c,'jmeno2');
      break;
    // texty do potvrzení
    case 'stredisko':                         // text z _cis.popis druh=stredisko
      if ( !$val->_dar ) dop_dar($ids_dar,$val->_dar);
      $val->$var= $val->_dar->c_popis;
      break;
    default:
      $val->$var= '???';
      throw new Exception(" '$var' není známé jméno proměnné v dopise '$typ'");
      break;
    }
  }
//                                                 debug($val,"dop_vars $id_clen(".implode(',',$vars).")");
  return $val;
}
# ------------------------------------------------------------------------------------------ dop dar
# doplnění informací o daru
function dop_dar($id_dar,&$d) { trace();
  $d= (object)array();
  $qry= "SELECT d.*,c.popis AS c_popis FROM dar AS d
         LEFT JOIN _cis AS c ON d.stredisko=c.data AND c.druh='stredisko'
         WHERE id_dar=$id_dar";
  $res= pdo_qry($qry);
  if ( $res && $d= pdo_fetch_object($res) ) {
    //$d->castka= $d->castka;
    $d->castka_kdy= sql_date1($d->castka_kdy,0,'. ');
    $d->popis= str_replace("\n","</li><li>",$d->popis);
  }
}








# --------------------------------------------------------------------------------------- dop pdf_id
# LOCAL
# vytvoření dopisu se šablonou pomocí TCPDF podle parametrů
# proměnná osloveni se aktualizuje podle stavu tabulky CLEN
# $dopis  - záznam DOPIS s textem s parametry ve tvaru {jméno}
# $vars   - pole jmen použitých parametrů
# $pars   - pole obsahující substituce parametrů pro $text
# $dne    - do hlavičky
# vygenerované dopisy ve tvaru souboru PDF se umístí do ./docs/$fname
# případná chyba se vrátí jako Exception
function dop_pdf_id($dopis,$vars,$pars,$id_clen,$ids_dar,$dne,$pro,$vyrizuje,&$listu) { trace();
  global $EZER, $USER;
  // úprava textu dopisu pro TCPDF
  $vzor= $dopis->obsah;
  $map_osloveni= map_cis('k_osloveni','zkratka');
  // přidání obsahu a data odeslani
  $text= (object)array();
  // definice částí hlavičky pro dopis
  if ( $dopis->druh=='D' || $dopis->druh=='P' ) {
    $text->adresa= $dopis->druh=='D'
      ? "{$pars->jmeno_postovni}<br>{$pars->adresa_postovni}"
      : "{$pars->jmeno_darce}<br>{$pars->adresa_clena}";       // pro potvrzení
    $text->ico= $pars->ico;
    $text->adresa_preview= $text->adresa;
    if ( $pars->ico ) {
      $adr= $text->adresa;
      $adr= substr($adr,0,4)==='<br>' ? substr($adr,4) : $adr;
      $text->adresa_preview= "<div style='float:right'>{$pars->ico}</div>$adr";
    }
    $text->telefon= $USER->options->telefon;
    // doplnění substitucí
    // doplnění aktuálního oslovení
    $id_clen= $pars->cislo;
    $qry= "SELECT osloveni,prijmeni5p FROM clen WHERE id_clen=$id_clen";
    $res= pdo_qry($qry,1,"člen $id_clen",1);
    $c= pdo_fetch_object($res);
    $pars->osloveni= ($c->osloveni!=0 && $c->prijmeni5p!='')
      ? "{$map_osloveni[$c->osloveni]} {$c->prijmeni5p}"
      : 'Milí';
  }
  else fce_error("dop_pdf_id - dopis s neznámou šablonou");
  // substituce v 'text'
  $verze= $vzor;
//   $subst_roz= array('dne'=>$dne);
  if ( $vars ) foreach ($vars as $var ) {
    $verze= str_replace('{'.$var.'}',$pars->$var,$verze);
  }
  $text->text= $verze;
  $druh= $dopis->druh;
  $result= (object)array();
  $result->dopis= $text;
//   $result->fname= $fname;
  $result->pro=   $pro;
  $result->vyrizuje= $vyrizuje;
  $result->listu= $listu;
  $result->druh=  $druh;
  $result->id_clen= $id_clen;
  $result->ids_dar= $ids_dar;
//                                                         debug($result,'dop_pdf_id-result');
  return $result;
}

# ---------------------------------------------------------------------------------------- clen_data
# formátování složených dat z položek CLEN  -- pro tisk do PDF
function clen_data($c,$part) {
  global $dop_rozesilani;
  switch ( $part ) {
  case 'adresa1':               // adresa na jeden řádek
    $psc= substr($c->psc,0,3).' '.substr($c->psc,3,2);
    $del= $c->ulice ? ', ' : '';
    $html= mb_substr($c->ulice,0,2,'UTF-8')=='č.'
      ? "$psc {$c->obec} {$c->ulice}"
      : "{$c->ulice}$del$psc {$c->obec}";
    break;
  case 'adresa2':               // adresa na dva řádky až tři řádky
    if ( $c->psc2 ) {
      $psc2= substr($c->psc2,0,3).' '.substr($c->psc2,3,2);
      $html= "{$c->ulice2}<br/><b>$psc2</b>  {$c->obec2}" . ($c->stat2 ? "<br/>    {$c->stat2}" : "");
    }
    else {
      $psc= substr($c->psc,0,3).' '.substr($c->psc,3,2);
      $html= "{$c->ulice}<br/><b>$psc</b>  {$c->obec}" . ($c->stat ? "<br/>        {$c->stat}" : "");
    }
    break;
//   case 'adresa2':               // adresa na dva řádky až tři řádky  ... do 20170313
//     $psc= substr($c->psc,0,3).' '.substr($c->psc,3,2);
//     $html= "{$c->ulice}<br/><b>$psc</b>  {$c->obec}" . ($c->stat ? "<br/>        {$c->stat}" : "");
//     break;
  case 'dary':                  // děkování za došlé dary
    $html= "Děkujeme ...";
    break;
  case 'jmeno1':
    $html= $c->osoba            // jméno na jeden řádek
      ? trim("{$c->titul} {$c->jmeno} {$c->prijmeni}")
      : ( (substr($c->prijmeni,0,3)=='FU ' || $c->prijmeni=='FU' )
        ? "Řk. farnost ".substr($c->prijmeni,4) : $c->prijmeni );
  case 'jmeno2':
    $html= $c->osoba            // úplné jméno fyzické i právnické osoby včetně oslovení na 2 řádky
      ? trim("{$c->titul}<br><b>{$c->jmeno} {$c->prijmeni}</b>")
      : ( (substr($c->prijmeni,0,3)=='FU ' || $c->prijmeni=='FU' )
        ? "Řk. farnost ".substr($c->prijmeni,4) : trim("{$c->titul}<br><b>{$c->prijmeni}</b>") )
          . ($c->jmeno ? "<br/>{$c->jmeno}" : "");
    break;
  case 'kod_poslani':           // X | Knnnnnn | D
    $html= $c->osobne ? 'X' : (
           $c->aktivita==6
           ? ($c->ka_clen!=$c->id_clen && $dop_rozesilani['velryby']
               ? 'V'.str_pad($c->ka_clen,5,'0',STR_PAD_LEFT) : 'D')
           : ($c->ka_clen ? 'K'.str_pad($c->ka_clen,5,'0',STR_PAD_LEFT) : 'D'));
    break;
  case 'kod_poslani_S':        // (X se nepíše) | Knnnnnn | D ... přes sardinku
    $html= $c->ka_clen ? 'K'.str_pad($c->ka_clen,5,'0',STR_PAD_LEFT) : 'D';
    break;
//   case 'potvrzeni_n':           // potvrzení darů za nadační fond
//     $html= "Potvrzujeme ...";
//     break;
//   case 'potvrzeni_r':           // potvrzení darů za radio
//     $html= "Potvrzujeme ...";
//     break;
  case 'ps':                    // P.S.: ... |
    $html= $c->ps ? "P.S.: {$c->ps}" : '';
    break;
  case 'rodcis':                // rodné číslo
    $rc= $c->rodcis;
    $html= substr($rc,0,2).' '.substr($rc,2,2).' '.substr($rc,4,2).'/'.substr($rc,6,4);
    break;
  default:
    throw new Exception("clen_data <b>$part</b> není známé jméno proměnné");
  }
//                                                 display("clen_data({$c->id_clen},$part)=$html");
  return $html;
}

/** =======================================================================================> ŠABLONY */
# ------------------------------------------------------------------------------------- dop sab_mail
# přečtení běžného dopisu daného typu
function dop_sab_mail($typ) { trace();
  $d= null;
  try {
    $d= pdo_object("SELECT id_dopis,obsah FROM dopis WHERE typ='$typ' AND id_davka=0 ");
  }
  catch (Exception $e) { display($e); fce_error("dop_sab_mail: mail '$typ' nebyl nalezen"); }
  return $d;
}
# ------------------------------------------------------------------------------------- dop sab_text
# přečtení běžného dopisu daného typu
function dop_sab_text($dopis) { trace();
  $d= null;
  try {
    $qry= "SELECT id_dopis,obsah FROM dopis WHERE typ='$dopis' AND id_davka=1 ";
    $res= pdo_qry($qry,1,null,1);
    $d= pdo_fetch_object($res);
  }
  catch (Exception $e) { display($e); fce_error("dop_sab_text: průběžný dopis '$dopis' nebyl nalezen"); }
  return $d;
}
# ------------------------------------------------------------------------------------- dop sab_cast
# přečtení části šablony
function dop_sab_cast($druh,$cast) { trace();
  $d= null;
  try {
    $qry= "SELECT id_dopis_cast,obsah FROM dopis_cast WHERE druh='$druh' AND name='$cast' ";
    $res= pdo_qry($qry,1,null,1);
    $d= pdo_fetch_object($res);
  }
  catch (Exception $e) { display($e); fce_error("dop_sab_cast: část '$cast' sablony nebyla nalezena"); }
  return $d;
}
# ----------------------------------------------------------------------------------- dop sab_nahled
# ukázka šablony
function dop_sab_nahled($k3) { trace();
  global $ezer_path_docs;
  $html= '';
  $fname= "sablona.pdf";
  $f_abs= "$ezer_path_docs/$fname";
  $f_rel= "docs/$fname";
  try {
    switch ( $k3 ) {
    case 'nahled_h':                                    // dopisy hromadně
      $html= tc_sablona($f_abs,'rozesilani','D');       // bude použito i dopis_cast.pro='rozesilani'
      $date= @filemtime($f_abs);
      $href= "<a target='dopis' href='$f_rel'>$fname</a>";
      $html.= "Byl vygenerován PDF soubor: $href (verze ze ".date('d.m.Y H:i',$date).")";
      $html.= "<br><br>Jméno vyřizujícícho pracovníka je součástí definice rozesílání.";
      break;
    case 'nahled_j':                                    // dopisy jednotivě
      $html= tc_sablona($f_abs,'','D');                 // jen části bez označení v dopis_cast.pro
      $date= @filemtime($f_abs);
      $href= "<a target='dopis' href='$f_rel'>$fname</a>";
      $html.= "Byl vygenerován PDF soubor: $href (verze ze ".date('d.m.Y H:i',$date).")";
      $html.= "<br><br>Jako jméno vyřizujícícho pracovníka je vždy použito jméno přihlášeného uživatele,"
        ." ve tvaru uvedeném v osobním nastavení. Pro změnu osobního nastavení požádejte prosím administrátora webu.";
      break;
    case 'nahled_N':                                    // potvrzení
      $html= tc_sablona($f_abs,'rozesilani','N');       // bude použito i dopis_cast.pro='rozesilani'
      $date= @filemtime($f_abs);
      $href= "<a target='dopis' href='$f_rel'>$fname</a>";
      $html.= "Byl vygenerován PDF soubor: $href (verze ze ".date('d.m.Y H:i',$date).")";
      break;
    case 'nahled_R':                                    // potvrzení
      $html= tc_sablona($f_abs,'rozesilani','R');       // bude použito i dopis_cast.pro='rozesilani'
      $date= @filemtime($f_abs);
      $href= "<a target='dopis' href='$f_rel'>$fname</a>";
      $html.= "Byl vygenerován PDF soubor: $href (verze ze ".date('d.m.Y H:i',$date).")";
      break;
    }
  }
  catch (Exception $e) { $html= $e->getMessage(); }
  return $html;
}
/** =========================================================================================> MAILY */
# ------------------------------------------------------------------------------------- dop mail_gen
# vygeneruje sadu mailů pro seznam příjemců
# err=5 ... chyby v adresách, err=3 ... nejsou 3 přílohy
function dop_mail_gen($id_davka,$id_dopis,$browse_status) {  trace();
  $ret= (object)array('err'=>0);
  $nomail= array();
  $num= $errs= 0;
  // smaž staré vygenerované maily
  pdo_qry("DELETE FROM mail WHERE id_dopis=$id_dopis");
  // zjisti dopis a zda je parametrizovaný
  $d= pdo_object("SELECT * FROM dopis WHERE id_dopis=$id_dopis");
  $parametrized= preg_match_all("/[\{]([^}]+)[}]/",$d->obsah,$list); $vars= $list[1];
  $prilohy= array();
  if ( $d->prilohy ) foreach( explode(',',$d->prilohy) as $priloha) {
    list($fname,$flen)= explode(':',$priloha);
    if ( $fname ) $prilohy[]= $fname;
  }
                                                 debug($prilohy,"přílohy");
  if ( count($prilohy)!=2 ) {
    $ret->err= 3;
    $ret->msg= "Maily nemohly být vygenerovány, nebyly vloženy všechny přílohy.";
    goto end;
  }
  $mbody= $d->obsah;
  // projdi příjemce a vygeneruj každému mail
  $y= browse_status($browse_status);
//                                                 debug($y,"browse_status");
  $rc= pdo_qry($y->qry);
  while ($rc && $c=pdo_fetch_object($rc) ) {
    $stav= 3; $msg= '';
    $id_clen= $c->id_clen;
    $stredisko= $c->str=='HO' ? 0 : (
                $c->str=='Oc' ? 1 : -1);
//    $stredisko= $c->str=='HO' ? 0 : (
//                $c->str=='Ž'  ? 1 : (
//                $c->str=='Oc' ? 2 : -1));
    list($email,$prijmeni,$jmeno)= select("email,prijmeni,jmeno","clen","id_clen=$id_clen");
    // prozkoumáme validitu mailu(ů)
    $email= trim($email," ,;");
    if ( substr($email,0,1)=='*' ) {
      $stav= 5;
    }
    else foreach(preg_split("/[\s,;]+/",$email) as $x) if ( !emailIsValid($x,$msg) ) {
      $stav= 5;
                                                display("email:$x:$msg");
      break;
    }
    // výběr přílohy
    $priloha= '';
    if ( $stredisko>=0 ) {
      $priloha= $prilohy[$stredisko];
    }
    else {
      $stav= 5;
      $msg.= "; chybné středisko";
    }
    $errs+= $stav==5 ? 1 : 0;
    // pokud je mail parametrizovaný, vygeneruj jeho instanci
    $mbody= '';
    if ( $parametrized ) {
      $mbody= $d->obsah;
      $parss= dop_vars($id_clen,'',$vars,'');
      foreach ($vars as $var ) {
        $mbody= str_replace('{'.$var.'}',$parss->$var,$mbody);
      }
    }
    // zapiš mail
    $mbody= pdo_real_escape_string($mbody);
    query("INSERT mail (id_davka,id_dopis,id_clen,email,stav,msg,body,prilohy)
           VALUE ($id_davka,$id_dopis,$id_clen,'$email',$stav,'$msg','$mbody','$priloha')");
    $num+= pdo_affected_rows();
  }
  // informační zpráva
  $ret->msg= "Bylo vygenerováno $num mailů";
  if ( $errs ) {
    $ret->err= 5;
    $ret->msg.= ", $errs příjemců má chybnou emailovou adresu nebo nekorektní středisko,
      jsou zobrazeni červeně v seznamu příjemců. Dvojklikem se dostanete na jejich kartu - pokud se
      rozhodnete jim mail dočasně neposílat, napište před něj hvězdičku.
      <br><br>Po skončení oprav použijte pro obnovu zobrazení opět menu <b>Bulletin mailem</b>.";
  }
end:
//                                                         debug($ret,"dop_mail_gen");
  return $ret;
}
# ---------------------------------------------------------------------------------- dop mail_attach
# zapíše i-tou přílohu k mailu (soubor je v docs/$ezer_root)
function dop_mail_attach($id_dopis,$f,$i) { trace();
  // nalezení záznamu v tabulce a přidání názvu souboru
  $names= explode(',',select('prilohy','dopis',"id_dopis=$id_dopis"));
  $names[$i]= "{$f->name}:{$f->size}";
  query("UPDATE dopis SET prilohy='".implode(',',$names)."' WHERE id_dopis=$id_dopis");
  return 1;
}
# ---------------------------------------------------------------------------------- dop mail_detach
# odebere i-tou přílohu a smaže soubor v docs/$ezer_root
function dop_mail_detach($id_dopis,$i) { trace();
                                                        display("dop_mail_detach");
  global $ezer_path_root,$ezer_root;
  $ret= (object)array();
  // nalezení záznamu v tabulce a odebrání názvu souboru
  $names= explode(',',select('prilohy','dopis',"id_dopis=$id_dopis"));
                                                        debug($names,"names");
  list($name,$length)= explode(':',$names[$i]);
  $file= "$ezer_path_root/docs/ch/bulletiny/$name";
                                                        display("?= unlink($file)");
  $ok= @unlink($file);
                                                        display("$ok= unlink($file)");
  if ( $ok ) {
    $names[$i]= '';
    query("UPDATE dopis SET prilohy='".implode(',',$names)."' WHERE id_dopis=$id_dopis");
  }
  $ret->msg= "soubor $name ".($ok ? "byl" : "nemohl být")." smazán";
  $ret->err= $ok ? 0 : 1;
  return $ret;
}
# ------------------------------------------------------------------------------------ dop mail_test
# ASK
# odešli dávku neodeslaných malů daného dopisu, pokud $kolik>0 a $id_mail=0
# nebo pošli $id_mail na testovací adresu, pokud $id_mail!=0, $kolik=1, $zkus!=''
#   ask('dop_mail_send',1,maily.id_dopis.get,maily.id_mail.get,from.get,name.get,zkus.get)
#   ask('dop_mail_send',davka.get,maily.id_dopis.get,0,from.get,name.get)
function dop_mail_send($kolik,$id_dopis,$id_mail,$name,$zkus='') { trace();
  $TEST= 0;
  global $EZER, $ezer_path_serv, $ezer_path_root;
  $phpmailer_path= "$ezer_path_serv/licensed/phpmailer";
  require_once("$phpmailer_path/class.phpmailer.php");
  require_once("$phpmailer_path/class.smtp.php");
  // návratový objekt
  $ret= (object)array('err'=>0,'msg'=>'');
  // zpětné adresy
  $reply= $EZER->smtp->reply;
  $name= $name ?: $EZER->smtp->name;
  // nalezení dopisu a dat člena
  $predmet= select("nazev","dopis","id_dopis=$id_dopis");
  $from= $EZER->smtp->from;
  // vygenerování mailu
  $mail= new PHPMailer(true);
  $mail->SetLanguage('cz',"$phpmailer_path/language/");
  $mail->IsSMTP(); // telling the class to use SMTP
//     $mail->SMTPDebug  = 4;
//   $mail->Debugoutput = function($str, $level) use (&$ret, $from) {
//     $ret->msg= "Při odesílání mailu na $from došlo k chybě: $str";
//     $ret->err= 1;
//   };
  $mail->SMTPAuth   = true;
  $mail->Password   = $EZER->smtp->pass;
  $mail->Host       = $EZER->smtp->host;
  $mail->Port       = $EZER->smtp->port;
  $mail->Username   = $EZER->smtp->user;
  $mail->Password   = $EZER->smtp->pass;
  $mail->SMTPSecure = $EZER->smtp->secure;
  $mail->From       = $from;
  $mail->CharSet    = "UTF-8";
  // nalezení dopisu a daného počtu mailů
  $n= $nko= $nok= 0;
  $AND= $id_mail ? "AND id_mail=$id_mail" : "AND stav IN (0,3)";
  $rm= pdo_qry("SELECT * FROM mail WHERE id_dopis=$id_dopis $AND ORDER BY id_clen LIMIT $kolik");
  while ( $rm && $m= pdo_fetch_object($rm) ) {
    try {
      // adresát a předmět
      $to= $zkus ?: $m->email;
      $mail->ClearAddresses();
      $mail->ClearCCs();
      $i= 0;
      foreach(preg_split("/,\s*|;\s*|\s+/",trim($to," ,;"),-1,PREG_SPLIT_NO_EMPTY) as $adresa) {
        if ( !$i++ )
          $mail->AddAddress($adresa);   // pošli na 1. adresu
        else                            // na další jako kopie
          $mail->AddCC($adresa);
      }
      $mail->FromName= $name;
      $mail->Subject= $predmet;
      $mail->AddReplyTo($reply,$name);
      $mail->IsHTML(true);
      $mail->Body= $m->body;
      // přidání příloh podle střediska
      $prilohy= explode(',',$m->prilohy);  // již je vybraná podle střediska
      $dbg_prilohy= array();
      $bulletin= '';
      if ( count($prilohy) ) foreach ( $prilohy as $fname ) {
        $fpath= "$ezer_path_root/docs/ch/bulletiny/$fname";
        $dbg_prilohy[]= $fpath;
        $bulletin.= " $fname";
        $mail->AddAttachment($fpath);
      }
      // odeslání mailu
      $n++;
      if ( $TEST ) {
        $ret->msg= "TESTOVÁNÍ - vlastní mail.send je vypnuto<br>obsah mailu viz trasování";
                        debug(array('to'=>$to,'from'=>"$name &lt;$from&gt;",'subject'=>$predmet,
                                    'prilohy'=>$dbg_prilohy,'body'=>$m->body));
      }
      else {
        // odeslání mailu
        $ok= $mail->Send();
        if ( $zkus ) {
                                    display("mail pro {$m->email} (člen {$m->id_clen}) "
                                    .($ok ? "odeslan na $to" : $mail->ErrorInfo));
          // testovací mail
          $ret->msg= "Byl odeslán testovací mail na $zkus "
            . ( $ok ? "je zapotřebí zkontrolovat obsah" : ("s chybou ".$mail->ErrorInfo));
          goto end;
        }
        else {
                                    display("mail to=$to pro (člen {$m->id_clen}) "
                                    .($ok ? "odeslan" : $mail->ErrorInfo));
          // ostrý mail - zapiš výsledek do tabulky a v případě úspěchu do historie
          if ( $ok ) {
            $stav= 4;
            $nok++;
            query("UPDATE mail SET stav=4,msg='ok' WHERE id_mail={$m->id_mail}");
            $id_clen= $m->id_clen;
            $prefix= "rozesílání mailem ".date('j.n.Y');
            $historie= pdo_real_escape_string("$prefix|$bulletin|");
            query("UPDATE clen SET historie=CONCAT('$historie',historie) WHERE id_clen=$id_clen ");
          }
          else {
            $stav= 5;
            $nko++;
            $msg= $mail->ErrorInfo;
            query("UPDATE mail SET stav=5,msg=\"$msg\" WHERE id_mail={$m->id_mail}");
          }
          sleep(5);
        }
      }
    } catch (phpmailerException $e) {
      display($e->errorMessage());
    } catch (Exception $e) {
      display($e->getMessage());
    }
  }
  if ( $n ) {
    $ret->msg= "Bylo odesíláno $n mailů"
      . ($n==$nok ? " - všechny úspěšně." : " - $nok úspěšně")
      . ($nko==0 ? '.' : ", $nko neúspěšně (příčiny chyb jsou v červeně označených řádcích)");
  }
  else {
    $ret->msg= "Nebyly nalezeny žádné další maily k odeslání";
  }
end:
  return $ret;
}
?>
