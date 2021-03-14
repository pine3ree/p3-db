<?php

/**
 * @package p3-db
 * @author  pine3ree https://github.com/pine3ree
 */

namespace P3\Db\Sql;

use P3\Db\Exception\RuntimeException;
use P3\Db\Sql\DriverInterface;
use P3\Db\Sql\Params;

/**
 * Represents a generic SQL element.
 *
 * A sql element provides the sql string it represents with the help of a sql driver.
 *
 * Sql elements also support parameters for usage used in full sql-statements and
 * may belong to other elements.
 *
 * A parameter collector is used to build and set ov values and types index by a
 * named (:variablename) parameter marker or by 1-indexed position of the generic
 * positional marker '?' The corresponding marker are used in the compiled sql string.
 *
 * The parameter collector is usally provided by the parent element if the element si
 * not a top level element.
 *
 * For top-level elements (full sql statements) an internal parameter collector
 * will be created if none is provided when triggering the xompilation of the sql string
 */
interface ElementInterface
{
    /**
     * Build and return the parametrized SQL-string
     *
     * This method must call each inner element getSQL() method passing down the
     * sql-driver and the parameter collector being used its
     *
     * @param DriverInterface $driver
     * @param Params $params An optional parameter collector. If not set a new instance will created.
     */
    public function getSQL(DriverInterface $driver = null, Params $params = null): string;

    /**
     * Check if there is any parameter, after the SQL string has been compiled
     *
     * This method returns null if getSQL() has not been called or if this is not
     * a top level element or if a parameter collector instance was provided in
     * the getSQL() call
     */
    public function hasParams(): ?bool;

    /**
     * Return the parameters collector created for this element after compiling
     * the sql string
     *
     * This method returns null if getSQL() has not been called or if this is not
     * a top level element or if a parameter collector instance was provided in
     * the getSQL() call
     *
     * @return Params|null
     */
    public function getParams(): ?Params;

    /**
     * Check if element has a parent
     *
     * @return bool
     */
    public function hasParent(): bool;

    /**
     * Return the parent element, if any
     *
     * @return ElementInterface|null
     */
    public function getParent(): ?ElementInterface;

    /**
     * Set the parent element
     * Raises exception if parent is already set.
     *
     * @return void
     * @throws RuntimeException
     */
    public function setParent(ElementInterface $parent): void;
}
