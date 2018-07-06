<?php

namespace App\Command;

use App\Traits\CurrentMatchdayTrait;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * @property \App\Model\Table\MatchdaysTable $Matchdays
 * @property \App\Model\Table\SelectionsTable $Selections
 */
class TransfertCommand extends Command
{

    use CurrentMatchdayTrait;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Matchdays');
        $this->loadModel('Selections');
        $this->getCurrentMatchday();
    }

    public function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->addOption('no-commit', [
            'help' => 'Disable commit.',
            'short' => 'c',
            'boolean' => true
        ]);
        $parser->addOption('force', [
            'help' => 'Force the execution',
            'short' => 'f',
            'boolean' => true
        ]);
        $parser->addOption('no-interaction', [
            'short' => 'n',
            'help' => 'Disable interaction',
            'boolean' => true,
            'default' => false
        ]);

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        if ($this->currentMatchday->isDoTransertDay() || $args->getOption('force')) {
            $selections = $this->Selections->find()
                ->contain(['OldMembers.Players', 'NewMembers.Players', 'Teams'])
                ->where(['matchday_id' => $this->currentMatchday->id, 'processed' => false]);
            $table[] = ['Team', 'New Member', 'Old Member'];
            if (!$selections->isEmpty()) {
                foreach ($selections as $selection) {
                    $selection->processed = true;
                    $selection->matchday_id = $this->currentMatchday->id;
                    $table[] = [
                        $selection->team->name,
                        $selection->old_member->player->fullName,
                        $selection->new_member->player->fullName,
                    ];
                }
                $io->helper('Table')->output($table);
                if (!$args->getOption('no-commit')) {
                    $this->doTransferts($io, $selections);
                }
            } else {
                $io->out('No unprocessed selections found');
            }
        }
    }

    private function doTransferts(ConsoleIo $io, array $selections)
    {
        if ($this->Selections->saveMany($selections)) {
            $io->out('Changes committed');
        } else {
            $io->out('Error occurred');
            foreach ($selections as $value) {
                if (!empty($value->getErrors())) {
                    $io->error($value);
                    $io->error(print_r($value->getErrors()));
                }
            }
        }
    }
}