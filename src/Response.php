<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 15:42
 */

namespace Picturae\OAI;


class Response
{

    /**
     * @var string
     */
    private $output;

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return \string[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param \string[] $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param $header string
     * @return $this
     */
    public function addHeader($header)
    {
        $this->headers [] = $header;
        return $this;
    }

    /**
     * @return $this
     */
    public function printHeaders()
    {
        foreach ($this->headers as $header) {
            header($header);
        }
        return $this;
    }

    /**
     *
     */
    public function outputAndExit()
    {
        $this->printHeaders();
        echo $this->output;
    }
}