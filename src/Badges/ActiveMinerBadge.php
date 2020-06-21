<?php

namespace SeedCloud\Badges;

use SeedCloud\BadgeManager;

class ActiveMinerBadge extends BaseBadge {
	public $description = 'Awarded for mining atleast 50 movables each month. (Resets when 50 movables not reached)';
	public $title = 'Active Miner';

	protected function registerEventListeners() {
	}

	public function getBadgeLevel() {
		return BadgeManager::BADGE_LEVEL_0;
	}

	public function getBadgeProgress() {
		return [0,"0 Months / 1 Months"];
	}
}
