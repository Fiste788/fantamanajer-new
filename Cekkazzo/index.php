<?php
/*g
index.php:
This is the main page. It switch every page of the website.
In this page I setup the not-logged user details and I create every page sending data to template.

Biblion

To Do:
-Require meta.lang.php
-Setup sessions


Included library:
 * Savant2.php that add the library for the template system
 * config.inc.php that contain the general configuration of the website
 * dblib.inc.php that defines database access function
 * authlib.inc.php that includes function to define the authorization
 * langlib.inc.php that defines functions for lang array

*/
session_start();

require_once 'config/config.inc.php';
require_once 'config/Savant2.php';
require_once INCDIR.'db.inc.php';
require_once INCDIR.'auth.inc.php';
require_once INCDIR.'strings.inc.php';


//Creating a new db istance
$dblink = &new db;
$dblink->dbConnect();

//Creating object for pages
$layouttpl =& new Savant2();
$headertpl =& new Savant2();
$footertpl =& new Savant2();
$contenttpl =& new Savant2();
$navbartpl =& new Savant2();

//If no page have been required give the default page (home.php and home.tpl.php)
if (isset($_GET['p']))
  $p = $_GET['p'];
else
  $p = 'home';


//Adding the language

if (!isset($_SESSION['lang']))
{
	$_SESSION['lang'] = 'it';
}

require_once(LANGDIR.$_SESSION['lang'].'/general.lang.php');
$sesslang=$_SESSION['lang'];

//Checking if the requested page exists otherwise $p = 'home'

$upages = array();
  $upages[] = 'home';
  $upages[] = 'rose';


$apages = array();
	$apages[] = 'home';
	$apages[] = 'formazione';

	
if ( (!in_array($p,$upages)) && (!in_array($p,$apages)) ) 
{
	$p = 'home';
}

//Try login if POSTDATA exists
require_once(CODEDIR.'login.code.php');

//Setting up the default user data


if (!isset($_SESSION['logged'])) {
  $_SESSION['userid'] = 1000;
  $_SESSION['login'] = 'Ospite';
  $_SESSION['usertype'] = 'Ospiti';
  $_SESSION['logged'] = FALSE;
}

/**
 * INIZIALIZZAZIONE VARIABILI CONTENT
 * Questo Switch discrimina tra i vari moduli di codice quello che deve
 * essere caricato per visualizzare la pagina corretta
 *
 */
if ($_SESSION['logged'] == TRUE)
	{
	switch($p) { 
  	case 'home' :
  	case 'formazione' : 
	case 'rose' : 
  	
 
		if (file_exists(CODEDIR.$p.'.code.php'))			//Including code file for this page
			require(CODEDIR.$p.'.code.php');
		$tplfile = TPLDIR.$p.'.tpl.php';				//Definition of template file
		break;
  
    default:
	    $p = 'Home';
      //INCLUDE IL FILE DI CODICE PER LA PAGINA
      if (file_exists(CODEDIR.$p.'.code.php'))
    	require(CODEDIR.$p.'.code.php');

	//definisce il file di template utilizzato per visualizzare questa pagina
   $tplfile = TPLDIR.$p.'.tpl.php';

  break;
	}
}
else
{
	switch($p) { 
  	case 'home' :
	case 'rose' :

		if (file_exists(CODEDIR.$p.'.code.php'))			//Including code file for this page
		require(CODEDIR.$p.'.code.php');

		$tplfile = TPLDIR.$p.'.tpl.php';				//Definition of template file
		break;
  
    default:
	    $p = 'home';
      //INCLUDE IL FILE DI CODICE PER LA PAGINA
      if (file_exists(CODEDIR.$p.'.code.php'))
    	require(CODEDIR.$p.'.code.php');

	//definisce il file di template utilizzato per visualizzare questa pagina
   $tplfile = TPLDIR.$p.'.tpl.php';

  break;
	}
}

/**
 *
 * INIZIALIZZAZIONE VARIABILI HEAD (<html><head>...</head><body>
 *
 */
	// $header->assign('title',$lang['title']);
  //$layouttpl->assign('styles', $styles);
//  $layouttpl->assign('meta', $lang['description']);
//  $layouttpl->assign('meta', $lang['keywords']);
  //$layouttpl->assign('js', $js);
  
/**
 * GENERAZIONE LAYOUT
 */

  /**
   * PRODUZIONE HEADER
   * il require include il file con il codice per l'header, incluso il nome del file template
   */
  $header=$headertpl->fetch(TPLDIR.'header.tpl.php');

  /**
   * PRODUZIONE FOOTER
   * il require include il file con il codice per il'footer, incluso il nome del file del file template
   */
//  $footertpl->assign('p',$p);
  $footer=$footertpl->fetch(TPLDIR.'footer.tpl.php');

  /**
   * PRODUZIONE MENU
   * il require include il file con il codice per il menu, incluso il nome del file del file template
   */
   
//  $navbartpl->assign('p',$p);
  $navbar=$navbartpl->fetch(TPLDIR.'navbar.tpl.php');
  /**
   * PRODUZIONE CONTENT
   * Esegue la fetch del template per l'area content
   */
  $content=$contenttpl->fetch($tplfile);

  /**
   * COMPOSIZIONE PAGINA
   */

  $layouttpl->assign('header', $header);
  $layouttpl->assign('footer', $footer);
  $layouttpl->assign('content', $content);
  $layouttpl->assign('navbar', $navbar);

/**
 * Output Pagina
 */
$result = $layouttpl->display(TPLDIR.'layout.tpl.php');
// now test the result of the display() call.  if there was an
// error, this will tell you all about it.
if ($layouttpl->isError($result)) {
    echo "There was an error displaying the template. <pre>";
    print_r($result);
    echo "</pre>";
}

$dblink->dbClose();

?>
