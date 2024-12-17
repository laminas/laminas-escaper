<?php

declare(strict_types=1);

namespace LaminasTest\Escaper\StaticAnalysis;

use Laminas\Escaper\Escaper;

/** @psalm-suppress UnusedClass */
final class EscaperReturnTypeChecks
{
    public function __construct(private readonly Escaper $escaper)
    {
    }

    /** @return non-empty-string */
    public function escapeHtmlReturnsNonEmptyString(): string
    {
        return $this->escaper->escapeHtml('Not Empty');
    }

    public function escapeHtmlReturnsEmptyString(): string
    {
        return $this->escaper->escapeHtml('');
    }

    /** @return non-empty-string */
    public function escapeJsReturnsNonEmptyString(): string
    {
        return $this->escaper->escapeJs('Not Empty');
    }

    public function escapeJsReturnsEmptyString(): string
    {
        return $this->escaper->escapeJs('');
    }

    /** @return non-empty-string */
    public function escapeCssReturnsNonEmptyString(): string
    {
        return $this->escaper->escapeCss('Not Empty');
    }

    public function escapeCssReturnsEmptyString(): string
    {
        return $this->escaper->escapeCss('');
    }

    /** @return non-empty-string */
    public function escapeAttributeReturnsNonEmptyString(): string
    {
        return $this->escaper->escapeHtmlAttr('Not Empty');
    }

    public function escapeAttributeReturnsEmptyString(): string
    {
        return $this->escaper->escapeHtmlAttr('');
    }
}
