<?php # (c) 2011-2015 Martin Smidek <martin@smidek.eu>
/** **************************************************************************************==> REYNET */
//# --------------------------------------------------------------------------------------- reynet get
//# zobrazí odkaz na člena
//function reynet_get($typ,$id) { trace();
//  $url= "https://app.raynet.cz/api/v2/$typ/$id";
//  // ezer
//  $inst= "ezer";
//  $user= "martin.smidek@outlook.com";
//  $pass= "ezer2017";
//  // procharitu
//  $inst= "procharitu";
//  $user= "martin@smidek.eu";
//  $pass= "wlxt1lsw";
//  $ch= curl_init($url);
//                                                        display("curl=$ch.");
//  $headers = array(
//    'accept: */*',
////     'accept-encoding:gzip, deflate, sdch, br',
////     'accept-language:cs,fr;q=0.8',
//    "Content-Type:application/json",
//    "X-Instance-Name: $inst",
////     'authorization: Basic bWFydGluLnNtaWRla0BvdXRsb29rLmNvbTplemVyMjAxNw=='
//    "Authorization: Basic ".base64_encode("$user:$pass")
//  );
//  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//  $resp= @curl_exec($ch);
//  $err= curl_error($ch);
//  $stat= curl_getinfo($ch);   //get status code
//                                                        display("curl=$resp");
//                                                        debug($stat,$err);
//  curl_close($ch);
//  $ret= json_decode($resp);
//  $ret= $ret->data;
//                                                        debug($ret);
//  return $ret;
//}
/** *************************************************************************************==> CLENOVE */
# ----------------------------------------------------------------------------------- klub firma_ico
# najde údaje o firmě podle zadaného IČO
function klub_firma_ico($ico) {
  $ret= (object)array('err'=>'');
  $ares= "http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_std.cgi?ico";
//                                                        $ico++;
  $url= "$ares=$ico#3";
                                                        display($url);
   $xml= file_get_contents($url);
/*
  $xml_= <<<__EOD
<?xml version="1.0" encoding="UTF-8"?>
<are:Ares_odpovedi xmlns:are="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_answer/v_1.0.1" xmlns:dtt="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_datatypes/v_1.0.4" xmlns:udt="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/uvis_datatypes/v_1.0.1" odpoved_datum_cas="2017-05-15T12:42:47" odpoved_pocet="1" odpoved_typ="Standard" vystup_format="XML" xslt="klient" validation_XSLT="/ares/xml_doc/schemas/ares/ares_answer/v_1.0.0/ares_answer.xsl" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_answer/v_1.0.1 http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_answer/v_1.0.1/ares_answer_v_1.0.1.xsd" Id="ares">
  <are:Odpoved>
    <are:Pocet_zaznamu>0</are:Pocet_zaznamu>
    <are:Typ_vyhledani>FREE</are:Typ_vyhledani>
  </are:Odpoved>
</are:Ares_odpovedi>
__EOD;

  $xml= <<<__EOD
<?xml version="1.0" encoding="UTF-8"?>
<are:Ares_odpovedi
    xmlns:are="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_answer/v_1.0.1"
    xmlns:dtt="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_datatypes/v_1.0.4"
    xmlns:udt="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/uvis_datatypes/v_1.0.1"
    odpoved_datum_cas="2017-04-26T16:06:21"
    odpoved_pocet="1" odpoved_typ="Standard" vystup_format="XML" xslt="klient"
    validation_XSLT="/ares/xml_doc/schemas/ares/ares_answer/v_1.0.0/ares_answer.xsl"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_answer/v_1.0.1 http://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_answer/v_1.0.1/ares_answer_v_1.0.1.xsd"
    Id="ares">
  <are:Odpoved>
  <are:Pocet_zaznamu>1</are:Pocet_zaznamu>
  <are:Typ_vyhledani>FREE</are:Typ_vyhledani>
  <are:Zaznam>
    <are:Shoda_ICO>
    <dtt:Kod>9</dtt:Kod>
    </are:Shoda_ICO>
    <are:Vyhledano_dle>ICO</are:Vyhledano_dle>
    <are:Typ_registru>
    <dtt:Kod>2</dtt:Kod>
    <dtt:Text>OR</dtt:Text>
    </are:Typ_registru>
    <are:Datum_vzniku>1992-05-21</are:Datum_vzniku>
    <are:Datum_platnosti>2017-04-26</are:Datum_platnosti>
    <are:Pravni_forma>
    <dtt:Kod_PF>112</dtt:Kod_PF>
    </are:Pravni_forma>
    <are:Obchodni_firma>100 Mega Hradec Králové, spol. s r.o.</are:Obchodni_firma>
    <are:ICO>46507141</are:ICO>
    <are:Identifikace>
      <are:Adresa_ARES>
        <dtt:ID_adresy>202693070</dtt:ID_adresy>
        <dtt:Kod_statu>203</dtt:Kod_statu>
        <dtt:Nazev_obce>Hradec Králové</dtt:Nazev_obce>
        <dtt:Nazev_ulice>Gočárova</dtt:Nazev_ulice>
        <dtt:PSC>50002</dtt:PSC>
        <dtt:Adresa_UIR>
          <udt:Kod_oblasti>51</udt:Kod_oblasti>
          <udt:Kod_kraje>86</udt:Kod_kraje>
          <udt:Kod_okresu>3602</udt:Kod_okresu>
          <udt:Kod_obce>569810</udt:Kod_obce>
          <udt:PSC>50002</udt:PSC>
          <udt:Kod_ulice>128627</udt:Kod_ulice>
        </dtt:Adresa_UIR>
      </are:Adresa_ARES>
    </are:Identifikace>
    <are:Kod_FU>228</are:Kod_FU>
    <are:Priznaky_subjektu>NAAZNANNNNNNNNNNNNNNNENNANNNNN</are:Priznaky_subjektu>
    </are:Zaznam>
  </are:Odpoved>
</are:Ares_odpovedi>
__EOD;
*/
                                                        display(htmlentities($xml));
  libxml_use_internal_errors(true);
  $xml= strtr($xml,array('are:'=>'','dtt:'=>'','udt:'=>'','xsi:'=>''));
//                                                         display(htmlentities($xml));
  $js= simplexml_load_string($xml);
  if ( $js===false ) {
    foreach (libxml_get_errors() as $error) {
      $ret->err.= "<br>{$error->message}";
      goto end;
    }
  }
  // rozbor odpovědi
  $x= $js->Odpoved->Pocet_zaznamu;
  if ( $x==1 ) {
    $ret->ok= 1;
    $z= (object)$js->Odpoved->Zaznam[0];
    $ret->prijmeni= (string)$z->Obchodni_firma;
    $ret->rodcis=   (string)$z->ICO;
    $i= $z->Identifikace[0]->Adresa_ARES;
    $ret->ulice=    (string)$i->Nazev_ulice;
    $ret->obec=     (string)$i->Nazev_obce;
    $ret->psc=      (string)$i->PSC;
  }
  else {
    $ret->ok= 0;
    $ret->err.= "<br>IČO nebylo systémem ARES rozpoznáno";
  }
//                                                         debug($js,"ARES");
end:
                                                        debug($ret,"klub_firma_ico($ico)");
  return $ret;
}
function dump($x) { trace();
  ob_start(); var_dump($x); display(ob_get_contents()); ob_end_clean();
}
# ---------------------------------------------------------------------------------- klub ukaz_clena
# zobrazí odkaz na člena
function klub_ukaz_clena($id_clen,$barva='') {
  $style= $barva ? "style='color:$barva'" : '';
  return "<b><a $style href='ezer://klu.cle.show_clen/$id_clen'>$id_clen</a></b>";
}
# -------------------------------------------------------------------------------- klub select_cleny
# zobrazí odkaz, který zařídí aby členové byli selected
function klub_select_cleny($ids_clen,$caption,$barva='') {
  $style= $barva ? "style='color:$barva'" : '';
  return "<b><a $style href='ezer://klu.cle.select_cleny/$ids_clen'>$caption</a></b>";
}
# ------------------------------------------------------------------------------------ klub ukaz_dar
# zobrazí odkaz na dar
function klub_ukaz_dar($id_dar,$barva='') {
  $style= $barva ? "style='color:$barva'" : '';
  return "<b><a $style href='ezer://klu.dry.show_dar/$id_dar'>$id_dar</a></b>";
}
# -------------------------------------------------------------------------------- klub oprav_prevod
# opraví dárce v převodu
function klub_oprav_prevod($ident,$id_clen) {
  $qu= "UPDATE prevod SET clen=$id_clen WHERE ident='$ident' ";
  $ru= pdo_qry($qu);
  $opraveno= pdo_affected_rows() ? 1 : 0;
  return $opraveno;
}
# ------------------------------------------------------------------------------ klub clen_stredisko
# ASK: vrátí středisko člena podle jeho darů a provede update záznamu (pokud není test=0)
function klub_clen_stredisko ($id_clen,$test=0) {
  list($n,$s_dary,$s_clen,$svyjimka)= select(/*fields,from,where*/
    "count(*),GROUP_CONCAT(DISTINCT d.stredisko ORDER BY d.stredisko),c.stredisko,svyjimka",
    "dar AS d JOIN clen AS c USING(id_clen)",
    "LEFT(d.deleted,1)!='D' AND id_clen=$id_clen GROUP BY id_clen");
  // diskuse výsledku, pokud není $svyjimka=corr
  $stredisko= 0;
  $vymazat= false;
  if ( $svyjimka!=1 ) {                           // není corr
    if ( !$n ) {
      // žádný dar
      $vymazat= true;
    }
    else if ( $s_dary=='1' || $s_dary=='2' ) {
      // homogenní dary
      if ( !$s_clen || $test || $s_clen!=$s_dary )
        $stredisko= $s_dary;
    }
    else {
      // nehomogenní dary
      if ( $s_clen!=7 || $test )
        $stredisko= 7;
    }
    // případný zápis
    if ( !$test && ($stredisko || $vymazat) ) {
      query("UPDATE clen SET stredisko=$stredisko WHERE id_clen=$id_clen");
    }
  }
              display("klub_clen_stredisko($id_clen)=$n,[$s_dary],$s_clen,$svyjimka -- $stredisko");
  return $stredisko;
}
# ------------------------------------------------------------------------------- klub all_stredisko
# ASK: naplní položku CLEN.stredisko podle darů
function klub_all_stredisko ($update) {     trace();
  $str= map_cis('stredisko','zkratka');
  $n= 0;
  $grp= $igrp= $c1grp= $c2grp= $dgrp= $sgrp= $ugrp= $x1grp= $x2grp= array();
  $qry= "SELECT id_clen,count(*) AS _p,
           c.stredisko AS c_stredisko, psc,
           GROUP_CONCAT(DISTINCT d.stredisko ORDER BY d.stredisko) AS _s,
           GROUP_CONCAT(DISTINCT zkratka ORDER BY zkratka) AS _is
         FROM dar AS d JOIN clen AS c USING(id_clen)
         LEFT JOIN _cis AS i ON druh='stredisko' AND data=d.stredisko
         WHERE svyjimka=0 AND LEFT(c.deleted,1)!='D' AND LEFT(d.deleted,1)!='D'
           AND castka_kdy>='2010-01-01' AND umrti='0000-00-00'
         GROUP BY id_clen ORDER BY _s
         LIMIT 999999";
  $res= pdo_qry($qry);
  while ( $res && ($c= pdo_fetch_object($res)) ) {
    $n++;
    $id_clen= $c->id_clen;
    $grp[$c->_s]++;
    if ( $c->psc ) {
      if ( isset($c1grp[$c->_s]) ) {
        if ( substr_count($c1grp[$c->_s],',') < 2 ) {
          $c1grp[$c->_s].= ",&nbsp;".klub_ukaz_clena($id_clen);
        }
      }
      else {
        $c1grp[$c->_s]= klub_ukaz_clena($id_clen);
      }
    }
    switch ($c->_s) {
    case '0,1':
    case '1':
    case '1,7':
    case '1,7,9':
    case '1,9':
    case '9':
      $stredisko= '1';
      break;
    case '0,2':
    case '2':
    case '2,7':
    case '2,7,8':
    case '2,7,8,9':
    case '2,7,9':
    case '2,8':
    case '2,8,9':
    case '7,8':
    case '7,8,9':
    case '7,9':
    case '8':
    case '8,9':
    case '9':
      $stredisko= '2';
      break;
    default:
      $stredisko= '7';
    }
    $sgrp[$c->_s]= $stredisko;
    $igrp[$c->_s]= $c->_is;
    if ( $stredisko!=$c->c_stredisko ) {
      $dgrp[$c->_s][]= $id_clen;
      if ( $c->psc ) {
        $x1grp[$c->_s]++;
        if ( isset($c2grp[$c->_s]) ) {
          if ( substr_count($c2grp[$c->_s],',') < 2 ) {
            $c2grp[$c->_s].= ",&nbsp;".klub_ukaz_clena($id_clen);
          }
        }
        else {
          $c2grp[$c->_s]= klub_ukaz_clena($id_clen);
        }
      }
      else {
        $x2grp[$c->_s]++;
      }
    }
    if ( $update ) {
      // změny a opravy
      if ( $stredisko!=$c->c_stredisko ) {
        $qry2= "UPDATE clen SET stredisko=$stredisko WHERE id_clen=$id_clen";
        $res2= pdo_qry($qry2);
        $ugrp[$c->_s]++;
      }
    }
  }
  // zjištění počtu nastavených výjimek
  $qry= "SELECT svyjimka,count(*) AS _p,id_clen,GROUP_CONCAT(id_clen) AS _ids
         FROM clen
         WHERE svyjimka!=0 AND LEFT(deleted,1)!='D'
         GROUP BY svyjimka";
  $res= pdo_qry($qry);
  $c= pdo_fetch_object($res);
  $napr= klub_ukaz_clena($c->id_clen); // 1 s nastaveným corr
  $ukazat= $c->_p ? klub_select_cleny($c->_ids,"ukázat") : ''; // všechny s nastaveným corr
  // tabulka
  $tab= "<table class='stat' style='width:280px'><tr><th>počet dárců</th>
    <th>dárci (po 1.1.2010) pro</th><!-- th>podle číselníku</th -->
    <th>např.</th><th>návrh příslušnosti ke středisku</th>
    <th>týká se dárců s&nbsp;PSČ</th><th>např.</th><th>týká se dárců bez&nbsp;PSČ</th>
    <th>ukázat všechny</th><th>provedených změn</th></tr>";
  $clr=
  $tab.= "<tr><td align='right'>{$c->_p}</td><td>nastavené jako 'corr'</td><!-- td></td -->
    <td>$napr</td><td>-</td><td></td><td></td><td></td><td>$ukazat</td><td></td></tr>";
  foreach ($grp as $i => $n) {
    $s= $sgrp[$i];
    $ukazat= count($dgrp[$i]) ? klub_select_cleny(implode(',',$dgrp[$i]),"ukázat") : '';
    $clr= $s==1 ? " style='background-color:lightgreen'"
      : ( $s==2 ? " style='background-color:yellow'" : '');
    $sc= $str[$s];
    $tab.= "<tr><td align='right'>$n</td>
      <td title='$i'$clr>{$igrp[$i]}</td><!-- td>$i</td -->
      <td>{$c1grp[$i]}</td><td>$sc</td>
      <td align='right'>{$x1grp[$i]}</td><td>{$c2grp[$i]}</td>
      <td align='right'>{$x2grp[$i]}</td><td>$ukazat</td>
      <td>{$ugrp[$i]}</td></tr>";
  }
  $tab.= "</table";
  $txt.= "<br>$tab";
  return $txt;
}
# ---------------------------------------------------------------------------------- klub clen_udaje
# ASK: vrátí jméno, příjmení a obec člena zadaného číslem
function klub_clen_udaje ($id_clen) {
  $udaje= 'chybné číslo člena!!!';
  $qry= "SELECT jmeno,prijmeni,obec FROM clen WHERE id_clen='$id_clen'";
  $res= pdo_qry($qry);
  if ( $res && ($row= pdo_fetch_assoc($res)) ) {
    $udaje= "{$row['jmeno']} {$row['prijmeni']}, {$row['obec']}";
  }
  return $udaje;
}
# --------------------------------------------------------------------------------------- klub check
# ASK: zjistí zda je člen dobře vyplněn ($id_clen není pro nového člena definován)
# 1. zda vyplněné rodné číslo nebo IČ není použito u nevymazaného kontaktu
# 2. zda vyplněný obvyklý dárce má správný formát tzn. buďto jméno nebo jméno a plná adresa
function klub_check ($id_clen,$rodcis='',$darce='') { trace();
  if ( !$id_clen ) $id_clen= 0;
  $ok= 1;
  $msg= '';
  $del= '';
  // kontrola jednoznačnosti rodného čísla nebo IČ
  if ( $rodcis ) {
    $ids= '';
    $qry= "SELECT id_clen FROM clen
           WHERE id_clen!=$id_clen AND rodcis='$rodcis' AND left(deleted,1)!='D' ";
    $res= pdo_qry($qry);
    while ( $res && $c= pdo_fetch_object($res) ) {
      $ids.= " {$c->id_clen}";
    }
    if ( $ids ) {
      $ok= 0;
      $msg.= "{$del}POZOR: rodné číslo nebo IČ $rodcis je použito pro: $ids";
      $del= "<br/>";
    }
  }
  // kontrola formátu obvyklého dárce tzn. buďto jméno nebo jméno a plná adresa
  if ( $darce ) {
    $x= preg_match("/^([^;]+)(?:|;\s*([^;]*);\s*(\d{3}\s*\d{2}[^\d][^;]+))$/",$darce,$m);
//                                                         debug($m,$darce);
    if ( !$x ) {
      $ok= 0;
      $msg.= "{$del}POZOR: položka 'příjemce potvrzení' smí obsahovat pouze jméno nebo "
      . "jméno po středníku doplněné o úplnou adresu: ulice;psč obec (zkuste kliknout [...])";
    }
  }
  return (object)array('ok'=>$ok,'msg'=>$msg);
}
// # ---------------------------------------------------------------------------------- clen_change_fld
// # provede hromadnou změnu v Klubu - pro členy
// function clen_change_fld($keys,$fld,$mode,$val) {
// //                                                         display("clen_change_fld($keys,$fld,$val)");
//   $zmeny= (object)array();
//   $zmeny->fld= $fld;
//   $zmeny->op= $mode;
//   $zmeny->val= $val;
// //                                                         debug($zmeny,"ezer_qry(UPDATE_keys,'clen',$keys,...);");
//   ezer_qry("UPDATE_keys",'clen',$keys,$zmeny);
//   return $keys;
// }
// # ----------------------------------------------------------------------------------- dar_change_fld
// # provede hromadnou změnu v Klubu - pro dary
// function dar_change_fld($keys,$fld,$mode,$val) {
// //                                                         display("clen_change_fld($keys,$fld,$val)");
//   $zmeny= (object)array();
//   $zmeny->fld= $fld;
//   $zmeny->op= $mode;
//   $zmeny->val= $val;
// //                                                         debug($zmeny,"ezer_qry(UPDATE_keys,'clen',$keys,...);");
//   ezer_qry("UPDATE_keys",'dar',$keys,$zmeny);
//   return $keys;
// }
/** ****************************************************************************************==> DARY */
# ----------------------------------------------------------------------------------- klub dary_suma
# ASK: vrátí součet darů dárce, $strediska je seznam _cis.data středisek
function klub_dary_suma ($id_clen,$strediska) {  trace();
  $suma= 0;
  $qry= "SELECT sum(castka) as suma FROM dar AS dd
         LEFT JOIN _cis ON dd.varsym=data AND druh='varsym'
         WHERE LEFT(deleted,1)!='D' AND id_clen=$id_clen AND _cis.zkratka=1 $strediska";
  $res= pdo_qry($qry);
  if ( $res && $u= pdo_fetch_object($res) ) {
    $suma= $u->suma;
  }
  return number_format($suma,2,'.','');
}
# --------------------------------------------------------------------------------- klub dary_soucet
# browse_map: vrátí součet částek vybraných darů
function klub_dary_soucet ($xkeys) {  trace();
  $keys= ''; $del= '';
  foreach ($xkeys as $key) {
    $keys.= "$del$key";
    $del= ',';
  }
  $suma= 0;
  if ( $keys ) {
    $qry= "SELECT sum(castka) as suma FROM dar WHERE id_dar IN ($keys)";
    $res= pdo_qry($qry);
    $row= pdo_fetch_assoc($res);
    $suma= $row['suma'];
  }
  return number_format($suma,2,'.','');
}
/** *******************************************************************************==> KLUB/složenky */
# ----------------------------------------------------------------------------- klub slozenky_soucet
# k balíčku zjistí počet a součet, vrátí součet
function klub_slozenky_soucet ($ident) {
  global $klub_slozenky;
  $klub_slozenky= array(0,0,0);
  // přečtení převodu
  $qry= "SELECT castka,platby FROM prevod AS p
         LEFT JOIN balicek AS b ON b.ident=concat(left(p.ident,3),substr(p.ident,5,3))
         WHERE p.ident='$ident'";
  $res= pdo_qry($qry);
  if ( $res && ($row= pdo_fetch_assoc($res)) ) {
    $klub_slozenky[2]= $row['castka'];
    $klub_slozenky[3]= $row['platby'];
  }
  // přečtení info o balíčku v Klubu
  $balicek= substr($ident,0,3).substr($ident,4,3);
  $cond= "ucet LIKE '$balicek%' AND left(deleted,1)!='D'";
  $qry= "SELECT sum(castka) as soucet,count(*) as pocet FROM dar
         WHERE $cond";
  $res= pdo_qry($qry);
  if ( $res && ($row= pdo_fetch_assoc($res)) ) {
    $klub_slozenky[0]= $row['soucet'];
    $klub_slozenky[1]= $row['pocet'];
  }
  $klub_slozenky[0]= number_format($klub_slozenky[0],2,'.','');
  return $klub_slozenky[0];
}
# ------------------------------------------------------------------------------ klub slozenky_pocet
# k balíčku vrátí v předchozí funkci zjištěny počet
function klub_slozenky_pocet ($ident) {
  global $klub_slozenky;
  return $klub_slozenky[1];
}
# ------------------------------------------------------------------------------ klub slozenky_color
# k balíčku vrátí v předchozí funkci zjištěnou barvu
function klub_slozenky_color ($ident) {
  global $klub_slozenky;
  return $klub_slozenky[0]==$klub_slozenky[2]-$klub_slozenky[3] ? 0 : 1;
}
/** ********************************************************************************==> KLUB/převody */
# funkce pro panel KLUB/převody
# ---------------------------------------------------------------------------------------- klub tipy
# tipy na možné dárce podle údajů v převodu
function klub_tipy ($clen,$popis,$protiucet,$banka) {
  // rozklad popisu na jméno
  $popis= strtr(trim($popis),array('  '=>' ',','=>' '));
  $jm= array();
  foreach(explode(' ',$popis) as $x) {
    if ( substr($x,-1)!='.' ) {
      $jm[]= $x;
    }
  }
  $jm0= pdo_real_escape_string($jm[0]);
  $jm1= pdo_real_escape_string($jm[1]);
                                                        debug($jm,"$popis");
  // rozklad protiúčtu na části
  $p= ltrim(substr($protiucet,0,6),'0');
  $u0= substr($protiucet,7);
  $u= ltrim($u0,'0');
  $uc1= $p ? "$p-$u0/$banka" : "$u0/$banka";
  $uc2= $p ? "$p-$u/$banka" : "$u0/$banka";
  $uc3= "%$u/$banka";
  // formování dotazu
  $cond= "(";
  $cond.= $jm0 && $jm1
        ? " (jmeno LIKE '$jm0' COLLATE utf8_general_ci
             AND prijmeni LIKE '$jm1' COLLATE utf8_general_ci)
           OR (jmeno LIKE '$jm1' COLLATE utf8_general_ci
             AND prijmeni LIKE '$jm0' COLLATE utf8_general_ci)"
        : "0";
  $cond.= " OR ucet='$uc1' OR ucet='$uc2' OR ucet LIKE '$uc3')";
  $cond.= " AND left(deleted,1)!='D' AND umrti='0000-00-00'";
                                                        display("cond=$cond");
  return $cond;
}
# -------------------------------------------------------------------------------------- klub x2ucet
# transformace mezi označeními darovacích účtů: písmeno -> _cis.data
function klub_x2ucet ($x) {
  global $bank_nase_nucty;
  bank_load_ucty();                             // zajistí naplnění $bank_nase_nucty
  $z= $bank_nase_nucty[$x];
  if ( !$z ) fce_error("neznámé označení našeho účtu: '$x'");
  return $z;
}
# ------------------------------------------------------------------------------- klub zrus_prevodem
# zruší dar a vazbu s převodem
#   $novy_typ=5 vymaž případný dar a odkaz na člena
#   $novy_typ=6 jen oprava prevodu
#   $novy_typ=7 vymaž dar
function klub_zrus_prevodem($novy_typ,$ident) {
  global $USER;
  if ( $ident && ($novy_typ==5 || $novy_typ==6 || $novy_typ==7) ) {
    // nalezení převodu
    $qry= "SELECT * FROM prevod WHERE ident='$ident'";
    $res= pdo_qry($qry,1);
    $ok= $res ? 1 : 0;
    if ( $ok ) {
      if ( $novy_typ!=6 ) {
        $row= pdo_fetch_assoc($res);
        $nas_ucet= klub_x2ucet(substr($ident,0,1));
        $clen= $row['clen'];
        $dar= $row['dar'];
        $zmena_kdo= $USER->abbr;
        $zmena_kdy= date('Y-m-d H:i:s');
        $deleted= "D $zmena_kdo ".date('Y-m-d');
        // zrušení  daru převodem
        $qry= "UPDATE dar SET deleted='$deleted',zmena_kdo='$zmena_kdo',zmena_kdy='$zmena_kdy'"
          . " WHERE id_dar=$dar";
        $res= pdo_qry($qry);
        $ok= $res ? 1 : 0;
      }
      if ( $ok ) {
        // oprava převodu
        $qry= "UPDATE prevod SET typ=$novy_typ,"
         . ($novy_typ==5 ? 'dar=0,clen=0' : ($novy_typ==6 ? 'clen=0' : 'dar=0'))
         . " WHERE ident='$ident'";
        $res= pdo_qry($qry);
        $ok= $res ? 1 : 0;
      }
    }
  }
  return $ok;
}
# ------------------------------------------------------------------------------- klub clen_prevodem
# sváže člena $id_clen s převodem $ident
function klub_clen_prevodem($id_clen,$ident) {
  if ( $id_clen && $ident ) {
    // vložení odkazu na dárce a dar
    $qry= "UPDATE prevod SET typ=7,clen=$id_clen WHERE ident='$ident'";
    $res= pdo_qry($qry);
    $ok= $res ? 1 : 0;
  }
  return $ok;
}
# ------------------------------------------------------------------------------------ klub clen_vs2
# zapíše opravený variabilní symbol
function klub_clen_vs2($vs2,$ident) {
  if ( $ident ) {
    // vložení opraveného symbolu
    $qry= "UPDATE prevod SET vsym2='$vs2' WHERE ident='$ident'";
    $res= pdo_qry($qry);
    $ok= $res ? 1 : 0;
  }
  return $ok;
}
# -------------------------------------------------------------------------------- klub darce_anonym
# vutvoří anonymního dárce s informacemi z daru převodem
function klub_darce_anonym($prijmeni,$jmeno,$ucet) {
  $id_clen= 0;
  // vytvoření dárce
  $qry= "INSERT INTO clen (osoba,prijmeni,jmeno,ucet) VALUES(1,'$prijmeni','$jmeno','$ucet')";
  $res= pdo_qry($qry);
  if ( $res )  $id_clen= pdo_insert_id();
  return $id_clen;
}
# -------------------------------------------------------------------------------- klub dar_prevodem
# přidá dar členovi $id_clen a sváže jej s převodem $ident
function klub_dar_prevodem($id_clen,$ident,$vs,$vs2) {
  global $map_varsym;
  $user= $_SESSION['user_id'];
  $now= date("Y-m-d H:i:s");
  $prevodem= 2;
  $id_dar= 0;
  $vs= (int)$vs2 ? (int)$vs2 : (int)$vs;
  if ( $id_clen && $ident ) {
    // nalezení střediska k VS
    if ( !isset($map_varsym) )
      $map_varsym= map_cis('varsym','ikona');
    $stredisko= isset($map_varsym[$vs]) ? $map_varsym[$vs] : 0;
//                                                                 debug($map_varsym,"$vs - $stredisko");
    // nalezení převodu
    $qry= "SELECT p.protiucet,p.banka,p.castka,p.splatnost,v.datum
           FROM prevod AS p
           JOIN vypis AS v ON v.ident=LEFT(p.ident,7)
           WHERE p.ident='$ident'";
    $res= pdo_qry($qry);
    $ok= $res ? 1 : 0;
    if ( $ok ) {
      $row= pdo_fetch_assoc($res);
      $nas_ucet= klub_x2ucet(substr($ident,0,1));
      $ucet= "{$row['protiucet']}/{$row['banka']}";
      $datum= $row['splatnost'];                // datum odeslání
      $datum= $row['datum'];                    // datum připsání na účet
      $castka= $row['castka'];
      // vytvoření daru převodem
      $qry= "INSERT INTO dar (id_clen,zpusob,ucet,nas_ucet,castka_kdy,castka,varsym,stredisko,zmena_kdo,zmena_kdy)
             VALUES($id_clen,$prevodem,'$ucet','$nas_ucet','$datum',$castka,$vs,'$stredisko','$user','$now')";
      $res= pdo_qry($qry);
      $ok= $res ? 1 : 0;
      if ( $ok ) {
        $res= pdo_query("SELECT LAST_INSERT_ID()");
        $ida= pdo_fetch_array($res);
        $id_dar= $ida[0];
        // vložení odkazu na dárce a dar
        $qry= "UPDATE prevod SET typ=9,clen=$id_clen,dar=$id_dar WHERE ident='$ident'";
        $res= pdo_qry($qry);
        $ok= $res ? 1 : 0;
      }
    }
  }
  return $id_dar;
}
# -------------------------------------------------------------------------------------- corr_110509
# oprava data daru
function corr_110509() {
  $n= 0;
  $qry= "SELECT DISTINCT id_dar,castka_kdy,datum
           FROM prevod AS p
	   JOIN dar AS d ON d.id_dar=p.dar
           JOIN vypis AS v ON v.ident=LEFT(p.ident,7)
           WHERE zpusob=2 AND castka_kdy<datum
	   ORDER BY id_dar";
  $res= pdo_qry($qry);
  while ( $res && ($x= pdo_fetch_object($res)) ) {
    $n++;
    $qryu= "UPDATE dar SET castka_kdy='{$x->datum}' WHERE id_dar='{$x->id_dar}' ";
    $resu= pdo_qry($qryu);
    if ( !$resu  ) fce_error("chba");
    $rows= pdo_affected_rows();
    if ( $rows!=1 )
      $html.= "<br>$rows - {$x->splatnost},{$x->castka_kdy},{$x->datum} - $qryu";
  }
  $html.= "$n oprav";
  return $html;
}
/** ***********************************************************************************==> INFORMACE */
# ------------------------------------------------------------------------------------------ klu_inf
# rozskok na informační funkce
function klu_inf($obj) {
  $args= (array)$obj;
  $a= array_shift($args);
  return call_user_func_array($a,$args);
}
# ------------------------------------------------------------------------------------- klu_inf_stat
# základní statistika
function klu_inf_stat() { trace();
  $html= '';
  // kontakty
  $c= pdo_object("SELECT count(*) as _pocet FROM clen
                    WHERE left(clen.deleted,1)!='D' AND umrti='0000-00-00' ");
  $html.= "<br>počet známých kontaktů = <b>{$c->_pocet}</b>";
  // dárci
  $clenu= $daru= 0;
  $qry= "SELECT count(*) as _pocet FROM dar JOIN clen USING(id_clen)
         WHERE left(dar.deleted,1)!='D' /*AND left(clen.deleted,1)!='D' AND umrti='0000-00-00'*/
         GROUP BY id_clen";
  $res= pdo_qry($qry);
  while ( $res && ($x= pdo_fetch_object($res)) ) {
    $clenu++;
    $daru+= $x->_pocet;
  }
  $html.= ", z toho je <b>$clenu</b> dárců s celkem <b>$daru</b> dary";
  return $html;
}
# ------------------------------------------------------------------------------------ klu_inf_vyvoj
# vývoj Klubu
function klu_inf_vyvoj($od) { trace();
  $letos= date('Y');
  // dary
  $fin_p= $vec_p= array();              // roční histogramy - počty
  $fin_s= $vec_s= array();              // roční histogramy - sumy
  $qry= "SELECT year(castka_kdy) as _year, castka, zpusob FROM dar JOIN clen USING(id_clen)
         WHERE left(dar.deleted,1)!='D' AND left(clen.deleted,1)!='D' ";
  $res= pdo_qry($qry);
  while ( $res && ($d= pdo_fetch_object($res)) ) {
    $y= $d->_year;
    if ( $d->zpusob==4 ) {                // věcný dar
      $vec_p[$y]++;
      $vec_s[$y]+= $d->castka;
    }
    else {                              // finanční dar
      $fin_p[$y]++;
      $fin_s[$y]+= $d->castka;
    }
  }
  // zobrazení
  $html.= "<table class='stat'>";
  $html.= "<tr><th>rok</th>
          <th align='right'>počet finančních darů</th>
          <th align='right'>a součty částek</th>
          <th align='right'>počet věcných darů</th>
          <th align='right'>a jejich hodnoty</th></tr>";
  for ($r= $od; $r<=$letos; $r++) {
    $html.= klu_vyvoj_row($r,$fin_p[$r],$fin_s[$r],$vec_p[$r],$vec_s[$r]);
  }
  $html.= "</table>";
//                                                 debug($clen_od,'clen_od');
  return $html;
}
function klu_vyvoj_row($r,$x1,$x2,$x3,$x4) {
  global $klu_vyvoj_stav;
  $html.= "<tr>";
  $html.= "<th>$r</th>";
  $x= number_format(round($x1), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $x= number_format(round($x2), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $x= number_format(round($x3), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $x= number_format(round($x4), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $html.= "</tr>";
  return $html;
}
function klu_vyvoj_add(&$kam,$rok) {
  $rok= $rok+0;
  $letos= date('Y');
  if ( $rok ) {
    if ( $rok>=1997 && $rok<=$letos) {
      if ( !isset($kam[$rok]) ) $kam[$rok]= 0;
      $kam[$rok]++;
    }
  }
  else
    $kam[0]++;
}
# -------------------------------------------------------------------------------------- klu_spor_vs
# kontrola zda jsou dárci v době darování mezi živými
function klu_spor_vs($roky_zpet) {
  $rok= date('Y')-$roky_zpet;
  $str= map_cis('stredisko','hodnota');
  $html= '';
  $n= 0;
  $qry=  "SELECT id_dar,stredisko,varsym,zkratka,ikona,castka
          FROM dar
          LEFT JOIN _cis ON varsym=data AND druh='varsym'
          WHERE stredisko!=ikona AND year(castka_kdy)=$rok
          ORDER BY varsym";
  $res= pdo_qry($qry);
  while ( $res && $d= pdo_fetch_object($res) ) {
    $n++;
    $str_vs= $d->ikona ? " je určen středisku <b>{$str[$d->ikona]}</b>" : 'nemá uvedeného středisko';
    $str_dar= $d->stredisko ? "byl připsán <b>{$str[$d->stredisko]}</b>" : 'nemá uvedeného příjemce';
    $msg.= "<br>varsym <b>{$d->varsym}</b>  $str_vs ";
    $msg.= "  ale dar <b>{$d->id_dar}</b> ({$d->castka} Kč) $str_dar";
  }
  $html.= $msg=='' ? 'Vše ok' : "<h3>Máme celkem $n takových případů:</h3><dl>$msg</dl>";
  return $html;
}
# -------------------------------------------------------------------------------- klu_dar_smazaneho
# kontrola zda jsou dary nejsou od smazaných dárců
function klu_dar_smazaneho($roky_zpet) {
  $rok= date('Y')-$roky_zpet;
  $html= '';
  $n= 0;
  $qry=  "SELECT c.id_clen,c.jmeno,c.prijmeni,c.deleted,id_dar,castka,castka_kdy
          FROM dar AS d
          JOIN clen AS c USING (id_clen)
          WHERE LEFT(d.deleted,1)!='D' AND LEFT(c.deleted,1)='D'
            AND YEAR(castka_kdy)=$rok
          ORDER BY c.prijmeni";
  $res= pdo_qry($qry);
  while ( $res && $d= pdo_fetch_object($res) ) {
    $n++;
    $msg.= "<br>dar ".klub_ukaz_dar($d->id_dar)." ({$d->castka} Kč ze dne {$d->castka_kdy})  ";
    $msg.= "  je připsán smazanému dárci ".klub_ukaz_clena($d->id_clen);
    $msg.= "  (<b>{$d->jmeno} {$d->prijmeni}</b>) ";
  }
  $note= "Kliknutím na číslo daru resp. číslo dárce se zobrazí příslušná karta
          <b>Kontakty</b> resp. <b>Dary</b>.
          <br>Pozor: na kartě <b>Kontakty</b> je vhodné zvolit <i>všichni</i> a <i> smazaní</i>";
  $html.= $msg=='' ? 'Vše ok' : "<h3>Máme celkem $n takových případů:</h3>$note<dl>$msg</dl>";
  return $html;
}
# ----------------------------------------------------------------------------------- klu_dar_prevod
# kontrola rovnosti PREVOD.clen==DAR[PREVOD.dar].id_clen
function klu_dar_prevod($roky_zpet,$update=0) {
  $rok= date('Y')-$roky_zpet;
  $html= '';
  $n= $opraveno= 0;
  $qry=  "SELECT p.id_prevod,ident,clen,
                 d.id_dar,d.castka,castka_kdy,d.deleted AS d_deleted,
                 dc.id_clen,dc.jmeno,dc.prijmeni,dc.deleted AS dc_deleted,
                 pc.deleted AS pc_deleted
          FROM prevod AS p
          JOIN dar AS d ON d.id_dar=p.dar
          JOIN clen AS dc ON dc.id_clen=d.id_clen
          JOIN clen AS pc ON pc.id_clen=p.clen
          WHERE YEAR(castka_kdy)=$rok AND p.dar!=0 AND d.id_clen!=p.clen
          ORDER BY dc.prijmeni";
  $res= pdo_qry($qry);
  while ( $res && $d= pdo_fetch_object($res) ) {
    $n++;
    $oprava= "";
    if ( $update ) {
      // korekce PREVOD.clen= DAR[PREVOD.dar].id_clen
      $qu= "UPDATE prevod SET clen={$d->id_clen} WHERE id_prevod={$d->id_prevod}";
      $ru= pdo_qry($qu);
      $oprava= pdo_affected_rows() ? " OPRAVENO" : "";
      $opraveno+= $oprava ? 1 : 0;
    }
    $ddel= trim($d->d_deleted) ? "SMAZANÝ " : '';
    $dcdel= trim($d->dc_deleted) ? " SMAZANÝ" : '';
    $pcdel= trim($d->pc_deleted) ? " SMAZANÝ" : '';
    $msg.= "<br>{$ddel}dar ".klub_ukaz_dar($d->id_dar)
        ." ({$d->castka} Kč ze dne {$d->castka_kdy}, převod {$d->ident}/dárce ".klub_ukaz_clena($d->clen)
        ."$pcdel) dárce je ".klub_ukaz_clena($d->id_clen)
        ."  (<b>{$d->jmeno} {$d->prijmeni}</b>$dcdel) $oprava";
  }
  $note= "Kliknutím na číslo daru resp. číslo dárce se zobrazí příslušná karta
          <b>Kontakty</b> resp. <b>Dary</b>.
          <br>Pozor: na kartě <b>Kontakty</b> je vhodné zvolit <i>všichni</i> a <i> smazaní</i>";
  $html.= $msg=='' ? 'Vše ok' : "<h3>Máme celkem $n takových případů:</h3>$note<dl>$msg</dl>";
  return $html;
}
# ---------------------------------------------------------------------------------- klu_mrtvi_darci
# kontrola zda jsou dárci v době darování mezi živými
function klu_mrtvi_darci($rok) {
  $html= '';
  $map_zpusob= array(1=>'<b>pokladnou!</b>',2=>'převodem',3=>'složenkou',4=>'věcný');
  $msg= '';
  $n= 0;
  $qry= "SELECT id_dar,dar.id_clen as clen,castka,castka_kdy,umrti,zpusob,
        CONCAT(prijmeni,' ',jmeno,', ',obec) as jm,zpusob FROM dar
        LEFT JOIN clen ON clen.id_clen=dar.id_clen
        WHERE YEAR(castka_kdy)=$rok AND left(dar.deleted,1)!='D'
          AND umrti!='0000-00-00' AND umrti!='1968-08-21' AND dar.castka_kdy>umrti
        ORDER BY castka_kdy";
  $res= pdo_qry($qry);
  while ( $res && $d= pdo_fetch_object($res) ) {
    $n++;
    $datum= sql_date1($d->castka_kdy);
    $umrti= sql_date1($d->umrti);
    $msg.= "<dt>dar <b>{$d->id_dar}</b> ze $datum {$d->castka} Kč {$map_zpusob[$d->zpusob]} </dt>";
    $msg.= "<dd>daroval člen <b>{$d->clen}</b> {$d->jm} po své smrti dne $umrti</dd>";
  }
  $html.= $msg=='' ? 'Vše ok' : "<h3>Máme celkem $n takových případů:</h3><dl>$msg</dl>";
  return $html;
}
/** =========================================================================================> BANKY */
# ---------------------------------------------------------------------------------------- bank_rady
# zjistí úplnost řady výpisů, $dr je posun v rocích
function bank_rady($dr) {
  $result= '';
  $year= date('Y')+$dr;
  $chyb= 0;
  // projdeme všechny naše účty
  $html= $year==2011 ? "(V roce 2011 jsou kontrolovány výpisy počínaje 10. březnem)" : '';
  $celkem= 0;
  $datum= "0000-00-00";
  $qry= "SELECT * FROM _cis WHERE druh='k_ucet' AND zkratka!='' ORDER BY poradi";
  $res= pdo_qry($qry);
  while ( $res && $a= pdo_fetch_object($res) ) {
    $ucet= $a->hodnota;
    $mena= $a->data;
    $u= $a->zkratka;
    $popis= $a->popis;
//                                         if ( substr($u,0,1)!='Y' ) continue;
    // projdeme všechny výpisy daného účtu v daném roce
    $zustatek= false;
    $last= 0;
    $prefix= '';
    $AND= $year==2011 ? "AND vypis.datum>'2011-03-09'" : '';
    $qry1= "SELECT * FROM vypis WHERE ucet='$u' AND year(vypis.datum)=$year $AND ORDER BY ident";
    $res1= pdo_qry($qry1);
    $err= '';
    $oks= 0;
    $lastfile= '';
    $span_gre= "<span style='color:#2c8831'>";
    $span_gri= "<span style='color:#345'>";
    $span_red= "<span style='color:#ffaaaa'>";
    $span_end= "</span>";
    $kod= $u.substr($year,-2,2).'_';
    while ( $res1 && $v= pdo_fetch_object($res1) ) {
      $end= $v->stav;
      $beg= $v->stav_poc;
      $nnn= substr($v->ident,4,3)+0;
//         $err.= " ($nnn) ";
      $v_last= $v->ident;
      $last++;
      if ( $zustatek===false ) {
        if ( $nnn!=1 && $year!=2011 ) {
          $id= str_pad($last,3,'0',STR_PAD_LEFT);
          $err.= "chybí výpis(y) <b>$kod$id</b> ...<br>";
          $chyb++;
        }
        $v_first= $v->ident;
      }
      else if ( $v->ucet!='O' && strlen($v->soubor)!=8 ) {
        $id= str_pad($last,3,'0',STR_PAD_LEFT);
//         if ( $oks ) { $err.= "$span_gre(v souboru $lastfile)$span_end<br>"; $oks= 0; }
        $err.= "<span style='color:#345'>soubor výpisu <b>$kod$id</b> má nestandardní jméno {$v->soubor}</span><br>";
      }
      else if ( $last!=$nnn ) {
        $id= str_pad($last,3,'0',STR_PAD_LEFT);
        $err.= "chybí výpis(y) <b title='$last!=$nnn'>$kod$id</b> ...<br>";
        $chyb++;
        //
        $id= str_pad($nnn,3,'0',STR_PAD_LEFT);
        $err.= "<span style='color:#2c8831'><b title='{$v->soubor}'>$kod$id</b> ok </span>";
        $lastfile= $v->soubor;
        $oks++;
      }
      else if ( $zustatek!=$beg ) {
//         if ( $oks ) { $err.= "$span_gre(v souboru $lastfile)$span_end<br>"; $oks= 0; }
        $err.= "výpis {$v->ident} má počáteční zůstatek <b>$beg</b> místo $zustatek<br>";
        $chyb++;
      }
      else {
        $id= str_pad($last,3,'0',STR_PAD_LEFT);
        $err.= "<span style='color:#2c8831'><b title='{$v->soubor}'>$kod$id</b> ok </span>";
        $lastfile= $v->soubor;
        $oks++;
      }
//       if ( $last-1==$nnn ) {
//         $id= str_pad($last,3,'0',STR_PAD_LEFT);
//         if ( $oks ) { $err.= "<br>"; $oks= 0; }
//         $err.= "duplicitní výpis <b>$kod$id</b> ...<br>";
//       }
      $zustatek= $end;
      $last= $nnn;
    }
    $v_last= substr($v_last,4,3);
    $err= $err ? "<span style='color:red'>$err</span>" : 'ok';
    if ( !$last )
      $html.= "<h3><b>nejsou výpisy $u</b> $ucet <i>$popis</i></h3>$err";
    else
      $html.= "<h3><b>$v_first...$v_last</b> $ucet <i>$popis</i></h3>$err";
  }
  $html= (!$chyb ? "Nebyly nalezeny chyby. " : "Bylo nalezeno <b style='color:red'>$chyb chyb</b>. ").$html;
  return $html;
}
/** ************************************************************************************==> OSLOVENÍ */
# ------------------------------------------------------------------------------------- klu_osl_zrus
# rozskok na informační funkce
function klu_osl_zrus($akeys) {
  $keys= implode(',',$akeys);
  $qry= "DELETE FROM osloveni WHERE FIND_IN_SET(id_clen,'$keys')";
  $res= pdo_qry($qry);
  $n= pdo_affected_rows();
  return "Bylo zrušeno $n navržených oslovení";
}
/** =========================================================================================> MAPKY */
# ---------------------------------------------------------------------------------------- klu_mapky
# zabrazení v okresech
# používá funkce z fis.eko.php
# <mapa> = array ( 'rgb'  => 'r,g,b', 'text' => text_v_mapě, 'title' => pod_myší,
#                  'href' => click,   'xy'   => 'x,y' )
# pro dekádu je $roky_zpet=počátku dekády
function klu_mapky($case,$roky_zpet) { trace();
  $html.= '';
  switch ($case) {
  case 'darci':                   // dárci roku
    $rok= date('Y')-$roky_zpet;
    $where= "year(castka_kdy)=$rok";
    $type= array(1601=>9,1401=>8,1201=>7,1001=>6, 801=>5, 601=>4, 401=>3, 201=>2, 100=>1, 0=>0);
//     $type= array( 90=>9,   80=>8,   70=>7,   60=>6,   50=>5,   40=>4,   30=>3,   20=>2,   10=>1,  0=>0);
//     $type= array( 9=>9,   8=>8,   7=>7,   6=>6,   5=>5,   4=>4,   3=>3,   2=>2,   1=>1,  0=>0);
    $bw=   array(9=> 10, 8=> 30, 7=> 50, 6=> 70, 5=> 90, 4=>120, 3=>150, 2=>180, 1=>210, 0=>240);
    break;
  case 'dekada':                  // dárci roku ... roku+9
    $rok= $roky_zpet;
    $rok9= $rok+9;
    $where= "year(castka_kdy) BETWEEN $rok AND $rok9";
    $type= array(1601=>9,1401=>8,1201=>7,1001=>6, 801=>5, 601=>4, 401=>3, 201=>2, 100=>1, 0=>0);
    $type= array(25601=>9,12801=>8,6401=>7,3201=>6,1601=>5, 801=>4, 401=>3, 201=>2, 100=>1, 0=>0);
    $bw=   array(9=> 10, 8=> 30, 7=> 50, 6=> 70, 5=> 90, 4=>120, 3=>150, 2=>180, 1=>210, 0=>240);
//     $type= array(180=>9,  160=>8,  140=>7,  120=>6,  100=>5,   80=>4,   60=>3,   40=>2,   20=>1,  0=>0);
//     $bw=   array(9=> 10, 8=> 30, 7=> 50, 6=> 70, 5=> 90, 4=>120, 3=>150, 2=>180, 1=>210, 0=>240);
    break;
  }
  $okress= "BE,BI,BK,BM,BN,BR,BV,CB,CH,CK,CL,CR,CV,DC,DO,FM,HB,HK,HO,JC,JE,JH,JI,JN,KD,KH,KI"
        . ",KM,KO,KT,KV,LI,LN,LT,MB,ME,MO,NA,NB,NJ,OC,OP,OV,PB,PE,PH,PI,PJ,PM,PR,PS,PT,PU,PV"
        . ",PY,PZ,RA,RK,RO,SM,SO,ST,SU,SY,TA,TC,TP,TR,TU,UH,UL,UO,VS,VY,ZL,ZN,ZR,??";
  // mapka okresů
  $mapa= okresy_create();
  $pocet= array();
  foreach ( explode(',',$okress) as $okres ) {
    $mapa[$okres]['rgb']= '255,255,255';
    $pocet[$c->okres]= 0;
  }
  $suma= 0;
  $max= 0; $max_okr= '';
  // výpočet
  $qry= "SELECT IF(ISNULL(okresy.abbr),'??',okresy.abbr) as okres,count(*) as pocet
         FROM dar JOIN clen USING(id_clen)
         LEFT JOIN _psc ON clen.psc=_psc.psc
         LEFT JOIN okresy ON okresy.kodokr=_psc.kodokr
         WHERE $where
         GROUP BY okresy.abbr";
  $res= pdo_qry($qry);
  while ( $res && $c= pdo_fetch_object($res) ) {
    $pocet[$c->okres]+= $c->pocet;
  }
  foreach($pocet as $okres=>$clenu) {
    // najdi typ a barvu
    $suma+= $clenu;
    if ( $clenu>$max ) {
      $max= $clenu;
      $max_okr= $okres;
    }
    foreach($type as $n=>$typ) {
      if ( $clenu>$n ) {
        $mapa[$okres]['rgb']= "{$bw[$typ]},{$bw[$typ]},{$bw[$typ]}";
        $mapa[$okres]['title'].= "+$clenu";
        break;
      }
    }
    if ( $clenu<0 ) {
      $mapa[$okres]['rgb']= "220,200,255";
      $mapa[$okres]['title'].= $clenu;
    }
  }
  // zobrazení
  $html.= "<div class='graph'>"; //<h3 class='graph_title'>$title</h3>";
  $html.= okresy_show($mapa,1,"align='center'",'255,255,255','get');
  $html.= "<br clear='all'/></div>";
  $html.= "CELKEM: $suma, MAXIMUM: $max v $max_okr";
  return $html;
}
function klu_mapky_skala($stupnu=10) {
  $bw= array();
  for ($i= $stupnu-1; $i>=0; $i--) {
    $c= 250*($stupnu-$i)/$stupnu;
    $bw[$i]= $c;
  }
//                                                         debug($bw,'škála');
  return $bw;
}
?>
