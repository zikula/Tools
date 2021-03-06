<?php

/**
 * This file is part of the PHP ST utility.
 *
 * (c) Sankar suda <sankar.suda@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Zikula\Tools\Converter;

use Zikula\Tools\ConverterAbstract;

/**
 * @author sankar <sankar.suda@gmail.com>
 */
class IfConverter extends ConverterAbstract
{
    private $alt = array(
        'gt' => '>',
        'lt' => '<',
        'eq' => '==',
        'neq' => '!=',
        'ne' => '!=',
        'not' => '!',
        'mod' => '%',
    );

    public function convert(\SplFileInfo $file, $content)
    {
        // Replace {if }
        $content = $this->replaceIf($content);
        // Replace {elseif }
        $content = $this->replaceElseIf($content);
        // Replace {/if}
        $content = preg_replace('#\{/if\s*\}#', "{% endif %}", $content);
        // Replace {else}
        $content = preg_replace('#\{else\s*\}#', "{% else %}", $content);

        return $content;
    }

    private function replaceIf($content)
    {

        $pattern = "#\{if\b\s*([^{}]+)?\}#i";
        $string = '{%% if %s %%}';

        return $this->replace($pattern, $content, $string);
    }

    private function replace($pattern, $content, $string)
    {
        return preg_replace_callback($pattern, function ($matches) use ($string) {

            $match = $matches[1];
            $search = $matches[0];

            foreach ($this->alt as $key => $value) {
                $match = str_replace(" $key ", " $value ", $match);
            }

            // take care of `!foo`
            $match = preg_replace_callback("/(!)([^=\s]+)/i", function ($m) {
                return (false === strpos($m[2], 'empty')) ? "not $m[2]" : "!$m[2]";
            }, $match);

            // take care of bar:foo['hello']
            $match = preg_replace_callback("/(:)([^}\s]+)/i", function ($m) {
                return "($m[2])";
            }, $match);

            // Replace $vars
            $match = $this->replaceVariable($match);

            $string = sprintf($string, $match);

            return str_replace($search, $string, $search);

        }, $content);
    }

    private function replaceVariable($string)
    {
        $pattern = '/\$([\w\.\-\>\[\]]+)/';
        return preg_replace_callback($pattern, function ($matches) {
            // Convert Object to dot
            $matches[1] = str_replace('->', '.', $matches[1]);

            return str_replace($matches[0], $matches[1], $matches[0]);

        }, $string);
    }

    private function replaceElseIf($content)
    {

        $pattern = "#\{elseif\b\s*([^{}]+)?\}#i";
        $string = '{%% elseif %s %%}';

        return $this->replace($pattern, $content, $string);

    }

    public function getPriority()
    {
        return 50;
    }

    public function getName()
    {
        return 'if';
    }

    public function getDescription()
    {
        return 'Convert smarty if/else/elseif to twig';
    }
}
