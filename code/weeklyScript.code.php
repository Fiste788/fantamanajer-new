<?php 
require_once(INCDIR.'mail.inc.php');
require_once(INCDIR.'squadra.inc.php');
require_once(INCDIR.'punteggi.inc.php');
require_once(INCDIR.'giocatore.inc.php');
require_once(INCDIR.'formazione.inc.php');

//INIZIALIZZO TUTTO CIÒ CHE MI SERVE PER ESEGUIRE LO SCRIPT
$punteggiObj = new punteggi();
$squadraObj = new squadra();
$formazioneObj = new formazione();
$mailObj = new mail();
$giocatoreObj = new giocatore();

$giornata = $giornataObj->getIdGiornataByDate(date("Y-m-d"))-1;
if(isset($_GET['user']) && trim($_GET['user']) == 'admin' && isset($_GET['pass']) && trim($_GET['pass']) == md5('omordotuanuoraoarounautodromo'))
{
	//CONTROLLO SE È IL SECONDO GIORNO DOPO LA FINE DELLE PARTITE QUINDI ESEGUO LO SCRIPT
	if( ($giornataObj->checkDay(date("Y-m-d")) != FALSE) && date("H") >= 15 && $punteggiObj->checkPunteggi($giornata))
	{
		//RECUPERO I VOTI DAL SITO DELLA GAZZETTA E LI INSERISCO NEL DB
		recupera_voti($giornata);
		//AGGIORNA LA LISTA GIOCATORI
		update_tab_giocatore($giornata);
		$mailContent = new Savant2();
		$result = array();
		$appo = $squadraObj->getElencoSquadre();
		foreach($appo as $key =>$val)
		{
			$squadra = $val[0];			
			//CALCOLO I PUNTI SE C'È LA FORMAZIONE
			if($formazioneObj->getFormazioneBySquadraAndGiornata($squadra,$giornata) != FALSE)
			{
				$punteggiObj->calcolaPunti($giornata , $squadra );
				$result[$key] = $giocatoreObj->getVotiGiocatoryByGiornataSquadra($giornata,$squadra);
			}
			else
			{
				$q = "INSERT INTO punteggi VALUES ('0','" . $giornata . "', '" . $squadra . "');";
				mysql_query($q) or die("Query non valida: ".$q . mysql_error());
			}
		}
	
		//ESTRAGGO LA CLASSIFICA E QUELLA DELLA GIORNATA PRECEDENTE
		$classifica = $punteggiObj->getAllPunteggiByGiornata($giornata);
		$appo2 = $classifica;
		foreach($appo2 as $key=>$val)
		{
			array_pop($appo2[$key]);
			$prevSum[$key] = array_sum($appo2[$key]);
		} 
		foreach($classifica as $key=>$val)
			$sum[$key] = array_sum($classifica[$key]);
		arsort($prevSum);
	
		foreach($prevSum as $key=>$val)
			$indexPrevSum[] = $key;
		foreach($sum as $key=>$val)
			$indexSum[] = $key;
		
		foreach($indexSum as $key => $val)
		{
			if($val == $indexPrevSum[$key])
				$diff[] = 0;
			else
				$diff[] = (array_search($val,$indexPrevSum))- $key;
		}
		$mailContent->assign('classifica',$sum);
		$mailContent->assign('differenza',$diff);
		$mailContent->assign('squadre',$appo);
		$mailContent->assign('giornata',$giornata);
		foreach ($appo as $key => $val)
		{
			if(!empty($val[4]) && isset($result[$key]))
			{
				$mailContent->assign('squadra',$val[1]);
				$mailContent->assign('somma',$punteggiObj->getPunteggi($val[0],$giornata));
				$mailContent->assign('formazione',$result[$key]);
				$mail = 0;
				
			   	//MANDO LA MAIL
			   	$object = "Giornata: ". $giornata . " - Punteggio: " . $punteggiObj->getPunteggi($val[0],$giornata);
			   	//$mailContent->display(TPLDIR.'mail.tpl.php');
			  	if(!$mailObj->sendEmail($val[2] . " " . $val[3] . "<" . $val[4]. ">",$mailContent->fetch(TPLDIR.'mail.tpl.php'),$object))
			  		$mail++ ;
			}
		}
		if($mail == 0)
			$contenttpl->assign('message','Operazione effettuata correttamente');
		else
			$contenttpl->assign('message','Si sono verificati degli errori');
		//aggiorna i giocatori ceduti di giornata in giornata
		//$giocatoreObj->updateListaGiocatori($giornata);
	}
	else
		$contenttpl->assign('message','Non puoi effettuare l\'operazione ora');
}
else
	$contenttpl->assign('message','Non sei autorizzato a eseguire l\'operazione');*/
?>
