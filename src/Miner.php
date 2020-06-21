<?php

namespace SeedCloud;

class Miner
{
    protected function getRealIP()
    {
        return $_SERVER["HTTP_X_REAL_IP"];
    }

    public static function Current()
    {
        return new self();
    }

    public function getStatus()
    {
        $dbCon = DatabaseManager::GetHandle();
        $statement = $dbCon->prepare('select * from minerstatus where ip_addr like :ipaddr and TIMESTAMPDIFF(MINUTE, last_action_at, now()) < 60');
        $statement->bindValue('ipaddr', $this->getRealIP());
        $result = $statement->execute();
        if ($result !== false) {
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (!isset($results[0])) {
                return 0;
            }

            $action = $results[0]['action'];
            return intval($action);
        } else {
            return 0;
        }
    }

    public function setStatus($action)
    {
        $lastStatus = false;
        $dbCon = DatabaseManager::GetHandle();
        $statement = $dbCon->prepare('select * from minerstatus where ip_addr = :ipaddr and TIMESTAMPDIFF(SECOND, last_action_at, now()) <= 35');
        $statement->bindValue('ipaddr', $this->getRealIP());
        $result = $statement->execute();
        if ($result !== false) {
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                $lastStatus = $results[0];
            }
        }

        $sql = 'INSERT INTO minerstatus (ip_addr, last_action_at, last_action_change, action) VALUES (:ipaddr, now(), :last_action_change, :action) ON DUPLICATE KEY UPDATE last_action_at = now(), action = :action, last_action_change = :last_action_change';
        $statement = DatabaseManager::GetHandle()->prepare($sql);
        $statement->bindValue('ipaddr', $this->getRealIP());
        $statement->bindValue('action', $action);

        $actionChangeDate = date('Y-m-d H:i:s');
        if ($lastStatus && $lastStatus['action'] == $action) {
            $actionChangeDate = $lastStatus['last_action_change'];
        }
        $statement->bindValue('last_action_change', $actionChangeDate);
        $statement->execute();
    }

    public function grantLeaderboardScore($username)
    {
        $username = preg_replace("/[^a-zA-Z0-9\_\-\|]/", '', $username);
        $sql = 'INSERT INTO minerscore (username, score, month) VALUES (:username, 5, CONCAT(YEAR(NOW()), MONTH(NOW()))) ON DUPLICATE KEY UPDATE score = score + 5';
        $statement = DatabaseManager::GetHandle()->prepare($sql);
        $statement->bindValue('username', $username);
        $statement->execute();
    }
}
