<?php

namespace SeedCloud;

class Router
{
    /** @var \Twig_Environment */
    public $twigEnvironment;

    /**
     * Router constructor.
     * @param $twigEnvironment \Twig_Environment
     */
    public function __construct($twigEnvironment)
    {
        $this->twigEnvironment = $twigEnvironment;
    }

    private function getController()
    {

        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        if ($page[0] == '/') {
            $page = substr($page, 1);
        }

        $page = explode('?', $page)[0];
        $pageArray = explode('/', $page);

        switch ($pageArray[0]) {
            case 'getWork':
                return ['MiningQueue', 'getWork'];
            case 'claimWork':
                return ['MiningQueue', 'claimWork'];
            case 'killWork':
                return ['MiningQueue', 'killWork'];
            case 'check':
                return ['MiningQueue', 'check'];
            case 'checkTimeouts':
                return ['MiningQueue', 'processTimeouts'];
            case 'getPart1':
                return ['Task', 'getPart1'];
            case 'get_movable':
                return ['Task', 'getMovable'];
            case 'upload':
                return ['Task', 'upload'];
            case 'minerprofile':
                return ['MinerProfile', 'index'];
            case 'trusted':
                return ['Trusted', 'index'];
            default:
                return ['Home', 'index'];
                break;
        }
    }

    /**
     * @param $dbManager DatabaseManager
     */
    public function process()
    {
        $controllerAction = $this->getController();

        if ($controllerAction[1] != 'index') {
            session_start();
            if (isset($_REQUEST['minername'])) {
                $minerName = $_REQUEST['minername'];
                $minerName = preg_replace("/[^a-zA-Z0-9\_\-\|]/", '', $minerName);
                \SeedCloud\BadgeManager::Bootstrap($minerName);
                $_SESSION['minername'] = $minerName;
                \SeedCloud\BadgeManager::FireEvent(\SeedCloud\BadgeManager::EVENT_MINER_SEEN);
            } else if (isset($_SESSION['minername']) && strlen($_SESSION['minername']) > 0) {
                \SeedCloud\BadgeManager::Bootstrap($_SESSION['minername']);
                \SeedCloud\BadgeManager::FireEvent(\SeedCloud\BadgeManager::EVENT_MINER_SEEN);
            }
        }

        //@TODO: Controller and Action will need to be parsed from request data.
        $controllerClassName = "\\SeedCloud\\Controllers\\" . $controllerAction[0] . "Controller";
        /** @var BaseController $controllerInstance */
        $controllerInstance = new $controllerClassName($this);

        $controllerInstance->process($controllerAction[1]); //@TODO: Pass Request info somehow
    }
}
