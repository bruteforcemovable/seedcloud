<?php

namespace SeedCloud\Controllers;

use SeedCloud\BaseController;
use SeedCloud\ConfigManager;
use SeedCloud\DatabaseManager;
use SeedCloud\Validation\FriendCode;
use SeedCloud\Validation\ID0;

class HomwController extends BaseController
{
    protected $viewFolder = 'home';

    public function getPart1DumperState($id0, $friendcode)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, ConfigManager::GetConfiguration('web.part1dumper') . '/getStatus.php?friendcode=' . $friendcode . '&id0=' . $id0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function resetPart1DumperState($friendcode)
    {
        $part1MechaResponse = file_get_contents(ConfigManager::GetConfiguration('web.part1dumper') . '/resettimeout.php?fc=' . $friendcode);

        return "done";
    }

    public function indexAction()
    {
        if (isset($_REQUEST['taskId']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'do-bruteforce') {
            $dbCon = DatabaseManager::GetHandle();
            $statement = $dbCon->prepare('update seedqueue set state = 3 where state = 2 and taskId like :taskId');
            $statement->bindParam('taskId', $_REQUEST['taskId']);
            $statement->execute();
            echo "ok";
            exit;
        }
        if (isset($_REQUEST['taskId']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'reset-fc') {
            $taskId = $_REQUEST['taskId'];
            $dbCon = DatabaseManager::GetHandle();
            $statement = $dbCon->prepare('select * from seedqueue where taskId like :taskId');
            $statement->bindValue('taskId', $taskId);
            $result = $statement->execute();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($result !== false && count($results) === 1) {
                $task = $results[0];
                $this->resetPart1DumperState($task['friendcode']);
            }
            echo "ok";
            exit;
        }
        if (isset($_REQUEST['taskId']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'get-state') {
            $taskId = $_REQUEST['taskId'];
            $dbCon = DatabaseManager::GetHandle();
            $statement = $dbCon->prepare('select * from seedqueue where taskId like :taskId');
            $statement->bindValue('taskId', $taskId);
            $result = $statement->execute();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($result !== false && count($results) === 1) {
                $task = $results[0];
                $currentState = intval($task['state']);

                if ($currentState === 0) {
                    //Ask Randals site for state info
                    $part1DumperResponse = $this->getPart1DumperState($task['id0'], $task['friendcode']);
                    if ($part1DumperResponse && intval($part1DumperResponse['claimedBy']) !== 0 && $part1DumperResponse['claimedBy'] !== false) {
                        //Yay we got a claim!!!
                        $statement = $dbCon->prepare('update seedqueue set state = 1 where taskId like :taskId');
                        $statement->bindParam('taskId', $taskId);
                        $result = $statement->execute();
                        echo json_encode(array(
                            'currentState' => 1,
                            'claimedBy' => $part1DumperResponse['claimedBy'],
                            'timeout' => $part1DumperResponse['timeout'],
                            'lockout' => $part1DumperResponse['lockout'],
                            'stuff' => $part1DumperResponse,
                        ));
                    } else if ($part1DumperResponse && $part1DumperResponse['dumped'] == 'true') {
                        $fp1 = '';
                        for ($i = 0; $i < 6; $i++) {
                            $fp1 .= hex2bin(substr($part1DumperResponse['lfcs'], 14 - ($i * 2), 2));
                        }

                        $part1b64 = base64_encode($fp1);
                        if (strlen($part1b64) == 0 || substr($fp1, 0, 4) == "\00\00\00\00") {
                            $statement = $dbCon->prepare('update seedqueue set state = -1, part1b64 = :part1b64 where taskId like :taskId');
                            $statement->bindParam('taskId', $taskId);
                            $statement->bindValue('part1b64', base64_encode($fp1));
                            $statement->execute();
                            echo json_encode(array(
                                'currentState' => -1,
                            ));
                            exit;
                        }

                        $statement = $dbCon->prepare('update seedqueue set state = 3, part1b64 = :part1b64 where taskId like :taskId');
                        $statement->bindParam('taskId', $taskId);
                        $statement->bindValue('part1b64', base64_encode($fp1));
                        $statement->execute();
                        echo json_encode(array(
                            'currentState' => 3,
                        ));
                    } else {
                        echo json_encode(array(
                            'currentState' => $currentState + (($part1DumperResponse['timeout'] || $part1DumperResponse['lockout']) ? 1 : 0),
                            'timeout' => $part1DumperResponse['timeout'],
                            'lockout' => $part1DumperResponse['lockout'], 'resp' => $part1DumperResponse,
                        ));
                    }
                } else if ($currentState === 1) {
                    $part1DumperResponse = $this->getPart1DumperState($task['id0'], $task['friendcode']);
                    if ($part1DumperResponse['dumped']) {
                        //Yay we got a dump!!!

                        $fp1 = '';
                        for ($i = 0; $i < 6; $i++) {
                            $fp1 .= hex2bin(substr($part1DumperResponse['lfcs'], 14 - ($i * 2), 2));
                        }

                        if (substr($fp1, 0, 4) == "\00\00\00\00") {
                            $statement = $dbCon->prepare('update seedqueue set state = -1, part1b64 = :part1b64 where taskId like :taskId');
                            $statement->bindParam('taskId', $taskId);
                            $statement->bindValue('part1b64', base64_encode($fp1));
                            $statement->execute();
                            echo json_encode(array(
                                'currentState' => -1,
                            ));
                            exit;
                        }

                        $statement = $dbCon->prepare('update seedqueue set state = 3, part1b64 = :part1b64 where taskId like :taskId');
                        $statement->bindParam('taskId', $taskId);
                        $statement->bindValue('part1b64', base64_encode($fp1));
                        $statement->execute();
                        echo json_encode(array(
                            'currentState' => 3,
                        ));
                    } else {
                        echo json_encode(array('currentState' =>
                            ($part1DumperResponse['timeout'] || $part1DumperResponse['lockout'] || $part1DumperResponse['claimedBy']) ? 1 : 0,
                            'claimedBy' => $part1DumperResponse['claimedBy'],
                            'timeout' => $part1DumperResponse['timeout'],
                            'lockout' => $part1DumperResponse['lockout'],
                            'stuff' => $part1DumperResponse,
                        ));
                    }
                } else {
                    echo json_encode(array(
                        'currentState' => $currentState,
                        'timeout' => false,
                        'lockout' => false,
                    ));
                }
            } else {
                echo json_encode(array(
                    'currentState' => -1,
                ));
            }
            exit;
        }
        if (isset($_REQUEST['id0']) && isset($_REQUEST['friendcode'])) {
            $newTaskId = md5(microtime(true) . '');
            $id0 = $_REQUEST['id0'];
            $friendcode = $_REQUEST['friendcode'];
            if (strlen($id0) == 32 && strlen($friendcode) == 12) {
                if (!ID0::IsValid($id0)) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'The id0 you provided does not seem valid. (Its probably an id1).',
                    ));
                    die;
                }
                if (!FriendCode::IsValid($friendcode)) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Success.',
                    ));
                    die;
                }

                $dbCon = DatabaseManager::GetHandle();
                $statement = $dbCon->prepare('select * from seedqueue where id0 like :id0 and friendcode like :friendcode and state > -99 order by state desc');
                $statement->bindValue('id0', $_REQUEST['id0']);
                $statement->bindValue('friendcode', $_REQUEST['friendcode']);
                $result = $statement->execute();
                $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
                if ($result !== false && count($results) >= 1) {
                    $task = $results[0];

                    if ($task['state'] == -1) {
                        echo json_encode(array(
                            'success' => true,
                            'taskId' => $task['taskId'],
                        ));
                        exit;
                    }

                    if (strlen($task['keyY']) > 0) {
                        $statement = $dbCon->prepare('update seedqueue set state = 5 where taskId like :taskId');
                        $statement->bindParam('taskId', $task['taskId']);
                        $statement->execute();
                    } else if (strlen($task['part1b64']) > 0) {
                        $statement = $dbCon->prepare('update seedqueue set state = 3 where taskId like :taskId');
                        $statement->bindParam('taskId', $task['taskId']);
                        $statement->execute();
                    } else {
                        $statement = $dbCon->prepare('update seedqueue set state = 0 where taskId like :taskId');
                        $statement->bindParam('taskId', $task['taskId']);
                        $statement->execute();
                    }
                    echo json_encode(array(
                        'success' => true,
                        'taskId' => $task['taskId'],
                    ));
                    exit;
                }

                $statement = DatabaseManager::GetHandle()->prepare("insert into seedqueue (id0, part1b64, friendcode, taskId, time_started, `state`, ip_addr) VALUES (:id0, '', :friendcode, :taskId, now(), 0, :ipAddr)");
                $statement->bindValue('id0', $_REQUEST['id0']);
                $statement->bindValue('friendcode', $_REQUEST['friendcode']);
                $statement->bindValue('taskId', $newTaskId);
                $statement->bindValue('ipAddr', $this->getRealIP());
                $result = $statement->execute();
                if ($result) {
                    echo json_encode(array(
                        'success' => true,
                        'taskId' => $newTaskId,
                    ));
                } else {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'The server was unable to save your request. Please try again in an hour.',
                    ));
                }
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Your supplied information could not be verified. Please check your inputs and try again.',
                ));
            }
            die;
        }
        if (isset($_REQUEST['id0']) && isset($_REQUEST['part1b64'])) {
            $newTaskId = md5(microtime(true) . '');
            $id0 = $_REQUEST['id0'];
            $part1b64 = $_REQUEST['part1b64'];
            echo json_encode(array(
                'success' => false,
                'message' => 'Part1 Uploads are disabled at the moment.',
            ));
            die;

            if (strlen($id0) == 32 && strlen($part1b64) > 0) {
                if (!ID0::IsValid($id0)) {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'The id0 you provided does not seem valid. (Its probably an id1).',
                    ));
                    die;
                }
                $part1bRaw = base64_decode($part1b64, true);

                if (strlen($part1bRaw) < 5 || ($part1bRaw[4] !== "\00" && $part1bRaw[4] !== "\02") || $part1bRaw == false || substr($part1bRaw, 0, 4) == "\00\00\00\00") {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'The part1 you provided seems to be invalid.',
                    ));
                    die;
                }

                $dbCon = DatabaseManager::GetHandle();
                $statement = $dbCon->prepare('select * from seedqueue where id0 like :id0 and part1b64 like :part1b64 and state > -99 order by state desc');
                $statement->bindValue('id0', $_REQUEST['id0']);
                $statement->bindValue('part1b64', $_REQUEST['part1b64']);
                $result = $statement->execute();
                $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
                if ($result !== false && count($results) >= 1) {
                    $task = $results[0];

                    if (strlen($task['movable']) > 0) {
                        $statement = $dbCon->prepare('update seedqueue set state = 5 where taskId like :taskId');
                        $statement->bindParam('taskId', $task['taskId']);
                        $statement->execute();
                    } else if (strlen($task['part1b64']) > 0) {
                        $statement = $dbCon->prepare('update seedqueue set state = 3 where taskId like :taskId');
                        $statement->bindParam('taskId', $task['taskId']);
                        $statement->execute();
                    }

                    echo json_encode(array(
                        'success' => true,
                        'taskId' => $task['taskId'],
                    ));
                    exit;
                }

                $statement = DatabaseManager::GetHandle()->prepare("insert into seedqueue (id0, part1b64, friendcode, taskId, time_started, `state`, ip_addr) VALUES (:id0, :part1b64, '', :taskId, now(), 3, :ipAddr)");
                $statement->bindValue('id0', $_REQUEST['id0']);
                $statement->bindValue('part1b64', $_REQUEST['part1b64']);
                $statement->bindValue('taskId', $newTaskId);
                $statement->bindValue('ipAddr', $this->getRealIP());
                $result = $statement->execute();
                if ($result) {
                    echo json_encode(array(
                        'success' => true,
                        'taskId' => $newTaskId,
                    ));
                } else {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'The server was unable to save your request. Please try again in an hour.',
                    ));
                }
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Your supplied information could not be verified. Please check your inputs and try again.',
                ));
            }
            die;
        }
        $dbConn1 = DatabaseManager::GetHandle();

        $statement = $dbConn1->prepare('select count(1) as number from seedqueue where state = 3');
        $result = $statement->execute();
        $waitingForBruteforceCount = $statement->fetchAll(\PDO::FETCH_ASSOC)[0]["number"];

        $statement = $dbConn1->prepare('select count(1) as number from seedqueue where state = 4');
        $result = $statement->execute();
        $bruteforcingCount = $statement->fetchAll(\PDO::FETCH_ASSOC)[0]["number"];

        $statement = $dbConn1->prepare('select (select count(1) from seedqueue) - count(1) as number from seedqueue where state < 5 or state > 5');
        $result = $statement->execute();
        $gotMovableCount = $statement->fetchAll(\PDO::FETCH_ASSOC)[0]["number"];

        $statement = $dbConn1->prepare('select count(1) as number from minerstatus where `action` = 0 and TIMESTAMPDIFF(SECOND, last_action_at, now()) < 60 ');
        $result = $statement->execute();
        $minerCount = $statement->fetchAll(\PDO::FETCH_ASSOC)[0]["number"];
        $statement = $dbConn1->prepare('select username, SUM(score) as score from minerscore group by username order by SUM(score) desc limit 25');
        $result = $statement->execute();
        $minerScores = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement = $dbConn1->prepare('select username, score from minerscore where month = CONCAT(YEAR(NOW()),MONTH(NOW())) order by score desc limit 25');
        $result = $statement->execute();
        $minerScoresThisMonth = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement = $dbConn1->prepare('select username, score from minerscore where month = CONCAT(YEAR(DATE_SUB(now(), INTERVAL 1 MONTH)),MONTH(DATE_SUB(now(), INTERVAL 1 MONTH))) order by score desc limit 25');
        $result = $statement->execute();
        $minerScoresLastMonth = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return array(
            'userCount' => $waitingForBruteforceCount,
            'miningCount' => $bruteforcingCount,
            'msCount' => $gotMovableCount,
            'minersStandby' => $minerCount,
            'minerScores' => $minerScores,
            'minerScoresCurrentMonth' => $minerScoresThisMonth,
            'minerScoresLastMonth' => $minerScoresLastMonth,
        );

    }
}
