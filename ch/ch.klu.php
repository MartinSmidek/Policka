<?php # (c) 2011-2015 Martin Smidek <martin@smidek.eu>
/** *************************************************************************************==> CLENOVE */
# --------------------------------------------------------------------------------------- klub vyber
# pro cmd='options' sestaví podmínky výběru kontaktů 
# pro cmd='cond' vrací SQL vybrané podmínky pro daný klíč
function klub_vyber($cmd,$key=0) {
  $conds= array(); // [key:{nazev,cond},...]
  $conds[1]= (object)array(nazev=>'všichni',cond=>" 1");
  $rk= pdo_query("SELECT data,hodnota FROM _cis WHERE druh='kategorie' ORDER BY zkratka ");
  while ($rk && (list($data,$nazev)= pdo_fetch_row($rk))) {
    $conds[$data+10]= (object)array(nazev=>"kategorie - $nazev",cond=>" FIND_IN_SET('$data',kategorie)");
  }
  $conds[100]= (object)array(nazev=>'změny tohoto měsíce',cond=>" month(c.zmena_kdy)=month(now()) and year(c.zmena_kdy)=year(now()) ");
  $conds[101]= (object)array(nazev=>'změny kým ...',cond=>" c.zmena_kdo=\$user");
  $conds[102]= (object)array(nazev=>'změny dne ...',cond=>" left(c.zmena_kdy,10)='\$datum'");
  $conds[103]= (object)array(nazev=>'změny dne ... kým ...',cond=>" c.zmena_kdo=\$user and left(c.zmena_kdy,10)='\$datum'");
  switch($cmd) {
    case 'options':
      $selects= $del= '';
      foreach ($conds as $key=>$desc) {
        $css= $key>=100 ? ":nasedly" : '';
        $selects.= "$del{$desc->nazev}:$key$css"; $del= ',';
      }
      return $selects;
    case 'cond':
      $desc= $conds[$key];
      return $desc->cond;
  }
}
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
# --------------------------------------------------------------------------------- klub role_pripni
# připne osobu k firmě
function klub_role_pripni($idf,$ido) {
  $ret= (object)array(msg=>'',ido=>0);
  list($idr,$role)= select('id_role,popis','role',"id_firma=$idf AND id_osoba=$ido");
  if ($idr) {
    $ret->msg= "POZOR tato osoba již má ve firmě roli '$role'";
  }
  elseif ($idf==$ido) {
    $ret->msg= "POZOR pokoušíte se připnout firmu k sobě samé ";
  }
  else {
    query("INSERT INTO role SET id_firma=$idf, id_osoba=$ido");
  }
  return $ret;
}
# --------------------------------------------------------------------------------- klub role_odepni
# odepne osobu z firmy
function klub_role_odepni($idf,$ido) {
  query("DELETE FROM role WHERE id_firma=$idf AND id_osoba=$ido");
}
# -------------------------------------------------------------------------------- klub oprav_prevod
# opraví dárce v převodu
function klub_oprav_prevod($ident,$id_clen) {
  $qu= "UPDATE prevod SET clen=$id_clen WHERE ident='$ident' ";
  $ru= pdo_qry($qu);
  $opraveno= pdo_affected_rows() ? 1 : 0;
  return $opraveno;
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
/** ****************************************************************************************==> DARY */
# ----------------------------------------------------------------------------------- klub dary_suma
# ASK: vrátí součet darů dárce, $strediska je seznam _cis.data středisek
function klub_dary_suma ($id_clen,$strediska) {  trace();
  $suma= 0;
  $qry= "SELECT sum(castka) as suma FROM dar AS dd
         -- LEFT JOIN _cis ON dd.varsym=data AND druh='varsym'
         WHERE LEFT(deleted,1)!='D' AND id_clen=$id_clen -- AND _cis.zkratka=1 $strediska";
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
