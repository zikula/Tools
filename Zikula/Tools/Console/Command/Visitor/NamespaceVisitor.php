<?php

namespace Zikula\Tools\Console\Command\Visitor;

class NamespaceVisitor extends \PhpParser\NodeVisitorAbstract
{
    private $imports = array();

    private $vendor = '';

    private $moduleDirectory = '';

    public function setImports($imports)
    {
        $this->imports = $imports;
    }

    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    public function setModuleDirectory($md)
    {
        $this->moduleDirectory = $md;
    }

    public function leaveNode(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
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
                $ns = strcasecmp($ns.'Module', $this->moduleDirectory) === 0 ? $this->moduleDirectory : $ns;
                $namespace = "$ns\\$type\\$mid\\$last";
            } elseif (count($p) == 4) {
                list($ns, $type, $mid, $name) = $p;
                $node->name = $name;
                $ns = strcasecmp($ns.'Module', $this->moduleDirectory) === 0 ? $this->moduleDirectory : $ns;
                $namespace = "$ns\\$type\\$mid";
            } elseif (count($p) == 3) {
                list($ns, $type, $name) = $p;
                if (preg_match('/.*'.$type.'$/', $node->name, $matches)) {
                    // handle things like Foo_Controller_AdminController
                    return $node;
                }
                $node->name = "{$name}{$type}";
                $ns = strcasecmp($ns.'Module', $this->moduleDirectory) === 0 ? $this->moduleDirectory : $ns;
                $namespace = $type ? "$ns\\$type" : $ns;
            } elseif (count($p) == 2) {
                list($ns, $name) = $p;
                $namespace = strcasecmp($ns.'Module', $this->moduleDirectory) === 0 ? $this->moduleDirectory : $ns;
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

            $use = new \PhpParser\Node\Stmt\UseUse(new \PhpParser\Node\Name("
namespace {$this->vendor}\\$namespace;
"));

            $comment = new \PhpParser\Node\Stmt\UseUse(new \PhpParser\Node\Name("
$fileDocBlock"));

            // build use import statements
            $return = array($comment, $use);
            foreach ($this->imports as $import) {
                $return[] = new \PhpParser\Node\Stmt\UseUse(new \PhpParser\Node\Name("use $import;"));
            }

            $return[] = new \PhpParser\Node\Stmt\UseUse(new \PhpParser\Node\Name(""));

            $return[] = $node;

            return $return;
        }
    }
}
