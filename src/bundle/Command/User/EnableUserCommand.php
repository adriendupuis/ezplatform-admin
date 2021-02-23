<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableUserCommand extends UpdateUserCommandAbstract
{
    protected static $defaultName = 'ezuser:enable';

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Enable an user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = parent::execute($input, $output);
        if (self::SUCCESS !== $exitCode) {
            return $exitCode;
        }

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        return $this->updateUser($userUpdateStruct);
    }
}
