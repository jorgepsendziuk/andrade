<?php

namespace Math;

/**
 * Represents a single token in a mathematical expression.
 *
 * Tokens can be operands, operators, or brackets, and they store a value and type.
 *
 * @author Adrean Boyadzhiev (netforce) <adrean.boyadzhiev@gmail.com>
 */
class Token
{

    const T_OPERATOR = 1;
    const T_OPERAND = 2;
    const T_LEFT_BRACKET = 3;
    const T_RIGHT_BRACKET = 4;

    /**
     * String representation of this token
     *
     * @var string
     */
    protected $value;
    
    /**
     * Token type one of Token::T_* constants
     *
     * @var integer
     */
    protected $type;

    /**
     * Creates a new token instance.
     *
     * @param string|int $value The value of the token.
     * @param int $type The type of token (T_OPERATOR, T_OPERAND, T_LEFT_BRACKET, or T_RIGHT_BRACKET).
     *
     * @throws \InvalidArgumentException If an invalid token type is provided.
     */
    public function __construct($value, $type)
    {
        $tokeTypes = array(
            self::T_OPERATOR,
            self::T_OPERAND,
            self::T_LEFT_BRACKET,
            self::T_RIGHT_BRACKET
        );
        if (!in_array($type, $tokeTypes, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid token type: %s', $type));
        }

        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Gets the value of the token.
     *
     * @return string|int The value of the token.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the type of the token.
     *
     * @return int The type of the token (one of the T_* constants).
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the string representation of the token.
     *
     * @return string The token value as a string.
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }

}
