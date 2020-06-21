<?php

namespace SeedCloud\Controllers;

use SeedCloud\BaseController;
use SeedCloud\ConfigManager;
use SeedCloud\DatabaseManager;

class TrustedController extends BaseController
{
    protected $viewFolder = 'trusted';

    public function indexAction()
    {
        $secret = ConfigManager::GetConfiguration('web.trustedsecret');
        if (!isset($_REQUEST[$secret])) {
            header("Location: https://www.youtube.com/watch?v=dQw4w9WgXcQ");
            exit();
        }

        if (isset($_REQUEST['searchterm'])) {
            $dbHandle = DatabaseManager::GetHandle();
            $sql = 'select * from seedqueue where id0 like :searchterm or friendcode like :searchterm';
            $statement = $dbHandle->prepare($sql);
            $statement->bindValue('searchterm', $_REQUEST['searchterm']);
            $statement->execute();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

            return ['searchterm' => $_REQUEST['searchterm'], 'results' => $results, 'urlsecret' => $secret];
        }

        return ['urlsecret' => $secret];
    }
}
