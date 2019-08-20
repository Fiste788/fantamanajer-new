<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Season;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Matchdays Model
 *
 * @property \App\Model\Table\SeasonsTable|\Cake\ORM\Association\BelongsTo $Seasons
 * @property \App\Model\Table\ArticlesTable|\Cake\ORM\Association\HasMany $Articles
 * @property \App\Model\Table\LineupsTable|\Cake\ORM\Association\HasMany $Lineups
 * @property \App\Model\Table\RatingsTable|\Cake\ORM\Association\HasMany $Ratings
 * @property \App\Model\Table\ScoresTable|\Cake\ORM\Association\HasMany $Scores
 * @property \App\Model\Table\TransfertsTable|\Cake\ORM\Association\HasMany $Transferts
 *
 * @method \App\Model\Entity\Matchday get($primaryKey, $options = [])
 * @method \App\Model\Entity\Matchday newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Matchday[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Matchday|bool save(\App\Model\Table\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Matchday patchEntity(\App\Model\Table\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Matchday[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Matchday findOrCreate($search, callable $callback = null, $options = [])
 * @method \App\Model\Entity\Matchday|bool saveOrFail(\App\Model\Table\EntityInterface $entity, $options = [])
 */
class MatchdaysTable extends Table
{
    /**
     * Initialize method
     *
     * @param  array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('matchdays');
        $this->setDisplayField('number');
        $this->setPrimaryKey('id');

        $this->belongsTo(
            'Seasons',
            [
                'foreignKey' => 'season_id',
                'joinType' => 'INNER',
            ]
        );
        $this->hasMany(
            'Articles',
            [
                'foreignKey' => 'matchday_id',
            ]
        );
        $this->hasMany(
            'Lineups',
            [
                'foreignKey' => 'matchday_id',
            ]
        );
        $this->hasMany(
            'Ratings',
            [
                'foreignKey' => 'matchday_id',
            ]
        );
        $this->hasMany(
            'Scores',
            [
                'foreignKey' => 'matchday_id',
            ]
        );
        $this->hasMany(
            'Transferts',
            [
                'foreignKey' => 'matchday_id',
            ]
        );
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('number')
            ->requirePresence('number', 'create')
            ->notEmpty('number');

        $validator
            ->dateTime('date')
            ->requirePresence('date', 'create')
            ->notEmpty('date');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['season_id'], 'Seasons'));

        return $rules;
    }

    public function findCurrent(Query $q, array $options): Query
    {
        $interval = array_key_exists('interval', $options) ? $options['interval'] : 0;
        $now = new Time();
        $now->addMinute($interval);

        return $q->contain(['Seasons'])
            ->where(['date > ' => $now])
            ->orderAsc('number');
    }

    /**
     *
     * @param \App\Model\Entity\Season $season
     * @return \App\Model\Entity\Matchday[]
     */
    public function findWithoutScores(Season $season): array
    {
        $query = $this->find();
        $res = $query->leftJoinWith("Scores")
            ->contain('Seasons')
            ->where(
                [
                    'team_id IS' => null,
                    'date <' => new \DateTime(),
                    'season_id' => $season->id,
                ]
            )
            ->toArray();

        return $res;
    }

    public function findWithScores(Season $season)
    {
        return $this->find()
            ->innerJoinWith('Scores')
            ->where(['season_id' => $season->id])
            ->orderDesc('Matchdays.id')
            ->limit(1);
    }

    public function findFirstWithoutScores(Query $q, array $options): Query
    {
        return $q->select('Matchdays.id')
            ->leftJoinWith('Scores')
            ->orderAsc('Matchdays.number')
            ->whereNull('Scores.id')->andWhere([
                'Matchdays.number >' => 0,
                'season_id' => $options['season'],
            ])->limit(1);
    }

    /**
     *
     * @param \App\Model\Entity\Season $season
     * @return \App\Model\Entity\Matchday[]
     */
    public function findWithoutRatings(Season $season): array
    {
        $query = $this->find();
        $res = $query->leftJoinWith("Ratings")
            ->contain('Seasons')
            ->where(
                [
                    'number !=' => 0,
                    'member_id IS' => null,
                    'date <' => new \DateTime(),
                    'season_id' => $season->id,
                ]
            )
            ->toArray();

        return $res;
    }

    /**
     * Undocumented function
     *
     * @param \App\Model\Entity\Season $season
     * @return \Cake\ORM\Query
     */
    public function findWithRatings(Season $season)
    {
        return $this->find()
            ->innerJoinWith('Ratings')
            ->where(['season_id' => $season->id])
            ->orderDesc('Matchdays.id')
            ->limit(1);
    }
}
