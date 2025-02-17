<?php

namespace DoctrineExtensions\Query\Postgresql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * @link https://www.postgresql.org/docs/current/functions-matching.html#FUNCTIONS-POSIX-TABLE
 *
 * @example SELECT REGEXP_REPLACE(string, search, replace)
 */
class RegexpReplace extends FunctionNode
{
    private $string;

    private $search;

    private $replace;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'REGEXP_REPLACE(' . $this->string->dispatch($sqlWalker) . ', ' . $this->search->dispatch($sqlWalker) . ', ' . $this->replace->dispatch($sqlWalker) . ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->string = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->search = $parser->StringExpression();
        $parser->match(Lexer::T_COMMA);
        $this->replace = $parser->StringExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
