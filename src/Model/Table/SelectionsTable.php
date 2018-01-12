<?php

namespace App\Model\Table;

use App\Model\Entity\Event;
use App\Model\Entity\Selection;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event as CakeEvent;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Minishlink\WebPush\WebPush;

/**
 * Selections Model
 *
 * @property TeamsTable|BelongsTo $Teams
 * @property BelongsTo $Members
 * @property BelongsTo $Members
 * @property MatchdaysTable|BelongsTo $Matchdays
 * @property MembersTable|BelongsTo $NewMembers
 * @property MembersTable|BelongsTo $OldMembers
 * @method Selection get($primaryKey, $options = [])
 * @method Selection newEntity($data = null, array $options = [])
 * @method Selection[] newEntities(array $data, array $options = [])
 * @method Selection|bool save(EntityInterface $entity, $options = [])
 * @method Selection patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Selection[] patchEntities($entities, array $data, array $options = [])
 * @method Selection findOrCreate($search, callable $callback = null, $options = [])
 */
class SelectionsTable extends Table
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

        $this->setTable('selections');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo(
            'Teams',
            [
                'foreignKey' => 'team_id',
                'joinType' => 'INNER'
            ]
        );
        $this->belongsTo(
            'Matchdays',
            [
                'foreignKey' => 'matchday_id',
                'joinType' => 'INNER'
            ]
        );
        $this->belongsTo(
            'NewMembers',
            [
                'className' => 'Members',
                'foreignKey' => 'old_member_id',
                'propertyName' => 'old_member'
            ]
        );
        $this->belongsTo(
            'OldMembers',
            [
                'className' => 'Members',
                'foreignKey' => 'new_member_id',
                'propertyName' => 'new_member'
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
            ->boolean('active')
            ->requirePresence('active', 'create')
            ->notEmpty('active');

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
        $that = $this;
        $rules->add($rules->existsIn(['team_id'], 'Teams'));
        $rules->add($rules->existsIn(['matchday_id'], 'Matchdays'));
        $rules->add($rules->existsIn(['old_member_id'], 'OldMembers'));
        $rules->add($rules->existsIn(['new_member_id'], 'NewMembers'));
        $rules->add(
            function (Selection $entity, $options) {
            $selection = $this->findAlreadySelectedMember($entity);
            if ($selection != null) {
                $ranking = TableRegistry::get('Scores')->findRanking($selection->team->championship_id);
                $rank = \Cake\Utility\Hash::extract($ranking->toArray(), '{n}.team_id');
                if (array_search($entity->team_id, $rank) > array_search($selection->team->id, $rank)) {
                    $selection->active = false;
                    $this->save($selection);
                    $this->notifyLostMember($selection);

                    return true;
                } else {
                    return false;
                }
            }

            return true;
        },
            'NewMemberIsSelectable',
            ['errorField' => 'new_member', 'message' => 'Un altro utente ha già selezionato il giocatore']
        );
        $rules->add(
            function (Selection $entity, $options) use ($that) {
            $championship = TableRegistry::get('Championships')->find()->innerJoinWith(
                    'Teams',
                    function ($q) use ($entity) {
                    return $q->where(['Teams.id' => $entity->team_id]);
                }
                )->first();

            return $that->find()->where(['team_id' => $entity->team_id, 'processed' => false])->count() < $championship->number_selections;
        },
            'TeamReachedMaximum',
            ['errorField' => 'new_member', 'message' => 'Hai raggiunto il limite di cambi selezione']
        );

        return $rules;
    }

    public function afterSave(CakeEvent $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $events = TableRegistry::get('Events');
            $ev = $events->newEntity();
            $ev->type = Event::NEW_PLAYER_SELECTION;
            $ev->team_id = $entity['team_id'];
            $events->save($ev);
        }
    }

    public function beforeSave(CakeEvent $event, Selection $entity, ArrayObject $options)
    {
        if ($entity->dirty('processed') && $entity->processed) {
            $membersTeamsTable = TableRegistry::get('MembersTeams');
            $transfertsTable = TableRegistry::get('Transferts');
            $memberTeam = $membersTeamsTable->find()
                ->where(
                    [
                        'team_id' => $entity->team_id,
                        'member_id' => $entity->old_member_id
                    ]
                )
                ->first();
            $memberTeam->member_id = $entity->new_member_id;
            $transfert = $entity->toTransfert($transfertsTable);
            $membersTeamsTable->save($memberTeam);
            $transfertsTable->save($transfert);
        }
    }

    /**
     * 
     * @param Selection $selection
     */
    public function notifyLostMember(Selection $selection)
    {
        \Cake\Log\Log::debug('notifico utente');
        $selection = $this->loadInto($selection, ['Teams.Users.Subscriptions', 'NewMembers.Players']);
        $webPush = new WebPush(Configure::read('WebPush'));
        foreach ($selection->team->user->subscriptions as $subscription) {
            $message = WebPushMessage::create(Configure::read('WebPushMessage.default'))
                ->title('Un altra squadra ti ha soffiato un giocatore selezionato')
                ->body('Hai perso il giocatore ' . $selection->new_member->player->surname . ' ' . $selection->new_member->player->name)
                ->tag('lost-player-' . $selection->id);
            $webPush->sendNotification(
                $subscription->endpoint,
                json_encode($message),
                $subscription->public_key,
                $subscription->auth_token
            );
        }
    }

    /**
     * 
     * @param Selection $selection
     * @return Selection
     */
    public function findAlreadySelectedMember($selection)
    {
        $team = TableRegistry::get('Teams')->get($selection->team_id);
        return $this->find()
                ->contain(['Teams'])
                ->matching(
                    'Teams',
                    function ($q) use ($team) {
                        return $q->where(['Teams.championship_id' => $team->championship_id]);
                    }
                )
                ->where(
                    [
                        'team_id !=' => $selection->team_id,
                        'new_member_id' => $selection->new_member_id
                    ]
                )->first();
    }
}