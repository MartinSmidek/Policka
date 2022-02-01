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
    $ret->osoba= 0;
    $ret->firma= (string)$z->Obchodni_firma;
    $ret->ico=      (string)$z->ICO;
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
//# zobrazí odkaz na dar
//function klub_ukaz_dar($id_dar,$barva='') {
//  $style= $barva ? "style='color:$barva'" : '';
//  return "<b><a $style href='ezer://klu.dry.show_dar/$id_dar'>$id_dar</a></b>";
//}
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
# --------------------------------------------------------------------------------- klub clen_delete
# smaže člena, pokud k němu není nic připnuto
function klub_clen_delete($idc) {
  global $USER;
  $msg= '';
  $n= select('COUNT(*)','role',"id_firma=$idc OR id_osoba=$idc");
  $m= select('COUNT(*)','dar',"id_clen=$idc AND deleted='' ");
  if ($n||$m) {
    if ($n) {
      $msg= "před smazáním je třeba od tohoto kontaktu odepnout $n připnutí";
    }
    elseif ($m) {
      $msg= "před smazáním osoby je třeba smazat nebo přepsat jejích $m darů";
    }
  }
  else {
    $D= "D {$USER->abbr} ".date('j.n.Y');
    ezer_qry("UPDATE",'clen',$idc,array((object)array('fld'=>'deleted', 'op'=>'u','val'=>$D)));
  }
  return $msg;
}
# --------------------------------------------------------------------------------- klub role_odepni
# odepne osobu z firmy
function klub_role_odepni($idf,$ido) {
  query("DELETE FROM role WHERE id_firma=$idf AND id_osoba=$ido");
}
# -------------------------------------------------------------------------------- klub oprav_prevod
# opraví dárce v převodu
function klub_oprav_prevod($ident,$id_clen) {
  query("UPDATE prevod SET clen=$id_clen WHERE ident='$ident' ");
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
    $m= null;
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
function klub_dary_suma ($id_clen) {  trace();
  $suma= 0;
  $qry= "SELECT sum(castka) as suma FROM dar AS dd
         WHERE LEFT(deleted,1)!='D' AND id_clen=$id_clen 
           AND typ IN (8,9) ";
  $res= pdo_qry($qry);
  if ( $res && $u= pdo_fetch_object($res) ) {
    $suma= $u->suma;
  }
  return number_format($suma,2,'.','');
}
/** ***********************************************************************************==> INFORMACE */
# ------------------------------------------------------------------------------------------ klu_inf
# rozskok na informační funkce
function klu_inf($par) {
  $html= '';
  switch($par->fce) {
    case 'stat': 
      $html= klu_inf_stat(); 
      break;
    case 'vyvoj': 
      $html= klu_inf_vyvoj($par->p); 
      break;
    case 'dary_dupl':
      $rok= date('Y') - $par->p;
      $msg= dop_kon_dupl($rok,$par->corr);
      $html= $msg=='' ? "vše ok" : $msg;
      break;
  }
  return $html;
}
# ------------------------------------------------------------==> . duplicitní dary
# MENU
# kontrola darů roku
function dop_kon_dupl($rok,$corr) {
  $map_zpusob= map_cis('k_zpusob','hodnota');
  $r= " align='right'";
  $html= '';
  $err= 0;
  $msg= '';
  $n_del= $n_kop= $n_ruc= 0;
  $qry= mysql_qry("
    SELECT castka_kdy,castka,zpusob,id_clen,count(*) AS _pocet_,
      prijmeni,jmeno,SUM(IF(
        diky_kdy OR potvrz_kdy OR popis!='' OR stredisko!=0  OR d.darce,1,0)),
        GROUP_CONCAT(id_dar)
    FROM dar AS d
    LEFT JOIN clen AS c USING (id_clen)
    LEFT JOIN vypis AS v USING (id_vypis)
    WHERE YEAR(castka_kdy)=$rok AND LEFT(d.deleted,1)!='D'
    GROUP BY castka_kdy,castka,d.id_clen,d.zpusob HAVING _pocet_>1 ORDER BY castka_kdy");
  while ( $qry 
    && list($datum,$castka,$zpusob,$idc,$pocet,$prijmeni,$jmeno,$rucne,$idds)= pdo_fetch_row($qry) ) {
    $datum= sql_date1($datum);
    $zpusob= $map_zpusob[$zpusob];
    $clen= klub_ukaz_clena($idc);
    if ( $rucne ) { // na vyžádání automatická oprava
      $pozn= $rucne; 
      if ($corr==2 && $zpusob=='bankou') {
        list($delete,$dkdy,$pkdy,$poz,$pop,$str)= 
            select('id_dar,diky_kdy,potvrz_kdy,pozn,popis,stredisko',
                'dar',"id_dar IN ($idds) AND id_vypis=0");
        $update= select('id_dar','dar',"id_dar IN ($idds) AND id_vypis!=0");
        if ($delete && $update) {
          $pozn.= " doplnit údaje: $update, smazat: $delete z $idds";
          // smažeme starý dar
          $new= 'D duplicita';
          ezer_qry("UPDATE",'dar',$delete,array(
            (object)array('fld'=>'deleted',  'op'=>'u','val'=>$new)
          ));
          // zkopírujeme údaje do nového
          ezer_qry("UPDATE",'dar',$update,array(
            (object)array('fld'=>'diky_kdy',  'op'=>'u','val'=>$dkdy),
            (object)array('fld'=>'potvrz_kdy','op'=>'u','val'=>$pkdy),
            (object)array('fld'=>'pozn',      'op'=>'u','val'=>$poz),
            (object)array('fld'=>'popis',     'op'=>'u','val'=>$pop),
            (object)array('fld'=>'stredisko', 'op'=>'u','val'=>$str)
          ));
          $n_kop++;
        }
        else {
          $pozn.= " upravit ručně";
          $n_ruc++;
        }
      }
    }
    else { // na vyžádání automatický výmaz
      $pozn= '';
      if ($corr==1 && $zpusob=='bankou') {
        $delete= select('id_dar','dar',"id_dar IN ($idds) AND id_vypis=0");
        if ($delete) {
          $pozn.= "smazat: $delete z ($idds)";
          // smažeme starý dar
          $new= 'D duplicita';
          ezer_qry("UPDATE",'dar',$delete,array(
            (object)array('fld'=>'deleted',  'op'=>'u','val'=>$new)
          ));
          $n_del++;
        }
        else {
          $pozn.= " upravit ručně";
          $n_ruc++;
        }
      }
    }
    $msg.= "<tr><td$r>$datum</td><td>$zpusob</td><td$r><b>$castka,-</b></td><td>{$pocet}x</td>
      <td$r>$clen</td><td>$prijmeni $jmeno</td><td>$pozn</td></tr>";
    $err++;
  }
end:  
  $html.= $msg=='' ? 'Nebyl zjištěn žádný problém' : "<h3>Podezřelé (stejný dárce, den a cesta) zápisy darů v roce $rok</h3>";
  $html.= $corr ? "$n_del darů bylo smazáno, $n_kop údajů převedeno, ručně zbývá posoudit $n_ruc takových duplicit<hr>" : '';
  $html.= "<table>$msg</table>";
  if ( $err ) $html= "CELKEM JE PODEZŘELÝCH DARŮ: $err<hr>$html";
  return $html;
}
# ------------------------------------------------------------------------------------- klu_inf_stat
# základní statistika
function klu_inf_stat() { trace();
  $html= '';
  // kontakty
  $c= pdo_object("SELECT count(*) as _pocet FROM clen
                    WHERE left(clen.deleted,1)!='D' AND umrti=0 ");
  $html.= "<br>počet známých kontaktů = <b>{$c->_pocet}</b>";
  // dárci
  $clenu= $daru= 0;
  $qry= "SELECT count(*) as _pocet FROM dar JOIN clen USING(id_clen)
         WHERE left(dar.deleted,1)!='D' AND typ IN (8,9)
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
         WHERE left(dar.deleted,1)!='D' AND left(clen.deleted,1)!='D' 
           AND (typ=9 OR typ=8 AND dar.id_clen!=0)";
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
  $html.= "<tr>";
  $html.= "<th>$r</th>";
  $x= number_format(round($x1), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $x= number_format(round($x2), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $x= number_format(round($x3), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $x= number_format(round($x4), 0, '.', ' ');  $html.= "<td align='right'>$x</td>";
  $html.= "</tr>";
  return $html;
}
?>
