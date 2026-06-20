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

use function preg_match;
use PharIo\Version\UnsupportedVersionConstraintException;
use PharIo\Version\VersionConstraintParser;

/**
 * @immutable
 *
 * @no-named-arguments
 */
abstract readonly class Requirement
{
    private const string VERSION_COMPARISON = "/(?P<operator>!=|<|<=|<>|=|==|>|>=)?\s*(?P<version>[\d\.-]+(dev|(RC|alpha|beta)[\d\.])?)[ \t]*\r?$/m";

    /**
     * @throws InvalidVersionOperatorException
     * @throws InvalidVersionRequirementException
     */
    public static function from(string $versionRequirement): self
    {
        try {
            return new ConstraintRequirement(
                (new VersionConstraintParser)->parse(
                    $versionRequirement,
                ),
            );
        } catch (UnsupportedVersionConstraintException) {
            if (preg_match(self::VERSION_COMPARISON, $versionRequirement, $matches) > 0) {
                return new ComparisonRequirement(
                    $matches['version'],
                    new VersionComparisonOperator(
                        $matches['operator'] !== '' ? $matches['operator'] : '>=',
                    ),
                );
            }
        }

        throw new InvalidVersionRequirementException;
    }

    abstract public function isSatisfiedBy(string $version): bool;

    abstract public function asString(): string;
}
