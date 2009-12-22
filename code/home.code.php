<?php 
require_once(INCDIR . 'punteggio.db.inc.php');
require_once(INCDIR . 'utente.db.inc.php');
require_once(INCDIR . 'articolo.db.inc.php');
require_once(INCDIR . 'evento.db.inc.php');
require_once(INCDIR . 'giornata.db.inc.php');
require_once(INCDIR . 'giocatore.db.inc.php');
require_once(INCDIR . 'emoticon.inc.php');

$punteggioObj = new punteggio();
$utenteObj = new utente();
$articoloObj = new articolo();
$eventoObj = new evento();
$giornataObj = new giornata();
$giocatoreObj = new giocatore();
$emoticonObj = new emoticon();

$ruo = array('P','D','C','A');
$contentTpl->assign('dataFine',date_parse($giornataObj->getTargetCountdown()));
$contentTpl->assign('squadre',$utenteObj->getElencoSquadreByLega($_SESSION['legaView']));

$giornata = $punteggioObj->getGiornateWithPunt();
foreach ($ruo as $ruolo)
	$bestPlayer[$ruolo] = $giocatoreObj->getBestPlayerByGiornataAndRuolo($giornata,$ruolo);
$contentTpl->assign('giornata',$giornata);
$contentTpl->assign('bestPlayer',$bestPlayer);
$articolo = $articoloObj->select($articoloObj,NULL,'*',0,1,'insertDate');
if($articolo != FALSE)
	foreach ($articolo as $key => $val)
		$articolo[$key]->text = $emoticonObj->replaceEmoticon($val->text,IMGSURL . 'emoticons/');
$contentTpl->assign('articoli',$articolo);
$eventi = $eventoObj->getEventi(NULL,NULL,0,7);
$contentTpl->assign('eventi',$eventi);
?>
