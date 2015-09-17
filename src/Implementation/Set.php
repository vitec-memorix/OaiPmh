<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 12/1/14
 * Time: 4:11 PM
 */

namespace Picturae\OaiPmh\Implementation;

use Picturae\OaiPmh\Interfaces\Set as SetInterface;

/**
 * Class Set
 * Basic implementation of Picturae\OaiPmh\Interfaces\Set
 *
 * @package Picturae\OaiPmh
 */
class Set implements SetInterface
{
    /**
     * @var string
     */
    private $spec;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DOMDocument|null
     */
    private $description;

    /**
     * @param string $spec
     * @param string $name
     * @param \DOMDocument|null $description
     */
    public function __construct($spec, $name, \DOMDocument $description = null)
    {
        $this->description = $description;
        $this->name = $name;
        $this->spec = $spec;
    }


    /**
     * @return string
     * a colon [:] separated list indicating the path from the root of the set hierarchy to the
     * respective node. Each element in the list is a string consisting of any valid URI unreserved characters, which
     * must not contain any colons [:]. Since a setSpec forms a unique identifier for the set within the repository, it
     * must be unique for each set. Flat set organizations have only sets with setSpec that do not contain any colons.
     */
    public function getSpec()
    {
        return $this->spec;
    }

    /**
     * @return string a short human-readable string naming the set.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DOMDocument|null
     * an optional and repeatable container that may hold community-specific XML-encoded data about
     * the set; the accompanying Implementation Guidelines document provides suggestions regarding the usage of this
     * container.
     */
    public function getDescription()
    {
        return $this->description;
    }
}
