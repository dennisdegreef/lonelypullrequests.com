<?php

namespace LonelyPullRequests\Infrastructure\Persistence;

use Assert\Assertion as Ensure;
use DateTime;
use Github\Client;
use LonelyPullRequests\Domain\Notification;
use LonelyPullRequests\Domain\Notifications;
use LonelyPullRequests\Domain\Repository\NotificationRepository;

final class GithubNotificationRepository implements NotificationRepository
{
    /**
     * @var \Github\Api\Notification
     */
    private $notificationService;

    /**
     * @param \Github\Client $client
     * @param string         $apiKey
     *
     */
    public function __construct(Client $client, $apiKey)
    {
        $client->authenticate($apiKey, null, Client::AUTH_HTTP_TOKEN);

        $this->notificationService = $client->notifications();
    }

    /**
     * @return Notifications
     */
    public function all()
    {
        $notifications = array();

        foreach($this->notificationService->all() as $notificationStruct) {
            $notification = $this->createNotificationFromStruct($notificationStruct);
            if($notification instanceof Notification) {
                $notifications[] = $notification;
            }
        }

        return new Notifications($notifications);
    }

    /**
     * @param \DateTimeInterface $since
     */
    public function markRead(\DateTimeInterface $since)
    {
        // NotificationService is PHP 5.3 compatible, so DateTime is needed
        if(!($since instanceof DateTime)) {
            $since = new DateTime($since->format(DateTime::ISO8601));
        }
        $this->notificationService->markRead($since);
    }

    /**
     * @param array $notificationStruct
     *
     * @return Notification|null
     */
    private function createNotificationFromStruct($notificationStruct)
    {
        Ensure::keyExists($notificationStruct, 'updated_at');

        Ensure::keyExists($notificationStruct, 'repository');
        Ensure::keyExists($notificationStruct['repository'], 'full_name');

        Ensure::keyExists($notificationStruct, 'subject');
        Ensure::keyExists($notificationStruct['subject'], 'title');
        Ensure::keyExists($notificationStruct['subject'], 'url');
        Ensure::keyExists($notificationStruct['subject'], 'type');

        if($notificationStruct['subject']['type'] !== 'PullRequest') {
            return null;
        }

        // Translate Github API url to public website url
        $url = str_replace('https://api.github.com/repos/', 'https://github.com/', $notificationStruct['subject']['url']);
        $url = str_replace('/pulls/', '/pull/', $url);

        return Notification::fromArray([
            'repositoryName' => $notificationStruct['repository']['full_name'],
            'title' => $notificationStruct['subject']['title'],
            'url' => $url,
            'eventDateTime' => $notificationStruct['updated_at'],
        ]);
    }
}
