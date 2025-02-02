<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\FileUpload\Processor;

use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\ProcessingStatus;

/**
 * Class SVGBlacklistPreProcessor
 *
 * PreProcessor which checks SVGs for scripts in the file.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 */
final class SVGBlacklistPreProcessor implements PreProcessor
{
    use IsMimeTypeOrExtension;

    private const SVG_MIME_TYPE = 'image/svg+xml';
    private const REGEX_SCRIPT = '/<script/m';
    private const REGEX_BASE64 = '/data:.*;base64/m';
    private const SVG = 'svg';
    private string $rejection_message = 'The SVG file contains possibily malicious code.';
    private string $rejection_message_script;
    private string $rejection_message_base64;
    private string $rejection_message_elements;
    private string $ok_message = 'SVG OK';
    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/Events
     */
    private array $svg_event_lists = [
        "onbegin",
        "onend",
        "onrepeat",
        "onabort",
        "onerror",
        "onresize",
        "onscroll",
        "onunload",
        "onabort",
        "onerror",
        "onresize",
        "onscroll",
        "onunload",
        "oncancel",
        "oncanplay",
        "oncanplaythrough",
        "onchange",
        "onclick",
        "onclose",
        "oncuechange",
        "ondblclick",
        "ondrag",
        "ondragend",
        "ondragenter",
        "ondragleave",
        "ondragover",
        "ondragstart",
        "ondrop",
        "ondurationchange",
        "onemptied",
        "onended",
        "onerror",
        "onfocus",
        "oninput",
        "oninvalid",
        "onkeydown",
        "onkeypress",
        "onkeyup",
        "onload",
        "onloadeddata",
        "onloadedmetadata",
        "onloadstart",
        "onmousedown",
        "onmouseenter",
        "onmouseleave",
        "onmousemove",
        "onmouseout",
        "onmouseover",
        "onmouseup",
        "onmousewheel",
        "onpause",
        "onplay",
        "onplaying",
        "onprogress",
        "onratechange",
        "onreset",
        "onresize",
        "onscroll",
        "onseeked",
        "onseeking",
        "onselect",
        "onshow",
        "onstalled",
        "onsubmit",
        "onsuspend",
        "ontimeupdate",
        "ontoggle",
        "onvolumechange",
        "onwaiting",
        "onactivate",
        "onfocusin",
        "onfocusout"
    ];

    public function __construct(
        ?string $rejection_message = null,
        ?string $additional_message_script = null,
        ?string $additional_message_base64 = null,
        ?string $additional_message_elements = null
    ) {
        $this->rejection_message = $rejection_message ?? $this->rejection_message;
        $this->rejection_message_script = $additional_message_script ?? 'contains script tags';
        $this->rejection_message_base64 = $additional_message_base64 ?? 'contains base64 encoded content';
        $this->rejection_message_elements = $additional_message_elements ?? 'contains not allowed or unknown elements or attributes';
    }

    private function isSVG(Metadata $metadata): bool
    {
        return $this->isMimeTypeOrExtension(
            $metadata,
            self::SVG,
            [self::SVG_MIME_TYPE]
        );
    }

    public function process(FileStream $stream, Metadata $metadata): ProcessingStatus
    {
        if ($this->isSVG($metadata) && !$this->checkStream($stream)) {
            return new ProcessingStatus(ProcessingStatus::DENIED, $this->rejection_message);
        }
        return new ProcessingStatus(ProcessingStatus::OK, $this->ok_message);
    }

    public function getDomDocument(string $raw_svg_content): ?\DOMDocument
    {
        $dom = new \DOMDocument();
        try {
            $dom->loadXML($raw_svg_content, LIBXML_NOWARNING | LIBXML_NOERROR);
        } catch (\Exception $e) {
            return null;
        }
        $errors = libxml_get_errors();
        if ($errors !== []) {
            return null;
        }
        return $dom;
    }

    protected function checkStream(FileStream $stream): bool
    {
        $raw_svg_content = (string) $stream;
        if (false === $raw_svg_content) {
            return false;
        }

        // Check for script tags directly
        if ($this->hasContentScriptTag($raw_svg_content)) {
            $this->rejection_message = $this->rejection_message;
            return false;
        }

        // Analyze the SVG
        $dom = $this->getDomDocument($raw_svg_content);
        if ($dom === null) {
            return false;
        }

        // loop through all attributes of elements recursively and check for event attributes
        $looper = $this->getDOMAttributesLooper();
        $prohibited_attributes = function (string $name): bool {
            return in_array(strtolower($name), $this->svg_event_lists, true);
        };
        if ($looper($dom, $prohibited_attributes) === false) {
            return false;
        }

        return true;
    }

    private function hasContentScriptTag(string $raw_svg_content): bool
    {
        // Check for Base64 encoded Content
        if (preg_match(self::REGEX_BASE64, $raw_svg_content)) {
            $this->rejection_message .= ' ' . $this->rejection_message_base64;
            return true;
        }

        // Check for script tags directly
        if (preg_match(self::REGEX_SCRIPT, $raw_svg_content)) {
            $this->rejection_message .= ' ' . $this->rejection_message_script;
            return true;
        }

        return false;
    }

    protected function getDOMAttributesLooper(): \Closure
    {
        return function (\DOMDocument $dom, \Closure $closure): bool {
            $attributes_looper = function (\DOMNode $node, \Closure $closure) use (&$attributes_looper): bool {
                foreach ($node->attributes as $attribute) {
                    if ($closure($attribute->name)) {
                        $this->rejection_message .= sprintf(
                            $this->rejection_message_elements . ' (%s)',
                            $attribute->name
                        );
                        return false;
                    }
                }
                foreach ($node->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        if(!$attributes_looper($child, $closure)) {
                            return false;
                        }
                    }
                }
                return true;
            };
            foreach ($dom->getElementsByTagName("*") as $i => $element) {
                if ($attributes_looper($element, $closure) === false) {
                    return false;
                }
            }
            return true;
        };
    }
}
