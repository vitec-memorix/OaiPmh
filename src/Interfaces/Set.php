<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28-11-14
 * Time: 12:56
 */

namespace Picturae\OAI\Interfaces;


interface Set
{

    /**
     * @return string
     * a colon [:] separated list indicating the path from the root of the set hierarchy to the
     * respective node. Each element in the list is a string consisting of any valid URI unreserved characters, which
     * must not contain any colons [:]. Since a setSpec forms a unique identifier for the set within the repository, it
     * must be unique for each set. Flat set organizations have only sets with setSpec that do not contain any colons.
     */
    public function getSpec();

    /**
     * @return string a short human-readable string naming the set.
     */
    public function getName();

    /**
     * @return \DOMDocument|null
     * an optional and repeatable container that may hold community-specific XML-encoded data about
     * the set; the accompanying Implementation Guidelines document provides suggestions regarding the usage of this
     * container.
     */
    public function getDescription();
}