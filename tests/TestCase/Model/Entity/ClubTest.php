<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Club;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Entity\Club Test Case
 */
class ClubTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Entity\Club
     */
    public $Club;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Club = new Club();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Club);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
