<?php

declare (strict_types=1);
namespace PHPStan\Node;

/** @api */
class MatchExpressionArm
{
    /**
     * @var \PHPStan\Node\MatchExpressionArmBody
     */
    private $body;
    /**
     * @var MatchExpressionArmCondition[]
     */
    private $conditions;
    /**
     * @var int
     */
    private $line;
    /**
     * @param MatchExpressionArmCondition[] $conditions
     */
    public function __construct(\PHPStan\Node\MatchExpressionArmBody $body, array $conditions, int $line)
    {
        $this->body = $body;
        $this->conditions = $conditions;
        $this->line = $line;
    }
    public function getBody() : \PHPStan\Node\MatchExpressionArmBody
    {
        return $this->body;
    }
    /**
     * @return MatchExpressionArmCondition[]
     */
    public function getConditions() : array
    {
        return $this->conditions;
    }
    public function getLine() : int
    {
        return $this->line;
    }
}
