<?php

namespace Math\TranslationStrategy;

/**
 * Defines the contract for a translation strategy that converts infix mathematical expressions 
 * into another notation such as Reverse Polish Notation (RPN).
 *
 * @author Adrean Boyadzhiev (netforce) <adrean.boyadzhiev@gmail.com>
 */
interface TranslationStrategyInterface
{
    public function translate(array $tokens);
}
