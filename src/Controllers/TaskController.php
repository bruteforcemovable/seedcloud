<?php

namespace SeedCloud\Controllers;

use SeedCloud\BaseController;
use SeedCloud\DatabaseManager;
use SeedCloud\Miner;

class BotApiController extends BaseController
{
    public function getMovableAction()
    {
        if (isset($_REQUEST['task']) && strlen($_REQUEST['task']) == 32) {
            $dbCon = DatabaseManager::GetHandle();
            $statement = $dbCon->prepare('select * from seedqueue where taskId like :taskId and state = 5');
            $statement->bindValue('taskId', $_REQUEST['task']);
            $result = $statement->execute();
            if ($result !== false) {
                $keyY = hex2bin($statement->fetchAll(\PDO::FETCH_ASSOC)[0]['keyY']);
            } else {
                echo "There was a problem with your movable, please try again.";
                exit;
            }
            $str = 'SEED' . str_repeat("\x00", 288 - 4 - 16) . $keyY;
            header('Content-Disposition: attachment; filename="movable.sed"');
            header('Content-Type: text/plain'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
            header('Content-Length: 288');
            echo $str;
            exit;
        }

        echo "Please specify a task identifier using the query search parameter 'task'.";
        exit;
    }

    public function getPart1Action()
    {
        if (isset($_REQUEST['task']) && strlen($_REQUEST['task']) == 32) {
            $str = "data";
            $dbCon = DatabaseManager::GetHandle();
            $statement = $dbCon->prepare('select * from seedqueue where taskId like :taskId');
            $statement->bindValue('taskId', $_REQUEST['task']);
            $result = $statement->execute();
            if ($result !== false) {
                $retData = $statement->fetchAll(\PDO::FETCH_ASSOC)[0];
                $str = str_pad(base64_decode($retData['part1b64']), 4096, "\0");
                for ($i = 0; $i < 32; $i++) {
                    $str[0x10 + $i] = $retData['id0'][$i];
                }
            } else {
                echo "There was a problem with your movable, please try again.";
                exit;
            }

            header('Content-Disposition: attachment; filename="movable_part1.sed"');
            header('Content-Type: text/plain'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
            header('Content-Length: ' . strlen($str));
            header('Connection: close');
            echo $str;
            exit;
        }
        echo "error";
        exit;
    }

    public function uploadAction()
    {
        if (isset($_REQUEST['task']) && $_FILES['movable']) {
            $taskId = $_REQUEST['task'];
            $dbCon = DatabaseManager::GetHandle();

            $fileContent = file_get_contents($_FILES['movable']['tmp_name']);

            $movableLength = strlen($fileContent);
            if ($movableLength !== 288 && $movableLength !== 320) {
                $statement = $dbCon->prepare('update seedqueue set state = 3 where taskId like :taskId');
                $statement->bindParam('taskId', $taskId);
                $statement->execute();
                echo "error (Movable Size: " . $movableLength . ")";exit;
            }
            $statement = $dbCon->prepare('select * from seedqueue where taskId like :taskId');

            $statement->bindValue('taskId', $taskId);
            $result = $statement->execute();
            $retData = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($result !== false && count($retData) > 0) {
                Miner::Current()->setStatus(0);

                $keyY = substr($fileContent, 0x110, 16);
                $sha = hex2bin(hash("sha256", $keyY));
                $checkId0 = bin2hex(strrev(substr($sha, 0, 4)) .
                    strrev(substr($sha, 4, 4)) .
                    strrev(substr($sha, 8, 4)) .
                    strrev(substr($sha, 12, 4)));
                if ($checkId0 != $retData[0]['id0']) {
                    $statement = $dbCon->prepare('update seedqueue set state = 3 where taskId like :taskId');
                    $statement->bindParam('taskId', $taskId);
                    $statement->execute();
                    echo "error (Movable invalid)";exit;
                }

                if ($retData[0]['state'] != 4 && $retData[0]['state'] != 3) {
                    echo "success";exit;
                }

                $statement = $dbCon->prepare('update seedqueue set state = 5, keyY = :keyY where taskId like :taskId');
                $statement->bindParam('taskId', $taskId);
                $statement->bindParam('keyY', bin2hex($keyY));
                $statement->execute();

                if (isset($_REQUEST['minername']) && strlen($_REQUEST['minername']) > 1) {
                    Miner::Current()->grantLeaderboardScore($_REQUEST['minername']);
                }
                \SeedCloud\BadgeManager::FireEvent(\SeedCloud\BadgeManager::EVENT_MINING_SUCCESS);
                echo "success";
                exit;
            }
        }
        echo "error";
        exit;
    }
}
