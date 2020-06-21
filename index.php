<?php

require_once './../vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('./views');
$twig = new Twig_Environment($loader, array(
//	'cache' => './views_cache'
));

//\SeedCloud\BadgeManager::Bootstrap('DeadPhoenix123');
//\SeedCloud\BadgeManager::FireEvent(\SeedCloud\BadgeManager::EVENT_MINING_FAILURE);


$router = new \SeedCloud\Router($twig);

$router->process();
