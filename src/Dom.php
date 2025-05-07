<?php

namespace Milanmadar\Dom;

class Dom
{
    protected \DOMDocument $dom;

    protected \DomXPath $xpath;

    /** @var string The HTML or XML code that was passed */
    protected string $source;
    protected bool $isXML;

    protected ?string $failed_elem_selector;

    private bool $origi_content_had_html_tag;

    /**
     * @param string $html HTML or XML
     * @throws \InvalidArgumentException
     */
    public function __construct(string $html)
    {
        $this->load($html);
        $this->failed_elem_selector = null;
    }

    /**
     * Returns the full Html
     * @param bool $is_origi Optional. Default false.<br>
     *                       False: the one produced by the dom parser
     *                       True: The original HTML that was passed to the constructor.<br>
     * @return string
     */
    public function source(bool $is_origi = false): string
    {
        if($is_origi) {
            return $this->source;
        }

        $source = $this->isXML
            ? $this->dom->saveXML()
            : $this->dom->saveHTML();
        if($source === false) return "";

        // PHP domdocument and xpath doesnt work well without <html> wrap
        // But if I let them add it, I can't remove it later
        if(!$this->origi_content_had_html_tag)
        {
            $source = trim( $source );
            if(strpos($source, '<html><body>') === 0 && strpos($source, '</body></html>')) {
                $source = str_replace('<html><body>', '', $source);
                $source = str_replace('</body></html>', '', $source);
            }
            $source = trim( $source );
        }

        return $source;
    }

    /**
     * Load the given html into a \DOMDocument class and \DomXPath
     * @param string $source
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function load(string $source): void
    {
        $this->origi_content_had_html_tag = false;

        // its not html/xml
        if(strpos($source, '<') === false || strpos($source, '>') === false) {
            throw new \InvalidArgumentException('Html\Dom: Invalid HTML/XML code was given');
        }

        $this->source = trim($source);

        // XML
        $this->isXML = str_starts_with($this->source, '<?xml');
        $isHTML = !$this->isXML;

        // fix utf-8 errors
        if($isHTML) {
            if(stripos($source, 'charset=utf')===false && stripos($source, 'charset="utf')===false && stripos($source, "charset='utf")===false) {
                $source = str_ireplace('<head>', '<head><meta charset="UTF-8">', $source);
            }
            if( stripos($source, 'utf-8') || stripos($source, 'charset')===false ) {
                //if(mb_detect_encoding($html, 'UTF-8', true) != 'HTML-ENTITIES') {
                if(mb_detect_encoding($source, 'HTML-ENTITIES', true) != 'HTML-ENTITIES') {
                    $source = mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8');
                }
            }

            // PHP DomDocument and xPath doesn't work well without <html> wrap
            // But if I let them add it, I mustremove it later
            $this->origi_content_had_html_tag = (stripos($source, '</html>') !== false);
            if(!$this->origi_content_had_html_tag) {
                $source = '<html><body>'.$source.'</body></html>';
            }
            $source = str_replace('</br>','<br>',$source); // DOMDocument can't deal with HTML5
        }
        else // XML
        {
            // we will convert it to HTML coz that can handle mistakes better

            // keep the encoding
            preg_match('/<\?xml[^>]*encoding=["\']([^"\']+)["\']/', $source, $matches);
            $encoding = $matches[1] ?? 'UTF-8';

            // remove the XML declaration so we can handle it as HTML
            $source = preg_replace('/^<\?xml.*\?>\s*/', '', $source);

            // PHP DomDocument and xPath doesn't work well without <html> wrap
            // But if I let them add it, I must remove it later
            $this->origi_content_had_html_tag = false;
            $source = '<html><meta charset="'.$encoding.'"><body>'.$source.'</body></html>';
        }

        // Load it
        $this->dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if( !$this->dom->loadHTML( $source, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
            throw new \InvalidArgumentException('Invalid HTML\XML code was given');
        }
        libxml_use_internal_errors(false);
        libxml_clear_errors();

        $this->xpath = new \DomXPath( $this->dom );
    }

    /**
     * Returns a nodelist
     * @param string $selector Kinda like jQuery<br>
     *        Examples:
     *        - img (tag name)
     *        - .class
     *        - .class1 class2 class3
     *        - .class1.class2.class3
     *        - div.class
     *        - #id
     *        - img#id
     *        - [attribute=value]
     *        - [attribute]
     *        - img[attribute=value]
     *        - img[attribute]
     * @param Node|null $parent Optional
     * @return NodeList Empty NodeList if nothing matched OR if the query makes no sense
     */
    protected function _elemList(string $selector, Node|null $parent = null): NodeList
    {
        // id
        if(($pos = strpos($selector, '#')) !== false)
        {
            // tagname
            if($pos != 0) {
                $parts = explode('#', $selector);
                $tagname = strtolower($parts[0]);
                $select = $parts[1];
            } else {
                $tagname = '*';
                $select = ltrim($selector, '#');
            }

            // handle substitute asterisk queries
            if(strpos($select, '*')!==false)
            {
                $select = ' '.trim($select).' ';

                // leftmost - rightmost asterisk
                if($select[1] == '*') $select = ltrim($select, '* ');
                if($select[strlen($select)-2] == '*') $select = rtrim($select, '* ');

                // inbetween asterisk queries
                if(strpos($select, '*')!==false)
                {
                    $expr_inner_attr = "";
                    $selects = explode('*', $select);
                    foreach($selects as $sel)
                    {
                        if(!empty($expr_inner_attr)) $expr_inner_attr .= " and ";
                        $expr_inner_attr .= "contains(concat(' ', normalize-space(@id), ' '), '$sel')";
                    }

                // no inbetween queries (but there were leftmost - rightmost asterisk)
                } else {
                    $expr_inner_attr = "contains(concat(' ', normalize-space(@id), ' '), '$select')";
                }

            // no substitute asterisk queries
            } else {
                $select = ' '.trim($select).' ';
                $expr_inner_attr = "contains(concat(' ', normalize-space(@id), ' '), '$select')";
            }

            // xpath expression
            $expr = ".//".$tagname."[".$expr_inner_attr."]";

            //$select = ' '.trim($select).' ';
            //$expr = ".//".$tagname."[@id='".$select."']";

        // attribute
        } elseif(($pos = strpos($selector, '[')) !== false)
        {
            // tagname
            if($pos != 0) {
                $parts = explode('[', $selector);
                $tagname = strtolower($parts[0]);
                $remain_selector = rtrim($parts[1], ']');
            } else {
                $tagname = '*';
                $remain_selector = trim($selector, '[]');
            }

            $expr_inner_attr = "";

            // attribute = value
            if(strpos($remain_selector, '=')) {
                $parts = explode('=', $remain_selector);
                $attr = trim($parts[0]);
                $val  = $parts[1];

            // attribute name only
            } else {
                $attr = trim($remain_selector);
                $val = null;
            }

            // having ':' and '*' in attr name is too complex
            if(str_contains($attr, '*') && str_contains($attr, ':')) {
                throw new \InvalidArgumentException("Dom: can't handle attribute name with both ':' and '*' in it");
            }

            // ':' in attr name but no '*'
            if(str_contains($attr, ':'))
            {
                $expr_inner_attr = 'name()="'.$attr.'"';

                if(isset($val)) {
                    $expr_inner_attr .= " and .= '".$val."'";
                }

                $expr_inner_attr = '@*['.$expr_inner_attr.']';
            }
            elseif(str_contains($attr, '*')) // '*' in attr name but no ':'
            {
                // leftmost '*'
                if($attr[0] == '*') {
                    $attr = ltrim($attr, '*');
                }

                // rightmost '*'
                if($attr[strlen($attr)-1] == '*') {
                    $attr = rtrim($attr, '*');
                }

                // '*' inbetween queries
                if(str_contains($attr, '*'))
                {
                    $attrs = explode('*', $attr);
                    foreach($attrs as $a) {
                        if(!empty($expr_inner_attr)) {
                            $expr_inner_attr .= " and ";
                        }
                        $expr_inner_attr .= "contains(name(), '$a')";
                    }

                    if(isset($val)) {
                        $expr_inner_attr .= " and .= '".$val."'";
                    }

                    $expr_inner_attr = "@*[".$expr_inner_attr."]";

                // no '*' inbetween queries (but there were leftmost - rightmost asterisk)
                } else {
                    $expr_inner_attr = "contains(name(), '$attr')";

                    if(isset($val)) {
                        $expr_inner_attr .= " and .='$val'";
                    }

                    $expr_inner_attr = "@*[".$expr_inner_attr."]";
                }
            }
            else // no '*' or ':' in attr name
            {
                $expr_inner_attr = "@".$attr;

                if(isset($val)) {
                    $expr_inner_attr .= "='$val'";
                }
            }

            // xpath expression
            $expr = ".//".$tagname."[$expr_inner_attr]";

        // class
        } elseif(($pos = strpos($selector, '.')) !== false)
        {
            // tagname
            if($pos != 0) {
                $parts = explode('.', $selector);
                $tagname = strtolower(array_shift($parts));
                $select = implode('.', $parts);
            } else {
                $tagname = '*';
                $select = ltrim($selector, '.');
            }

            // in case the guy passed: '.class1.class2.class3'
            $select = str_replace('.', ' ', $select);
            $select = str_replace('  ', ' ', $select);

            // handle substitute asterisk queries
            if(str_contains($select, '*'))
            {
                $select = ' '.trim($select).' ';

                // leftmost - rightmost asterisk
                if($select[1] == '*') $select = ltrim($select, '* ');
                if($select[strlen($select)-2] == '*') $select = rtrim($select, '* ');

                // inbetween asterisk queries
                if(str_contains($select, '*'))
                {
                    $expr_inner_attr = "";
                    $selects = explode('*', $select);
                    foreach($selects as $sel) {
                        if(!empty($expr_inner_attr)) $expr_inner_attr .= " and ";
                        $expr_inner_attr .= "contains(concat(' ', normalize-space(@class), ' '), '$sel')";
                    }

                // no inbetween queries (but there were leftmost - rightmost asterisk)
                } else {
                    $expr_inner_attr = "contains(concat(' ', normalize-space(@class), ' '), '$select')";
                }

            // no substitute asterisk queries
            } else {
                $select = ' '.trim($select).' ';
                $expr_inner_attr = "contains(concat(' ', normalize-space(@class), ' '), '$select')";
            }

            // xpath expression
            $expr = ".//".$tagname."[".$expr_inner_attr."]";

            //$select = ' '.trim($select).' ';
            //$expr = ".//".$tagname."[contains(concat(' ', normalize-space(@class), ' '), '$select')]";

        // tagname OR unknown expression
        } else
        {
            if(preg_match('/^[a-zA-Z0-9_-]+$/', $selector)) { // tagname
                $expr = ".//".strtolower($selector);
            } else { // unknown expression
                $expr = ".//".$selector;
            }
        }

        $parent_el = null;
        if(isset($parent)) {
            $parent_el = $parent->getPhpDOMElement();
        }

        $php_nodes_list = $this->xpath->query($expr, $parent_el);
        return ($php_nodes_list === false) ? new NodeList( new \DOMNodeList() ) : new NodeList($php_nodes_list);
    }

    /**
     * Returns a nodelist
     * @param string|array<string, int|null> $selector Selectors are like jQuery (asterisk placeholder is ok for class and id)<br>
     *        Examples:
     *        - img (tag name)
     *        - .class
     *        - .class1 class2 class3
     *        - .class1.class2.class3
     *        - div.class
     *        - #id
     *        - img#id
     *        - [attribute=value]
     *        - [attribute]
     *        - img[attribute=value]
     *        - img[attribute]<br>
     *        Pass an associative array for chaining (value = -1 first from the end):<br>
     *        [<br>'.details'=>0,<br>'.money_details'=>0,<br>'.saleprice'=>-1,<br>'.discount'=>null<br>]<br>
     *        The last value must be null
     *
     * @param Node|null $el Optional parent element
     * @return NodeList The NodeList is empty ( $list->isEmpty() ) if a point of the chain has failed (when $selector is an assoc arr),
     *         and then $dom->lastFailedSelector() tells you the failed selector
     */
    public function lister(string|array $selector, ?Node $el = null): NodeList
    {
        if(is_array($selector)) {
            foreach($selector as $the_selector=>$elem_i)
            {
                $parent = $el;

                $list = $this->_elemList($the_selector, $parent);

                if(!isset($elem_i)) {
                    return $list;
                }

                if($elem_i >= 0) {
                    $el = $list->i($elem_i);
                } else {
                    $el = $list->iEnd($elem_i * (-1) - 1);
                }

                if(!isset($el)) {
                    $this->failed_elem_selector = $the_selector.' => '.$elem_i;
                    return $this->_elemList('thisSurelyDoesntExist_Aufn8hj');
                }
            }
            return $list ?? $this->_elemList('.dkeushfdkshfskrufhrfsh765');
        }

        return $this->_elemList($selector, $el);
    }

    /**
     * Returns an element
     * @param array<string, int|null> $associative_array [ '#details'=>0 , '.price'=>1 , '.currency'=>-1 ]<br>
     * Selectors are like in jQuery (asterisk placeholder is ok for class and id)<br>
     * (value = -1 means first from the end)<br>
     *        Examples:<br>
     *        - h2 (tag name) <br>
     *        - .class <br>
     *        - .class1 class2 class3 <br>
     *        - .class1.class2.class3 <br>
     *        - div.class <br>
     *        - #id <br>
     *        - img#id <br>
     *        - [attribute=value] <br>
     *        - [attribute] <br>
     *        - h2[attribute=value] <br>
     *        - h2[attribute]
     * @param Node|null $el Optional parent element, the whole thing starts here then
     * @return Node|null NULL if a point of the chain has failed, and then $dom->lastFailedSelector() tells you the failed selector
     */
    public function elem(array $associative_array, ?Node $el = null): ?Node
    {
        foreach($associative_array as $selector=>$elem_i)
        {
            $parent = $el;

            $list = $this->_elemList($selector, $parent);
            if($list->isEmpty()) {
                return null;
            }

            if(!isset($elem_i)) {
                $elem_i = 0;
            }

            if($elem_i >= 0) {
                $el = $list->i($elem_i);
            } else {
                $el = $list->iEnd($elem_i * (-1) - 1);
            }

            if(!isset($el)) {
                $this->failed_elem_selector = $selector.' => '.$elem_i;
                return null;
            }
        }

        return $el;
    }

    /**
     * If $dom->elem() returns null, this will tell you which selector failed
     * @return string|null
     */
    public function lastFailedSelector(): ?string
    {
        return $this->failed_elem_selector;
    }

    /**
     * creates an element
     * @param string $name 'div', 'a', 'span', 'p', ...
     * @param string $value Optional. Text written inside the element. Will be escaped inside
     * @param array<string, mixed>|null $attribs Optional. Assoc array. Keys are attribute names, values are attribute values
     * @return Node
     */
    public function createElem(string $name, ?string $value = null, ?array $attribs = null): Node
    {
        $elem = new Node( $this->dom->createElement($name) );

        if(isset($value)) {
            $elem->setText($value);
        }

        if( !empty($attribs) ) {
            foreach( $attribs as $attr_name=>$attr_val ) {
                $elem->setAttr($attr_name, $attr_val);
            }
        }

        return $elem;
    }

}