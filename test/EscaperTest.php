<?php

declare(strict_types=1);

namespace LaminasTest\Escaper;

use Exception;
use Laminas\Escaper\Escaper;
use Laminas\Escaper\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function chr;
use function in_array;

class EscaperTest extends TestCase
{
    /** @var Escaper */
    protected $escaper;

    protected function setUp(): void
    {
        $this->escaper = new Escaper('UTF-8');
    }

    public function testSettingEncodingToEmptyStringShouldThrowException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Escaper('');
    }

    /** @psalm-return array<string, array{0: string}> */
    public function supportedEncodingsProvider(): array
    {
        return [
            'iso-8859-1'   => ['iso-8859-1'],
            'iso8859-1'    => ['iso8859-1'],
            'iso-8859-5'   => ['iso-8859-5'],
            'iso8859-5'    => ['iso8859-5'],
            'iso-8859-15'  => ['iso-8859-15'],
            'iso8859-15'   => ['iso8859-15'],
            'utf-8'        => ['utf-8'],
            'cp866'        => ['cp866'],
            'ibm866'       => ['ibm866'],
            '866'          => ['866'],
            'cp1251'       => ['cp1251'],
            'windows-1251' => ['windows-1251'],
            'win-1251'     => ['win-1251'],
            '1251'         => ['1251'],
            'cp1252'       => ['cp1252'],
            'windows-1252' => ['windows-1252'],
            '1252'         => ['1252'],
            'koi8-r'       => ['koi8-r'],
            'koi8-ru'      => ['koi8-ru'],
            'koi8r'        => ['koi8r'],
            'big5'         => ['big5'],
            '950'          => ['950'],
            'gb2312'       => ['gb2312'],
            '936'          => ['936'],
            'big5-hkscs'   => ['big5-hkscs'],
            'shift_jis'    => ['shift_jis'],
            'sjis'         => ['sjis'],
            'sjis-win'     => ['sjis-win'],
            'cp932'        => ['cp932'],
            '932'          => ['932'],
            'euc-jp'       => ['euc-jp'],
            'eucjp'        => ['eucjp'],
            'eucjp-win'    => ['eucjp-win'],
            'macroman'     => ['macroman'],
        ];
    }

    /**
     * @dataProvider supportedEncodingsProvider
     */
    public function testSettingValidEncodingShouldNotThrowExceptions(string $encoding): void
    {
        $escaper = new Escaper($encoding);
        $this->assertSame($encoding, $escaper->getEncoding());
    }

    public function testSettingEncodingToInvalidValueShouldThrowException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Escaper('invalid-encoding');
    }

    public function testReturnsEncodingFromGetter()
    {
        $this->assertEquals('utf-8', $this->escaper->getEncoding());
    }

    /** @psalm-return array<string, array{0: string, 1: string}> */
    public function htmlSpecialCharsProvider(): array
    {
        return [
            '\'' => ['\'', '&#039;'],
            '"'  => ['"', '&quot;'],
            '<'  => ['<', '&lt;'],
            '>'  => ['>', '&gt;'],
            '&'  => ['&', '&amp;'],
        ];
    }

    /**
     * @dataProvider htmlSpecialCharsProvider
     */
    public function testHtmlEscapingConvertsSpecialChars(string $string, string $encoded): void
    {
        $this->assertEquals($encoded, $this->escaper->escapeHtml($string), 'Failed to escape: ' . $string);
    }

    /** @psalm-return array<string, array{0: string, 1: string}> */
    public function htmlAttrSpecialCharsProvider(): array
    {
        return [
            '\'' => ['\'', '&#x27;'],
            /* Characters beyond ASCII value 255 to unicode escape */
            'Ā' => ['Ā', '&#x0100;'],
            /* Characters beyond Unicode BMP to unicode escape */
            "\xF0\x90\x80\x80" => ["\xF0\x90\x80\x80", '&#x10000;'],
            /* Immune chars excluded */
            ',' => [',', ','],
            '.' => ['.', '.'],
            '-' => ['-', '-'],
            '_' => ['_', '_'],
            /* Basic alnums exluded */
            'a' => ['a', 'a'],
            'A' => ['A', 'A'],
            'z' => ['z', 'z'],
            'Z' => ['Z', 'Z'],
            '0' => ['0', '0'],
            '9' => ['9', '9'],
            /* Basic control characters and null */
            "\r" => ["\r", '&#x0D;'],
            "\n" => ["\n", '&#x0A;'],
            "\t" => ["\t", '&#x09;'],
            "\0" => ["\0", '&#xFFFD;'], // should use Unicode replacement char
            /* Encode chars as named entities where possible */
            '<' => ['<', '&lt;'],
            '>' => ['>', '&gt;'],
            '&' => ['&', '&amp;'],
            '"' => ['"', '&quot;'],
            /* Encode spaces for quoteless attribute protection */
            ' ' => [' ', '&#x20;'],
        ];
        /*
        foreach ($this->htmlAttrSpecialChars as $string => $encoded) {
            yield $string => [$string, $encoded];
        }
         */
    }

    /**
     * @dataProvider htmlAttrSpecialCharsProvider
     */
    public function testHtmlAttributeEscapingConvertsSpecialChars(string $string, string $encoded): void
    {
        $this->assertEquals($encoded, $this->escaper->escapeHtmlAttr($string), 'Failed to escape: ' . $string);
    }

    /** @psalm-return array<string, array{0: string, 1: string}> */
    public function jsSpecialCharsProvider(): array
    {
        return [
            /* HTML special chars - escape without exception to hex */
            '<'  => ['<', '\\x3C'],
            '>'  => ['>', '\\x3E'],
            '\'' => ['\'', '\\x27'],
            '"'  => ['"', '\\x22'],
            '&'  => ['&', '\\x26'],
            /* Characters beyond ASCII value 255 to unicode escape */
            'Ā' => ['Ā', '\\u0100'],
            /* Characters beyond Unicode BMP to unicode escape */
            "\xF0\x90\x80\x80" => ["\xF0\x90\x80\x80", '\\uD800\\uDC00'],
            /* Immune chars excluded */
            ',' => [',', ','],
            '.' => ['.', '.'],
            '_' => ['_', '_'],
            /* Basic alnums exluded */
            'a' => ['a', 'a'],
            'A' => ['A', 'A'],
            'z' => ['z', 'z'],
            'Z' => ['Z', 'Z'],
            '0' => ['0', '0'],
            '9' => ['9', '9'],
            /* Basic control characters and null */
            "\r" => ["\r", '\\x0D'],
            "\n" => ["\n", '\\x0A'],
            "\t" => ["\t", '\\x09'],
            "\0" => ["\0", '\\x00'],
            /* Encode spaces for quoteless attribute protection */
            ' ' => [' ', '\\x20'],
        ];
    }

    /**
     * @dataProvider jsSpecialCharsProvider
     */
    public function testJavascriptEscapingConvertsSpecialChars(string $string, string $encoded): void
    {
        $this->assertEquals($encoded, $this->escaper->escapeJs($string), 'Failed to escape: ' . $string);
    }

    public function testJavascriptEscapingReturnsStringIfZeroLength()
    {
        $this->assertEquals('', $this->escaper->escapeJs(''));
    }

    public function testJavascriptEscapingReturnsStringIfContainsOnlyDigits()
    {
        $this->assertEquals('123', $this->escaper->escapeJs('123'));
    }

    /** @psalm-return array<string, array{0: string, 1: string}> */
    public function cssSpecialCharsProvider(): array
    {
        return [
            /* HTML special chars - escape without exception to hex */
            '<'  => ['<', '\\3C '],
            '>'  => ['>', '\\3E '],
            '\'' => ['\'', '\\27 '],
            '"'  => ['"', '\\22 '],
            '&'  => ['&', '\\26 '],
            /* Characters beyond ASCII value 255 to unicode escape */
            'Ā' => ['Ā', '\\100 '],
            /* Characters beyond Unicode BMP to unicode escape */
            "\xF0\x90\x80\x80" => ["\xF0\x90\x80\x80", '\\10000 '],
            /* Immune chars excluded */
            ',' => [',', '\\2C '],
            '.' => ['.', '\\2E '],
            '_' => ['_', '\\5F '],
            /* Basic alnums exluded */
            'a' => ['a', 'a'],
            'A' => ['A', 'A'],
            'z' => ['z', 'z'],
            'Z' => ['Z', 'Z'],
            '0' => ['0', '0'],
            '9' => ['9', '9'],
            /* Basic control characters and null */
            "\r" => ["\r", '\\D '],
            "\n" => ["\n", '\\A '],
            "\t" => ["\t", '\\9 '],
            "\0" => ["\0", '\\0 '],
            /* Encode spaces for quoteless attribute protection */
            ' ' => [' ', '\\20 '],
        ];
    }

    /**
     * @dataProvider cssSpecialCharsProvider
     */
    public function testCssEscapingConvertsSpecialChars(string $string, string $encoded): void
    {
        $this->assertEquals($encoded, $this->escaper->escapeCss($string), 'Failed to escape: ' . $string);
    }

    public function testCssEscapingReturnsStringIfZeroLength()
    {
        $this->assertEquals('', $this->escaper->escapeCss(''));
    }

    public function testCssEscapingReturnsStringIfContainsOnlyDigits()
    {
        $this->assertEquals('123', $this->escaper->escapeCss('123'));
    }

    /** @psalm-return array<string, array{0: string, 1: string}> */
    public function urlSpecialCharsProvider(): array
    {
        return [
            /* HTML special chars - escape without exception to percent encoding */
            '<'  => ['<', '%3C'],
            '>'  => ['>', '%3E'],
            '\'' => ['\'', '%27'],
            '"'  => ['"', '%22'],
            '&'  => ['&', '%26'],
            /* Characters beyond ASCII value 255 to hex sequence */
            'Ā' => ['Ā', '%C4%80'],
            /* Punctuation and unreserved check */
            ',' => [',', '%2C'],
            '.' => ['.', '.'],
            '_' => ['_', '_'],
            '-' => ['-', '-'],
            ':' => [':', '%3A'],
            ';' => [';', '%3B'],
            '!' => ['!', '%21'],
            /* Basic alnums excluded */
            'a' => ['a', 'a'],
            'A' => ['A', 'A'],
            'z' => ['z', 'z'],
            'Z' => ['Z', 'Z'],
            '0' => ['0', '0'],
            '9' => ['9', '9'],
            /* Basic control characters and null */
            "\r" => ["\r", '%0D'],
            "\n" => ["\n", '%0A'],
            "\t" => ["\t", '%09'],
            "\0" => ["\0", '%00'],
            /* PHP quirks from the past */
            ' ' => [' ', '%20'],
            '~' => ['~', '~'],
            '+' => ['+', '%2B'],
        ];
    }

    /**
     * @dataProvider urlSpecialCharsProvider
     */
    public function testUrlEscapingConvertsSpecialChars(string $string, string $encoded): void
    {
        $this->assertEquals($encoded, $this->escaper->escapeUrl($string), 'Failed to escape: ' . $string);
    }

    /**
     * Range tests to confirm escaped range of characters is within OWASP recommendation
     */

    /**
     * Only testing the first few 2 ranges on this prot. function as that's all these
     * other range tests require
     */
    public function testUnicodeCodepointConversionToUtf8()
    {
        $expected   = " ~ޙ";
        $codepoints = [0x20, 0x7e, 0x799];
        $result     = '';
        foreach ($codepoints as $value) {
            $result .= $this->codepointToUtf8($value);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * Convert a Unicode Codepoint to a literal UTF-8 character.
     *
     * @param int $codepoint Unicode codepoint in hex notation
     * @return string UTF-8 literal string
     * @throws Exception When codepoint requested is outside Unicode range.
     */
    protected function codepointToUtf8($codepoint)
    {
        if ($codepoint < 0x80) {
            return chr($codepoint);
        }
        if ($codepoint < 0x800) {
            return chr($codepoint >> 6 & 0x3f | 0xc0)
                . chr($codepoint & 0x3f | 0x80);
        }
        if ($codepoint < 0x10000) {
            return chr($codepoint >> 12 & 0x0f | 0xe0)
                . chr($codepoint >> 6 & 0x3f | 0x80)
                . chr($codepoint & 0x3f | 0x80);
        }
        if ($codepoint < 0x110000) {
            return chr($codepoint >> 18 & 0x07 | 0xf0)
                . chr($codepoint >> 12 & 0x3f | 0x80)
                . chr($codepoint >> 6 & 0x3f | 0x80)
                . chr($codepoint & 0x3f | 0x80);
        }
        throw new Exception('Codepoint requested outside of Unicode range');
    }

    /** @psalm-return iterable<string, array{0: string, 1: string}> */
    public function owaspJSRecommendedEscapeRangeProvider(): iterable
    {
        $immune = [',', '.', '_']; // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; $chr++) {
            if (
                $chr >= 0x30 && $chr <= 0x39
                || $chr >= 0x41 && $chr <= 0x5A
                || $chr >= 0x61 && $chr <= 0x7A
            ) {
                $literal = $this->codepointToUtf8($chr);
                yield $literal => [$literal, 'assertEquals'];
                continue;
            }

            $literal = $this->codepointToUtf8($chr);
            if (in_array($literal, $immune)) {
                yield $literal => [$literal, 'assertEquals'];
                continue;
            }

            yield $literal => [$literal, 'assertNotEquals'];
        }
    }

    /**
     * @dataProvider owaspJSRecommendedEscapeRangeProvider
     */
    public function testJavascriptEscapingEscapesOwaspRecommendedRanges(string $value, string $assertion): void
    {
        $this->$assertion($value, $this->escaper->escapeJs($value));
    }

    public function testHtmlAttributeEscapingEscapesOwaspRecommendedRanges()
    {
        $immune = [',', '.', '-', '_']; // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; $chr++) {
            if (
                $chr >= 0x30 && $chr <= 0x39
                || $chr >= 0x41 && $chr <= 0x5A
                || $chr >= 0x61 && $chr <= 0x7A
            ) {
                $literal = $this->codepointToUtf8($chr);
                $this->assertEquals($literal, $this->escaper->escapeHtmlAttr($literal));
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (in_array($literal, $immune)) {
                    $this->assertEquals($literal, $this->escaper->escapeHtmlAttr($literal));
                } else {
                    $this->assertNotEquals(
                        $literal,
                        $this->escaper->escapeHtmlAttr($literal),
                        $literal . ' should be escaped!'
                    );
                }
            }
        }
    }

    /** @psalm-return iterable<string, array{0: string, 1: string}> */
    public function owaspCSSRecommendedEscapeRangeProvider(): iterable
    {
        for ($chr = 0; $chr < 0xFF; $chr++) {
            if (
                $chr >= 0x30 && $chr <= 0x39
                || $chr >= 0x41 && $chr <= 0x5A
                || $chr >= 0x61 && $chr <= 0x7A
            ) {
                $literal = $this->codepointToUtf8($chr);
                yield $literal => [$literal, 'assertEquals'];
                continue;
            }

            $literal = $this->codepointToUtf8($chr);
            yield $literal => [$literal, 'assertNotEquals'];
        }
    }

    /**
     * @dataProvider owaspCSSRecommendedEscapeRangeProvider
     */
    public function testCssEscapingEscapesOwaspRecommendedRanges(string $value, string $assertion): void
    {
        $this->$assertion($value, $this->escaper->escapeCss($value));
    }
}
