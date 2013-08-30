<?php

namespace Zikula\Tools\Printer;

/**
 * Class PrinterPSR2
 *
 * From https://github.com/tcopestake/PHP-Parser-PSR-2-pretty-printer
 * until added to packagist
 */
class Psr2Printer extends \PHPParser_PrettyPrinter_Default
{
    protected $classCount = 0;
    protected $methodCount = 0;

    protected $methodsVolatile = true;

    public function setMethodsVolatile($value)
    {
        $this->methodsVolatile = ($value ? true : false);

        return $this;
    }

    protected function implementsSeparated($nodes)
    {
        if (count($nodes) > 1) {
            return "\n    ".$this->pImplode($nodes, ",\n    ");
        } else {
            return ' '.$this->pImplode($nodes, ', ');
        }
    }

    protected function patchMethodName($method_name)
    {
        if (stristr($method_name, '_')) {
            $method_name = strtolower($method_name);

            $name_parts = explode('_', $method_name);

            $new_name = '';

            $part_count = 0;
            foreach($name_parts as $part) {
                $part = trim($part);

                if (!$part) {
                    continue;
                }

                if (!$part_count) {
                    $new_name .= $part;
                } else {
                    $new_name .= ucfirst($part);
                }

                ++$part_count;
            }

            $method_name = $new_name;
        }

        return $method_name;
    }

    public function pExpr_Closure(\PHPParser_Node_Expr_Closure $node) {
        return ($node->static ? 'static ' : '')
        . 'function ' . ($node->byRef ? '&' : '')
        . '(' . $this->pCommaSeparated($node->params) . ')'
        . (!empty($node->uses) ? ' use (' . $this->pCommaSeparated($node->uses) . ')': '')
        . ' {' . "\n" . $this->pStmts($node->stmts) . "\n" . '}';
    }

    public function pStmt_Class(\PHPParser_Node_Stmt_Class $node)
    {
        $result = ($this->classCount ? "\n" : '').$this->pModifiers($node->type)
            . 'class ' . $node->name
            . (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
            . (!empty($node->implements) ? ' implements' . $this->implementsSeparated($node->implements) : '')
            . "\n" . '{' . "\n" . $this->pStmts($node->stmts) . "\n" . '}';

        ++$this->classCount;

        $this->methodCount = 0;

        return $result;
    }

    protected function pCommaSeparatedMethodParams(array $nodes) {
        if(count($nodes) > 2) {
            return "\n    ".$this->pImplode($nodes, ",\n    ")."\n";
        } else {
            return $this->pImplode($nodes, ', ');
        }
    }

    public function pStmt_ClassMethod(\PHPParser_Node_Stmt_ClassMethod $node)
    {
        if ($this->methodsVolatile) {
            $method_name = $node->name;
        } else {
            $method_name = $this->patchMethodName($node->name);
        }

        $method_params = $this->pCommaSeparatedMethodParams($node->params);

        if (isset($method_params{0}) && $method_params{0} == "\n") {
            $multiline_params = true;
        } else {
            $multiline_params = false;
        }

        $result = $this->pModifiers($node->type)
            . 'function ' . ($node->byRef ? '&' : '') . $method_name
            . '(' . $method_params . ')'
            . (null !== $node->stmts
                ? (!$multiline_params ? "\n" : ' ') . '{' . "\n" . $this->pStmts($node->stmts) . "\n" . '}'
                : ';')."\n";

        ++$this->methodCount;

        return $result;
    }

    public function pExpr_MethodCall(\PHPParser_Node_Expr_MethodCall $node)
    {
        if ($this->methodsVolatile) {
            $method_name = $this->pObjectProperty($node->name);
        } else {
            $method_name = $this->patchMethodName($this->pObjectProperty($node->name));
        }

        $method_params = $this->pCommaSeparatedMethodParams($node->args);

        return $this->pVarOrNewExpr($node->var) . '->' . $method_name
        . '(' . $method_params . ')';
    }
}