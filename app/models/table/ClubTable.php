<?php

namespace Fantamanajer\Models\Table;

use Fantamanajer\Models\Club;
use Fantamanajer\Models\Giocatore;
use Fantamanajer\Models\View\GiocatoreStatistiche;
use Lib\Database\Table;

abstract class ClubTable extends Table {

    const TABLE_NAME = 'club';

    /**
     *
     * @var string
     */
    public $nome;

    /**
     *
     * @var string
     */
    public $partitivo;

    /**
     *
     * @var string
     */
    public $determinativo;

    public function __construct() {
        parent::__construct();
        $this->nome = is_null($this->nome) ? NULL : $this->getNome();
        $this->partitivo = is_null($this->partitivo) ? NULL : $this->getPartitivo();
        $this->determinativo = is_null($this->determinativo) ? NULL : $this->getDeterminativo();
    }

    /**
     * Setter: nome
     * @param string $nome
     * @return void
     */
    public function setNome($nome) {
        $this->nome = $nome;
    }

    /**
     * Setter: partitivo
     * @param string $partitivo
     * @return void
     */
    public function setPartitivo($partitivo) {
        $this->partitivo = $partitivo;
    }

    /**
     * Setter: determinativo
     * @param string $determinativo
     * @return void
     */
    public function setDeterminativo($determinativo) {
        $this->determinativo = $determinativo;
    }

    /**
     * Getter: nome
     * @return string
     */
    public function getNome() {
        return $this->nome;
    }

    /**
     * Getter: partitivo
     * @return string
     */
    public function getPartitivo() {
        return $this->partitivo;
    }

    /**
     * Getter: determinativo
     * @return string
     */
    public function getDeterminativo() {
        return $this->determinativo;
    }

    /**
     * Getter: giocatori
     * @return Giocatore[]
     */
    public function getGiocatori() {
        if (empty($this->giocatori))
            $this->giocatori = GiocatoreStatistiche::getByFields(array('idClub' => $this->getId()));
        return $this->giocatori;
    }

    /**
     *
     * @return string
     */
    public function __toString() {
        return $this->getNome();
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return Club[]|Club|null
     */
    public static function getByField($key, $value) {
        return parent::getByField($key, $value);
    }

    /**
     *
     * @param int $id
     * @return Club
     */
    public static function getById($id) {
        return parent::getById($id);
    }

    /**
     *
     * @param int[] $ids
     * @return Club[]|null
     */
    public static function getByIds(array $ids) {
        return parent::getByIds($ids);
    }

    /**
     *
     * @return Club[]
     */
    public static function getList() {
        return parent::getList();
    }

}

 