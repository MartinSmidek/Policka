<?php # (c) 2011 Martin Smidek <martin@smidek.eu>
# ----------------------------------------------------------------------------------==> ch ban_zmena
# 
# 
#  nutno upravit do nového způsobu, kdy dar.typ=7 implikuje dar.idc!=0
# 
# 
# # ASK
# $idc_new je buďto '*' pokud se ID plátce nemá měnit, nebo nové ID plátce
# povolené kombinace old(typ,idc)->new(typ,idc) 
#   jedar     (6,0)->(5,0)
#   nedar     (5,0)->(6,0)
#   spojit    (5/7/8,0)->(8/9,x)
#   rozpojit  (9,x)->(5/6,0) nebo (8,x)->(8,0)
# upozornění na chybu obsluhy
#   napřed rozpoj (8/9,x)->(8/9,y)
#   napřed rozpoj (8/9,x)->(5/6,0)
#   už je dar (8/9,x)->(5,0)
# možné hodnoty old (1/5/6/7/8,0),(8/9,x)
function ch_ban_zmena($idd,$typ_new,$idc_new='*') {  trace();
  $y= (object)array('err'=>'','msg'=>'ok');
  $upd= array();
  // zjištění starých hodnot 
  list($typ_old,$idc_old)= select('typ,id_clen','dar',"id_dar=$idd");
  // test zakázaných kombinací
  if (!(!$idc_old && in_array($typ_old,array(1,5,6,7,8)) 
      || $idc_old && in_array($typ_old,array(8,9)))) {
    $y->err= "chybná vazba - je nutné opravit! Martin)";
    goto end;
  }
  // test upozornění na chybný požadavek
  if ( $idc_old && in_array($typ_old,array(8,9)) && $idc_new && in_array($typ_new,array(8,9)) ) {
    $y->err= "napřed rozpoj od již zapsaného dárce";
    goto end;
  }
  // rozbor povolených operací
  if ( $typ_old==6 && !$idc_old && $typ_new==5 && !$idc_new ) { // jen změna typu
    $upd[]= (object)array('fld'=>'typ', 'op'=>'u','val'=>$typ_new,'old'=>$typ_old);
  }
  elseif ( $typ_old==5 && !$idc_old && $typ_new==6 && !$idc_new ) { // jen změna typu
    $upd[]= (object)array('fld'=>'typ', 'op'=>'u','val'=>$typ_new,'old'=>$typ_old);
  }
  elseif ( in_array($typ_old,array(5,7,8)) && !$idc_old // spojit
      && in_array($typ_new,array(8,9)) && $idc_new ) { 
    $upd[]= (object)array('fld'=>'typ', 'op'=>'u','val'=>$typ_new,'old'=>$typ_old);
    $upd[]= (object)array('fld'=>'id_clen', 'op'=>'u','val'=>$idc_new,'old'=>$idc_old);
  }
  elseif ( $typ_old==9 && $idc_old && in_array($typ_new,array(5,6)) && !$idc_new ) { 
    $upd[]= (object)array('fld'=>'typ', 'op'=>'u','val'=>$typ_new,'old'=>$typ_old);
    $upd[]= (object)array('fld'=>'id_clen', 'op'=>'u','val'=>$idc_new,'old'=>$idc_old);
  }
  elseif ( $typ_old==8 && $idc_old && $typ_old==8 && !$idc_new ) { 
    $upd[]= (object)array('fld'=>'id_clen', 'op'=>'u','val'=>$idc_new,'old'=>$idc_old);
  }
  else {
    $y->err= "nepřípustný požadavek na změnu ($typ_old,$idc_old)->($typ_new,$idc_new)";
    goto end;
  }
  // proveď změnu se zápisem do _track
  ezer_qry("UPDATE",'dar',$idd,$upd);
end:
  return $y;
}
# -----------------------------------------------------------------------------------==> ch ban_load
# viz https://php.vrana.cz/vyhledani-textu-bez-diakritiky.php
function ch_search_popis($popis) {
  $popis= utf2ascii($popis,' .');
  $popis= strtr($popis,array(
      'mgr.'=>'', 'mudr.'=>'', 'mvdr.'=>'', 'rndr.'=>'', 'ing.'=>'', 'bc.'=>'', 
      '_'=>''));
  $popis= trim($popis);
  $cond1= "CONCAT(ascii_prijmeni,' ',ascii_jmeno) LIKE '%$popis%'";
  $cond2= "CONCAT(ascii_jmeno,' ',ascii_prijmeni) LIKE '%$popis%'";
//  $cond1= "REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(prijmeni,' ',jmeno),
//    'č','c'),'ř','r'),'š','s'),'ž','z') LIKE '%$popis%'";
//  $cond2= "REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(jmeno,' ',prijmeni),
//    'č','c'),'ř','r'),'š','s'),'ž','z') LIKE '%$popis%'";
  return "($cond1 OR $cond2)";
}
# ===========================================================================================> BANKA
# -------------------------------------------------------------------------------- ch bank_join_dary
# spáruj dary výpisu 
function ch_bank_join_dary ($idv) {
  $rv= pdo_query("SELECT id_dar FROM dar WHERE id_vypis=$idv AND typ=5 AND ucet_popis!='' ");
  while ($rv && (list($idd)=pdo_fetch_row($rv))) {
    ch_bank_join_dar($idd);
  }
}
# --------------------------------------------------------------------------------- ch bank_join_dar
# spáruj dar
function ch_bank_join_dar ($idd) {
  // podrobnosti z převodu a cískání podmínky na popis
  list($castka,$datum,$popis,$typ)= 
      select('castka,castka_kdy,ucet_popis,typ','dar',"id_dar=$idd");
  if ($typ==9) goto end;
  $cond= ch_search_popis($popis);
  // hledání dárce
  list($idd2,$idc)= select('id_dar,id_clen','dar JOIN clen USING (id_clen)',
      "zpusob=2 AND typ=9 AND dar.deleted='' AND $cond AND castka=$castka AND castka_kdy='$datum' ");
  if ($idd2) {
    display("idc=$idc, idd2= $idd2");
    query("UPDATE dar SET deleted='D x' WHERE id_dar=$idd2");
    query("UPDATE dar SET typ=9,id_clen=$idc WHERE id_dar=$idd");
  }
  else {
    $idc= select('id_clen','clen',$cond);
    if ($idc) {
      display("idc=$idc, idd2= ---");
      query("UPDATE dar SET typ=7,id_clen=$idc WHERE id_dar=$idd");
    }
    display("? $popis ");
  }
end:
}
# -------------------------------------------------------------------------------- ch bank_load_ucty
function ch_bank_pub($pub,&$p,&$u,&$b,$padding=true) {
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
# -------------------------------------------------------------------------------- ch bank_load_ucty
# $bank_nase_banky = array ('0100',...)
# $bank_nase_ucty  = array ('0100'=> array('000000-1234567890'=>'X'),...)
# $bank_nase_nucty = array ('X'=> n) -- n je data účtu v _cis.druh=='k_ucty'
function ch_bank_load_ucty () {
  global $bank_nase_banky, $bank_nase_ucty, $bank_nase_nucty;
  if ( !isset($bank_nase_banky) ) {
    $bank_nase_ucty= array();
    $bank_nase_banky= array();
    $qry= "SELECT * FROM _cis WHERE druh='b_ucty' AND ikona!='' ";
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
# -----------------------------------------------------------------------------------==> ch ban_load
# ASK
# načtení souboru CSV z ČSAS
function ch_ban_load($file) {  trace();
  global $ezer_path_root;
  $y= (object)array('err'=>'','msg'=>'ok');
  // načti vlastní účty
  $nase_ucty= array(); // účet -> ID
  $res= pdo_qry("SELECT data,ikona FROM _cis WHERE druh='b_ucty' AND ikona!='' ");
  while ( $res && list($idu,$u_b)= pdo_fetch_row($res) ) {
    list($u,$b)= explode('/',$u_b);
    if ($b!='0800') { $y->err= "import je možný (zatím) pouze z ČSAS"; goto end; }
    $nase_ucty[$u]= $idu;
  }
  debug($nase_ucty);
  $csv= "$ezer_path_root/banka/$file";
  $data= array();
//  $msg= 
      ch_csv2array($csv,$data,0,',','UTF-16LE');
//  debug($data,"$csv - $msg");
  // účet organizace, způsob platby
  $zpusob= 2;
  $nas_ucet= 1;
  // ověření existence základních položek
  $flds= array(
      "Datum zaúčtování"  => array(0,'d','castka_kdy'),
      "Název protiúčtu"   => array(0,'n','ucet_popis'),
      "Protiúčet"         => array(0,'u','ucet'),
      "Částka"            => array(0,'c','castka'),
      "Měna"              => array(0,'m')
    );
  foreach ($data as $i=>$rec) {
    // v prvním průchodu proveď kotroly a založ záznam pro výpis
    if ($i==0) {
      // ověření existence základních položek
      foreach ($rec as $fld=>$val) {
        if (isset($flds[$fld])) $flds[$fld][0]++;
      }
      foreach ($flds as $fld=>$desc) {
        if (!$desc[0]) { $y->err= "ve výpisu chybí povinné pole '$fld'"; goto end; }
      }
      // zkontroluj název souboru: účet_0800_yyyy_mm_dd_yyyy_mm_dd.csv
      $m= null;
      if (!preg_match("~(\d+)_0800_(\d\d\d\d_\d\d_\d\d)_(\d\d\d\d_\d\d_\d\d)\.csv~",$file,$m)) { 
        $y->err= "vložený soubor nemá standardní pojmenování:<br> 'účet-0800_od_do.csv', 
          <br>kde od a do jsou datumy ve tvaru rrr-mm-dd"; goto end; }
          debug($m);
      // vytvoř výpis
      $idu= isset($nase_ucty[$m[1]]) ? $nase_ucty[$m[1]] : 0;
      if (!$idu) { $y->err= "'$m[1]' není mezi účty zapsanými v Nastavení"; goto end; }
      $od= str_replace('_','-',$m[2]);
      $do= str_replace('_','-',$m[3]);
      query("INSERT INTO vypis SET soubor='$file', nas_ucet=$idu, datum_od='$od', datum_do='$do' ");
      $idv= pdo_insert_id();
    }
    // vložení záznamu
    $set= ''; $castka= 0; $ucet= $popis= '';
    foreach ($rec as $fld=>$val) {
      list(,$fmt,$f)= $flds[$fld];
      switch ($fmt) {
        case 'd': $set.= ", $f='".sql_date($val,1)."'"; break;
        case 'n': $popis= $val; 
                  $set.= ", $f='$val'"; break;
        case 'u': $ucet= $val; 
                  $set.= ", $f='$val'"; break;
        case 'c': $castka= preg_replace(array("/\s/u","/,/u"),array('','.'),$val);
                  $set.= ", $f=$castka"; break;
        case 'm': if ($val=='CZK') break;
                  $y->err= "nekorunové platby nejsou implementovány"; goto end;
      }
    }
    // určení typu
    $typ= $castka<=0 ? 1 : ($ucet=='160987123/0300' && $popis=='CESKA POSTA, S.P.' ? 8 : 5);
    // vložení záznamu
    $qry= "INSERT INTO dar SET id_vypis=$idv, nas_ucet=$nas_ucet, typ= $typ, zpusob=$zpusob $set ";
    display($qry);
    query($qry);
  }
end:
  return $y;
}
