<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ChangePasswordCommand extends Command
{
    protected static $defaultName = 'ezuser:password';

    /** @var Repository */
    private $repository;

    /** @var UserService */
    private $userService;

    /** @var PermissionResolver */
    private $permissionResolver;

    public function __construct(Repository $repository)
    {
        parent::__construct(self::$defaultName);
        $this->repository = $repository;
        $this->userService = $this->repository->getUserService();
        $this->permissionResolver = $this->repository->getPermissionResolver();
    }

    protected function configure()
    {
        $this
            ->setDescription('Change an user password')
            ->addArgument('login', InputArgument::REQUIRED, 'login or email of the target user')
            ->addArgument('password', InputArgument::OPTIONAL, 'If omitted, asked on prompt (it is recommended to omit it to avoid having password in shell history)')
            ->addOption('admin-user', 'a', InputOption::VALUE_REQUIRED, 'Login of the admin user creating this new user', 'admin')
            ->addOption('sudo', 's', InputOption::VALUE_NONE, 'Use sudo instead of an admin user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $login = $input->getArgument('login');
        try {
            $user = $this->userService->loadUserByLogin($login);
        } catch (NotFoundException $notFoundException) {
            try {
                $user = $this->userService->loadUserByEmail($login);
            } catch (NotFoundException $notFoundException) {
                $output->writeln("<error>User $login not found.</error>");
                return Command::FAILURE;
            }
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

        if ($input->getOption('sudo')) {
            $this->repository->sudo(function () use ($user, $userUpdateStruct) {
                $this->userService->updateUser($user, $userUpdateStruct);
            });
        } else {
            $adminUserLogin = $input->getOption('admin-user');
            try {
                $adminUser = $this->userService->loadUserByLogin($adminUserLogin);
            } catch (NotFoundException $notFoundException) {
                $output->writeln("<error>Error: $adminUserLogin can't be found.</error>");

                return self::ERROR_ADMIN_NOT_FOUND;
            }
            $this->permissionResolver->setCurrentUserReference($adminUser);

            $this->userService->updateUser($user, $userUpdateStruct);
        }
    }
}
