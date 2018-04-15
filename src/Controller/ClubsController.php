<?php

namespace App\Controller;

use App\Controller\AppController;

/**
 * @property \App\Model\Table\ClubsTable $Clubs
 */
class ClubsController extends AppController
{
    public function index()
    {
        $this->Crud->action()->findMethod([
            'bySeasonId' => [
                'season_id' => $this->currentSeason->id
            ]
        ]);
        return $this->Crud->execute();
    }
}
