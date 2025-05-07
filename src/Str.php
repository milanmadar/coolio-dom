<?php

namespace Milanmadar\Dom;

class Str
{
    /**
     * - HTML tags and entities are converted to the plaintext version
     * - < br> tags (all their versions) will be converted to a newline (\n)
     * - < div> tags will be converted to a newline (\n)
     * - < p> tags will be converted to 2 newlines (\n\n)
     *
     * @param string $html The html string
     * @param string|null $keep_tags Optional. These tags will be kept as HTML code<br>
     *                          Eg: '<a><div><span>' (can also keep '< br>' and '< p>' and '< div>')
     * @param array<string, string>|null $replace_tags Optional. Assoc arr, eg: ["<li>" => "\n- "]
     * @return string Cleaned plain text (trim, multi space removed, max 2 newlines, see desc for more)
     */
    public static function htmlToPlain(string $html, ?string $keep_tags = null, ?array $replace_tags = null): string
    {
        // remove non-html linebreaks
        $html = str_replace("\r\n", " ", $html);
        $html = str_replace("\r", " ", $html);
        $html = str_replace("\n", " ", $html);
        $html = str_replace("\t", " ", $html);

        // will remove <p>
        if(!isset($keep_tags) || strpos($keep_tags, '<p>') === false) {
            // <p> tag means a 2 linebreaks
            $html = str_ireplace( '<p>', "<br><br><p>", $html );
            $html = str_ireplace( '<p ', "<br><br><p ", $html );
        }

        // <div> means a linebreak too
        $html = str_ireplace('<div', '<br><div', $html);

        // will remove <h1>
        for($i=1; $i<7; ++$i)
        {
            $tag = 'h'.$i;
            if(!isset($keep_tags) || strpos($keep_tags, '<'.$tag.'>') === false) {
                // <p> tag means a 2 linebreaks
                $html = str_ireplace( '<'.$tag.'>', "<br><br><".$tag.">", $html );
                $html = str_ireplace( '<'.$tag.' ', "<br><br><".$tag." ", $html );
                $html = str_ireplace( '</'.$tag.'>', "</".$tag."><br><br>", $html );
            }
        }

        // will remove <br>
        if(!isset($keep_tags) || strpos($keep_tags, '<br>') === false) {
            $html = self::br2Nl($html);
        }

        // replaces defined by the user
        if(!empty($replace_tags)) {
            foreach($replace_tags as $from=>$to)
            {
                $html = str_replace($from, $to.$from, $html);

                // eg: "<li>"=>"-" should work with
                // "<li class="">" and also "<li>"
                if(substr($from, -1) == '>') {
                    $from2 = substr($from, 0, -1).' ';
                    $html = str_replace($from2, $to.$from2, $html);
                }
            }
        }

        // make HTML to be plain text
        $html = str_replace(html_entity_decode('&nbsp;'), ' ', $html);
        $html = str_replace(html_entity_decode('&#39;'), "'", $html);
        $html = str_replace(html_entity_decode('&NewLine;'), "\n", $html);
        $html = str_replace(html_entity_decode('&vert;'), "|", $html);
        $html = str_replace(html_entity_decode('&vert;'), "|", $html);
        $html = str_replace(html_entity_decode('&period;'), ".", $html);
        $html = str_replace(html_entity_decode('&lpar;'), "(", $html);
        $html = str_replace(html_entity_decode('&rpar;'), ")", $html);
        $html = str_replace(html_entity_decode('&sol;'), "x", $html);
        $html = str_replace(html_entity_decode('&colon;'), ":", $html);
        $html = str_replace(html_entity_decode('&comma;'), ",", $html);
        $html = str_replace(html_entity_decode('&dollar;'), "$", $html);
        $html = str_replace(html_entity_decode('&percnt;'), "%", $html);
        $html = strip_tags($html, $keep_tags);
        $html = html_entity_decode($html);

        $html = self::cleanText($html);

        // max 1 empty line between the lines
        $html = str_replace("\n ", "\n", $html);
        $html = str_replace(" \n", "\n", $html);
        do { $html = str_replace("\n\n\n", "\n\n", $html, $found); } while($found);
        $html = trim($html);

        return $html;
    }

    /**
     * Cleans the text. Linebreak is \n, removes \t \r, removeNonPrintables(), trims, remove multiple spaces.
     * @param string|null $txt The string
     * @param bool $make_one_line Optional. Default is false
     * @return string The clean string
     */
    public static function cleanText(?string $txt, bool $make_one_line = false): string
    {
        if($txt === '0') return '0';
        if(empty($txt)) return '';
        $txt = self::removeNonPrintables($txt);
        return self::removeMultiSpace($txt, $make_one_line);
    }

    /**
     * Removes all non printable characters from the string
     * @param string|null $str
     * @return string
     */
    public static function removeNonPrintables(?string $str): string
    {
        if($str === '0') return '0';
        if(empty($str)) return '';

        $enc = mb_detect_encoding($str, 'UTF-8', true);
        if($enc != 'UTF-8') {
            return $str;
        }

        //reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ''
        /** @var string $str */
        $str = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'
            .'|[\x00-\x7F][\x80-\xBF]+'
            .'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'
            .'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'
            .'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '', $str );

        //reject overly long 3 byte sequences and UTF-16 surrogates and replace with ''
        /** @var string $str */
        $str = preg_replace(
            '/\xE0[\x80-\x9F][\x80-\xBF]'
            .'|\xED[\xA0-\xBF][\x80-\xBF]/S',
            '', $str );

        // save linebreaks
        $str = str_replace("\r\n", "_<{MY_PER-r_PER-n}>_", $str);
        $str = str_replace("\n", "_<{MY_PER-n}>_", $str);
        $str = str_replace("\t", "_<{MY_PER-t}>_", $str);

        // this is what does the main job
        /** @var string $str */
        $str = preg_replace('/[^\pL\pN\pP\pS\pM]/u', ' ', $str);

        // put back linebreaks
        $str = str_replace("_<{MY_PER-r_PER-n}>_", "\r\n", $str);
        $str = str_replace("_<{MY_PER-n}>_", "\n", $str);
        $str = str_replace("_<{MY_PER-t}>_", "\t", $str);

        return $str;
    }

    /**
     * Removes multiple spaces and tabs, trims the text
     * @param string|null $txt
     * @param bool $make_one_line bool
     * @return string
     */
    public static function removeMultiSpace(?string $txt, bool $make_one_line, bool $spaceAroundNewlines=false): string
    {
        if(empty($txt)) return '';

        if($make_one_line) {
            $txt = str_replace(["\r\n", "\n", "\t", "\r"], ' ', $txt);
        } else {
            $from = array(); $to = array();
            $from[] = "\r\n"; $to[] = "\n";
            $from[] = "\t"; $to[] = ' ';
            $from[] = "\r"; $to[] = ' ';
            $txt = str_replace($from, $to, $txt);
        }

        if($spaceAroundNewlines) {
            /** @var string $txt */
            $txt = preg_replace('/( ?\n ?)+/', " \n ", $txt);
        }

        do { $txt = str_replace('  ', ' ', $txt, $space_cnt); } while($space_cnt);

        return trim($txt);
    }

    /**
     * Replaces all kinds of < br> tags with \n
     * @param string|null $txt
     * @return string
     */
    public static function br2Nl(?string $txt): string
    {
        if(empty($txt)) return '';

        $txt = str_ireplace("<br>\n", "\n", $txt);
        $txt = str_ireplace('<br>', "\n", $txt);

        $txt = str_ireplace("<br />\n", "\n", $txt);
        $txt = str_ireplace('<br />', "\n", $txt);

        $txt = str_ireplace("<br/>\n", "\n", $txt);
        $txt = str_ireplace('<br/>', "\n", $txt);

        $txt = str_ireplace("</br>\n", "\n", $txt);
        $txt = str_ireplace('</br>', "\n", $txt);

        $txt = str_ireplace("<br >\n", "\n", $txt);
        $txt = str_ireplace('<br >', "\n", $txt);

        return $txt;
    }
}