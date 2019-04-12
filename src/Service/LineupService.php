<?php

namespace App\Service;

use App\Model\Entity\Disposition;
use App\Model\Entity\Lineup;
use App\Model\Entity\Matchday;
use Cake\Datasource\ModelAwareTrait;

/**
 *
 * @property \App\Model\Table\TeamsTable $Teams
 * @property \App\Model\Table\LineupsTable $Lineups
 */
class LineupService
{
    use ModelAwareTrait;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadModel('Teams');
        $this->loadModel('Lineups');
    }

    /**
     * Return a copy of lineup
     *
     * @param Lineup $lineup Lineup
     * @param int $teamId Team id
     * @param int $matchday Matchday id
     * @return Lineup
     */
    public function duplicate(Lineup $lineup, $teamId, $matchday)
    {
        if ($lineup->team_id == $teamId && $lineup->matchday_id != $matchday->id) {
            $lineup = $this->copy($lineup, $matchday, true, false);
        }
        $lineup->modules = Lineup::$module;

        return $lineup;
    }

    /**
     * Return new empty lineup
     *
     * @param Team $team Team
     * @return Lineup
     */
    public function getEmptyLineup($team)
    {
        $lineup = new Lineup();
        $lineup->team = $this->Teams->get($team, ['contain' => ['Members' => ['Roles', 'Players']]]);
        $lineup->modules = Lineup::$module;

        return $lineup;
    }

    /**
     * Return new empty lineup with matchday
     *
     * @param int $team_id Team id
     * @param int $matchday_id Matchday id
     * @return Lineup
     */
    public function newLineup($team_id, $matchday_id)
    {
        $lineup = $this->getEmptyLineup();
        $lineup->team_id = $team_id;
        $lineup->matchday_id = $matchday_id;

        return $lineup;
    }

    /**
     * Substitute member in lineup
     *
     * @param Lineup $lineup Lineup
     * @param int $old_member_id Old member id
     * @param int $new_member_id New memeber id
     * @return bool
     */
    public function substitute(Lineup $lineup, $old_member_id, $new_member_id)
    {
        foreach ($lineup->dispositions as $key => $disposition) {
            if ($old_member_id == $disposition->id) {
                $lineup->dispositions[$key]->id = $new_member_id;
                $lineup->setDirty('dispositions', true);
            }
        }
        if ($old_member_id == $lineup->captain_id) {
            $lineup->captain_id = $new_member_id;
        }
        if ($old_member_id == $lineup->vcaptain_id) {
            $lineup->vcaptain_id = $new_member_id;
        }
        if ($old_member_id == $lineup->vvcaptain_id) {
            $lineup->vvcaptain_id = $new_member_id;
        }

        return $lineup->isDirty();
    }

    /**
     * Return a not saved copy of the entity with the specified matchday
     *
     * @param Matchday $lineup the matchday to use
     * @param bool $matchday if false empty the captain. Default: true
     * @param bool $isCaptainActive if true the lineup was missing. Default true
     * @param bool $cloned if true the lineup is cloned. Default true
     * @return Lineup
     */
    public function copy(Lineup $lineup, Matchday $matchday, $isCaptainActive = true, $cloned = true)
    {
        $lineupCopy = $this->Lineups->newEntity(
            $lineup->toArray(),
            ['associated' => ['Teams.Championships', 'Dispositions.Members.Ratings']]
        );
        $lineupCopy->id = null;
        $lineupCopy->jolly = null;
        $lineupCopy->cloned = $cloned;
        $lineupCopy->matchday_id = $matchday->id;
        if (!$isCaptainActive) {
            $lineupCopy->captain_id = null;
            $lineupCopy->vcaptain_id = null;
            $lineupCopy->vvcaptain_id = null;
        }
        $lineupCopy->dispositions = array_map(function ($disposition) {
            return $this->reset($disposition);
        }, $lineupCopy->dispositions);

        return $lineupCopy;
    }

    /**
     * Reset the entity to default value and new
     *
     * @param Disposition $disposition Disposition
     * @return Disposition
     */
    private function reset(Disposition $disposition)
    {
        unset($disposition->id);
        unset($disposition->lineup_id);
        $disposition->consideration = 0;

        return $disposition;
    }

    /**
     * Reset disposition in a lineup
     *
     * @param Lineup $lineup Lineup
     * @return void
     */
    public function resetDispositions(Lineup $lineup)
    {
        foreach ($lineup->dispositions as $key => $disposition) {
            $disposition->consideration = 0;
            $lineup->disposition[$key] = $disposition;
        }
    }
}