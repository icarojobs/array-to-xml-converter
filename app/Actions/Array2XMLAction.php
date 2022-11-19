<?php

declare(strict_types=1);

namespace App\Actions;

use DomDocument;
use DOMElement;
use DOMNode;
use Exception;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use RuntimeException;

class Array2XMLAction
{
    private static ?DomDocument $xml = null;
    private static $encoding = 'UTF-8';

    const ATTRIBUTES = '@attributes';
    const VALUES = '@values';
    const CDATA = '@cdata';
    const COMMENT = '@comment';
    const XML = '@xml';

    public function __construct(
        private readonly string $nodeName = '',
        private readonly array  $array = [],
        private ?DOMElement $node = null,
    )
    {
        self::getXMLRoot();
    }

    private static function getXMLRoot(): ?DomDocument
    {
        if (empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }

    public static function init(string $version = '1.0', string $encoding = 'UTF-8', bool $formatOutput = true): void
    {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->formatOutput = $formatOutput;
        self::$encoding = $encoding;
    }

//    public function createXML(): void
//    {
//        $xml?->appendChild($this->convertXML());
//        $this->convertXML();
//    }

    public function saveXML(): string
    {
        return self::$xml->saveXML();
    }

    public function convertXML(string $nodeName = '', array $arr = []): ?DOMNode
    {
        try {
            $this->node = self::$xml->createElement($this->nodeName);

            $node = $xml->createElement($nodeName);

            if (is_array($arr)) {
                // get the attributes first.;
                if (isset($arr[self::ATTRIBUTES])) {
                    foreach ($arr[self::ATTRIBUTES] as $key => $value) {
                        if (!self::isValidTagName($key)) {
                            throw new InvalidCharactersException(
                                '[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: '
                                . $nodeName
                            );
                        }
                        $node->setAttribute($key, self::bool2str($value));
                    }
                    unset($arr[self::ATTRIBUTES]); //remove the key from the array once done.
                }

                // check if it has a value stored in @value, if yes store the value and return
                // else check if its directly stored as string
                if (isset($arr[self::VALUES])) {
                    $node->appendChild($xml->createTextNode(self::bool2str($arr[self::VALUES])));
                    unset($arr[self::VALUES]);    //remove the key from the array once done.
                    //return from recursion, as a note with value cannot have child nodes.
                    return $node;
                } elseif (isset($arr[self::CDATA])) {
                    $node->appendChild($xml->createCDATASection(self::bool2str($arr[self::CDATA])));
                    unset($arr[self::CDATA]);    //remove the key from the array once done.
                    //return from recursion, as a note with cdata cannot have child nodes.
                    return $node;
                } elseif (isset($arr[self::COMMENT]) && is_string($arr[self::COMMENT])) {
                    $node->appendChild($xml->createComment(self::bool2str($arr[self::COMMENT])));
                    unset($arr[self::COMMENT]);
                } elseif (isset($arr[self::XML])) {
                    $fragment = $xml->createDocumentFragment();
                    $fragment->appendXML($arr[self::XML]);
                    $node->appendChild($fragment);
                    unset($arr[self::XML]);
                    return $node;
                }
            }

            //create subnodes using recursion
            if (is_array($arr)) {
                // recurse to get the node for that key
                foreach ($arr as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: '
                            . $nodeName);
                    }
                    if (is_array($value) && is_numeric(key($value))) {
                        // MORE THAN ONE NODE OF ITS KIND;
                        // if the new array is numeric index, means it is array of nodes of the same kind
                        // it should follow the parent key name
                        foreach ($value as $keyOk => $v) {
                            $node->appendChild($this->convertXML($key, $v));
                        }
                    } else {
                        // ONLY ONE NODE OF ITS KIND
                        $node->appendChild($this->convertXML($key, $value));
                    }
                    unset($arr[$key]); //remove the key from the array once done.
                }
            }

            // after we are done with all the keys in the array (if it is one)
            // we check if it has any text value, if yes, append it.
            if (!is_array($arr)) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr)));
            }

            return $node;

        } catch (\DOMException $domException) {
            dd($domException->getMessage());
        }
    }


//    /**
//     * Convert an Array to XML.
//     *
//     * @param string $node_name
//     *   Name of the root node to be converted.
//     * @param array $arr
//     *  Array to be converted.
//     *
//     * @return \DOMNode
//     * @throws \Exception
//     *
//     */
//    private static function &convert($nodeName, $arr = array())
//    {
//        $xml = self::getXMLRoot();
//
//        $node = $xml->createElement($nodeName);
//
//        if (is_array($arr)) {
//            // get the attributes first.;
//            if (isset($arr[self::ATTRIBUTES])) {
//                foreach ($arr[self::ATTRIBUTES] as $key => $value) {
//                    if (!self::isValidTagName($key)) {
//                        throw new InvalidCharactersException(
//                            '[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: '
//                            . $nodeName
//                        );
//                    }
//                    $this->node->setAttribute($key, self::bool2str($value));
//                }
//                unset($arr[self::ATTRIBUTES]); //remove the key from the array once done.
//            }
//
//            // check if it has a value stored in @value, if yes store the value and return
//            // else check if its directly stored as string
//            if (isset($arr[self::VALUES])) {
//                $this->node->appendChild($xml->createTextNode(self::bool2str($arr[self::VALUES])));
//                unset($arr[self::VALUES]);    //remove the key from the array once done.
//                //return from recursion, as a note with value cannot have child nodes.
//                return $node;
//            } elseif (isset($arr[self::CDATA])) {
//                $this->node->appendChild($xml->createCDATASection(self::bool2str($arr[self::CDATA])));
//                unset($arr[self::CDATA]);    //remove the key from the array once done.
//                //return from recursion, as a note with cdata cannot have child nodes.
//                return $node;
//            } elseif (isset($arr[self::COMMENT]) && is_string($arr[self::COMMENT])) {
//                $this->node->appendChild($xml->createComment(self::bool2str($arr[self::COMMENT])));
//                unset($arr[self::COMMENT]);
//            } elseif (isset($arr[self::XML])) {
//                $fragment = $xml->createDocumentFragment();
//                $fragment->appendXML($arr[self::XML]);
//                $this->node->appendChild($fragment);
//                unset($arr[self::XML]);
//                return $node;
//            }
//        }
//
//        //create subnodes using recursion
//        if (is_array($arr)) {
//            // recurse to get the node for that key
//            foreach ($arr as $key => $value) {
//                if (!self::isValidTagName($key)) {
//                    throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: '
//                        . $nodeName);
//                }
//                if (is_array($value) && is_numeric(key($value))) {
//                    // MORE THAN ONE NODE OF ITS KIND;
//                    // if the new array is numeric index, means it is array of nodes of the same kind
//                    // it should follow the parent key name
//                    foreach ($value as $keyOk => $v) {
//                        $this->node->appendChild(self::convert($key, $v));
//                    }
//                } else {
//                    // ONLY ONE NODE OF ITS KIND
//                    $this->node->appendChild(self::convert($key, $value));
//                }
//                unset($arr[$key]); //remove the key from the array once done.
//            }
//        }
//
//        // after we are done with all the keys in the array (if it is one)
//        // we check if it has any text value, if yes, append it.
//        if (!is_array($arr)) {
//            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
//        }
//
//        return $node;
//    }


    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v)
    {
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }

    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}
