<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ChampionshipsTable;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * App\Model\Table\ChampionshipsTable Test Case
 */
class ChampionshipsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ChampionshipsTable
     */
    protected $Championships;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Championships',
        'app.Leagues',
        'app.Seasons',
        'app.Teams',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Championships') ? [] : ['className' => ChampionshipsTable::class];
        $this->Championships = TableRegistry::getTableLocator()->get('Championships', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Championships);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $validation = $this->Championships->validationDefault(new Validator());
        $this->assertNotNull($validation);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $rules = $this->Championships->buildRules(new RulesChecker());
        $this->assertNotNull($rules);
    }
}
