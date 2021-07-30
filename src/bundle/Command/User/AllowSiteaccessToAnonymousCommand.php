<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\User;

use AdrienDupuis\EzPlatformAdminBundle\Command\AdminCommandAbstract;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\Values\User\Limitation\SiteAccessLimitation;
use eZ\Publish\API\Repository\Values\User\PolicyDraft;
use eZ\Publish\API\Repository\Values\User\RoleDraft;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use EzSystems\EzPlatformAdminUi\Siteaccess\SiteAccessKeyGeneratorInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AllowSiteaccessToAnonymousCommand extends AdminCommandAbstract
{
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
            ->addArgument('siteaccess', InputArgument::REQUIRED, 'siteaccess code name')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Add even if siteaccess doesn\'t exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $initCode = $this->initAdminFunctionExecution($input, $output);
        if (self::SUCCESS !== $initCode) {
            return $initCode;
        }

        $siteAccessIdentifier = $input->getArgument('siteaccess');

        if ($this->siteAccessService->exists($siteAccessIdentifier)) {
            //TODO: Check that current siteaccess and added siteaccess are on the same repository
        } else {
            $message = "SiteAccess “{$siteAccessIdentifier}” doesn't exist.";
            if ($input->getOption('force')) {
                $output->writeln("<warning>$message</warning>");
            } else {
                $output->writeln("<error>$message</error>");

                return self::FAILURE;
            }
        }

        return $this->executeAdminFunction(function () use ($siteAccessIdentifier, $output) {
            /** @var RoleDraft $roleDraft */
            $roleDraft = $this->roleService->createRoleDraft($this->roleService->loadRoleByIdentifier('Anonymous'));

            /** @var PolicyDraft $policy */
            $policy = null;
            /** @var PolicyDraft $testedPolicy */
            foreach ($roleDraft->getPolicies() as $testedPolicy) {
                if ('user' === $testedPolicy->module && 'login' === $testedPolicy->function) {
                    $policy = $testedPolicy;
                }
            }

            if ($policy) {
                $limitationValues = [];
                foreach ($policy->getLimitations() as $limitation) {
                    if ($limitation instanceof SiteAccessLimitation) {
                        $limitationValues = $limitation->limitationValues;
                    }
                }
                $siteAccessKey = $this->siteAccessKeyGenerator->generate($siteAccessIdentifier);
                if (in_array($siteAccessKey, $limitationValues)) {
                    $output->writeln("<info>Siteaccess “{$siteAccessIdentifier}” is already part of Anonymous login policy.</info>");
                } else {
                    $limitationValues[] = $siteAccessKey;
                }

                $policyUpdateStruct = $this->roleService->newPolicyUpdateStruct();
                $policyUpdateStruct->addLimitation(new SiteAccessLimitation(['limitationValues' => $limitationValues]));
                $this->roleService->updatePolicyByRoleDraft($roleDraft, $policy, $policyUpdateStruct);
                $this->roleService->publishRoleDraft($roleDraft);

                return self::SUCCESS;
            } else {
                //TODO: error
            }
        });
    }
}
