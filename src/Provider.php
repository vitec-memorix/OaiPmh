<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 15:42
 */

namespace Picturae\OAI;


use Picturae\OAI\Interfaces\Repository;

class Provider
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var array
     */
    private $request = [];

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request)
    {
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function execute()
    {

    }
}