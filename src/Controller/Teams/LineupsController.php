<?php

namespace App\Controller\Teams;

class LineupsController extends \App\Controller\LineupsController
{

    public function current()
    {
        $team = $this->Lineups->Teams->get(
            $this->request->getParam('team_id'),
            ['contain' => ['Users', 'Members' => ['Roles', 'Players']]]
        );
        \Cake\Log\Log::debug($this->Authentication->getIdentity());
        if ($this->Authentication->getIdentity()->id == $team->user->id) {
            $lineup = $this->Lineups->findLast($this->currentMatchday, $team)->first();
            if ($lineup && $lineup->matchday_id != $this->currentMatchday->id) {
                $lineup = $lineup->copy($this->currentMatchday, true, false);
            }
        } else {
            $lineup = $this->Lineups->find()
                ->contain(['Dispositions'])
                ->where([
                    'Lineups.team_id' => $team->id,
                    'Lineups.matchday_id =' => $this->currentMatchday->id,
                ])->first();
        }

        $this->set(
            [
                'success' => true,
                'data' => [
                    'members' => $team->members,
                    'lineup' => $lineup,
                    'modules' => \App\Model\Entity\Lineup::$module
                ],
                '_serialize' => ['success', 'data']
            ]
        );
    }
}
