<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AdminCommandAbstract extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const ERROR_ADMIN_NOT_FOUND = 2;

    /** @var Repository */
    protected $repository;

    /** @var UserService */
    protected $userService;

    /** @var PermissionResolver */
    protected $permissionResolver;

    /* @var User */
    protected $admin;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    public function __construct(Repository $repository)
    {
        parent::__construct(self::$defaultName);
        $this->repository = $repository;
        $this->userService = $this->repository->getUserService();
        $this->permissionResolver = $this->repository->getPermissionResolver();
    }

    protected function configure(): void
    {
        $this->addOption('admin', 'a', InputOption::VALUE_REQUIRED, 'Login of the admin user creating this new user; If not given, sudo is used');
    }

    protected function initAdminFunctionExecution(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        if ($input->getOption('admin')) {
            $this->admin = $this->loadUser($input->getOption('admin'));
        }
        if (null === $this->admin) {
            if ($input->getOption('admin')) {
                return self::ERROR_ADMIN_NOT_FOUND;
            } else {
                $output->writeln('<info>sudo will be used.</info>');
            }
        } else {
            $this->permissionResolver->setCurrentUserReference($this->admin);
        }

        return self::SUCCESS;
    }

    protected function executeAdminFunction($function): int
    {
        if (null === $this->admin) {
            return $this->repository->sudo($function);
        } else {
            return $function();
        }
    }

    protected function loadUser($loginOrEmail): ?User
    {
        /* @var User|null $user */
        $user = null;

        try {
            $user = $this->userService->loadUserByLogin($loginOrEmail);
        } catch (NotFoundException $notFoundException) {
            try {
                $user = $this->userService->loadUserByEmail($loginOrEmail);
            } catch (NotFoundException $notFoundException) {
                $this->output->writeln("<error>User '$loginOrEmail' not found.</error>");
            }
        }

        return $user;
    }
}
