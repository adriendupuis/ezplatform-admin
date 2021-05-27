<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\User;

use AdrienDupuis\EzPlatformAdminBundle\Command\AdminCommandAbstract;
use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\Values\User\UserCreateStruct;
use eZ\Publish\Core\FieldType\ValidationError;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends AdminCommandAbstract
{
    protected static $defaultName = 'ezuser:create';

    public const ERROR_CREATION_FAILED = 4;
    public const ERROR_GROUP_NOT_FOUND = 8;
    public const ERROR_NO_ACCESS_GROUP = 16;
    public const ERROR_GROUP_NOT_GIVEN = 32;

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Create a new user')
            ->addArgument('first_name', InputArgument::REQUIRED)
            ->addArgument('last_name', InputArgument::REQUIRED)
            ->addArgument('email', InputArgument::REQUIRED)
            ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'If omitted, email\'s username (part before “at” sign “@”) is used as login')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'If omitted, asked on prompt (it is recommended to omit it to avoid having password in shell history)')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'User\'s parent user group content ID. Default is “Guest accounts” group (11)', [11])
            ->addOption('lang', 'c', InputOption::VALUE_REQUIRED, 'Main language code for user object creation', 'eng-GB')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $initCode = $this->initAdminFunctionExecution($input, $output);
        if (self::SUCCESS !== $initCode) {
            return $initCode;
        }

        $email = $input->getArgument('email');
        $login = $input->getOption('login');
        $password = $input->getOption('password');
        $parentGroupIds = $input->getOption('group');
        $mainLanguageCode = $input->getOption('lang');

        if (!$login) {
            $login = explode('@', $email);
            $login = $login[0];
            $output->writeln("<comment>Email username  “{$login}” used as new user's login.</comment>");
        }
        while (!$password) {
            $question = new Question('Enter new user\'s password…');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $this->getHelper('question')->ask($input, $output, $question);
        }

        $userCreateStruct = $this->userService->newUserCreateStruct(
            $login,
            $email,
            $password,
            $mainLanguageCode
        );
        $userCreateStruct->setField('first_name', $input->getArgument('first_name'));
        $userCreateStruct->setField('last_name', $input->getArgument('last_name'));

        return $this->executeAdminFunction(function () use ($userCreateStruct, $parentGroupIds, $output) {
            return $this->createUser($userCreateStruct, $parentGroupIds, $output);
        });
    }

    private function createUser(UserCreateStruct $userCreateStruct, array $parentGroupIds, OutputInterface $output): int
    {
        $parentGroups = [];
        foreach ($parentGroupIds as $groupId) {
            try {
                $parentGroups[] = $this->userService->loadUserGroup($groupId);
            } catch (NotFoundException $exception) {
                $output->writeln("<error>Error: No group found for ID $groupId.</error>");

                return self::ERROR_GROUP_NOT_FOUND;
            } catch (UnauthorizedException $unauthorizedException) {
                $output->writeln("<error>Error: Current user ({$this->permissionResolver->getCurrentUserReference()->getUserId()}) can't access to group with ID $groupId.</error>");

                return self::ERROR_NO_ACCESS_GROUP;
            }
        }
        if (!count($parentGroups)) {
            $output->writeln('<error>Error: A minimum of one user group is mandatory.</error>');

            return self::ERROR_GROUP_NOT_GIVEN;
        }

        try {
            $this->userService->createUser($userCreateStruct, $parentGroups);

            return self::SUCCESS;
        } catch (ContentFieldValidationException $contentFieldValidationException) {
            foreach ($contentFieldValidationException->getFieldErrors() as $fieldDefinitionId => $fieldErrors) {
                /** @var ValidationError $fieldError */
                foreach ($fieldErrors[$userCreateStruct->mainLanguageCode] as $fieldError) {
                    $output->writeln("<error>Error: {$fieldError->getTranslatableMessage()}</error>");
                }
            }

            return self::ERROR_CREATION_FAILED;
        } catch (\Exception $exception) {
            $output->writeln("<error>Error: {$exception->getMessage()}</error>");

            return self::ERROR_CREATION_FAILED;
        }

        return self::FAILURE;
    }
}
