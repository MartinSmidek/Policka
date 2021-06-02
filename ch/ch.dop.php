<?php # Aplikace Polička, (c) 2021 Martin Smidek <martin@smidek.eu>

# ======================================================================================> JEDNOTLIVÉ
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
  $zmena_kdo= $USER->abbr;
  $zmena_kdy= date('Y-m-d H:i:s');
  $qry= "UPDATE dar SET $x_txt='$txt',$x_kdy='$kdy',$x_kdo='{$dop->kdo}' WHERE id_dar=$id_dar";
  $qry= "UPDATE dar SET $x_txt='$txt',$x_kdy='$kdy',$x_kdo='{$dop->kdo}',
         zmena_kdo='$zmena_kdo',zmena_kdy='$zmena_kdy' WHERE id_dar=$id_dar";
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



# ========================================================================================> HROMADNÉ
# ---------------------------------------------------------------------------------==> dop show_vars
# ASK
# vrátí seznamy proměnných: all=všech, use=použitých
function dop_show_vars($idd=0) {  trace();
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
  $vars= dop_show_vars($idd);
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
