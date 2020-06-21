<?php

namespace SeedCloud\Badges;

use SeedCloud\DatabaseManager;

class BaseBadge {
	//This class is not needed yet but it will be...
	private $badgeState = [];
	private $minerName = '';

	function __construct($minerName, $badgeState) {
		$this->badgeState = $badgeState;
		$this->minerName = $minerName;
		$this->registerEventListeners();
	}

	protected function registerEventListeners() { return; }

	protected function getBadgeStateVar($key, $defaultValue = 0) {
		if (isset($this->badgeState[$key])) {
			return $this->badgeState[$key];
		}
		return $defaultValue;
	}

	protected function setBadgeStateVar($key, $value) {
		$this->badgeState[$key] = $value;
	}

	public function saveBadgeState() {
		//@TODO: save state and level to the database
		$currentBadgeClassName = get_class($this);
		$dbHandle = DatabaseManager::GetHandle();

                $sql = 'INSERT INTO minerbadges (minername, badgeclass, badgestate) VALUES (:minername, :badgeclass, :badgestate) ON DUPLICATE KEY UPDATE badgestate = :badgestate';
                $statement = $dbHandle->prepare($sql);
                $statement->bindValue('minername', $this->minerName);
                $statement->bindValue('badgeclass', $currentBadgeClassName);
                $statement->bindValue('badgestate', json_encode($this->badgeState));
                $result = $statement->execute();
	}
}
