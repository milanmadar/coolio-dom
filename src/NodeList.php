<?php

namespace Milanmadar\Dom;

/**
 * @implements \Iterator<Node|null>
 * @implements \ArrayAccess<int, Node|null>
 */
class NodeList implements \Iterator, \Countable, \ArrayAccess
{
    /** @var \DOMNodeList<\DOMNode>  */
    private \DOMNodeList $nodelist;
    
    private int $iterator_pos;
    
    /**
     * Constructor
     * @param \DOMNodeList<\DOMNode> $nodelist
     */
    public function __construct(\DOMNodeList $nodelist)
    {
        $this->nodelist = $nodelist;
        $this->iterator_pos = 0;
    }

    /**
     * The internal PHP type its working with
     * @return \DOMNodeList<\DOMNode>
     */
    public function phpDOMNodeList(): \DOMNodeList
    {
        return $this->phpDOMNodeList();
    }
    
    /**
     * How many elements are in it
     * @return int
     */
    public function count(): int
    {
        return $this->nodelist->length;
    }
    
    /**
     * Same as $this->count() == 0
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ($this->count() == 0);
    }
    
    /**
     * Returns an element
     * @param int $i Starts with 0
     * @return Node|null
     */
    public function i(int $i): ?Node
    {
        if($i < 0) return null;
        $php_node = $this->nodelist->item( $i );
        if(!isset($php_node)) return null;
        return new Node($php_node);
    }
    
    /**
     * Returns an element, the index starts from the END of the list
     * @param int $i 0 means the last elem
     * @return Node|null
     */
    public function iEnd(int $i): ?Node
    {
        // $this->i() handles when it's argument is less then zero
        return $this->i( $this->count()-1-$i );
    }
    
    /**
     * Returns the next element OR null
     * @return Node|null
     */
    public function nextElem(): ?Node
    {
        $elem = $this->current();
        $this->next();
        return $elem;
    }
    
    // Iterator INTERFACE //
    
    public function rewind(): void {
        $this->iterator_pos = 0;
    }

    public function current(): mixed {
        return $this->i( $this->iterator_pos );
    }

    public function key(): mixed {
        return $this->iterator_pos;
    }

    /**
     * <b>THIS DOESN'T RETURN ANYTHING!!</b><br>
     * Use: $this->nextElem() OR $this->i()
     */
    public function next(): void {
        ++$this->iterator_pos;
    }

    public function valid(): bool {
        $node = $this->nodelist->item( $this->iterator_pos );
        return isset($node);
    }

    // ArrayAccess INTERFACE //

    public function offsetExists ( $offset ): bool
    {
        if($offset < 0) return false;
        $php_node = $this->nodelist->item( $offset );
        return isset($php_node);
    }

    public function offsetGet ( $offset ): mixed
    {
        if($offset < 0) {
            return $this->iEnd( intval($offset)+1 );
        }
        return $this->i( intval($offset) );
    }

    public function offsetSet( $offset, $value ): void
    {
        throw new \BadMethodCallException("Can't set Nodes in NodeList");
    }

    public function offsetUnset( $offset ): void
    {
        throw new \BadMethodCallException("Can't unset Nodes in NodeList");
    }
    
}
