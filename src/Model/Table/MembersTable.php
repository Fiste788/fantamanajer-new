<?php

namespace App\Model\Table;

use App\Model\Entity\Event as Event2;
use App\Model\Entity\Matchday;
use App\Model\Entity\Role;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Members Model
 *
 * @property \App\Model\Table\PlayersTable|\Cake\ORM\Association\BelongsTo $Players
 * @property \App\Model\Table\RolesTable|\Cake\ORM\Association\BelongsTo $Roles
 * @property \App\Model\Table\ClubsTable|\Cake\ORM\Association\BelongsTo $Clubs
 * @property \App\Model\Table\SeasonsTable|\Cake\ORM\Association\BelongsTo $Seasons
 * @property \App\Model\Table\DispositionsTable|\Cake\ORM\Association\HasMany $Dispositions
 * @property \App\Model\Table\RatingsTable|\Cake\ORM\Association\HasMany $Ratings
 * @property \App\Model\Table\TeamsTable|\Cake\ORM\Association\BelongsToMany $Teams
 *
 * @method \App\Model\Entity\Member get($primaryKey, $options = [])
 * @method \App\Model\Entity\Member newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Member[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Member|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Member patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Member[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Member findOrCreate($search, callable $callback = null, $options = [])
 * @property \App\Model\Table\VwMembersStatsTable|\Cake\ORM\Association\HasOne $VwMembersStats
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MembersTable extends Table
{

    /**
     * Initialize method
     *
     * @param  array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('members');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        /* $this->addBehavior(
          'Timestamp',
          [
          'events' => [
          'Model.beforeSave' => [
          'created_at' => 'new',
          'modified_at' => 'always'
          ]
          ]
          ]
          ); */
        $this->belongsTo(
            'Players',
            [
                'foreignKey' => 'player_id',
                'joinType' => 'INNER'
            ]
        );
        $this->belongsTo(
            'Roles',
            [
                'foreignKey' => 'role_id',
                'joinType' => 'INNER'
            ]
        );
        $this->belongsTo(
            'Clubs',
            [
                'foreignKey' => 'club_id',
                'joinType' => 'INNER'
            ]
        );
        $this->belongsTo(
            'Seasons',
            [
                'foreignKey' => 'season_id',
                'joinType' => 'INNER'
            ]
        );
        $this->hasMany(
            'Dispositions',
            [
                'foreignKey' => 'member_id'
            ]
        );
        $this->hasMany(
            'Ratings',
            [
                'foreignKey' => 'member_id',
                'strategy' => 'select'
            ]
        );
        $this->belongsToMany(
            'Teams',
            [
                'foreignKey' => 'member_id',
                'targetForeignKey' => 'team_id',
                'joinTable' => 'members_teams'
            ]
        );
        $this->hasOne(
            'VwMembersStats',
            [
                'foreignKey' => 'member_id',
                'propertyName' => 'stats'
            ]
        );
    }

    /**
     * Default validation rules.
     *
     * @param  Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('code_gazzetta')
            ->requirePresence('code_gazzetta', 'create')
            ->notEmpty('code_gazzetta');

        $validator
            ->boolean('playmaker');

        $validator
            ->boolean('active');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param  RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['player_id'], 'Players'));
        $rules->add($rules->existsIn(['role_id'], 'Roles'));
        $rules->add($rules->existsIn(['club_id'], 'Clubs'));
        $rules->add($rules->existsIn(['season_id'], 'Seasons'));

        return $rules;
    }

    public function findWithStats(Query $query, array $options)
    {
        return $query->contain(['VwMembersStats'])
                ->where(['season_id' => $options['season_id']])
                ->group('Members.id');
    }

    public function findWithStats2(Query $query, array $options)
    {
        return $query->select(['sum_valued' => $query->func()->count('Ratings.valued')], false)
                ->enableAutoFields()
                ->innerJoinWith('Ratings')
                ->group('Members.id');
    }

    public function findWithDetails(Query $query, array $options)
    {
        return $query->contain(
                    ['Roles', 'Clubs', 'Seasons', 'Ratings' => function (Query $q2) {
                        return $q2->contain(['Matchdays'])
                                ->order(['Matchdays.number' => 'ASC']);
                    }]
                )
                ->order(['Seasons.year' => 'DESC']);
    }

    public function findFree($championshipId)
    {
        $membersTeams = TableRegistry::get('MembersTeams');
        $ids = $membersTeams->find()
            ->select(['member_id'])
            ->matching(
            'Teams',
            function ($q) use ($championshipId) {
                return $q->where(['Teams.championship_id' => $championshipId]);
            }
        );

        return $this->find()
                ->innerJoinWith('Seasons.Championships')
                ->contain(['Players', 'Clubs', 'Roles'])
                ->where([
                    'Members.id NOT IN' => $ids,
                    'Members.active' => true,
                    'Championships.id' => $championshipId
                    ])
                ->orderAsc('Players.surname')
                ->orderAsc('Players.name');
    }

    public function findBestByMatchday(Matchday $matchday, Role $role, $limit = 5)
    {
        return $this->find('all')
                ->contain(
                    ['Players', 'Ratings' => function (Query $q) use ($matchday) {
                        return $q->where(['matchday_id' => $matchday->id]);
                    }]
                )
                ->innerJoinWith(
                    'Ratings',
                    function (Query $q) use ($matchday) {
                        return $q->where(['matchday_id' => $matchday->id]);
                    }
                )
                ->innerJoinWith('Roles')
                ->where(['Roles.id' => $role->id])
                ->orderDesc('Ratings.points')
                ->limit($limit);
    }

    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $events = TableRegistry::get('Events');
            $ev = $events->newEntity();
            $ev->type = Event2::NEW_PLAYER;
            $ev->team_id = $entity['team_id'];
            $ev->external = $entity['id'];
            $events->save($ev);
        } elseif ($entity->isDirty('club_id')) {
            $events = TableRegistry::get('Events');
            $ev = $events->newEntity();
            $ev->type = Event2::EDIT_CLUB;
            $ev->team_id = $entity['team_id'];
            $ev->external = $entity['id'];
            $events->save($ev);
        } elseif ($entity->isDirty('active')) {
            $events = TableRegistry::get('Events');
            $ev = $events->newEntity();
            $ev->type = Event2::DELETE_PLAYER;
            $ev->team_id = $entity['team_id'];
            $ev->external = $entity['id'];
            $events->save($ev);
        }
    }
}
