<?php

namespace Zikula\Tools\Console\Command\Visitor;

class ControllerActionVisitor extends \PhpParser\NodeVisitorAbstract
{
    public function leaveNode(\PhpParser\Node $node)
    {
        // rewrite controller methods with Action suffix
        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            if ($node->isPublic()) {
                if (preg_match('/.*Action$/', $node->name, $matches)) {
                    return $node;
                }

                $node->name = "{$node->name}Action";
            }

            return $node;
        }
    }
}