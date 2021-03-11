<?php # Systém Chap/Ezer, (c) 2021 Martin Šmídek <martin@smidek.eu>

  global // import 
    $ezer_root; 
  global // export
    $EZER, $ezer_server;
  
  // vyzvednutí ostatních hodnot ze SESSION
  $ezer_server=  $_SESSION[$ezer_root]['ezer_server'];
  $kernel= "ezer{$_SESSION[$ezer_root]['ezer']}";
  $abs_root=     $_SESSION[$ezer_root]['abs_root'];
  $rel_root=     $_SESSION[$ezer_root]['rel_root'];
  chdir($abs_root);

//  // rozlišení ostré verze a serveru proglas/chap
//  $ezer_local= preg_match('/^\w+\.bean$/',$_SERVER["SERVER_NAME"])?1:0;
//  $chap= in_array($_SERVER["SERVER_NAME"],array("mail.telepace.cz","192.168.100.7","217.64.3.170"))?1:0;

  // inicializace objektu Ezer
  $EZER= (object)array(
      'version'=>'ezer'.$_SESSION[$ezer_root]['ezer'],
      'options'=>(object)array(
          'mail' => "martin@smidek.eu",
          'phone' => "603&nbsp;150&nbsp;565",
          'author' => "Martin"
      ),
      'activity'=>(object)array());
  
  // specifické cesty pro Ark
    $path_backup= array(
      "C:/Ezer/beans/policka/sql"
    )[$ezer_server];
  
  // cesta k utilitám MySQL/MariaDB
  $ezer_mysql_path= array(
      "C:/Apache/bin/mysql/mysql5.7.31/bin"
    )[$ezer_server];

  // parametry aplikace Ark
  $db= array('ezer_chap'); // pro ostrý server jméno db vynecháno
  $dbs= array(
    array( // lokální
      'ezer_chap'   => array(0,'localhost','gandi','radost','utf8','ezer_chap'),
      'ezer_system' => array(0,'localhost','gandi','radost','utf8','ezer_chap','no dump'),
      'ezer_kernel' => array(0,'localhost','gandi','radost','utf8','','no dump')
    )
  );
  $tracked= ',clen,dar,pdenik,_user,_cis,';
  
  // PHP moduly aplikace Ark
  $app_php= array(
//    "ck/ck.dop.php", ?
    "chap/chap.dop.jed.php",
    "chap/chap.$.php",
    "chap/chap.klu.php",
    "chap/chap.klu.pre.php",
    "chap/chap.eko.php",
    "chap/chap_pdf.php",
    "chap/chap_tcpdf.php"
  );
  
  // PDF knihovny
  require_once('tcpdf/tcpdf.php');

  // je to aplikace se startem v rootu
  chdir($_SESSION[$ezer_root]['abs_root']);
  require_once("{$EZER->version}/ezer_ajax.php");

  // specifické cesty
  global $ezer_path_root;

  $path_www= './';
  
//  // nahrazení "PDO" funkcí jejich mysql verzí pro ezer2.2
//  if ( $EZER->version=='ezer2.2' ) { //version_compare(PHP_VERSION, '7.0.0') == -1 ) {
//    function pdo_num_rows($rs) {
//      return mysql_num_rows($rs);
//    }
//    function pdo_result($rs,$cnum) {
//      return mysql_result($rs,$cnum);
//    }
//    function pdo_fetch_object($rs) {
//      return mysql_fetch_object($rs);
//    }
//    function pdo_fetch_assoc($rs) {
//      return mysql_fetch_assoc($rs);
//    }
//    function pdo_fetch_row($rs) {
//      return mysql_fetch_row($rs);
//    }
//    function pdo_fetch_array($rs) {
//      return mysql_fetch_array($rs);
//    }
//    function pdo_real_escape_string($inp) {
//      return mysql_real_escape_string($inp);
//    }
//    function pdo_query($query) {
//      return mysql_query($query);
//    }
//    function pdo_insert_id() {
//      return mysql_insert_id();
//    }
//    function pdo_error() {    
//      return mysql_error();
//    }
//    function pdo_affected_rows() {
//      return mysql_affected_rows();
//    }
//    function pdo_qry($qry,$pocet=null,$err=null,$to_throw=false,$db='.main.') {
//      return mysql_qry($qry,$pocet,$err,$to_throw,$db);
//    }
//  }
?>
