<?php

namespace Math\TranslationStrategy;

use InvalidArgumentException;
use Math\Token;
use SplQueue;
use SplStack;

/**
 * Implements the Shunting-Yard algorithm to convert infix mathematical expressions
 * into Reverse Polish Notation (RPN).
 *
 * @see http://en.wikipedia.org/wiki/Shunting-yard_algorithm
 * @author Adrean Boyadzhiev (netforce) <adrean.boyadzhiev@gmail.com>
 */
class ShuntingYard implements TranslationStrategyInterface
{
    /**
     * Operator stack
     *
     * @var \SplStack 
     */
    private $operatorStack;
    
    /**
     * Output queue
     *
     * @var \SplQueue 
     */
    private $outputQueue;

    /**
     * Converts an array of tokens from infix notation to Reverse Polish Notation (RPN).
     *
     * @param Token[] $tokens Array of Token instances representing the mathematical expression in infix notation.
     *
     * @return Token[] Array of Token instances in RPN order.
     * @throws InvalidArgumentException If mismatched parentheses or invalid tokens are detected.
     */
    public function translate(array $tokens)
    {
        $this->operatorStack = new SplStack();
        $this->outputQueue = new SplQueue();
        foreach($tokens as $token) {
            switch ($token->getType()) {
                case Token::T_OPERAND:
                    $this->outputQueue->enqueue($token);
                    break;
                case Token::T_OPERATOR:
                    $o1 = $token;
                    while($this->hasOperatorInStack()
                            && ($o2 = $this->operatorStack->top())
                            && $o1->hasLowerPriority($o2))
                    {
                        $this->outputQueue->enqueue($this->operatorStack->pop());
                    }

                    $this->operatorStack->push($o1);
                    break;
                case Token::T_LEFT_BRACKET:
                    $this->operatorStack->push($token);
                    break;
                case Token::T_RIGHT_BRACKET:
                    while((!$this->operatorStack->isEmpty()) && (Token::T_LEFT_BRACKET != $this->operatorStack->top()->getType())) {
                        $this->outputQueue->enqueue($this->operatorStack->pop());
                    }
                    if($this->operatorStack->isEmpty()) {
                        throw new InvalidArgumentException(sprintf('Mismatched parentheses: %s', implode(' ',$tokens)));
                    }
                    $this->operatorStack->pop();
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Invalid token detected: %s', $token));
                    break;
            }
        }
        while($this->hasOperatorInStack()) {
            $this->outputQueue->enqueue($this->operatorStack->pop());
        }

        if(!$this->operatorStack->isEmpty()) {
            throw new InvalidArgumentException(sprintf('Mismatched parenthesis or misplaced number: %s', implode(' ',$tokens)));
        }

        return iterator_to_array($this->outputQueue);
    }

    /**
     * Checks if the operator stack contains any operator tokens.
     *
     * @return bool True if the stack contains an operator token, false otherwise.
     */
    private function hasOperatorInStack()
    {
        $hasOperatorInStack = false;
        if(!$this->operatorStack->isEmpty()) {
            $top = $this->operatorStack->top();
            if(Token::T_OPERATOR == $top->getType()) {
                $hasOperatorInStack = true;
            }
        }

        return $hasOperatorInStack;
    }
}
