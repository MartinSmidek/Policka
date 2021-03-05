<?php # (c) 2007-2008 Martin Smidek <martin@smidek.eu>
/** ========================================================================================> EKONOM */
# ------------------------------------------------------------------------------------- eko uzaverka
# nastaví den v číselníku jako den uzávěrky
# údaje v tabulce dar bude možné měnit jen uživatelem s oprávněním hddeu
function eko_uzaverka($den_sql) { trace(); 
  query("UPDATE _cis SET ikona='$den_sql' WHERE druh='uzaverky' AND data=1");
  $html= '';
  return $html;
}
# --------------------------------------------------------------------------------- eko uzaverka_den
# vrátí aktuální den uzávěrky
function eko_uzaverka_den() { trace(); 
  $den= select('ikona','_cis',"druh='uzaverky' AND data=1");
  return $den;
}
# ------------------------------------------------------------------------------------- eko rok_dary
# export=1 způsobí export do Excelu
# parms.nuly - povolí nulové řádky
function eko_rok_dary($export,$parms,$opts) { trace(); debug($parms); debug($opts);
  $rok= $opts->rok;
  $zpusob= $parms->vecne ? "zpusob=4" : "zpusob!=4";
  $mesice= array(1=>'leden','únor','březen','duben','květen','červen',
    'červenec','srpen','září','říjen','listopad','prosinec');
  $html= '';
  $err= '';
  $dary= array();
  $strediska= array();
  $map_stredisko= map_cis('stredisko','hodnota');
  $map_deleni= map_cis('deleni','zkratka');
  $flds= 'id_clen,titul,prijmeni,jmeno,osoba,ulice,obec,psc,castka,varsym,_pro,castka_kdy';
  $flds.= $vecne ? ",popis" : ",zpusob,_ucet";
  // střediska a jejich vs
  $rd= pdo_qry("
    SELECT data,ikona,hodnota,barva,zkratka
    FROM ezer_ck._cis  WHERE druh='varsym'
     --  AND data IN (1000,1100,1111,8002,8300)
    ORDER BY ikona,data");
  while ( $rd && list($vs,$stredisko,$nazev,$deleni,$dary)= pdo_fetch_row($rd) ) {
    $nazev= str_replace("\n",' ',$nazev);
    $pr= $deleni ? $map_deleni[$deleni] : '';
    $strediska[$stredisko][$vs]= array($nazev,$dar,$pr);
  }
  // dary
  $rd= pdo_qry("
    SELECT MONTH(castka_kdy) AS _mesic,SUM(castka),varsym
    FROM dar WHERE NOT left(dar.deleted,1)='D' AND YEAR(castka_kdy)=$rok AND $zpusob
     -- AND varsym IN (1000,1100,1111,8002,8300)
    GROUP BY _mesic,varsym ORDER BY _mesic,varsym ");
  while ( $rd && list($m,$kc,$vs)= pdo_fetch_row($rd) ) {
    $dary[$vs][$m]+= $kc;
  }
  // filtry po dotazu
  if ( !$opts->nuly ) {
    foreach ($map_stredisko as $s=>$stredisko) if ($strediska[$s]) {
      $subtotal= 0;
      foreach ($strediska[$s] as $vs=>$vss) {
        list($nazev,$dar,$pr)= $vss;
        $celkem= 0;
        for ($m=1; $m<=12; $m++) {
          $kc= $dary[$vs][$m];
          $celkem+= $kc;
          $subtotal+= $kc;
        }
        if ( !$celkem ) {
          unset($strediska[$s][$vs]);
          unset($dary[$vs]);
        }
      }
      if ( !$subtotal ) {
        unset($map_stredisko[$s]);
      }
    }
  }
//                                                         debug($map_deleni);
//                                                         debug($strediska);
//                                                         debug($dary);
  // ------------------------------------------------- tabulka v HTML
  // hlavička
  $th= "th";
  $thm= "th"; // style='font-size:8pt'";
  $tdc= "td style='text-align:right'";
  $thr= "th style='text-align:right'";
  $thl= "th style='text-align:left;background-color:gold'";
  $tht= "th style='text-align:right;background-color:orange'";
  $ths= "th style='text-align:right;background-color:silver'";
  $tab= "<table class='stat'><tr><th>$rok</th>";
  for ($m=1; $m<=12; $m++) {
    $tab.= "<$thm>$mesice[$m]</th>";
  }
  $tab.= "<$th>Celkem</th><$th>P/R/S/INV</th><$th>název akce</th></tr>";
  $total= array();
  // střediska
  foreach ($map_stredisko as $s=>$stredisko) if ($strediska[$s]) {
    $tab.= "<tr><$thl colspan=15>$stredisko</th><td></td></tr>";
    // vs
    $subtotal= array();
    foreach ($strediska[$s] as $vs=>$vss) {
      list($nazev,$dar,$pr)= $vss;
      $tab.= "<tr><$thr>$vs</th>";
      $celkem= 0;
      // měsíce
      for ($m=1; $m<=12; $m++) {
        $kc= round($dary[$vs][$m]);
        $celkem+= $kc;
        $subtotal[$m]+= $kc;
        $total[$m]+= $kc;
        $tab.= "<$tdc>$kc</td>";
      }
      $tab.= "<$thr>$celkem</th><$th>$pr</th><td>$nazev</td></tr>";
    }
    // mezisoučty
    $tab.= "<tr><$ths>Součet</th>";
    $celkem= 0;
    for ($m=1; $m<=12; $m++) {
      $kc= $subtotal[$m];
      $celkem+= $kc;
      $tab.= "<$ths>$kc</th>";
    }
    $tab.= "<$ths>$celkem</th><$ths>SUBTOTAL</th><td></td></tr>";
  }
  // total
  $tab.= "<tr><$tht>Celkem</th>";
  $celkem= 0;
  for ($m=1; $m<=12; $m++) {
    $kc= $total[$m];
    $celkem+= $kc;
    $tab.= "<$tht>$kc</th>";
  }
  $tab.= "<$tht>$celkem</th><$tht>TOTAL</th><td></td></tr>";
  $tab.= "</table>";
  if ( !$export ) goto end;
  // ------------------------------------------------- tabulka v Excelu
  // soubor
  $fname= "rocni_prehled_$rok";
  $titl= "Dary za rok $rok";
  $xls= "|open $fname\n|sheet list1;;L;page\n";
  // hlavička
  $h=  "bcolor=aac0e2c2";
  $hs= "bcolor=aae2c222";
  $hc= "bcolor=aae2e2e2";
  $ht= "bcolor=aae2c222";
  $wm= 10;
  $head= "";
  $columns= "columns A=$wm";
  $n= 3;
  $m= 1;
  for ($ms=1; $ms<=12; $ms++) {
    $A= Excel5_n2col($ms);
    $head.= "|$A$n $mesice[$ms] ::$h border=t,t,t,t";
    $columns.= ",$A=$wm";
  }
  $A++;
  $head.= "|$A$n Celkem ::$h border=t,t,t,t";
  $columns.= ",$A=$wm";
  $A++;
  $wt= 10;
  $head.= "|$A$n P/R/S/INV ::$h border=t,t,t,t";
  $columns.= ",$A=$wt";
  $A++;
  $wa= 50;
  $head.= "|$A$n název akce ::$h border=t,t,t,t";
  $columns.= ",$A=$wa";
  $xls.= "$columns\n|A1 $titl ::bold size=14\n|A$n $a2 ::$h border=t,t,t,t $head\n";
  $total= array();
  // střediska
  foreach ($map_stredisko as $s=>$stredisko) if ($strediska[$s]) {
    $n++;
    $A= Excel5_n2col(0);
    $xls.= "|$A$n $stredisko ::$hs border=t|A$n:P$n bold merge\n";
    // vs
    $subtotal= array();
    foreach ($strediska[$s] as $vs=>$vss) {
      list($nazev,$dar,$pr)= $vss;
      $n++; $m= 0;
      $xls.= "|A$n $vs ::$h border=t\n";
      $celkem= 0;
      // měsíce
      for ($m=1; $m<=12; $m++) {
        $kc= $dary[$vs][$m];
        $celkem+= $kc;
        $subtotal[$m]+= $kc;
        $total[$m]+= $kc;
        $A= Excel5_n2col($m);
        $xls.= "|$A$n $kc ::border=t\n";
      }
      $A= Excel5_n2col($m++);
      $xls.= "|$A$n $celkem ::$h border=t\n";
      $A= Excel5_n2col($m++);
      $xls.= "|$A$n $pr ::center border=t\n";
      $A= Excel5_n2col($m++);
      $xls.= "|$A$n $nazev ::border=t\n";
    }
    // mezisoučty
    $n++;
    $xls.= "|A$n Součet ::$hc border=t\n";
    $celkem= 0;
    for ($m=1; $m<=12; $m++) {
      $kc= $subtotal[$m];
      $celkem+= $kc;
      $A= Excel5_n2col($m);
      $xls.= "|$A$n $kc ::$hc border=t\n";
    }
    $A= Excel5_n2col($m++);
    $xls.= "|$A$n $celkem ::$hc border=t\n";
    $A= Excel5_n2col($m++);
    $xls.= "|$A$n Subtotal ::$hc border=t\n";
    $A= Excel5_n2col($m++);
    $xls.= "|$A$n ::border=t\n";
  }
  // total
  $n++;
  $xls.= "|A$n Celkem ::$ht border=t\n";
  $celkem= 0;
  for ($m=1; $m<=12; $m++) {
    $kc= $total[$m];
    $celkem+= $kc;
    $A= Excel5_n2col($m);
    $xls.= "|$A$n $kc ::$ht border=t\n";
  }
  $A= Excel5_n2col($m++);
  $xls.= "|$A$n $celkem ::$ht border=t\n";
  $A= Excel5_n2col($m++);
  $xls.= "|$A$n Total ::$ht border=t\n";
  $A= Excel5_n2col($m++);
  $xls.= "|$A$n ::border=t\n";
xls:
  $xls.= "\n|close";
  $err= @Excel2007($xls,1);
  if ( $err ) $html.= "$err<hr>".nl2br($xls);
  else $html.= "Stáhněte si tabulku s exportem do Excelu: <a href='docs/$fname.xlsx'>$fname.xls</a><br/><br/>";
end:
  $html.= $tab;
  return $html;
}
# ------------------------------------------------------------------------------------ eko_histogram
# histogram darů v daném období
#   $par->deleni= sub1,sub2;main1;main2 středník odděluje hlavní dělení, čárka poddělení
#   $par->anonym=0      1 => anonym se bere jako 1 člověk
#   $par->jednotlive=0  1 => neprovádí se sdružování podle dárců
# $export=1 způsobí export do Excelu
function eko_histogram($export,$od,$do,$vecne,$par,$deleni) { trace();
  $html= '';
  $tab= $divdel= $sum= array();
  $od_sql= sql_date($od,1);
  $do_sql= sql_date($do,1);
  $cond= $vecne ? "zpusob=4" : "zpusob!=4";
  // vytvoření groupovací podmínky
//   $deleni= '300,700,1000;1500,2000;3000,4000,5000;10000;20000;50000;100000';
  $div= preg_split("/([,;])/",$deleni,-1,PREG_SPLIT_DELIM_CAPTURE);
  $group= "CASE ";
  $last= 0;
  for($i= 0; $i<count($div); $i+= 2) {
    $group.= "\n  WHEN dary>=$last AND dary<{$div[$i]} THEN ".$i/2;
    $divdel[count($div)/2-$i/2+1]= $div[$i+1];
    $last= $div[$i];
  }
  $last_i= $i/2;
  $end= $last_i;
  $sumi= 0;
  for($i= $last_i; $i>0; $i--) {
    if ( $divdel[$i]==',' ) {
      $sum[$i]= 0;
      $sumi++;
    }
    else {
      $sum[$i]= $sumi;
      $sumi= 0;
    }
  }
//                                         debug($sum,"sum");
//                                         debug($divdel,$last_i);
  $group.= "\n  ELSE $last_i \n END ";
  $group_clen= $par->jednotlive ? "id_dar" : (
               $par->anonym ? "id_clen" : "IF(id_clen=9999,id_dar,id_clen)" );
  // výpočet
  $qry= "SELECT count(*) AS _pocet, sum(dary) AS _suma, $group AS _div FROM
           (SELECT sum(castka) AS dary FROM dar JOIN clen USING(id_clen)
            WHERE NOT left(dar.deleted,1)='D' AND NOT left(clen.deleted,1)='D' AND $cond
           AND castka_kdy BETWEEN '$od_sql' AND '$do_sql' GROUP BY $group_clen) AS d
         GROUP BY _div ORDER BY _div DESC";
  $res= pdo_qry($qry);
  $n= $i= 0;
  while ( $res && $d= pdo_fetch_object($res) ) {
    $n++; $i++;
    foreach (explode(',','_div,_mezi,_pocet,_suma') as $f) {
      switch ($f) {
      case '_div':
        $k= $d->$f;
        $tab[$n][$f]= ($k ? $div[2*$k-2] : 0).($k==$last_i ? " a více" : " až ".($div[2*$k]-1));
        break;
      case '_mezi':
        if ( $sum[$i] ) {
          $tab[$n][$f]= $d->$f;
        }
        break;
      case '_pocet':
        $tab[$n][$f]= $d->$f;
        $tab['*&sum;'][$f]+= $d->$f;
        break;
      case '_suma':
        $tab[$n][$f]= round($d->$f,0);
        $tab['*&sum;'][$f]+= round($d->$f,0);
        break;
      default:
        $tab[$n][$f]= $d->$f;
      }
    }
    if ( $sum[$i] ) {
      $n++;
//                                                 display("$n:$k,{$sum[$i]},$last_i");
      $odk= 2*$k-4-2*$sum[$i];
      $tab[$n]['_div']= ($odk>0 ? $div[$odk] : 0).' až '.($div[2*$k-2]-1);
      $tab[$n]['_s']= $sum[$i]+1;
    }
  }
  // doplnění mezisoučtů
  for($i= 1; $i<=$n; $i++) {
    $s= $tab[$i]['_s'];
    if ( $s ) {
      $suma= $pocet= 0;
      for($mc= 1; $mc<=$s; $mc++) {
        $pocet+= $tab[$i+$mc]['_pocet'];
        $suma+= $tab[$i+$mc]['_suma'];
        $tab[$i+$mc]['_mezi']= $tab[$i+$mc]['_pocet'];
        $tab[$i+$mc]['_pocet']= '';
        $tab[$i+$mc]['_mezis']= $tab[$i+$mc]['_suma'];
        $tab[$i+$mc]['_suma']= '';
        $tab[$i+$mc]['_sub']= $tab[$i+$mc]['_div'];
        $tab[$i+$mc]['_div']= '';
      }
      $tab[$i]['_suma']= $suma;
      $tab[$i]['_pocet']= $pocet;
    }
    else {
//       $tab[$i]['_mezi']= "-";
    }
  }
//                                         debug($tab,"tabulka $n");
  // zobrazení
  $clmn= array(
    '_div'=>'Rozpětí výše darů v Kč:20','_sub'=>':20','_pocet'=>'počet dárců:11',
      '_mezi'=>'mezipočet:10','_suma'=>'suma darů:15','_mezis'=>'mezisoučet:11');
  $algn= array('_pocet'=>'right','_mezi'=>'right','_suma'=>'right','_mezis'=>'right');
  $titl= "Histogram $jakych darů za období od $od do $do";
  $html.= "<h2 class='CTitle'>$titl</h2>";
  // případný export do Excelu
  if ( $export )
    $html.= tab_xls($tab,"Skupina:8",$clmn,$algn,$titl,"histogram_{$od_sql}_{$do_sql}");
  // zobrazení tabulky
  $html.= tab_show($tab,"Skupina",$clmn,$algn,'stat');
//   $html.= nl2br("{$par->deleni}\n$group\n\n$qry");
  return $html;
}
# ----------------------------------------------------------------------------------- eko_mesic_dary
# $export=1 způsobí export do Excelu
function eko_mesic_dary($export,$od,$do,$vecne,$VS='') { trace();
  $html= '';
  $err= '';
  $tab= array();
  $od_sql= sql_date($od,1);
  $do_sql= sql_date($do,1);
  $cond= $vecne ? "zpusob=4" : "zpusob!=4";
  $jakych= $vecne ? "věcných" : "finančních";
  $map_osoba= map_cis('k_osoba','zkratka');
  $map_zpusob= map_cis('k_zpusob','hodnota');
  $map_stredisko= map_cis('stredisko','hodnota');
  $flds= 'id_clen,titul,prijmeni,jmeno,osoba,ulice,obec,psc,castka,varsym,_pro,castka_kdy';
  $flds.= $vecne ? ",popis" : ",zpusob,_ucet";
  $AND_VS= $symbol= '';
  if ( $VS!='' ) {
    $symbol= " s variabilním symbolem $VS";
    $VS= strtr($VS,array('*'=>'%','?'=>'_'));
    $AND_VS= "AND varsym LIKE '$VS'";
  }
  $qry= "SELECT IFNULL(clen.deleted,id_clen) AS _err,
           id_dar,id_clen,titul,prijmeni,jmeno,osoba,ulice,obec,psc,castka,varsym,
           dar.stredisko AS _pro,castka_kdy,zpusob,dar.popis,IF(zpusob=2,dar.ucet,'') AS _ucet
         FROM dar
         LEFT JOIN clen USING(id_clen)
         LEFT JOIN _cis ON dar.varsym=data AND druh='varsym'
         WHERE NOT left(dar.deleted,1)='D' /*AND NOT left(clen.deleted,1)='D'*/
           AND dar.id_clen!=9999 AND $cond
           AND castka_kdy BETWEEN '$od_sql' AND '$do_sql' $AND_VS
           AND _cis.zkratka=1
         ORDER BY prijmeni  ";
  $res= pdo_qry($qry);
  $n= 0;
  while ( $res && $d= pdo_fetch_object($res) ) {
    if ( trim($d->_err)!=='' ) {
      $err.= "dar č. ".klub_ukaz_dar($d->id_dar)." má ";
      $err.= is_numeric($d->_err) ? "neexistujícího":"smazaného";
      $err.= " dárce č. ".klub_ukaz_clena($d->id_clen)."<br>";
    }
    else {
      $n++;
      foreach (explode(',',$flds) as $f) {
        switch ($f) {
        case 'castka_kdy':
          $tab[$n][$f]= sql_date1($d->$f);
          break;
        case 'zpusob':
          $tab[$n][$f]= $map_zpusob[$d->$f];
          break;
        case 'osoba':
          $tab[$n][$f]= $map_osoba[$d->$f];
          break;
        case '_pro':
          $tab[$n][$f]= $map_stredisko[$d->$f];
          break;
        case 'castka':
          $tab[$n][$f]= $d->$f;
          $tab['*&sum;'][$f]+= $d->$f;
          break;
        default:
          $tab[$n][$f]= $d->$f;
        }
      }
    }
  }
  $clmn= array(
    'id_clen'=>'č.dárce:10', 'titul'=>'titul:15',
    'prijmeni'=>'příjmení:15', 'jmeno'=>'jméno:15','osoba'=>'typ:5',
    'ulice'=>'ulice:20', 'obec'=>'obec:20', 'psc'=>'psč:8',
    'castka'=>'částka:10', 'varsym'=>'VS:6', '_pro'=>'pro:6','castka_kdy'=>'datum:12');
  if ( $vecne )
    $clmn['popis']= 'popis:40';
  else {
    $clmn['zpusob']='forma:11';
    $clmn['_ucet']= 'účet:23';
  }
  $algn= array('castka'=>'right', '_ucet'=>'right');
  $titl= "Přehled $jakych darů za období od $od do $do";
  $html.= "<h2 class='CTitle'>$titl</h2>";
  // případný export do Excelu
  if ( $export )
    $html.= tab_xls($tab,"Dar:8",$clmn,$algn,$titl,"dary_{$od_sql}_{$do_sql}");
  // zobrazení tabulky
  $html.= tab_show($tab,"Dar",$clmn,$algn,'stat');
  // reakce na chybu
  if ( $err ) {
    $html= "<b style='color:red'>POZOR, v záznamech o následujících darech jsou nesrovnalosti:</b>
      <br><br>$err$html";
  }
  return $html;
}
# --------------------------------------------------------------------------------- eko_mesic_vynosy
# $export=1 způsobí export do Excelu
# $vecne,$jen_sloz lze kombinovat jako (0,0), (1,0), (0,1)
function eko_mesic_vynosy($export,$od,$do,$vecne,$jen_sloz=0) { trace();
  $html= '';
  $od_sql= sql_date($od,1);
  $do_sql= sql_date($do,1);
  $cond= $vecne ? "zpusob=4" : ($jen_sloz ? "zpusob=3" : "zpusob!=4");
  $jakych_daru= $vecne ? "věcných darů" : ($jen_sloz ? "darů složenkou" : "finančních darů");
  $platby= 0;
  $tab= array();
  $qry= "SELECT varsym,COUNT(castka) AS _pocet,SUM(castka) AS castky,castka_kdy,
           _cis.hodnota,_cis.zkratka
         FROM dar LEFT JOIN _cis ON varsym=data AND druh='varsym'
         WHERE NOT left(dar.deleted,1)='D'
           AND castka_kdy BETWEEN '$od_sql' AND '$do_sql'
           AND id_clen!=9999 AND $cond
         GROUP BY varsym ORDER BY varsym ";
  $res= pdo_qry($qry);
  $n= 0;
  while ( $res && $d= pdo_fetch_object($res) ) {
    $varsym= ltrim($d->varsym,' 0');
    $varsym= $varsym=='' ? '0000' : $varsym ;
    if ( $d->zkratka!=1 ) {
      if ( $platby ) {
        // platba
        $tab[$varsym]['pocet']+= $d->_pocet;
        $tab['*&sum;']['pocet']+= $d->_pocet;
        $tab[$varsym]['dar']= '';
        $tab[$varsym]['platba']= $d->castky;
        if ( $platby ) $tab['*&sum;']['platba']+= $d->castky;
      }
    }
    else {
      // dar
      $tab[$varsym]['pocet']+= $d->_pocet;
      $tab['*&sum;']['pocet']+= $d->_pocet;
      $tab[$varsym]['dar']= $d->castky;
      if ( $platby ) $tab[$varsym]['platba']= '';
      $tab[$varsym]['popis']= $d->varsym ? $d->hodnota : '(VS neuveden)';
      $tab['*&sum;']['dar']+= $d->castky;
    }
  }
  $clmn= array('pocet'=>'počet:8','dar'=>'součet darů:12');
  if ( $platby ) $clmn['platba']= 'součet plateb:12';
  $clmn['popis']= 'význam VS:70';
  $algn= array('pocet'=>'right', 'dar'=>'right', 'platba'=>'right');
  $titl= "Měsíční výnos $jakych_daru podle VS za období od $od do $do";
  $html.= "<h2 class='CTitle'>$titl</h2>";
  // případný export do Excelu
  if ( $export )
    $html.= tab_xls($tab,"VS:8",$clmn,$algn,$titl,"varsym_{$od_sql}_{$do_sql}");
  // zobrazení tabulky
  $html.= tab_show($tab,"VS",$clmn,$algn,'stat');
  return $html;
}
# ------------------------------------------------------------------------------------------ tab_xls
# exportuje tabulku ve formátu XLS
function tab_xls($tab,$left,$clmn,$align,$titl='sestava',$fname='tmp') { trace();
  $html= '';
  $h= "bcolor=aac0e2c2";
  list($a2,$w)= explode(':',$left);
  $columns= "columns A=$w";
  $head= '';
  $n= 3;
  $m= 1;
  // header
  foreach ($clmn as $c) {
    $A= Excel5_n2col($m++);
    list($an,$w)= explode(':',$c);
    $head.= "|$A$n $an ::$h border=t,t,T,t";
    $columns.= ",$A=$w";
  }
  $xls= <<<__XLS
    |open $fname
    |sheet list1;;L;page
    |$columns
    |A1 $titl ::bold size=14
    |A$n $a2 ::$h border=t,t,T,t $head
__XLS;
  // tělo
  foreach ($tab as $i => $row) if ( substr($i,0,1)!='*' ) {
    $n++; $m= 0;
    $A= Excel5_n2col($m++);
    $xls.= "\n|$A$n $i ::$h border=t";
    foreach ($clmn as $j => $nic) {
      $A= Excel5_n2col($m++);
      $val= strtr($row[$j],array("\n"=>' '));
      $atr= "";
      if ( isset($align[$j]) && is_numeric($val) && strstr($align[$j],'right')!==false ) {
        $atr.= " right";
      }
      if ( strstr($align[$j],'bold')!==false ) $atr.= " bold";
      $xls.= "|$A$n $val ::$atr border=t";
    }
  }
  // patička (index začíná *)
  foreach ($tab as $i => $row) if ( substr($i,0,1)=='*' ) {
    $n++; $m= 0;
    $A= Excel5_n2col($m++);
    $xls.= "\n|$A$n ".substr(str_replace("&sum;","CELKEM",$i),1)." ::$h border=T,t,t,t";
    foreach ($clmn as $j => $nic) {
      $A= Excel5_n2col($m++);
      $val= $row[$j];
      $atr= '';
      if ( isset($align[$j]) && strstr($align[$j],'right')!==false ) {
        $atr.= " right";
      }
      if ( strstr($align[$j],'bold')!==false ) $atr.= " bold";
      $xls.= "|$A$n $val ::$atr $h border=T,t,t,t";
    }
  }
  // rámečky
  $xls.= "\n|close";
  $err= Excel2007($xls,1);
  // zpráva o exportu
//   $html.= nl2br("$xls\n\n");
  if ( $err )
    $html.= "Během exportu tabulky došlo k chybě: $err";
  else
    $html.= "Stáhněte si tabulku s exportem do Excelu: <a href='docs/$fname.xlsx'>$fname.xls</a><br/><br/>";
  return $html;
}
# ----------------------------------------------------------------------------------------- tab_show
# ukáže tabulku ve formátu HTML
function tab_show($tab,$left,$clmn,$align,$class='') {
//                                                 debug($tab,"tab_show");
  // nadpis
  $class= $class ? "class='$class'" : '';
  list($left)= explode(':',$left);
  $t= "<table bgcolor='#fff' $class><tr><th>$left</th>";
  foreach ($clmn as $j => $nazev) {
    list($nazev)= explode(':',$nazev);
    $t.= "<th>$nazev</th>";
  }
  $t.= "</tr>";
  // tělo
  foreach ($tab as $i => $row) if ( substr($i,0,1)!='*' ) {
    $t.= "<tr><th align='right'>$i</th>";
    foreach ($clmn as $j => $nic) {
      $num= $val= $row[$j];
      $atr= "align='left'";
      if ( isset($align[$j]) && is_numeric($val) && strstr($align[$j],'right')!==false ) {
        $val= number_format(round($val), 0, ',', '.');
        $atr= "align='right'";
      }
      if ( strstr($align[$j],'sign')!==false && $num<0 ) $val= "<font color='red'>$val</font>";
      if ( strstr($align[$j],'bold')!==false ) $val= "<b>$val</b>";
      $t.= "<td $atr>$val</td>";
    }
    $t.= "</tr>";
  }
  // patička (index začíná *)
  foreach ($tab as $i => $row) if ( substr($i,0,1)=='*' ) {
    $t.= "<tr><th align='right'>".substr($i,1)."</th>";
    foreach ($clmn as $j => $nic) {
      $num= $val= $row[$j];
      $atr= '';
      if ( isset($align[$j]) && strstr($align[$j],'right')!==false ) {
        $val= number_format(round($val), 0, ',', '.');
        $val= str_replace(' ',"&nbsp;",$val);
        $atr= "align='right'";
      }
      if ( strstr($align[$j],'sign')!==false && $num<0 ) $val= "<font color='red'>$val</font>";
      if ( strstr($align[$j],'bold')!==false ) $val= "<b>$val</b>";
      $t.= "<th $atr>$val</th>";
    }
    $t.= "</tr>";
  }
  // redakce
  $t.= "</table>";
  return $t;
}
?>
