<?php

namespace Math;

/**
 * Represents a mathematical operator as a token.
 *
 * Extends Token and includes additional properties such as priority and associativity.
 * Supports left, right, and non-associative operators.
 *
 * @author Adrean Boyadzhiev (netforce) <adrean.boyadzhiev@gmail.com>
 */
class Operator extends Token
{
    const O_LEFT_ASSOCIATIVE = -1;
    const O_NONE_ASSOCIATIVE = 0;
    const O_RIGHT_ASSOCIATIVE = 1;

    protected $priority;
    protected $associativity;

    /**
     * Creates a new operator token.
     *
     * @param string $value The string representation of the operator.
     * @param int $priority The priority level of the operator.
     * @param int $associativity The associativity of the operator (left, right, or none).
     *
     * @throws \InvalidArgumentException If an invalid associativity value is provided.
     */
    public function __construct($value, $priority, $associativity)
    {
        if(!in_array($associativity, array(self::O_LEFT_ASSOCIATIVE, self::O_NONE_ASSOCIATIVE, self::O_RIGHT_ASSOCIATIVE))) {
            throw new \InvalidArgumentException(sprintf('Invalid associativity: %s', $associativity));
        }

        $this->priority = (int) $priority;
        $this->associativity = (int) $associativity;
        parent::__construct($value, Token::T_OPERATOR);
    }
    
    /**
     * Gets the associativity of the operator.
     *
     * @return int Associativity constant (O_LEFT_ASSOCIATIVE, O_NONE_ASSOCIATIVE, or O_RIGHT_ASSOCIATIVE).
     */
    public function getAssociativity()
    {
        return $this->associativity;
    }

    /**
     * Gets the priority of the operator.
     *
     * @return int The priority value of the operator.
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Determines whether this operator has a lower priority than another operator.
     *
     * @param Operator $o The operator to compare against.
     *
     * @return bool True if this operator has lower priority, false otherwise.
     */
    public function hasLowerPriority(Operator $o)
    {
        $hasLowerPriority = ((Operator::O_LEFT_ASSOCIATIVE == $o->getAssociativity()
                            && $this->getPriority() == $o->getPriority())
                            || $this->getPriority() < $o->getPriority());


        return $hasLowerPriority;
    }
}
