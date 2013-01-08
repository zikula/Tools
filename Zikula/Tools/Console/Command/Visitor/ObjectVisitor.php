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
            $node instanceof \PHPParser_Node_Expr_New ||
            $node instanceof \PHPParser_Node_Expr_StaticCall) {
            $name = $node->class->parts[0];
            if ('self' === $name || 'parent' === $name || 'static' === $name) {
                return $node;
            }
            $this->imports[$name] = $name;
        } elseif ($node instanceof \PHPParser_Node_Stmt_Class) {
            // handle extends
            if (isset($node->extends)) {
                $name = $node->extends->parts[0];
                if (false === strpos($name, '\\')) {
                    $node->extends->parts[0] = '\\'.$name;
                }
            }

            // handle implements
            if (isset($node->implements[0])) {
                foreach ($node->implements as $k => $value) {
                    $name = $node->implements[$k]->parts[0];
                    if (false === strpos($name, '\\')) {
                        $node->implements[$k]->parts[0] = '\\'.$name;
                    }
                }
            }
        } elseif ($node instanceof \PHPParser_Node_Param) {
            if (isset($node->class->parts[0])) {
                $this->imports[$node->type->parts[0]] = $node->type->parts[0];
            }
        } elseif ($node instanceof \PHPParser_Node_Stmt_Throw) {
            $this->imports[$node->expr->class->parts[0]] = $node->expr->class->parts[0];
        }

        return $node;
    }
}