<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PlayersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PlayersTable Test Case
 */
class PlayersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PlayersTable
     */
    public $PlayersTable;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Players',
        'app.Members',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Players') ? [] : ['className' => PlayersTable::class];
        $this->PlayersTable = TableRegistry::getTableLocator()->get('Players', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->PlayersTable);

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

    /**
     * Test findWithDetails method
     *
     * @return void
     */
    public function testFindWithDetails()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
