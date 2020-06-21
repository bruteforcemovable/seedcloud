<?php

namespace SeedCloud\Controllers;

use SeedCloud\BadgeManager;
use SeedCloud\BaseController;

class MinerProfileController extends BaseController
{
    protected $viewFolder = 'minerprofile';

    public function indexAction()
    {
        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        if ($page[0] == '/') {
            $page = substr($page, 1);
        }

        $page = explode('?', $page)[0];
        $pageArray = explode('/', $page);

        if (count($pageArray) < 2) {echo "error";exit;}
        $minerName = urldecode($pageArray[1]);
        $minerName = preg_replace("/[^a-zA-Z0-9\_\-\|]/", '', $minerName);
        BadgeManager::Bootstrap($minerName);

        $badgeData = [];

        foreach (BadgeManager::$badgeInstances as $currentBadgeInstance) {
            $badgeProgress = $currentBadgeInstance->getBadgeProgress();
            $badgeData[] = [
                'title' => $currentBadgeInstance->title,
                'description' => $currentBadgeInstance->description,
                'currentLevel' => $currentBadgeInstance->getBadgeLevel(),
                'badgeProgressPercentage' => $badgeProgress[0],
                'badgeProgressText' => $badgeProgress[1],
            ];
        }

        return [
            'minerName' => $minerName,
            'badges' => $badgeData,
        ];
    }
}
