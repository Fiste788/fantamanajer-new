<?php
declare(strict_types=1);

namespace App\Model\Rule;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\ModelAwareTrait;

/**
 * @property \App\Model\Table\MatchdaysTable $Matchdays
 * @property \App\Model\Table\LineupsTable $Lineups
 */
class JollyAlreadyUsedRule
{
    use ModelAwareTrait;

    /**
     * Construct
     *
     * @throws \Cake\Datasource\Exception\MissingModelException
     * @throws \UnexpectedValueException
     */
    public function __construct()
    {
        $this->loadModel('Matchdays');
        $this->loadModel('Lineups');
    }

    /**
     * Invoke
     *
     * @param \App\Model\Entity\Lineup $entity Entity
     * @param array $options Options
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        if ($entity->jolly) {
            $matchday = $this->Matchdays->get($entity->matchday_id);
            $matchdays = $this->Matchdays->find()
                ->where(['season_id' => $matchday->season_id])
                ->count();

            return $this->Lineups->find()
                ->contain(['Matchdays'])
                ->innerJoinWith('Matchdays')
                ->where([
                    'Lineups.id IS NOT' => $entity->id,
                    'jolly' => true,
                    'team_id' => $entity->team_id,
                    'Matchdays.number ' . ($matchday->number <= $matchdays / 2 ? '<=' : '>') => $matchdays / 2,
                ])
                ->isEmpty();
        }

        return true;
    }
}
