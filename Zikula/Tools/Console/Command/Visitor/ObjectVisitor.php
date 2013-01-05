<?php

namespace Zikula\Tools\Console\Command\Visitor;

class ObjectVisitor extends \PHPParser_NodeVisitorAbstract
{
    private $imports = array();

    public function getImports()
    {
        return array_keys($this->imports);
    }

    /**
     * Gathers all class references within the class so
     * an appropriate import can be written.
     *
     * @param \PHPParser_Node $node
     *
     * @return \PHPParser_Node|\PHPParser_Node_Param|void
     */
    public function leaveNode(\PHPParser_Node $node)
    {
        if ($node instanceof \PHPParser_Node_Expr_Instanceof ||
            $node instanceof \PHPParser_Node_Expr_ClassConstFetch ||
            $node instanceof \PHPParser_Node_Expr_StaticCall) {
            $this->imports[$node->class->parts[0]] = $node->class->parts[0];
        } elseif ($node instanceof \PHPParser_Node_Stmt_Class) {
            if (isset($node->extends)) {
                $this->imports[$node->extends->parts[0]] = $node->extends->parts[0];
            }
        } elseif ($node instanceof \PHPParser_Node_Param) {
            if (isset($node->type->parts[0])) {
                $this->imports[$node->type->parts[0]] = $node->type->parts[0];
            }
        }

        return $node;
    }
}