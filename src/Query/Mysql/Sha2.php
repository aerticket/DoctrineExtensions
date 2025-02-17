<?php

namespace DoctrineExtensions\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/** @author Andreas Gallien <gallien@seleos.de> */
class Sha2 extends FunctionNode
{
    public $stringPrimary;

    public $simpleArithmeticExpression;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'SHA2(' .
            $sqlWalker->walkStringPrimary($this->stringPrimary) .
            ',' .
            $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression) .
        ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
