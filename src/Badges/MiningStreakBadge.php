<?php

namespace SeedCloud\Badges;

use SeedCloud\BadgeManager;

class MiningStreakBadge extends BaseBadge {
	public $description = 'Awarded for a certain highscore of continuously mined hours.';
	public $title = 'Mining Streak';

	protected function registerEventListeners() {
		BadgeManager::RegisterEventListener(BadgeManager::EVENT_MINER_SEEN, function () {
			$this->onMinerSeen();
		});
	}

	private function onMinerSeen() {
		$now = microtime(true);
		$lastSeen = $this->getBadgeStateVar('lastSeen', $now);
		$longestStreakInSeconds = $this->getBadgeStateVar('longestStreakInSeconds', 0);
		$currentStreakInSeconds = $this->getBadgeStateVar('currentStreakInSeconds', 0);

		if (($lastSeen + 60) < $now) {
			//Last seen was more than 60 seconds ago
			$currentStreakInSeconds = 0;
		} else {
			$currentStreakInSeconds += $now - $lastSeen;
		}

		if ($currentStreakInSeconds > $longestStreakInSeconds) {
			$longestStreakInSeconds = $currentStreakInSeconds;
		}

		$this->setBadgeStateVar('lastSeen', $now);
		$this->setBadgeStateVar('longestStreakInSeconds', $longestStreakInSeconds);
		$this->setBadgeStateVar('currentStreakInSeconds', $currentStreakInSeconds);
		$this->saveBadgeState();
	}

	private $streakHoursStages = [
		BadgeManager::BADGE_LEVEL_7 => 336,
		BadgeManager::BADGE_LEVEL_6 => 168,
		BadgeManager::BADGE_LEVEL_5 => 72,
		BadgeManager::BADGE_LEVEL_4 => 48,
		BadgeManager::BADGE_LEVEL_3 => 24,
		BadgeManager::BADGE_LEVEL_2 => 12,
		BadgeManager::BADGE_LEVEL_1 => 6,
		BadgeManager::BADGE_LEVEL_0 => 0
	];

	public function getBadgeLevel() {
		$longestStreakInSeconds = $this->getBadgeStateVar('longestStreakInSeconds', 0);
		foreach($this->streakHoursStages as $badgeLevel => $stageHours) {
			if (($longestStreakInSeconds / 60 / 60) >= $stageHours) return $badgeLevel;
		}
	}

	public function getBadgeProgress() {
		if (!isset($this->streakHoursStages[$this->getBadgeLevel()+1])) {
			return [1,"completed"];
		}
		$longestStreakInSeconds = $this->getBadgeStateVar('longestStreakInSeconds');
		$hoursNeededForNextStage = $this->streakHoursStages[$this->getBadgeLevel()+1];
		$hoursNeededForCurrentStage = $this->streakHoursStages[$this->getBadgeLevel()];
		$hoursCount = (int)($longestStreakInSeconds / 60 / 60);
		$minutesCount = (int)(($longestStreakInSeconds - ($hoursCount * 60 * 60)) / 60); 
		return [
			($longestStreakInSeconds)/($hoursNeededForNextStage * 60 * 60),
			$hoursCount . 'h ' . $minutesCount . 'm / ' . $hoursNeededForNextStage . 'h 0m'
		];
	}
}
