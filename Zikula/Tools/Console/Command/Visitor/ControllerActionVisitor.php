<?php

namespace Zikula\Tools\Console\Command\Visitor;

class ControllerActionVisitor extends \PHPParser_NodeVisitorAbstract
{
    public function leaveNode(\PHPParser_Node $node)
    {
        // rewrite controller methods with Action suffix
        if ($node instanceof \PHPParser_Node_Stmt_ClassMethod) {
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