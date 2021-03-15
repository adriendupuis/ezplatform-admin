<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\User;

use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Values\User\UserUpdateStruct;
use eZ\Publish\Core\FieldType\ValidationError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class UpdateUserCommandAbstract extends Command
{
    protected static $defaultName = 'ezuser:TODO';

    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const ERROR_USER_NOT_FOUND = 2;
    public const ERROR_UPDATE_FAILED = 4;
    public const ERROR_ADMIN_NOT_FOUND = 8;

    /** @var Repository */
    private $repository;

    /** @var UserService */
    protected $userService;

    /** @var PermissionResolver */
    private $permissionResolver;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /* @var User */
    protected $user = null;

    /* @var User */
    protected $admin = null;

    public function __construct(Repository $repository)
    {
        parent::__construct(self::$defaultName);
        $this->repository = $repository;
        $this->userService = $this->repository->getUserService();
        $this->permissionResolver = $this->repository->getPermissionResolver();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::REQUIRED, 'login or email of the target user')
            ->addOption('admin', 'a', InputOption::VALUE_REQUIRED, 'Login of the admin user creating this new user; If not given, sudo is used');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->user = $this->loadUser($input->getArgument('user'));
        if (null === $this->user) {
            return ERROR_USER_NOT_FOUND;
        }
        if ($input->getOption('admin')) {
            $this->admin = $this->loadUser($input->getOption('admin'));
        }
        if (null === $this->admin) {
            if ($input->getOption('admin')) {
                return ERROR_ADMIN_NOT_FOUND;
            } else {
                $output->writeln('<info>sudo will be used.</info>');
            }
        } else {
            $this->permissionResolver->setCurrentUserReference($this->admin);
        }

        return self::SUCCESS;
    }

    private function loadUser($loginOrEmail): User
    {
        /* @var User $user */
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

    public function updateUser(UserUpdateStruct $userUpdateStruct): int
    {
        if (null === $this->admin) {
            return $this->repository->sudo(function () use ($userUpdateStruct) {
                return $this->finalUpdateUser($userUpdateStruct);
            });
        } else {
            return $this->finalUpdateUser($userUpdateStruct);
        }
    }

    private function finalUpdateUser(UserUpdateStruct $userUpdateStruct): int
    {
        try {
            $this->userService->updateUser($this->user, $userUpdateStruct);

            return self::SUCCESS;
        } catch (ContentFieldValidationException $contentFieldValidationException) {
            foreach ($contentFieldValidationException->getFieldErrors() as $fieldDefinitionId => $fieldErrors) {
                /** @var ValidationError $fieldError */
                foreach ($fieldErrors[$this->user->getContentType()->mainLanguageCode] as $fieldError) {
                    $this->output->writeln("<error>Error: {$fieldError->getTranslatableMessage()}</error>");
                }
            }

            return self::ERROR_UPDATE_FAILED;
        } catch (\Exception $exception) {
            $this->output->writeln("<error>Error: {$exception->getMessage()}</error>");

            return self::ERROR_UPDATE_FAILED;
        }
    }
}
