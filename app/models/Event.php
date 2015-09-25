<?php

namespace Fantamanajer\Models;

use Fantamanajer\Models\Table\EventsTable;
use FirePHP;
use Lib\Database\ConnectionFactory;
use Lib\Router;
use PDO;

class Event extends EventsTable {

    const CONFERENZASTAMPA = 1;
    const SELEZIONEGIOCATORE = 2;
    const FORMAZIONE = 3;
    const TRASFERIMENTO = 4;
    const NUOVOGIOCATORE = 5;
    const RIMOSSOGIOCATORE = 6;
    const CAMBIOCLUB = 7;

    public static function deleteEventoByIdExternalAndTipo($idExternal, $tipo) {
        $q = "DELETE
				FROM evento WHERE idExternal = '" . $idExternal . "' AND tipo = '" . $tipo . "'";
        return (ConnectionFactory::getFactory()->getConnection()->exec($q) != FALSE);
    }

    /**
     *
     * @param int $idLega
     * @param int $tipo|null
     * @param int $min
     * @param int $max
     * @return Evento[]
     */
    public static function getEventi($idLega, $tipo = NULL, $min = 0, $max = 10) {
        $ruoli = array("articoli" =>
            array(
                'P' => "il",
                'D' => "il",
                'C' => "il",
                'A' => "l'"),
            "nome" =>
            array(
                'P' => "portiere",
                'D' => "difensore",
                'C' => "centrocampista",
                'A' => "attaccante"
                ));
        $q = "SELECT evento.*,utente.nomeSquadra
				FROM evento LEFT JOIN utente ON evento.idUtente = utente.id ";
        if ($idLega != NULL)
            $q .= "WHERE (evento.idLega = '" . $idLega . "' OR evento.idLega IS NULL)";
        if ($tipo != NULL && $tipo != 0)
            $q .= " AND tipo = '" . $tipo . "'";
        $q .= " ORDER BY data DESC
				LIMIT " . $min . "," . $max . ";";
        FirePHP::getInstance()->log($q);
        $exe = ConnectionFactory::getFactory()->getConnection()->query($q);
        $values = $exe->fetchAll(PDO::FETCH_CLASS,__CLASS__);
        if ($values) {
            foreach ($values as $key => $val) {
                $val->titolo = "";
                $val->content = "";
                $val->link = "";
                switch ($val->tipo) {
                    case self::CONFERENZASTAMPA:
                        $values[$key]->idExternal = Articolo::getById($val->idExternal);
                        $values[$key]->titolo = $val->nomeSquadra . ' ha rilasciato una conferenza stampa intitolata ' . $values[$key]->idExternal->titolo;
                        $values[$key]->content = '';
                        if (!empty($values[$key]->idExternal->abstract))
                            $values[$key]->content = '<em>' . $values[$key]->idExternal->sottoTitolo . '</em><br />';
                        $values[$key]->content .= $values[$key]->idExternal->testo;
                        $values[$key]->link = Router::generate('articoli',array('giornata'=>$values[$key]->idExternal->idGiornata));
                        break;
                    case self::SELEZIONEGIOCATORE: $values[$key]->titolo = $val->nomeSquadra . ' ha selezionato un giocatore per l\'acquisto';
                        $values[$key]->content = ' ';
                        break;
                        $values[$key]->link = '';
                        break;
                    case self::FORMAZIONE: $values[$key]->idExternal = Formazione::getById($val->idExternal);
                        $values[$key]->titolo = $val->nomeSquadra . ' ha impostato la formazione per la giornata ' . $values[$key]->idExternal->idGiornata;
                        $titolari = $values[$key]->idExternal->giocatori;
                        $titolari = array_splice($titolari, 0, 11);
                        //$titolari = Giocatore::getByIds($titolari);
                        $values[$key]->content = 'Formazione: ';
                        foreach ($titolari as $key2 => $val2)
                            $values[$key]->content .= $val2->getGiocatore()->cognome . ', ';
                        $values[$key]->content = substr($values[$key]->content, 0, -2);
                        $values[$key]->link = Router::generate('formazione', array('giornata' => $values[$key]->idExternal->idGiornata, 'squadra' => $values[$key]->idExternal->idUtente));
                        break;
                    case self::TRASFERIMENTO:
                        $values[$key]->idExternal = Trasferimento::getById($val->idExternal);
                        $val->titolo = $val->nomeSquadra . ' ha effettuato un trasferimento';
                        if(!is_null($val->idExternal)) {
                            $giocOld = Giocatore::getById($val->idExternal->idGiocatoreOld);
                            $giocNew = Giocatore::getById($val->idExternal->idGiocatoreNew);
                            $val->idExternal->idGiocatoreOld = $giocOld->id;
                            $val->idExternal->idGiocatoreNew = $giocNew->id;
                            $val->content = $val->nomeSquadra . ' ha ceduto il giocatore ' . $giocOld . ' e ha acquistato ' . $giocNew;
                            $val->link = Router::generate('trasferimento_index', array('id' => $val->idExternal->idUtente));
                            unset($giocOld, $giocNew);
                        }
                        break;
                    case self::NUOVOGIOCATORE:
                        $player = Giocatore::getById($values[$key]->idExternal);
                        //$selected = $player[$values[$key]->idExternal];
                        if(!is_null($player)) {
                            $values[$key]->titolo = $player->nome . ' ' . $player->cognome . ' (' . $player->getClub()->getNome() . ') inserito nella lista giocatori';
                            $values[$key]->content = ucwords($ruoli['articoli'][$player->ruolo]) . ' ' . $ruoli['nome'][$player->ruolo] . ' ' . $player . ' ora fa parte della rosa ' . $player->getClub()->partitivo . ' ' . $player->getClub()->nome . ', pertanto è stato inserito nella lista giocatori';
                            $values[$key]->link = Router::generate('giocatore_show', array('edit' => 'view', 'id' => $player->id));
                        }
                        else
                            FirePHP::getInstance ()->log ("<aa" . $values[$key]->idExternal);  
                        break;
                    case self::RIMOSSOGIOCATORE:
                        $player = Giocatore::getById($values[$key]->idExternal);
                        $values[$key]->titolo = $player . ' (ex ' . $player->getClub()->nome . ') non fa più parte della lista giocatori';
                        $values[$key]->content = ucwords($ruoli['articoli'][$player->ruolo]) . ' ' . $ruoli['nome'][$player->ruolo] . ' ' . $player . ' non è più un giocatore ' . $player->getClub()->partitivo . ' ' . $player->getClub()->nome;
                        $values[$key]->link = Router::generate('giocatore_show', array('edit' => 'view', 'id' => $player->id));
                        break;
                    case self::CAMBIOCLUB:
                        $player = Giocatore::getById($values[$key]->idExternal);
                        $values[$key]->titolo = ucwords($player->getClub()->determinativo) . ' ' . $player->getclub()->nome . ' ha ingaggiato ' . $player;
                        $values[$key]->content = '';
                        $values[$key]->link = Router::generate('giocatore_show', array('edit' => 'view', 'id' => $player->id));
                        break;
                }
            }
            return $values;
        }
        else
            return FALSE;
    }

    public function check(array $array) {
        return TRUE;
    }

}

 