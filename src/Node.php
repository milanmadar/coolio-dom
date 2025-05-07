<?php

namespace Milanmadar\Dom;

class Node
{
    private \DOMNode $elem;
    
    /**
     * @param \DOMNode $elem
     */
    public function __construct(\DOMNode $elem)
    {
        $this->elem = $elem;
    }
    
    /**
     * @return \DOMNode
     */
    public function getPhpDOMElement(): \DOMNode
    {
        return $this->elem;
    }
    
    /**
     * Retuns the inside text.
     * - HTML tags and entities are converted to the plaintext version
     * - < br> tags (all their versions) will be converted to a newline (\n)
     * - < div> tags will be converted to a newline (\n)
     * - < p> tags will be converted to 2 newlines (\n\n)
     * 
     * @param string $keep_tags Optional. These tags will be kept as HTML code<br>
     *                          Eg: '<a><div><span>' (can also keep '< br>' and '< p>' and '< div>')
     * @param array<string, string>|null $replace_tags Optional. Assoc arr, eg: ["<li>" => "\n- "]
     * @return string Cleaned plain text (trim, multi space removed, max 2 newlines, see desc for more)
     */
    public function text(?string $keep_tags = null, ?array $replace_tags = null): string
    {
        return Str::htmlToPlain($this->html(), $keep_tags, $replace_tags);
    }
    
    /**
     * Sets the inside text of the element.
     * @param string $txt It will be escaped correctly
     * @return Node
     */
    public function setText(string $txt): Node
    {
        // escape as https://www.php.net/manual/en/domdocument.createelement.php
        if(!empty($txt))
        {
            $txt = strval($txt);
            
            // & but not &nbsp; or &quote; or &amp
            $txt = str_replace("&nbsp", "-_N_B_S_P_-", $txt);
            $txt = str_replace("&quote", "-_Q_U_O_T_-", $txt);
            $txt = str_replace("&amp", "-_A_M_P_-", $txt);
            $txt = str_replace("&lt;", "-_L_T_semi_-", $txt);
            $txt = str_replace("&gt;", "-_G_T_semi_-", $txt);
            $txt = str_replace("&#0", "-_#_0_-", $txt);
            
            $txt = str_replace("&", "&amp;", $txt);
            
            $txt = str_replace("-_N_B_S_P_-", "&nbsp", $txt);
            $txt = str_replace("-_Q_U_O_T_-", "&quot", $txt);
            $txt = str_replace("-_A_M_P_-", "&amp", $txt);
            $txt = str_replace("-_L_T_semi_-", "&lt;", $txt);
            $txt = str_replace("-_G_T_semi_-", "&gt;", $txt);
            $txt = str_replace("-_#_0_-", "&#0", $txt);
            
            // "
            $txt = str_replace('"', '&quot;', $txt);
        }
        
        $this->elem->nodeValue = $txt;
        
        return $this;
    }
    
    /**
     * Tells if this element has text in it or not
     * @return bool
     */
    public function hasText(): bool
    {
        $txt = $this->text();
        return !empty($txt);
    }
    
    /**
     * Returns the inner HTML code
     * @param bool $include_self Should we include the tab itself too?
     * @return string
     */
    public function html(bool $include_self = false): string
    {
        if(!isset($this->elem->ownerDocument)) return "";

        if(get_class($this->elem) == "DOMText") {
            $str = $this->elem->ownerDocument->saveHTML($this->elem);
            return ($str === false) ? "" : $str;
        }
        
        if($include_self) {
            $str = $this->elem->ownerDocument->saveHTML($this->elem);
            return ($str === false) ? "" : $str;
        }
        
        $html = ''; 
        $children  = $this->elem->childNodes;
        foreach ($children as $child)  { 
            $html .= $this->elem->ownerDocument->saveHTML($child);
        }
        
        return $html; 
    }
    
    /**
     * Returns the value of the attribute (as a string)
     * @param string $attr_name
     * @return string clean text (trim, multi space removed)
     */
    public function attr(string $attr_name): string
    {
        if(get_class($this->elem) == "DOMText") return '';
        
        $txt = '';
        if($this->elem instanceof \DOMElement) {
            $txt = $this->elem->getAttribute( $attr_name );
        }
        if(empty($txt)) return $txt;
        return Str::cleanText($txt);
    }
    
    /**
     * Sets the attribute
     * @param string $attr_name
     * @param string $attr_value
     * @return Node
     */
    public function setAttr(string $attr_name, string $attr_value): Node
    {
        if(get_class($this->elem) == "DOMText") return $this;
        
        if($this->elem instanceof \DOMElement) {
            $this->elem->setAttribute($attr_name, $attr_value);
        }
        return $this;
    }
    
    /**
     * Appends the attribute correctly
     * @param string $attr_name
     * @param string $attr_value
     * @param string $glue If the attribute already has a value, this will be appended to the end of it before we append the $attr_value
     * @return Node
     */
    public function appendAttr(string $attr_name, string $attr_value, string $glue): Node
    {
        if(get_class($this->elem) == "DOMText") return $this;
        
        // creating the attribute the first time
        if(!$this->hasAttr($attr_name)) {
            $this->setAttr($attr_name, $attr_value);
            return $this;
        }
        
        // attribute exist
        $val = rtrim($this->attr($attr_name));
        
        // but it has no value
        if(empty($val)) {
            $val = $attr_value;
            
        // it already has a value
        } else {
            // add glue
            if(substr($val, -1) != $glue) {
                $val .= $glue;
            }
            // append value
            $val .= $attr_value;
        }
        
        $this->setAttr($attr_name, $val);
        
        return $this;
    }
    
    /**
     * Tells if this element has the given attribute or not
     * @param string $attr_name
     * @return bool
     */
    public function hasAttr(string $attr_name): bool
    {
        if(get_class($this->elem) == "DOMText") return false;
        
        if($this->elem instanceof \DOMElement) {
            return $this->elem->hasAttribute($attr_name);
        }
        return false;
    }

    /**
     * @return array<string, string>
     */
//    public function allAttrs(): array
//    {
//        $attributes = [];
//        if($this->elem instanceof \DOMElement) {
//            foreach($this->elem as $attribute_name => $attribute_node) {
//                /** @var /DOMNode $attribute_node */
//                $attributes[$attribute_name] = $attribute_node->nodeValue;
//            }
//        }
//        return $attributes;
//    }
    
    /**
     * Returns the name of the node, like 'div'
     * @return string
     */
    public function name(): string
    {
        if(get_class($this->elem) == "DOMText") return "DOMText";
        return $this->elem->nodeName;
    }
    
    /**
     * Returns the parent node
     * @return Node|null
     */
    public function parentNode(): ?Node
    {
        $el = $this->elem->parentNode;
        if(!isset($el)) return null;
        return new Node($el);
    }
    
    /**
     * Returns the next sibling element (skipping whitespace and comment nodes)
     * @param bool $include_textelem Optional. Include DOMText elems too? Default is false
     * @return Node|null
     */
    public function nextSibling(bool $include_textelem = false): ?Node
    {
        $el = $this->elem;
        while(1)
        {
            $el = $el->nextSibling;
            if(!isset($el)) return null;
            $name = $el->nodeName;
            
            $el_class = get_class($el);
            if($el_class == 'DOMText' && !$include_textelem) continue;
            if($el_class == 'DOMComment') continue;
            
            return new Node($el);
        }
    }
    
    /**
     * Returns the previous sibling (skipping whitespace and comment nodes)
     * @param bool $include_textelem Optional. Include DOMText elems too? Default is false
     * @return Node|null
     */
    public function prevSibling(bool $include_textelem = false): ?Node
    {
        $el = $this->elem;
        while(1)
        {
            $el = $el->previousSibling;
            if(!isset($el)) return null;
            $name = $el->nodeName;
            
            $el_class = get_class($el);
            if($el_class == 'DOMText' && !$include_textelem) continue;
            if($el_class == 'DOMComment') continue;
            
            return new Node($el);
        }
    }
    
    /**
     * Returns all the child elements (only 1st level children, skipping whitespace and comment elements)
     * @param bool $include_textelem Optional. Include DOMText elems too? Default is false
     * @return NodeList
     */
    public function children(bool $include_textelem = false): NodeList
    {
        if(get_class($this->elem) == "DOMText") {
            return new NodeList( new \DOMNodeList() );
        }
        
        // get the nodes
        $nodelist = $this->elem->childNodes;

        // delete DOMText and DOMComment
        for($i = $nodelist->length; $i >= 0; --$i )
        {    
            $el = $nodelist->item($i);
            if(!isset($el->parentNode)) continue;
            
            // remove textnode and comments
            $el_class = get_class($el);
            if($el_class == 'DOMText' && !$include_textelem) {
                $el->parentNode->removeChild($el);
            } elseif($el_class == 'DOMComment') {
                $el->parentNode->removeChild($el);
            }
        }
        
        return new NodeList($nodelist);
    }
    
    /**
     * Deletes this node
     */
    public function delete(): void
    {
        $parent = $this->elem->parentNode;
        if(isset($parent)) $parent->removeChild($this->elem);
    }
    
    /**
     * Adds a new child before this node. If you plan to do further modifications on the appended child you must use the returned node.
     * @param Node $new_node
     * @return Node A new Node. If you plan to do further modifications on the inserted child you must use this returned node.
     */
    public function insertBefore(Node $new_node): Node
    {
        if(isset($this->elem->parentNode)) {
            $ret = $this->elem->parentNode->insertBefore( $new_node->getPhpDOMElement() , $this->elem );
        } else {
            $ret = $this->elem->cloneNode(false)->insertBefore( $new_node->getPhpDOMElement() , $this->elem );
        }
        return new Node($ret);
    }
    
    /**
     * Adds a new child after this node. If you plan to do further modifications on the appended child you must use the returned node.
     * @param Node $new_node
     * @return Node A new Node. If you plan to do further modifications on the inserted child you must use this returned node.
     */
    public function insertAfter(Node $new_node): Node
    {
        if($this->elem->nextSibling) {
            if(isset($this->elem->parentNode)) {
                $ret = $this->elem->parentNode->insertBefore( $new_node->getPhpDOMElement() , $this->elem->nextSibling );
            } else {
                $ret = $this->elem->cloneNode(false)->insertBefore( $new_node->getPhpDOMElement() , $this->elem->nextSibling );
            }
        } else {
            if(isset($this->elem->parentNode)) {
                $ret = $this->elem->parentNode->appendChild( $new_node->getPhpDOMElement() );
            } else {
                $ret = $this->elem->cloneNode(false)->appendChild( $new_node->getPhpDOMElement() );
            }
        }
        return new Node($ret);
    }
    
    /**
     * Adds new child at the end of the children
     * @param Node $new_node
     * @return Node A new Node. If you plan to do further modifications on the inserted child you must use this returned node.
     */
    public function appendChild(Node $new_node): Node
    {
        if(get_class($this->elem) == "DOMText") {
            return $this->insertAfter($new_node);
        }
        
        $ret = $this->elem->appendChild( $new_node->getPhpDOMElement() );
        return new Node($ret);
    }
    
    /**
     * Adds new child at the beginning of the children
     * @param Node $new_node
     * @return Node A new Node. If you plan to do further modifications on the inserted child you must use this returned node.
     */
    public function prependChild(Node $new_node): Node
    {
        if(get_class($this->elem) == "DOMText") {
            return $this->insertBefore($new_node);
        }
        
        $ret = $this->elem->insertBefore( $new_node->getPhpDOMElement(), $this->elem->firstChild );
        return new Node($ret);
    }
    
    /**
     * Changes the name of the tag (name of the element). This is possible with delete+insert
     * @param string $name The new name
     * @return Node|null The new node, OR NULL if the given node didn't have a name (such as \DOMText type nodes)
     */
    public function changeTagName(string $name): ?Node
    {
        if(get_class($this->elem) == "DOMText") return null;
        
        $node = $this->elem;
        
        $childnodes = array();
        foreach ($node->childNodes as $child){
            $childnodes[] = $child;
        }

        if(isset($node->ownerDocument)) {
            $newnode = $node->ownerDocument->createElement($name);
            foreach ($childnodes as $child){
                $child2 = $node->ownerDocument->importNode($child, true);
                $newnode->appendChild($child2);
            }
        } else {
            $newnode = new \DomElement( $node->nodeName );
        }

        if(isset($node->attributes)) {
            foreach ($node->attributes as $attrName => $attrNode) {
                $attrName = $attrNode->nodeName;
                $attrValue = $attrNode->nodeValue;
                $newnode->setAttribute($attrName, $attrValue);
            }
        }

        if(isset($node->parentNode)) {
            $node->parentNode->replaceChild($newnode, $node);
        }
        
        return new Node($newnode);
    }
    
}
