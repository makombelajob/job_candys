<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-user-creator',
    description: 'Teste la génération d’une adresse email interne pour un utilisateur',
)]
class TestUserCreatorCommand extends Command
{
    private const SENDER_DOMAIN = 'send.job-candys.fr';

    protected function configure(): void
    {
        $this->addArgument(
            'email',
            InputArgument::REQUIRED,
            'Adresse email de l’utilisateur'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $email = $input->getArgument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln([
                '<error>Adresse email invalide.</error>',
                'Exemple : php bin/console app:test-user-creator example@yes.com',
            ]);

            return Command::INVALID;
        }

        $identifier = bin2hex(random_bytes(8));

        $senderEmail = sprintf(
            'u-%s@%s',
            $identifier,
            self::SENDER_DOMAIN
        );

        $output->writeln('');
        $output->writeln('<info>Test réussi.</info>');
        $output->writeln('');
        $output->writeln('Email utilisateur : ' . $email);
        $output->writeln('Identifiant       : ' . $identifier);
        $output->writeln('Email interne     : ' . $senderEmail);
        $output->writeln('');

        return Command::SUCCESS;
    }
}

