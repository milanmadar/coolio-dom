# DOM Parser for XML and HTML

It can handle broken (invalid) XML and HTML files too. I uses the common css selectors.

```php
$htmlCode = file_get_contents('htmlTest.html');
$dom = new \Milanmadar\Dom\Dom( (string)$htmlCode );

// This returns the HTML code as the parser could process it. It has a fixed encoding, unclosed tags are closed somewhere, etc.
// Its useful when you are working with invalid HTML code and want to see how the parser could process it.
$parsedHtml = $dom->source();

// This returns the HTML exactly as it was passed in the constructor
$origiHtml = $dom->source(true);


//
// FINDING SINGLE ELEMENTS
// The selectors are like the jQuery selectors: https://api.jquery.com/category/selectors/
//

// Find the 1st element with id='myId'. For example: <div id='myId'>...</div>
$elem = $dom->elem( [ "#myId" => 0 ] );

// Find the 1st element that has the 'myClass' css class. For example:
// <div class='myClass'>...</div>
// OR
// <div class='otherClass myClass moreClass'>...</div>
$elem = $dom->elem( [ ".myClass" => 0 ] );

// Find the 2nd element that has the 'myClass' css class. For example:
// <div class='myClass'>...</div> <!-- not this -->
// <div class='myClass'>...</div> <!-- YES this -->
// <div class='myClass'>...</div> <!-- not this -->
$elem = $dom->elem( [ ".myClass" => 1 ] );

// Find the 3rd element that has the 'myClass' css class
// <div class='myClass'>...</div> <!-- not this -->
// <div class='myClass'>...</div> <!-- not this -->
// <div class='myClass'>...</div> <!-- YES this -->
$elem = $dom->elem( [ ".myClass" => 2 ] );

// Find the 1st element that has a css class starting with 'my'... For example:
// <div class='myClass'>...</div>
// OR
// <div class='myGreatness'>...</div>
// OR
// <div class='otherClass myGreatness moreClass'>...</div>
$elem = $dom->elem( [ ".my*" => 0 ] );

// Find the 1st element that has a class with both css classes called 'class1' and 'class2'. For example:
// <div class='class1 class2'>...</div>
// OR
// <div class='hello class1 otherthing class2 moreclasses'>...</div>
$elem = $dom->elem( [ ".class1.class2" => 0 ] );
$elem = $dom->elem( [ ".class1 class2" => 0 ] ); // same as the previous line
$elem = $dom->elem( [ ".class1 c*s2" => 0 ] ); // same as the previous line, the * is the usual placeholder for any character

// Find the 1st element with attrname='attrvalue'. For example:
// <div attrname='attrvalue'>...</div>
$elem = $dom->elem( [ "[attrname=attrvalue]" => 0 ]);

// you can use ':' and '*' for attributes too
$elem = $dom->elem( [ "[system:id=attrvalue]" => 0 ]);
$elem = $dom->elem( [ "[wild*card=attrvalue]" => 0 ]);

// Find the 1st element that has an attribute called "whateverattribute". For example:
// <div whateverattribute="value-doesnt-matter">...</div>
$elem = $dom->elem( [ "[whateverattribute]" => 0 ]);

// Find the 1st <img> tag
$elem = $dom->elem( [ "img" => 0 ]);


//
// YOU CAN PREPAND ALL ABOVE SELECTORS WITH A TAGNAME
// Examples:
//

// Find the 1st <div> element with id='myId'. For example:
// <span id='myId'>...</span> <!-- not this because its a <span> -->
// <div id='notMyId'>...</div> <!-- not this because the id is different -->
// <div id='myId'>...</div> <!-- YES this -->
$elem = $dom->elem( [ "div#myId" => 0 ] );

// Find the 2nd <div element that has a class staring with 'my'...
$elem = $dom->elem( [ "div.my*" => 1 ] );

// Find the 1st <a> element with rel="nofollow"
$elem = $dom->elem( [ "a[rel=nofollow]" => 0 ]);
$elem = $dom->elem( [ "tagname[system:id=attrvalue]" => 0 ]);


//
// TRAVERSING THE DOM WITH SELCETORS IN ONE CALL:
//
$elem = $dom->elem( [
    '#myId'=>0, // inside the <... id='myId' ...>
    '[target=_blank]'=>0, // then inside the elem with <... target='_blank' ...>
    'span.best'=>1, // find the 2nd <span class='best'>
]);


//
// TRY DIFFERENT SELECTORS UNTIL YOU FIND THE ELEMENT
//
$header = $dom->elem(['.header_v1'=>0]); // Try this
if( is_null($header) ) { // The above didn’t find anything
    $header = $dom->elem(['.header_v2'=>0]); // Then try this
}


//
// SEARCH ONLY INSINDE A KNOWN ELEMENT
//
// Lets find an element inside the $header
if($header = $dom->elem(['.header_v2'=>0])) { // first find the header
    $elem = $dom->elem(["div.myClass" => 1, "h2" => 1],   $header); // then look only inside the $header to find the 2nd <h2> tag inside the 2nd <div class='myClass'>
}


//
// LIST OF ELEMENTS
//
// Every <h2> tag inside the 2nd <div class='myClass'>
$elemlist = $dom->lister(["div.myClass"=>1, "h2"=> NULL ]);

// Did we find any elements?
$elemlist->isEmpty();

// How many elements did we find?
$elemlist->count();
count( $elemlist ); /* @phpstan-ignore-line */ // Same as above (Countable interface)

// Get the 3rd found Node
$elem = $elemlist->i( 2 );
$elem = $elemlist[2]; // Same as above (ArrayAccess interface)

// Get the last found Node
$elem = $elemlist->iEnd( 0 );
$elem = $elemlist[-1]; // Same as above (ArrayAccess interface)

// Get the one-bfore-the-last found Node
$elem = $elemlist->iEnd( 1 );
$elem = $elemlist[-2]; // Same as above (ArrayAccess interface)

// Traverse the list
while( $elem = $elemlist->nextElem() ) {
    // $txt = $elem->text();
    // $href = $elem->attr('href');
}
// Traverse the list (same as the while loop [Iterator interface])
foreach($elemlist as $elem) {
    // $txt = $elem->text();
    // $href = $elem->attr('href');
}

//
// "WALKING" ALONG NODES
//
/** @var Node $elem */
$elemParent = $elem->parentNode();
$elemNext = $elem->nextSibling();
$elemPrev = $elem->prevSibling();
$elemlistChildren = $elem->children();
```
