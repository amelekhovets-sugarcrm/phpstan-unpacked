<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeAbstract;
class InForeachNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Stmt\Foreach_
     */
    private $originalNode;
    public function __construct(Foreach_ $originalNode)
    {
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode() : Foreach_
    {
        return $this->originalNode;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_InForeachNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
