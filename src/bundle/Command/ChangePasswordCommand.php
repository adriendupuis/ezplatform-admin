<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ChangePasswordCommand extends UpdateUserCommandAbstract
{
    protected static $defaultName = 'ezuser:password';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Change an user password')
            ->addArgument('password', InputArgument::OPTIONAL, 'If omitted, asked on prompt (it is recommended to omit it to avoid having password in shell history)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = parent::execute($input, $output);
        if (self::SUCCESS !== $exitCode) {
            return $exitCode;
        }


        $password = $input->getArgument('password');
        while (!$password) {
            $question = new Question('Enter user\'s new password…');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $this->getHelper('question')->ask($input, $output, $question);
        }

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $password;

        return $this->updateUser($userUpdateStruct);
    }
}
