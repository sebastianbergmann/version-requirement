<?php declare(strict_types=1);
/*
 * This file is part of sebastian/version-requirement.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\VersionRequirement;

use function version_compare;

/**
 * @immutable
 *
 * @no-named-arguments
 */
final readonly class ComparisonRequirement extends Requirement
{
    /**
     * @var non-empty-string
     */
    private string $version;
    private VersionComparisonOperator $operator;

    /**
     * @param non-empty-string $version
     */
    public function __construct(string $version, VersionComparisonOperator $operator)
    {
        $this->version  = $version;
        $this->operator = $operator;
    }

    public function isSatisfiedBy(string $version): bool
    {
        return version_compare($version, $this->version, $this->operator->asString());
    }

    /**
     * @return non-empty-string
     */
    public function asString(): string
    {
        return $this->operator->asString() . ' ' . $this->version;
    }

    /**
     * @return non-empty-string
     */
    public function version(): string
    {
        return $this->version;
    }
}
