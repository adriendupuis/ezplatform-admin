<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\User;

use AdrienDupuis\EzPlatformAdminBundle\Command\AdminCommandAbstract;
use AdrienDupuis\EzPlatformAdminBundle\Command\OutputStyleTrait;
use eZ\Publish\API\Repository\Exceptions\LimitationValidationException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\Values\User\Limitation\SiteAccessLimitation;
use eZ\Publish\API\Repository\Values\User\PolicyDraft;
use eZ\Publish\API\Repository\Values\User\RoleDraft;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use EzSystems\EzPlatformAdminUi\Siteaccess\SiteAccessKeyGeneratorInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AllowSiteaccessToAnonymousCommand extends AdminCommandAbstract
{
    use OutputStyleTrait;

    protected static $defaultName = 'ezuser:anonymous:siteaccess';

    /** @var RoleService */
    private $roleService;

    /** @var SiteAccessKeyGeneratorInterface */
    private $siteAccessKeyGenerator;

    /** @var SiteAccessServiceInterface */
    private $siteAccessService;

    public function __construct(Repository $repository, SiteAccessKeyGeneratorInterface $siteAccessKeyGenerator, SiteAccessServiceInterface $siteAccessService)
    {
        parent::__construct($repository);
        $this->roleService = $this->repository->getRoleService();
        $this->siteAccessKeyGenerator = $siteAccessKeyGenerator;
        $this->siteAccessService = $siteAccessService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Add a siteaccess to Anonymous role login policy to make it public')
            ->addArgument('siteaccess', InputArgument::REQUIRED, 'siteaccess code name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setStyle($output);

        $initCode = $this->initAdminFunctionExecution($input, $output);
        if (self::SUCCESS !== $initCode) {
            return $initCode;
        }

        $siteAccessIdentifier = $input->getArgument('siteaccess');

        if ($this->siteAccessService->exists($siteAccessIdentifier)) {
            //TODO: Check that current siteaccess and added siteaccess are on the same repository
        } else {
            $message = "SiteAccess “{$siteAccessIdentifier}” doesn't exist.";
            $output->writeln("<error>$message</error>");

            return self::FAILURE;
        }

        return $this->executeAdminFunction(function () use ($siteAccessIdentifier, $output) {
            $roleIdentifier = 'Anonymous';
            try {
                /** @var RoleDraft $roleDraft */
                $roleDraft = $this->roleService->createRoleDraft($this->roleService->loadRoleByIdentifier($roleIdentifier));
            } catch (NotFoundException $notFoundException) {
                $output->writeln("Role “{$roleIdentifier}” not found.");

                return self::FAILURE;
            }

            /** @var PolicyDraft $policy */
            $policy = null;
            /** @var PolicyDraft $testedPolicy */
            foreach ($roleDraft->getPolicies() as $testedPolicy) {
                if ('user' === $testedPolicy->module && 'login' === $testedPolicy->function) {
                    $policy = $testedPolicy;
                }
            }

            $availableSiteAccessList = [];
            foreach ($this->siteAccessService->getAll() as $availableSiteAccess) {
                $availableSiteAccessList[$this->siteAccessKeyGenerator->generate($availableSiteAccess->name)] = $availableSiteAccess->name;
            }

            $addedLimitationValues = [];
            $removedLimitationValues = [];

            if ($policy) {
                foreach ($policy->getLimitations() as $limitation) {
                    if ($limitation instanceof SiteAccessLimitation) {
                        foreach ($limitation->limitationValues as $limitationValue) {
                            if (array_key_exists($limitationValue, $availableSiteAccessList)) {
                                $addedLimitationValues[] = $limitationValue;
                            } else {
                                $removedLimitationValues[] = $limitationValue;
                            }
                        }
                    }
                }
            }

            $siteAccessKey = $this->siteAccessKeyGenerator->generate($siteAccessIdentifier);
            if (in_array($siteAccessKey, $addedLimitationValues)) {
                $output->writeln("<info>Siteaccess “{$siteAccessIdentifier}” is already part of Anonymous login policy.</info>");

                return self::SUCCESS;
            } else {
                $addedLimitationValues[] = $siteAccessKey;
            }

            $siteAccessLimitation = new SiteAccessLimitation(['limitationValues' => $addedLimitationValues]);

            try {
                if ($policy) {
                    $policyUpdateStruct = $this->roleService->newPolicyUpdateStruct();
                    $policyUpdateStruct->addLimitation($siteAccessLimitation);
                    $this->roleService->updatePolicyByRoleDraft($roleDraft, $policy, $policyUpdateStruct);
                } else {
                    $output->writeln("<info>Policy user/login will be created on “{$roleIdentifier}”.</info>");
                    $policyCreateStruct = $this->roleService->newPolicyCreateStruct('user', 'login');
                    $policyCreateStruct->addLimitation($siteAccessLimitation);
                    $this->roleService->addPolicyByRoleDraft($roleDraft, $policyCreateStruct);
                }
            } catch (LimitationValidationException $limitationValidationException) {
                //foreach ($limitationValidationException->getLimitationErrors() as $limitationError) {
                foreach ($limitationValidationException->validationErrors as $limitationErrors) {
                    /** @var ValidationError $limitationError */
                    foreach ($limitationErrors as $limitationError) {
                        $output->writeln("<error>{$limitationError->getTranslatableMessage()}</error>");
                    }
                }

                return self::FAILURE;
            } catch (\Throwable $throwable) {
                $output->writeln("<error>{$throwable->getMessage()}</error>");

                return self::FAILURE;
            }

            try {
                $this->roleService->publishRoleDraft($roleDraft);
                $output->writeln("<info>Siteaccess “{$siteAccessIdentifier}” has been added (as $siteAccessKey).</info>");
                if ($count = count($removedLimitationValues)) {
                    $output->writeln("<warning>$count non-existent siteaccesses were removed during this action.</warning>");
                }

                return self::SUCCESS;
            } catch (\Throwable $throwable) {
                $output->writeln("<error>{$throwable->getMessage()}</error>");

                return self::FAILURE;
            }
        });
    }
}
