<?php

namespace App\Command;

use App\Model\Entity\Season;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Symfony\Component\DomCrawler\Crawler;

class CreateStreamCommand extends Command
{

    /**
     *
     * @var \GetStream\Stream\Client
     */
    private $client;

    public function initialize()
    {
        parent::initialize();
        $this->client = new \GetStream\Stream\Client('sgyn7qrwwa6g', 'hbgt5gxen8dzs2mx8fypqmszykb7x62d6pxpn38m62e5jrreaz5jh289qsmra23h');
    }

    public function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->setDescription('Test');

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $championshipFeed = $this->client->feed('championship', 12);
        $teamFeed = $this->client->feed('team', '49');
        $teamFeed->follow('championship', 12);
        //$io->out($championshipFeed->getToken());
    }
}
