<?php

namespace SeedCloud;

abstract class BaseController
{
    /** @var Router */
    protected $router;
    protected $viewFolder = '.';

    /**
     * BaseController constructor.
     * @param $router Router
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    protected function getRealIP()
    {
        return $_SERVER["HTTP_X_REAL_IP"];
    }

    public function process($actionName)
    {
        $actionMethodName = $actionName . "Action";
        $viewData = $this->$actionMethodName();
        if (!is_array($viewData)) {
            $viewData = [];
        }
        echo $this->router->twigEnvironment->render($this->viewFolder . '/' . $actionName . '.html', $viewData);
    }

    public function Redirect($targetUrl)
    {
        header("Location: " . $targetUrl);
        die();
    }

    public function RedirectIfNotAdmin($targetUrl)
    {

    }
}
