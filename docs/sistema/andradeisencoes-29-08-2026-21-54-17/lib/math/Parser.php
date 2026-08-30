<?php

namespace Math;

/**
 * Parses and evaluates mathematical expressions using tokenization and translation strategies.
 *
 * Converts expressions into tokens, translates them into RPN, and evaluates the result.
 * Uses a lexer for tokenization and a translation strategy to convert infix notation into RPN.
 *
 * @author Adrean Boyadzhiev (netforce) <adrean.boyadzhiev@gmail.com>
 */
class Parser
{
    /**
     * Lexer wich should tokenize the mathematical expression.
     *
     * @var Lexer
     */
    protected $lexer;

    /**
     * TranslationStrategy that should translate from infix
     * mathematical expression notation to reverse-polish 
     * mathematical expression notation.
     *
     * @var TranslationStrategy\TranslationStrategyInterface
     */
    protected $translationStrategy;
    
    /**
     * Array of key => value options.
     *
     * @var array 
     */
    private $options = array(
        'translationStrategy' => '\Math\TranslationStrategy\ShuntingYard',
    );

    /**
     * Initializes the Parser with an optional configuration for the translation strategy.
     *
     * Accept array of configuration options, currently supports only 
     * one option "translationStrategy" => "Fully\Qualified\Classname".
     *
     * Class represent by this options is responsible for translation
     * from infix mathematical expression notation to reverse-polish
     * mathematical expression notation.
     *
     * <code>
     *  $options = array(
     *      'translationStrategy' => '\Math\TranslationStrategy\ShuntingYard'
     *  );
     * </code>
     *
     * @param array $options Configuration options. Supported option:
     *                       - 'translationStrategy' (string): Fully qualified class name of the translation strategy.
     */
    public function __construct(array $options = array())
    {
        $this->lexer = new Lexer();
        $this->options = array_merge($this->options, $options);
        $this->translationStrategy = new $this->options['translationStrategy']();
    }

    /**
     * Evaluates a mathematical expression provided as a string.
     *
     * @param string $expression The mathematical expression to evaluate.
     *
     * @return float The computed result of the expression.
     */
    public function evaluate($expression)
    {
        $lexer = $this->getLexer();
        $tokens = $lexer->tokenize($expression);

        $translationStrategy = new \Math\TranslationStrategy\ShuntingYard();

        return $this->evaluateRPN($translationStrategy->translate($tokens));
    }

    /**
     * Evaluates a mathematical expression represented in Reverse Polish Notation (RPN).
     *
     * @param Token[] $expressionTokens Array of Token instances in RPN order.
     *
     * @return float The computed result of the expression.
     * @throws \InvalidArgumentException If an invalid operator is encountered.
     */
    private function evaluateRPN(array $expressionTokens)
    {
        $stack = new \SplStack();

        foreach ($expressionTokens as $token) {
            $tokenValue = $token->getValue();
            if (is_numeric($tokenValue)) {
                $stack->push((float) $tokenValue);
                continue;
            }

            switch ($tokenValue) {
                case '+':
                    $stack->push($stack->pop() + $stack->pop());
                    break;
                case '-':
                    $n = $stack->pop();
                    $stack->push($stack->pop() - $n);
                    break;
                case '*':
                    $stack->push($stack->pop() * $stack->pop());
                    break;
                case '/':
                    $n = $stack->pop();
                    $stack->push($stack->pop() / $n);
                    break;
                case '%':
                    $n = $stack->pop();
                    $stack->push($stack->pop() % $n);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Invalid operator detected: %s', $tokenValue));
                    break;
            }
        }

        return $stack->top();
    }

    /**
     * Retrieves the Lexer instance used for tokenization.
     *
     * @return Lexer The Lexer instance.
     */
    public function getLexer()
    {
        return $this->lexer;
    }
}
