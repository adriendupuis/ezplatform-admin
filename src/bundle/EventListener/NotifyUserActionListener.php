<?php

namespace AdrienDupuis\EzPlatformAdminBundle\EventListener;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\NotificationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Notification\CreateStruct;
use eZ\Publish\API\Repository\Values\User\UserGroup;
use EzSystems\EzPlatformWorkflow\Event\Action\AbstractStageWorkflowActionListener;
use EzSystems\EzPlatformWorkflow\MarkingStore\ContextualMarking;
use Symfony\Component\Workflow\Event\EnteredEvent;

class NotifyUserActionListener extends AbstractStageWorkflowActionListener
{
    /** @var UserService */
    private $userService;

    /** @var NotificationService */
    private $notificationService;

    /** @var PermissionResolver */
    private $permissionResolver;

    public function __construct(
        UserService $userService,
        NotificationService $notificationService,
        PermissionResolver $permissionResolver
    ) {
        $this->userService = $userService;
        $this->notificationService = $notificationService;
        $this->permissionResolver = $permissionResolver;
    }

    public function getIdentifier(): string
    {
        return 'notify_user';
    }

    public function onWorkflowEvent(EnteredEvent $event): void
    {
        $marking = $event->getWorkflow()->getMarkingStore()->getMarking($event->getSubject());

        if (!$marking instanceof ContextualMarking) {
            return;
        }

        /** @var Content $content */
        $content = $event->getSubject();
        $versionInfo = $content->getVersionInfo();

        $context = $marking->getContext();

        $sender = $this->userService->loadUser(
            $this->permissionResolver->getCurrentUserReference()->getUserId()
        );

        $userList = [];
        foreach (array_keys($event->getMarking()->getPlaces()) as $place) {
            foreach ($event->getWorkflow()->getMetadataStore()->getPlaceMetadata($place)['actions'][$this->getIdentifier()]['data'] as $id) {
                $userList = array_merge($userList, $this->getUserList($id));
            }
        }

        foreach ($userList as $user) {
            $notification = new CreateStruct();
            $notification->ownerId = $user->id;
            $notification->type = 'Workflow:NotifyReviewer';
            $notification->data = [
                'content_id' => $content->id,
                'content_name' => $content->getName(),
                'version_number' => $versionInfo->versionNo,
                'language_code' => $versionInfo->initialLanguageCode,
                'sender_id' => $sender->id,
                'sender_name' => $sender->getName(),
                'message' => $context->message,
            ];

            $this->notificationService->createNotification($notification);
        }
    }

    /**
     * @param $id int|string User ID, user group ID, user login or user email
     *
     * @return User[]
     */
    public function getUserList($id)
    {
        $userList = [];

        if (is_int($id)) {
            try {
                $userGroup = $this->userService->loadUserGroup($id);
                $userList = array_merge($userList, $this->loadUsersOfUserGroup($userGroup));
            } catch (NotFoundException $groupNotFoundException) {
                try {
                    $userList[] = $this->userService->loadUser($id);
                } catch (NotFoundException $userNotFoundException) {
                    //TODO
                }
            }
        } elseif (is_string($id)) {
            if (false === strpos($user, '@')) {
                try {
                    $userList[] = $this->userService->loadUserByLogin($user);
                } catch (NotFoundException $userNotFoundException) {
                    //TODO
                }
            } else {
                try {
                    $userList[] = $this->userService->loadUserByEmail($user);
                } catch (NotFoundException $userNotFoundException) {
                    //TODO
                }
            }
        }

        return $userList;
    }

    /** @return User[] */
    public function loadUsersOfUserGroup(UserGroup $userGroup): array
    {
        $users = [];

        for ($offset = 0, $limit = 25; count($usersSlice = $this->userService->loadUsersOfUserGroup($userGroup, $offset, $limit)); $offset += $limit) {
            $users = array_merge($users, $usersSlice);
        }

        for ($offset = 0, $limit = 25; count($subUserGroupsSlice = $this->userService->loadSubUserGroups($userGroup, $offset, $limit)); $offset += $limit) {
            foreach ($subUserGroupsSlice as $subUserGroup) {
                $users = array_merge($users, $this->loadUsersOfUserGroup($subUserGroup));
            }
        }

        return $users;
    }
}
