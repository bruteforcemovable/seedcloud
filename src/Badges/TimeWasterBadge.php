<?php

namespace SeedCloud\Badges;

use SeedCloud\BadgeManager;

class TimeWasterBadge extends BaseBadge {
	public $description = 'Awarded for getting a certain amount of unmineable jobs.';
	public $title = 'Time Waster';

	protected function registerEventListeners() {
		BadgeManager::RegisterEventListener(BadgeManager::EVENT_MINING_FAILURE, function () {
			$this->onMiningFailure();
		});
	}

	private function onMiningFailure() {
		$unmineableCount = $this->getBadgeStateVar('unmineableCount');
		$unmineableCount++;
		$this->setBadgeStateVar('unmineableCount', $unmineableCount);
		$this->saveBadgeState();
	}

	private $unmineableCountStages = [
		BadgeManager::BADGE_LEVEL_7 => 10000,
		BadgeManager::BADGE_LEVEL_6 => 5000,
		BadgeManager::BADGE_LEVEL_5 => 2500,
		BadgeManager::BADGE_LEVEL_4 => 1000,
		BadgeManager::BADGE_LEVEL_3 => 500,
		BadgeManager::BADGE_LEVEL_2 => 100,
		BadgeManager::BADGE_LEVEL_1 => 25,
		BadgeManager::BADGE_LEVEL_0 => 0
	];

	public function getBadgeLevel() {
		$unmineableCount = $this->getBadgeStateVar('unmineableCount');
		foreach($this->unmineableCountStages as $badgeLevel => $stageUnmineableCount) {
			if ($unmineableCount >= $stageUnmineableCount) return $badgeLevel;
		}
	}

	public function getBadgeProgress() {
		if (!isset($this->unmineableCountStages[$this->getBadgeLevel()+1])) {
			return [1,"completed"];
		}
		$unmineableCount = $this->getBadgeStateVar('unmineableCount');
		$unmineablesNeededForNextStage = $this->unmineableCountStages[$this->getBadgeLevel()+1];
		$unmineablesNeededForCurrentStage = $this->unmineableCountStages[$this->getBadgeLevel()];
		return [
			($unmineableCount - $unmineablesNeededForCurrentStage)/($unmineablesNeededForNextStage - $unmineablesNeededForCurrentStage),
			$unmineableCount . ' / ' . $unmineablesNeededForNextStage
		];
	}
}
