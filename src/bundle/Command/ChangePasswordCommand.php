<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\FieldType\ValidationError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ChangePasswordCommand extends Command
{
    protected static $defaultName = 'ezuser:password';

    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const ERROR_USER_NOT_FOUND = 1;
    public const ERROR_UPDATE_FAILED = 2;
    public const ERROR_ADMIN_NOT_FOUND = 9;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = $input->getArgument('login');

        $password = $input->getArgument('password');
        while (!$password) {
            $question = new Question('Enter user\'s new password…');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $this->getHelper('question')->ask($input, $output, $question);
        }

        if ($input->getOption('sudo')) {
            return $this->repository->sudo(function () use ($user, $userUpdateStruct) {
                return $this->updatePassword($login, $password, $output);
            });
        } else {
            $adminUserLogin = $input->getOption('admin-user');
            try {
                $adminUser = $this->userService->loadUserByLogin($adminUserLogin);
            } catch (NotFoundException $notFoundException) {
                $output->writeln("<error>Error: User '$adminUserLogin' not found.</error>");

                return self::ERROR_ADMIN_NOT_FOUND;
            }
            $this->permissionResolver->setCurrentUserReference($adminUser);

            return $this->updatePassword($login, $password, $output);
        }

        return self::FAILURE;
    }

    private function updatePassword($loginOrEmail, $password, $output): int
    {
        /* @var User $user */
        try {
            $user = $this->userService->loadUserByLogin($loginOrEmail);
        } catch (NotFoundException $notFoundException) {
            try {
                $user = $this->userService->loadUserByEmail($loginOrEmail);
            } catch (NotFoundException $notFoundException) {
                $output->writeln("<error>User '$login' not found.</error>");

                return self::ERROR_USER_NOT_FOUND;
            }
        }

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $password;

        try {
            $this->userService->updateUser($user, $userUpdateStruct);

            return self::SUCCESS;
        } catch (ContentFieldValidationException $contentFieldValidationException) {
            foreach ($contentFieldValidationException->getFieldErrors() as $fieldDefinitionId => $fieldErrors) {
                /** @var ValidationError $fieldError */
                foreach ($fieldErrors[$user->getContentType()->mainLanguageCode] as $fieldError) {
                    $output->writeln("<error>Error: {$fieldError->getTranslatableMessage()}</error>");
                }
            }

            return self::ERROR_UPDATE_FAILED;
        } catch (\Exception $exception) {
            $output->writeln("<error>Error: {$exception->getMessage()}</error>");

            return self::ERROR_UPDATE_FAILED;
        }

        return self::FAILURE;
    }
}
