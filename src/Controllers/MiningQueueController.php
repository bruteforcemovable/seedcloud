<?php

namespace SeedCloud\Controllers;

use SeedCloud\BaseController;
use SeedCloud\DatabaseManager;
use SeedCloud\Miner;

class MiningQueueController extends BaseController
{
    public function getWorkAction()
    {
        $dbCon = DatabaseManager::GetHandle();

        if (!isset($_REQUEST['ver'])) {
            Miner::Current()->setStatus(3);
        }

        if (Miner::Current()->getStatus() > 0) {
            echo "nothing";exit;
        }

        Miner::Current()->setStatus(0);

        $statement = $dbCon->prepare('select * from (select seedqueue.*, minerstatus.ip_addr as miner_ip from minerstatus ' .
            'join (select * from seedqueue where state = 3 and part1b64 <> ' . "''" . ' order by id_seedqueue asc limit 1) as seedqueue on (1=1) ' .
            'where TIMESTAMPDIFF(SECOND, last_action_at, now()) < 60 and minerstatus.last_action_change is not null and minerstatus.action = 0 ' .
            'order by last_action_change asc limit 1) as tmp where miner_ip like :ip_addr');
        $statement->bindValue('ip_addr', $this->getRealIP());
        $result = $statement->execute();
        if ($result !== false) {
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (count($results) > 0) {
                echo $results[0]['taskId'];exit;
            }
        } else {
            echo "nothing";exit;
        }

        echo "nothing";exit;
    }

    public function claimWorkAction()
    {
        if (isset($_REQUEST['task'])) {
            $taskId = $_REQUEST['task'];
            $dbCon = DatabaseManager::GetHandle();

            $statement = $dbCon->prepare('update seedqueue set state = 4, time_started = now() where taskId like :taskId and state = 3');
            $statement->bindParam('taskId', $taskId);
            $statement->execute();
            if ($statement->rowCount() == 1) {
                Miner::Current()->setStatus(1);
                echo "okay";
                exit;
            }
        }
        echo "error";
        exit;
    }

    public function checkAction()
    {
        if (isset($_REQUEST['task'])) {
            $taskId = $_REQUEST['task'];
            $dbCon = DatabaseManager::GetHandle();

            $statement = $dbCon->prepare('select * from seedqueue where state = 4 and taskId like :taskId');
            $statement->bindValue('taskId', $taskId);
            $result = $statement->execute();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (count($results) >= 1) {
                echo "ok";exit;
            } else {
                Miner::Current()->setStatus(0);
                echo "error";exit;
            }
        }
        echo "error";exit;
    }

    public function processTimeoutsAction()
    {
        $dbCon = DatabaseManager::GetHandle();

        $statement = $dbCon->prepare('update seedqueue set state = -1 where TIMESTAMPDIFF(MINUTE, time_started, now()) >= 60 and state = 4');
        $result = $statement->execute();
        if ($result !== false) {
            echo "okay";
        }
        $statement = $dbCon->prepare('update seedqueue set state = 5 where keyY is not null and keyY != \'\'');
        $result = $statement->execute();
        if ($result !== false) {
            echo "okay";
        }
        exit;
    }

    public function killWorkAction()
    {
        if (isset($_REQUEST['task'])) {
            $taskId = $_REQUEST['task'];
            $dbCon = DatabaseManager::GetHandle();

            $statement = $dbCon->prepare('select * from seedqueue where taskId like :taskId');
            $statement->bindValue('taskId', $taskId);
            $result = $statement->execute();
            if ($result !== false && intval($statement->fetchAll(\PDO::FETCH_ASSOC)[0]['state']) === 4) {
                $wantedState = $_REQUEST['kill'] == 'n' ? 3 : -1;
                $statement = $dbCon->prepare('update seedqueue set state = :state where taskId like :taskId');
                $statement->bindParam('taskId', $taskId);
                $statement->bindParam('state', $wantedState);
                $statement->execute();
                Miner::Current()->setStatus(-1);
                if ($wantedState == -1) {
                    \SeedCloud\BadgeManager::FireEvent(\SeedCloud\BadgeManager::EVENT_MINING_FAILURE);
                }
                echo "okay";
                exit;
            }
        }
        echo "error";
        exit;
    }
}
