<?php

namespace App\Model\Table;

use App\Model\Entity\Lineup;
use App\Model\Entity\Matchday;
use App\Model\Entity\Season;
use App\Model\Entity\Team;
use Cake\Log\Log;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use PDOException;

/**
 * Scores Model
 *
 * @property BelongsTo $Teams
 * @property BelongsTo $Matchdays
 * @property HasOne $Lineup
 */
class ScoresTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) {
        parent::initialize($config);

        $this->table('scores');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('Lineups', [
            'foreignKey' => 'lineup_id'
        ]);
        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsTo('Matchdays', [
            'foreignKey' => 'matchday_id',
            'joinType' => 'INNER'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator) {
        $validator
                ->integer('id')
                ->allowEmpty('id', 'create');

        $validator
                ->numeric('points')
                ->requirePresence('points', 'create')
                ->notEmpty('points');

        $validator
                ->numeric('real_points')
                ->requirePresence('real_points', 'create')
                ->notEmpty('real_points');

        $validator
                ->numeric('penality_points')
                ->requirePresence('penality_points', 'create')
                ->notEmpty('penality_points');

        $validator
                ->allowEmpty('penality');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->existsIn(['team_id'], 'Teams'));
        $rules->add($rules->existsIn(['matchday_id'], 'Matchdays'));
        return $rules;
    }

    /**
     * 
     * @param Season $season
     * @return int
     */
    public function findMaxMatchday(Season $season) {
        $query = $this->find();
        $res = $query->hydrate(false)
                ->join([
                    'table' => 'matchdays',
                    'alias' => 'm',
                    'type' => 'LEFT',
                    'conditions' => 'm.id = Scores.matchday_id',
                ])
                ->select(['matchday_id' => $query->func()->max('Scores.matchday_id'),])
                ->where(['m.season_id' => $season->id])
                ->first();
        return $res['matchday_id'];
    }

    public function findRankingDetails($championshipId) {
        $ranking = $this->findRanking($championshipId);
        
        $scores = $this->find('all', ['contain' =>
                    ['Teams', 'Matchdays']
                ])->matching('Teams', function($q) use ($championshipId) {
                    return $q->where(['Teams.championship_id' => $championshipId]);
                });
                        //->order('FIELD(Teams.id, ' . Hash::flatten($ranking, ",") . ')')->all();
        $result = [];
        $combined = [];
        foreach ($scores as $score) {
            $combined[$score->team->id][$score->matchday->id] = $score;
        }
        Log::debug($ranking);
        foreach ($ranking as $score) {
            $result[] = $combined[$score->team->id];
        }
        return $result;
    }

    /**
     * 
     * @param int $championshipId
     * @return mixed
     */
    public function findRanking($championshipId) {
        $query = $this->find('all', [
                    'contain' => ['Teams']
                ])->matching('Teams', function($q) use ($championshipId) {
            return $q->where(['Teams.championship_id' => $championshipId]);
        });
        return $query->select($this)->select([
                    'sum_points' => $query->func()->sum('points')
                ])->group('team_id')->orderDesc('sum_points')->all();
    }

    /**
     * 
     * @param Season $season
     * @param Matchday $matchday
     * @param Championships $championship
     * @return int
     */
    public function getAllPointsByMatchay($matchday, $championship) {
        $result = $this->find()
                ->where(['matchday_id <=' => $matchday->id])
                ->orWhere(['matchday_id' => NULL])
                ->matching('Teams.Championships', function($q) use($championship) {
                    return $q->where(['championship_id' => $championship->id]);
                })
                ->order(['team_id', 'matchday_id'])
                ->all();
        $classification = [];
        foreach ($result as $row) {
            $classification[$row->team_id][$row->matchday_id] = $row->points;
        }
        $sums = $this->getClassificationByMatchday($championship, $matchday);
        //die("<pre>" . print_r($sums,1) . "</pre>");
        if (isset($sums)) {
            foreach ($sums as $key => $val) {
                $sums[$key] = $classification[$key];
            }
        } else {
            $teamsReg = TableRegistry::get('Teams');
            $teams = $teamsReg->find('list')->where(['league_id' => $championship->id])->toArray();
            foreach ($teams as $key => $val) {
                $sums[$key][0] = 0;
            }
        }
        return $sums;
    }

    public function getClassificationByMatchday($championship, $matchday) {
        $query = $this->find();
        $result = $query->select([
                            'pointsTotal' => $query->func()->sum('points.points'),
                            'pointsAvg' => $query->func()->avg('points.real_points'),
                            'pointsMax' => $query->func()->max('points.real_points'),
                            'pointsMin' => $query->func()->min('points.real_points'),
                            'matchdays_wins' => 'COALESCE(vw1.matchdays_wins,0)',
                            'team_id' => 'points.team_id'
                        ])
                        ->hydrate(false)
                        ->join([
                            'table' => 'vw_1_matchday_wins',
                            'alias' => 'vw1',
                            'type' => 'LEFT',
                            'conditions' => 'vw1.team_id = points.team_id',
                        ])
                        ->where(['points.matchday_id <=' => $matchday->id])
                        ->matching('Teams.Championships', function($q) use($championship) {
                            return $q->where(['championship_id' => $championship->id]);
                        })
                        //->andWhere(['points.league_id' => $league->id])
                        ->group(['points.team_id'])->order([
                    'pointsTotal' => 'DESC',
                    'matchdays_wins' => 'DESC'
                ])->toArray();
        return Hash::combine($result, '{n}.team_id', '{n}');
        //->combine('team_id','pointsTotal')->toArray();
    }

    protected function substitution($member, &$notRegular, &$change) {

        for ($i = 0; $i < count($notRegular); $i++) {
            $schieramento = $notRegular[$i];
            $giocatorePanchina = $schieramento->member;
            $voto = $giocatorePanchina->ratings[0];
            if (($member->role_id == $giocatorePanchina->role_id) && ($voto->valued)) {
                array_splice($notRegular, $i, 1);
                $change++;
                return $schieramento;
            }
            //die($member);
        }
        return null;
    }

    public function getCaptainActive(Lineup $lineup) {
        $captains = [];
        $captains[] = $lineup->captain_id;
        $captains[] = $lineup->vcaptain_id;
        $captains[] = $lineup->vvcaptain_id;
        foreach ($captains as $cap) {
            if (!is_null($cap) && $cap != "") {
                $dispositions = array_filter($lineup->dispositions, function($value) use ($cap) {
                    return $value->member_id == $cap;
                });
                $disposition = array_shift($dispositions);
                if ($disposition && $disposition->member->ratings[0]->present) {
                    return $cap;
                }
            }
        }
        return null;
    }

    /**
     *
     * @param Team $team
     * @param Matchday $matchday
     * @return int
     * @throws PDOException
     */
    public function calculate(Team $team, Matchday $matchday) {
        $lineups = TableRegistry::get('Lineups');
        $dispositions = TableRegistry::get('Dispositions');
        $scores = TableRegistry::get('Scores');
        $lineup = $lineups->find()
                        ->innerJoinWith('Matchdays')
                        ->contain(['Dispositions' => ['Members' => function(Query $q) use($matchday) {
                                    return $q->find('list', [
                                                'keyField' => 'id',
                                                'valueField' => function ($obj) {
                                                    return $obj;
                                                }])
                                            ->contain(['Ratings' => function(Query $q) use($matchday) {
                                                    return $q->where(['Ratings.matchday_id' => $matchday->id]);
                                                }]);
                                }
                    ]])
                        ->where(['Lineups.team_id' => $team->id, 'Lineups.matchday_id <=' => $matchday->id, 'Matchdays.season_id' => $matchday->season->id])
                        ->order(['Lineups.matchday_id' => 'DESC'])->first();
        $score = $scores->findByTeamIdAndMatchdayId($team->id, $matchday->id);
        $championship = $team->championship;
        if ($score->isEmpty()) {
            $score = $this->newEntity();
            $score->matchday_id = $matchday->id;
            $score->team_id = $team->id;
        }
        if ($lineup == null || ($lineup->matchday_id != $matchday->id && $championship->points_missed_lineup == 0)) {
            $score->set('real_points', 0);
            $score->set('points', 0);
            $scores->save($score);
        } else {
            if ($lineup->matchday_id != $matchday->id) {
                $lineup->jolly = null;
                $lineup->matchday_id = $matchday->id;
                $lineup->team_id = $team->id;
                if (!$championship->captain_missed_lineup) {
                    $lineup->captain_id = NULL;
                    $lineup->vcaptain_id = NULL;
                    $lineup->vvcaptain_id = NULL;
                }
                //$formazione = clone $formazione;
                $lineup = $lineups->newEntity($lineup->toArray(), ['associated' => [
                        'Dispositions' => ['associated' => ['Members' => ['associated' => ['Ratings']]]]
                ]]);
                $lineup->id = null;
                foreach ($lineup->dispositions as $key => $val) {
                    $val->consideration = 0;
                    unset($val->id);
                    unset($val->lineup_id);
                    $lineup->dispositions[$key] = $val;
                }
            }

            $change = 0;
            $sum = 0;

            $cap = $this->getCaptainActive($lineup);
            //die("aaa " . $cap);
            $notRegular = $lineup->dispositions;
            $regular = array_splice($notRegular, 0, 11);
            foreach ($regular as $disposition) {
                $member = $disposition->member;
                if ($member) {
                    $disp = $disposition;
                    $rating = $member->ratings[0];
                    if ((!$member->active || !$rating->valued) && ($change < 3)) {
                        $sostituto = $this->substitution($member, $notRegular, $change, $matchday);
                        if ($sostituto != null) {
                            $disp->consideration = 0;
                            $disp = $sostituto;
                            $member = $disp->member;
                            $rating = $member->ratings[0];
                        }
                    }
                    if ($disp) {
                        $disp->consideration = 1;
                        $points = $rating->points;
                        if ($championship->captain && $cap && $member->id == $cap) {
                            $disp->consideration = 2;
                            $points *= 2;
                        }
                        //$dispositions->save($disposition, ['associated' => false]);
                        $sum += $points;
                    }
                }
            }
            $lineups->save($lineup, ['associated' => ['Dispositions' => ['associated' => false]]]);

            if ($lineup->jolly) {
                $sum *= 2;
            }
            $score->points = $sum;
            $score->real_points = $sum;
            $score->lineup_id = $lineup->id;
            if ($championship->points_missed_lineup != 100 && $matchday->id != $lineup->matchday_id) {
                $puntiDaTogliere = round((($sum / 100) * (100 - $championship->points_missed_lineup)), 1);
                $modulo = ($puntiDaTogliere * 10) % 5;
                $score->penality_points = -(($puntiDaTogliere * 10) - $modulo) / 10;
                $score->penality = 'Formazione non settata';
                $score->points = $score->points - $score->penality_points;
            }
            $this->save($score);
        }

        return $score->points;
    }

}
