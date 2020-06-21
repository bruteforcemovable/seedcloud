<?php
namespace SeedCloud;

use SeedCloud\DatabaseManager;

class BadgeManager
{
    const EVENT_MINING_SUCCESS = 100;
    const EVENT_MINING_FAILURE = 200;
    const EVENT_MINER_SEEN = 300;

    const BADGE_LEVEL_0 = 0;
    const BADGE_LEVEL_1 = 1;
    const BADGE_LEVEL_2 = 2;
    const BADGE_LEVEL_3 = 3;
    const BADGE_LEVEL_4 = 4;
    const BADGE_LEVEL_5 = 5;
    const BADGE_LEVEL_6 = 6;
    const BADGE_LEVEL_7 = 7;

    private static $eventListeners = [];

    private static $badges = [
        //Add Class Full Names here...
        'SeedCloud\\Badges\\TimeWasterBadge',
        'SeedCloud\\Badges\\ManualMiningPTSDBadge',
        'SeedCloud\\Badges\\ActiveMinerBadge',
        'SeedCloud\\Badges\\StrongArmedBadge',
        'SeedCloud\\Badges\\MiningStreakBadge',
    ];

    public static $badgeInstances = [];

    public static function FireEvent($eventType, ...$args)
    {
        //Trigger event listeners
        if (isset(self::$eventListeners[$eventType])) {
            foreach (self::$eventListeners[$eventType] as $currentCallback) {
                //While this is potentially dangerous to do, this is a fine usecase to use that function
                call_user_func_array($currentCallback, $args);
            }
        }
    }

    public static function RegisterEventListener($eventType, $callback)
    {
        if (!isset(self::$eventListeners[$eventType])) {
            self::$eventListeners[$eventType] = [];
        }
        self::$eventListeners[$eventType][] = $callback;
    }

    public static function Bootstrap($minerName)
    {
        $dbHandle = DatabaseManager::GetHandle();
        $sql = 'select * from minerbadges where minername = :minername';
        $statement = $dbHandle->prepare($sql);
        $statement->bindValue('minername', $minerName);
        $result = $statement->execute();
        $badgeStates = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $stateVarsMap = [];
        foreach ($badgeStates as $currentBadgeState) {
            $stateVarsMap[$currentBadgeState['badgeclass']] = json_decode($currentBadgeState['badgestate'], true);
        }

        foreach (self::$badges as $currentBadge) {
            if (isset($stateVarsMap[$currentBadge])) {
                $classInstance = new $currentBadge($minerName, $stateVarsMap[$currentBadge]);
                self::$badgeInstances[] = $classInstance;
            } else {
                $classInstance = new $currentBadge($minerName, []);
                $classInstance->saveBadgeState();
                self::$badgeInstances[] = $classInstance;
            }
        }
    }
}
