<?php

namespace DZunke\SlackBundle\Command;

use DZunke\SlackBundle\Slack\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends ContainerAwareCommand
{

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Client
     */
    protected $client;

    protected function configure()
    {
        $this
            ->setName('dzunke:slack:debug')
            ->setDescription('Gives some Debug Informations about the SlackBundle');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->client = $this->getContainer()->get('dz.slack.client');

        $config = $this->getContainer()->getParameter('d_zunke_slack.config');

        $this->renderConnection($config);
        $this->output->writeln('');
        $this->renderIdentities($config);
        $this->output->writeln('');
        $this->renderSlackChannels($config);
    }

    protected function renderSlackChannels()
    {
        $this->output->writeln('<info>Available Channels</info>');

        $channels = $this->client->send(
            Client\Actions::ACTION_CHANNELS_LIST,
            [],
            null
        );

        if ($channels->getStatus() == false) {
            $this->output->writeln('<error>' . $channels->getError() . '</error>');
            return;
        }

        $table = new Table($this->output);

        $index = 0;
        foreach ($channels->getData() as $channel) {

            if ($index > 0) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow(['<info>' . $channel['name'] . '</info>']);
            $table->addRow(
                [
                    '  id',
                    $channel['id']
                ]
            );
            $table->addRow(
                [
                    '  members',
                    $channel['num_members']
                ]
            );

            $index++;
        }

        $table->render();
    }

    protected function renderIdentities(array $config)
    {
        $this->output->writeln('<info>Identities</info>');

        $table = new Table($this->output);

        $index = 0;
        foreach ($config['identities'] as $username => $userConfig) {

            if ($index > 0) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow(['<info>' . $username . '</info>']);

            foreach ($userConfig as $configName => $configValue) {
                $table->addRow(
                    [
                        "  " . $configName,
                        is_null($configValue) ? 'null' : $configValue
                    ]
                );
            }

            $index++;
        }

        $table->render();
    }

    protected function renderConnection(array $config)
    {
        $this->output->writeln('<info>Connection</info>');

        $table = new Table($this->output);

        $table->addRow(['endpoint', $config['endpoint']]);
        $table->addRow(['token', $config['token']]);

        $connectionTest = $this->client->send(
            Client\Actions::ACTION_API_TEST,
            [],
            null
        );

        $table->addRow(new TableSeparator());

        if ($connectionTest->getStatus()) {
            $statusMessage = '<info>Ok</info>';
        } else {
            $statusMessage = '<error>' . $connectionTest->getError() . '</error>';
        }
        $table->addRow(['ConnectionStatus', $statusMessage]);

        $table->render();
    }

}
