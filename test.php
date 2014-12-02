<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 9:40 AM
 */
include "vendor/autoload.php";

$mockRepository = new \Picturae\OAI\Mock\Repository();
$provider = new \Picturae\OAI\Provider($mockRepository);
$provider->setRequest(['verb' => 'Identify']);
$output = $provider->execute();
var_dump($output);