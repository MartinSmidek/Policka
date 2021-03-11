<?php # Systém Archa/Ezer, (c) 2008-2018 Martin Šmídek <martin@smidek.eu>
  
  // volba verze jádra Ezer
  $kernel= "ezer3.1";
  $_GET['pdo']= 2; 
  $_GET['touch']= 0; // nezavede jquery.touchSwipe.min.js => filtry v browse jdou upravit myší

  // hostující servery
  $ezer_server= 
    $_SERVER["SERVER_NAME"]=='policka.bean'         ? 0 : -1;       // 0:lokální NTB

  // parametry aplikace FiS
  $app_name=  "Polička";
  $app_root=  'ch';
  $app_js=    array('/ch/ch_user.js');
  $app_css=   array('/ch/ch.css.php=skin',"/$kernel/client/wiki.css");
  $skin=      'ck';
  $title_style= $ezer_server==0 ? " style='color:#ef7f13'" : '' ;
  $title_flag=  $ezer_server==0 ? 'lokální' : '';

  $abs_roots= array(
      "C:/Ezer/beans/policka",
    );
  $rel_roots= array(
      "http://policka.bean:8080"
    );

  // (re)definice Ezer.options
  $kontakt= " V případě zjištění problému nebo <br/>potřeby konzultace mi prosím napište<br/>na "
      . "mail&nbsp;<a target='mail' href='mailto:martin@smidek.eu?subject=FiS'>martin@smidek.eu</a> "
      . "případně zavolejte&nbsp;603 150 565 "
      . "<br/>Za spolupráci děkuje <br/>Martin";

  $favicon= array(
      "ch_local.png"
    )[$ezer_server];

  $add_pars= array(
    'favicon' => $favicon,
    'title_right' => "<span$title_style>$title_flag $app_name</span>",
    'watch_ip' => false,
    'contact' => $kontakt,
    'CKEditor' => "{
      version:'4.6',
      Minimal:{toolbar:[['Bold','Italic','Source']]}
    }"
  );
  
  // je to aplikace se startem v rootu
  require_once("$kernel/ezer_main.php");

?>
