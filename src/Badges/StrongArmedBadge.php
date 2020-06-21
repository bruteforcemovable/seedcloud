<?php

namespace SeedCloud\Badges;

use SeedCloud\BadgeManager;

class StrongArmedBadge extends BaseBadge {
	public $description = 'Awarded for mining a certain amount of movables.';
	public $title = 'Strong Armed';

	protected function registerEventListeners() {
		BadgeManager::RegisterEventListener(BadgeManager::EVENT_MINING_SUCCESS, function () {
			$this->onMiningSuccess();
		});
	}

	private function onMiningSuccess() {
		$movableCount = $this->getBadgeStateVar('movableCount');
		$movableCount++;
		$this->setBadgeStateVar('movableCount', $movableCount);
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
		$unmineableCount = $this->getBadgeStateVar('movableCount');
		foreach($this->unmineableCountStages as $badgeLevel => $stageUnmineableCount) {
			if ($unmineableCount >= $stageUnmineableCount) return $badgeLevel;
		}
	}

	public function getBadgeProgress() {
		if (!isset($this->unmineableCountStages[$this->getBadgeLevel()+1])) {
			return [1,"completed"];
		}
		$unmineableCount = $this->getBadgeStateVar('movableCount');
		$unmineablesNeededForNextStage = $this->unmineableCountStages[$this->getBadgeLevel()+1];
		$unmineablesNeededForCurrentStage = $this->unmineableCountStages[$this->getBadgeLevel()];
		return [
			($unmineableCount - $unmineablesNeededForCurrentStage)/($unmineablesNeededForNextStage - $unmineablesNeededForCurrentStage),
			$unmineableCount . ' / ' . $unmineablesNeededForNextStage
		];
	}
}
