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

use const PREG_UNMATCHED_AS_NULL;
use function array_map;
use function explode;
use function in_array;
use function preg_match;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Parses version constraints such as "^8.1", "~8.1.0", "8.1.*", or "^7.4 || ^8.0"
 * into the ranges of versions that satisfy them.
 *
 * @immutable
 *
 * @no-named-arguments
 *
 * @internal This class is not covered by the backward compatibility promise for sebastian/version-requirement
 */
final readonly class VersionConstraintParser
{
    private const string SYNTAX     = '/^[\^~*]?v?[\d.*]+(?:-.*)?$/i';
    private const string CONSTRAINT = '/^(?P<operator>[\^~])?v?(?P<major>\d+|\*)(?:\.(?P<minor>\d+|\*)(?:\.(?P<patch>\d+|\*))?)?(?:-.*)?$/i';

    /**
     * Returns true when the given string uses the syntax of a version constraint,
     * even when it is not a valid one ("^8.*", for example), and false when it
     * does not (">= 8.1", for example).
     */
    public function looksLikeVersionConstraint(string $constraint): bool
    {
        foreach ($this->alternatives($constraint) as $alternative) {
            if (preg_match(self::SYNTAX, $alternative) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws InvalidVersionRequirementException
     *
     * @return non-empty-list<VersionRange>
     */
    public function parse(string $constraint): array
    {
        $ranges = [];

        foreach ($this->alternatives($constraint) as $alternative) {
            $ranges[] = $this->parseAlternative($alternative) ?? throw new InvalidVersionRequirementException($constraint);
        }

        return $ranges;
    }

    /**
     * @return non-empty-list<string>
     */
    private function alternatives(string $constraint): array
    {
        if (!str_contains($constraint, '|')) {
            return [$constraint];
        }

        return array_map(trim(...), explode('|', str_replace('||', '|', $constraint)));
    }

    private function parseAlternative(string $constraint): ?VersionRange
    {
        if (preg_match(self::CONSTRAINT, $constraint, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $operator = $matches['operator'];
        $major    = $matches['major'];
        $minor    = $matches['minor'];
        $patch    = $matches['patch'];

        if ($operator !== null && in_array('*', [$major, $minor, $patch], true)) {
            return null;
        }

        // "8.5.*" means the same as "8.5", "8.*" means the same as "8", and "*" matches any version
        if ($patch === '*') {
            $patch = null;
        }

        if ($minor === '*' && $patch === null) {
            $minor = null;
        }

        if ($major === '*' && $minor === null) {
            return new VersionRange(new Version(0, 0, 0), null);
        }

        // A wildcard that is followed by a version number, such as "8.*.1"
        if ($major === '*' || $minor === '*') {
            return null;
        }

        return $this->range(
            $operator,
            (int) $major,
            $minor !== null ? (int) $minor : null,
            $patch !== null ? (int) $patch : null,
        );
    }

    private function range(?string $operator, int $major, ?int $minor, ?int $patch): VersionRange
    {
        // "8", "^8", and "~8" are satisfied by 8.x.y
        if ($minor === null) {
            return new VersionRange(new Version($major, 0, 0), new Version($major + 1, 0, 0));
        }

        $lowerBound = new Version($major, $minor, $patch ?? 0);

        // "^8.5" is satisfied by 8.x.y from 8.5.0, but "^0.5" only by 0.5.y; "~8.5" means the same as "^8.5"
        if ($operator === '^' || ($operator === '~' && $patch === null)) {
            return new VersionRange(
                $lowerBound,
                $major === 0 ? new Version(0, $minor + 1, 0) : new Version($major + 1, 0, 0),
            );
        }

        // "~8.5.1" is satisfied by 8.5.y from 8.5.1, "8.5" by 8.5.y
        if ($operator === '~' || $patch === null) {
            return new VersionRange($lowerBound, new Version($major, $minor + 1, 0));
        }

        return new VersionRange($lowerBound, new Version($major, $minor, $patch + 1));
    }
}
