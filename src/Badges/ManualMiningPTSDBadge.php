<?php

namespace SeedCloud\Badges;

use SeedCloud\BadgeManager;

class ManualMiningPTSDBadge extends BaseBadge {
	public $description = 'Awarded for beeing active in the manual mining days.';
	public $title = 'Manual Mining PTSD';

	protected function registerEventListeners() {
	}

	public function getBadgeLevel() {
		$hasMinedManually = $this->getBadgeStateVar('awarded', false);
		return $hasMinedManually ? BadgeManager::BADGE_LEVEL_1 : BadgeManager::BADGE_LEVEL_0;
	}

	public function getBadgeProgress() {
		if ($this->getBadgeLevel() == BadgeManager::BADGE_LEVEL_1) {
			return [1,"completed"];
		}
		return [0,"not awarded"];
	}
}
