<?php

namespace App\Command;

use App\Service\WebsiteContactFinderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:test-contact-finder',
    description: 'Test recherche emails website'
)]
class TestWebsiteContactFinderCommand extends Command
{

    public function __construct(
        private WebsiteContactFinderService $websiteContactFinderService
    ) {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this->addArgument(
            'website',
            InputArgument::REQUIRED,
            'URL du site'
        );
    }


    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $website = $input->getArgument('website');


        $emails = $this->websiteContactFinderService
            ->findContacts($website);


        if ($emails === null) {

            $output->writeln(
                '<error>Aucun email trouvé</error>'
            );

            return Command::FAILURE;
        }


        $output->writeln(
            '<info>Emails trouvés :</info>'
        );


        foreach ($emails as $email) {

            $output->writeln(
                '- ' . $email
            );
        }


        return Command::SUCCESS;
    }
}