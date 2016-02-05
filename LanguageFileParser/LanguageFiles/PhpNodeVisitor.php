<?php
/**
 * Port from PageKit
 */
namespace Weble\Sayes\Admin\Service\LanguageFiles;

use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor as BaseVisitor;
use PhpParser\Parser;

class PhpNodeVisitor extends NodeVisitor implements BaseVisitor
{
    /**
     * {@inheritdoc}
     */
    public function traverse(array $files)
    {
        $parser    = new Parser(new Lexer);
        $traverser = new NodeTraverser;
        $traverser->addVisitor($this);
        
        foreach ($files as $file) 
        {
            try 
            {
                $traverser->traverse($parser->parse(file_get_contents($this->loadTemplate($file))));
            } 
            catch (\Exception $e) 
            {
            }
        }

        return $this->results;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\StaticCall
                && isset($node->name)
                && isset($node->class)
                && $node->class->getLast() == 'JText'
            ) 
        {
            if (isset($node->args[0]) && isset($node->args[0]->value) && $node->args[0]->value instanceof Node\Scalar\String_)
            {
                $methods = array('_', 'script', 'sprintf');
                
                if (in_array($node->name, $methods))
                {
                    $string = $node->args[0]->value->value;
                    $this->results[$string][] = ['file' => $this->file, 'line' => $node->getLine()];
                }
            }
        }
    }

    public function beforeTraverse(array $nodes)
    {
    }

    public function leaveNode(Node $node)
    {
    }

    public function afterTraverse(array $nodes)
    {
    }
}