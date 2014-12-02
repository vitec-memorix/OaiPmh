<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 11:09
 */

namespace Picturae\OAI\Interfaces;


interface ResultList
{
    /**
     * @return array
     */
    public function getItems();

    /**
     * @return string
     */
    public function getResumptionToken();
}