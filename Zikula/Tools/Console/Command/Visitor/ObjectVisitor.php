<?php

namespace Zikula\Tools\Console\Command\Visitor;

class ObjectVisitor extends \PhpParser\NodeVisitorAbstract
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
     * @param \PhpParser\Node $node
     *
     * @return \PhpParser\Node|\PhpParser\Node\Param|void
     */
    public function leaveNode(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Expr\Instanceof_ ||
            $node instanceof \PhpParser\Node\Expr\ClassConstFetch ||
            $node instanceof \PhpParser\Node\Expr\New_ ||
            $node instanceof \PhpParser\Node\Expr\StaticCall) {
            $name = $node->class->parts[0];
            if ('self' === $name || 'parent' === $name || 'static' === $name) {
                return $node;
            }
            $this->imports[$name] = $name;
        } elseif ($node instanceof \PhpParser\Node\Stmt\Class_) {
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
        } elseif ($node instanceof \PhpParser\Node\Param) {
            if (isset($node->class->parts[0])) {
                $this->imports[$node->type->parts[0]] = $node->type->parts[0];
            }
        } elseif ($node instanceof \PhpParser\Node\Stmt\Throw_) {
            $this->imports[$node->expr->class->parts[0]] = $node->expr->class->parts[0];
        }

        return $node;
    }
}