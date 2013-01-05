<?php

namespace Zikula\Tools\Console\Command\Visitor;

class NamespaceVisitor extends \PHPParser_NodeVisitorAbstract
{
    private $imports = array();

    public function setImports($imports)
    {
        $this->imports = $imports;
    }

    public function leaveNode(\PHPParser_Node $node)
    {
        if ($node instanceof \PHPParser_Node_Stmt_Class) {
            // extract file level docblock if one exists
            $attributes = $node->getAttributes();
            $comments = isset($attributes['comments']) ? $attributes['comments'] : array();
            $fileDocBlock = array_shift($comments);
            $fileDocBlock = is_object($fileDocBlock) ? $fileDocBlock->getText() : '';

            $node->setAttribute('comments', $comments);

            $p = explode('_', $node->name);
            if (count($p) == 3) {
                list($ns, $type, $name) = $p;
            } else {
                list($ns, $name) = $p;
                $type = '';
            }
            if (preg_match('/.*'.$type.'$/', $node->name, $matches)) {
                return $node;
            }

            $comment = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name("
$fileDocBlock"));
            $node->name = "{$name}{$type}";
            $namespace = $type ? "$ns\\$type" : $ns;
            $use = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name("
namespace $namespace;
"));
            $return = array($comment, $use);
            if ('Api' === $type || 'Controller' === $type) {
                foreach ($this->imports as $import) {
                    $return[] = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name("use $import;"));
                }

                $return[] = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name(""));
            }

            $return[] = $node;

            return $return;
        }
    }
}
