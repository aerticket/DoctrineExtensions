<?php

namespace DoctrineExtensions\Query\Mysql;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

use function implode;
use function sprintf;
use function strtolower;

class MatchAgainst extends FunctionNode
{
    /** @var array<int, PathExpression> */
    protected $pathExp = null;

    /** @var string */
    protected $against = null;

    /** @var bool */
    protected $booleanMode = false;

    /** @var bool */
    protected $queryExpansion = false;

    public function parse(Parser $parser): void
    {
        // match
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        // first Path Expression is mandatory
        $this->pathExp   = [];
        $this->pathExp[] = $parser->StateFieldPathExpression();

        // Subsequent Path Expressions are optional
        $lexer = $parser->getLexer();
        while ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->pathExp[] = $parser->StateFieldPathExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);

        // against
        if (strtolower($lexer->lookahead->value) !== 'against') {
            $parser->syntaxError('against');
        }

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->against = $parser->StringPrimary();

        if (strtolower($lexer->lookahead->value) === 'boolean') {
            $parser->match(Lexer::T_IDENTIFIER);
            $this->booleanMode = true;
        } elseif (strtolower($lexer->lookahead->value) === 'in') {
            $parser->match(Lexer::T_IDENTIFIER);

            if (strtolower($lexer->lookahead->value) !== 'boolean') {
                $parser->syntaxError('boolean');
            }

            $parser->match(Lexer::T_IDENTIFIER);

            if (strtolower($lexer->lookahead->value) !== 'mode') {
                $parser->syntaxError('mode');
            }

            $parser->match(Lexer::T_IDENTIFIER);

            $this->booleanMode = true;
        }

        if (strtolower($lexer->lookahead->value) === 'expand') {
            $parser->match(Lexer::T_IDENTIFIER);
            $this->queryExpansion = true;
        } elseif (strtolower($lexer->lookahead->value) === 'with') {
            $parser->match(Lexer::T_IDENTIFIER);

            if (strtolower($lexer->lookahead->value) !== 'query') {
                $parser->syntaxError('query');
            }

            $parser->match(Lexer::T_IDENTIFIER);

            if (strtolower($lexer->lookahead->value) !== 'expansion') {
                $parser->syntaxError('expansion');
            }

            $parser->match(Lexer::T_IDENTIFIER);

            $this->queryExpansion = true;
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $walker): string
    {
        $fields = [];

        foreach ($this->pathExp as $pathExp) {
            $fields[] = $pathExp->dispatch($walker);
        }

        $against = $walker->walkStringPrimary($this->against)
        . ($this->booleanMode ? ' IN BOOLEAN MODE' : '')
        . ($this->queryExpansion ? ' WITH QUERY EXPANSION' : '');

        return sprintf('MATCH (%s) AGAINST (%s)', implode(', ', $fields), $against);
    }
}
