<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use function array_filter;
use function is_string;
use function strpos;
use function substr_count;
use const ARRAY_FILTER_USE_KEY;
use const T_COMMENT;
use const T_DOC_COMMENT;
class RichParser implements \PHPStan\Parser\Parser
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;
    /**
     * @var \PhpParser\Lexer
     */
    private $lexer;
    /**
     * @var \PhpParser\NodeVisitor\NameResolver
     */
    private $nameResolver;
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    public const VISITOR_SERVICE_TAG = 'phpstan.parser.richParserNodeVisitor';
    public function __construct(\PhpParser\Parser $parser, Lexer $lexer, NameResolver $nameResolver, Container $container)
    {
        $this->parser = $parser;
        $this->lexer = $lexer;
        $this->nameResolver = $nameResolver;
        $this->container = $container;
    }
    /**
     * @param string $file path to a file to parse
     * @return Node\Stmt[]
     */
    public function parseFile(string $file) : array
    {
        try {
            return $this->parseString(FileReader::read($file));
        } catch (\PHPStan\Parser\ParserErrorsException $e) {
            throw new \PHPStan\Parser\ParserErrorsException($e->getErrors(), $file);
        }
    }
    /**
     * @return Node\Stmt[]
     */
    public function parseString(string $sourceCode) : array
    {
        $errorHandler = new Collecting();
        $nodes = $this->parser->parse($sourceCode, $errorHandler);
        $tokens = $this->lexer->getTokens();
        if ($errorHandler->hasErrors()) {
            throw new \PHPStan\Parser\ParserErrorsException($errorHandler->getErrors(), null);
        }
        if ($nodes === null) {
            throw new ShouldNotHappenException();
        }
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->nameResolver);
        $traitCollectingVisitor = new \PHPStan\Parser\TraitCollectingVisitor();
        $nodeTraverser->addVisitor($traitCollectingVisitor);
        foreach ($this->container->getServicesByTag(self::VISITOR_SERVICE_TAG) as $visitor) {
            $nodeTraverser->addVisitor($visitor);
        }
        /** @var array<Node\Stmt> */
        $nodes = $nodeTraverser->traverse($nodes);
        $linesToIgnore = $this->getLinesToIgnore($tokens);
        if (isset($nodes[0])) {
            $nodes[0]->setAttribute('linesToIgnore', $linesToIgnore);
        }
        foreach ($traitCollectingVisitor->traits as $trait) {
            $trait->setAttribute('linesToIgnore', array_filter($linesToIgnore, static function (int $line) use($trait) : bool {
                return $line >= $trait->getStartLine() && $line <= $trait->getEndLine();
            }, ARRAY_FILTER_USE_KEY));
        }
        return $nodes;
    }
    /**
     * @param mixed[] $tokens
     * @return array<int, list<string>|null>
     */
    private function getLinesToIgnore(array $tokens) : array
    {
        $lines = [];
        foreach ($tokens as $token) {
            if (is_string($token)) {
                continue;
            }
            $type = $token[0];
            if ($type !== T_COMMENT && $type !== T_DOC_COMMENT) {
                continue;
            }
            $text = $token[1];
            $line = $token[2];
            if (strpos($text, '@phpstan-ignore-next-line') !== \false) {
                $line++;
            } elseif (strpos($text, '@phpstan-ignore-line') === \false) {
                continue;
            }
            $line += substr_count($token[1], "\n");
            $lines[$line] = null;
        }
        return $lines;
    }
}
