<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\User;

use AdrienDupuis\EzPlatformAdminBundle\Command\AdminCommandAbstract;
use eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Values\User\UserUpdateStruct;
use eZ\Publish\Core\FieldType\ValidationError;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class UpdateUserCommandAbstract extends AdminCommandAbstract
{
    protected static $defaultName = 'ezuser:TODO';

    public const ERROR_USER_NOT_FOUND = 4;
    public const ERROR_UPDATE_FAILED = 8;

    /* @var User */
    protected $user = null;

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('user', InputArgument::REQUIRED, 'login or email of the target user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $initCode = $this->initAdminFunctionExecution($input, $output);
        if (self::SUCCESS !== $initCode) {
            return $initCode;
        }

        $this->user = $this->loadUser($input->getArgument('user'));
        if (null === $this->user) {
            return self::ERROR_USER_NOT_FOUND;
        }

        return self::SUCCESS;
    }

    public function updateUser(UserUpdateStruct $userUpdateStruct): int
    {
        return $this->executeAdminFunction(function () use ($userUpdateStruct) {
            try {
                $this->userService->updateUser($this->user, $userUpdateStruct);

                return self::SUCCESS;
            } catch (ContentFieldValidationException $contentFieldValidationException) {
                foreach ($contentFieldValidationException->getFieldErrors() as $fieldDefinitionId => $fieldErrors) {
                    /** @var ValidationError $fieldError */
                    foreach ($fieldErrors[$this->user->getContentType()->mainLanguageCode] as $fieldError) {
                        $this->getOutput()->writeln("<error>Error: {$fieldError->getTranslatableMessage()}</error>");
                    }
                }

                return self::ERROR_UPDATE_FAILED;
            } catch (\Exception $exception) {
                $this->getOutput()->writeln("<error>Error: {$exception->getMessage()}</error>");

                return self::ERROR_UPDATE_FAILED;
            }
        });
    }
}
