<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 11:09
 */

namespace Picturae\OaiPmh\Interfaces;

interface SetList extends ResultList
{

    /**
     * @return Set[]
     */
    public function getItems();
}
