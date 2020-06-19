<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\Core\Repository\Values\User\UserCreateStruct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'ezuser:create';

    const SUCCESS=0;
    const FAILURE=1;
    const ERROR_CREATION_FAILED=2;
    const ERROR_GROUP_NOT_FOUND=4;
    const ERROR_NO_ACCESS_GROUP=5;
    const ERROR_GROUP_NOT_GIVEN=6;
    const ERROR_ADMIN_NOT_FOUND=9;

    /** @var UserService */
    private $userService;

    /** @varPermissionResolver */
    private $permissionResolver;

    public function __construct(UserService $userService, PermissionResolver $permissionResolver)
    {
        parent::__construct(self::$defaultName);
        $this->userService = $userService;
        $this->permissionResolver = $permissionResolver;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new user')
            ->addArgument('first_name', InputArgument::REQUIRED)
            ->addArgument('last_name', InputArgument::REQUIRED)
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('login', InputArgument::OPTIONAL, 'If omitted, email is used as login')
            ->addArgument('password', InputArgument::OPTIONAL, 'If omitted, asked on prompt (recommanded to avoid having password in shell history)')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'User\'s parent user group content ID.')
            ->addOption('admin-user', 'a', InputOption::VALUE_REQUIRED, 'Login of the admin user creating this new user', 'admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adminUserLogin = $input->getOption('admin-user');
        try {
            $adminUser = $this->userService->loadUserByLogin($adminUserLogin);
        } catch (NotFoundException $notFoundException) {
            $output->writeln("<error>Error: $adminUserLogin can't be found.</error>");
            return self::ERROR_ADMIN_NOT_FOUND;
        }

        $email = $input->getArgument('email');
        $login = $input->getArgument('login');
        $password = $input->getArgument('password');
        $mainLanguageCode = 'eng-GB';//TODO: How to select the right language?

        //if (!$login) { $login=$email; }
        if (!$login) { $login='dummy'; }
        while (!$password) {
            $password = $this->getHelper('question')->ask($input, $output, new Question('Enter password: '));
        }

        $userCreateStruct = $this->userService->newUserCreateStruct(
            $login,
            $email,
            $password,
            $mainLanguageCode
        );
        $userCreateStruct->setField('first_name', $input->getArgument('first_name'));
        $userCreateStruct->setField('last_name', $input->getArgument('last_name'));

        $this->permissionResolver->setCurrentUserReference($adminUser);

        $parentGroups = [];
        foreach ($input->getOption('group') as $groupId) {
            try {
                $parentGroups[] = $this->userService->loadUserGroup($groupId);
            } catch (NotFoundException $exception) {
                $output->writeln("Error: No group found for ID $groupId.");
                return self::ERROR_GROUP_NOT_FOUND;
            } catch (UnauthorizedException $unauthorizedException) {
                $output->writeln("Error: $adminUserLogin can't access to group with ID $groupId.");
                return self::ERROR_NO_ACCESS_GROUP;
            }
        }
        if (!count($parentGroups)) {
            $output->writeln('Error: A minimum of one user group is mandatory.');
            return self::ERROR_GROUP_NOT_GIVEN;
        }

        try {
            $this->userService->createUser($userCreateStruct, $parentGroups);
            return self::SUCCESS;
        } catch (ContentFieldValidationException $contentFieldValidationException) {
            foreach ($contentFieldValidationException->getFieldErrors() as $fieldDefinitionId => $fieldErrors) {
                /** @var ValidationError $fieldError */
                foreach ($fieldErrors[$mainLanguageCode] as $fieldError) {
                    $output->writeln("<error>Error: {$fieldError->getTranslatableMessage()}</error>");
                }
            }
            return self::ERROR_CREATION_FAILED;
        } catch (\Exception $exception) {
            $output->writeln("Error: {$exception->getMessage()}");
            return self::ERROR_CREATION_FAILED;
        }

        return self::FAILURE;
    }
}
