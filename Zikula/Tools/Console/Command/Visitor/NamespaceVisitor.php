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
            if (count($p) == 5) {
                list($ns, $type, $mid, $last, $name) = $p;
                $node->name = $name;
                $namespace = "$ns\\$type\\$mid\\$last";
            } elseif (count($p) == 4) {
                list($ns, $type, $mid, $name) = $p;
                $node->name = $name;
                $namespace = "$ns\\$type\\$mid";
            } elseif (count($p) == 3) {
                list($ns, $type, $name) = $p;
                if (preg_match('/.*'.$type.'$/', $node->name, $matches)) {
                    // handle things like Foo_Controller_AdminController
                    return $node;
                }
                $node->name = "{$name}{$type}";
                $namespace = $type ? "$ns\\$type" : $ns;
            } elseif (count($p) == 2) {
                list($namespace, $name) = $p;
                switch ($name) {
                    case 'Installer':
                    case 'Version':
                        $node->name = $namespace.$name;
                        break;
                    default:
                        $node->name = $name;
                }
            } else {
                // skip - this should not happen
                return $node;
            }

            $use = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name("
namespace $namespace;
"));

            $comment = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name("
$fileDocBlock"));

            // build use import statements
            $return = array($comment, $use);
            foreach ($this->imports as $import) {
                $return[] = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name("use $import;"));
            }

            $return[] = new \PHPParser_Node_Stmt_UseUse(new \PHPParser_Node_Name(""));

            $return[] = $node;

            return $return;
        }
    }
}
