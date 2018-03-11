<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SeasonsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SeasonsTable Test Case
 */
class SeasonsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\SeasonsTable
     */
    public $Seasons;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.seasons',
        'app.championships',
        'app.leagues',
        'app.teams',
        'app.matchdays',
        'app.articles',
        'app.lineups',
        'app.members',
        'app.players',
        'app.roles',
        'app.clubs',
        'app.dispositions',
        'app.ratings',
        'app.members_teams',
        'app.scores',
        'app.transferts'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Seasons') ? [] : ['className' => 'App\Model\Table\SeasonsTable'];
        $this->Seasons = TableRegistry::get('Seasons', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Seasons);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
