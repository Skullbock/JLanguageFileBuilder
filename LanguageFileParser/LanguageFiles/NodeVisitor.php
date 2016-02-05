<?php
/**
 * Port from PageKit
 */
namespace Weble\Sayes\Admin\Service\LanguageFiles;

use PhpParser\Lexer;
use PhpParser\Node;

abstract class NodeVisitor
{
    /**
     * @var string
     */
    public $file;
    
    /**
     * @var array
     */
    public $results = [];
    /**
     * Starts traversing an array of files.
     *
     * @param  array $files
     * @return array
     */
    abstract public function traverse(array $files);
   
    /**
     * @param  string $name
     * @return string
     */
    protected function loadTemplate($name)
    {
        return $this->file = $name;
    }
}