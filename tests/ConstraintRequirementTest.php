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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConstraintRequirement::class)]
#[CoversClass(VersionConstraintParser::class)]
#[CoversClass(VersionRange::class)]
#[CoversClass(Version::class)]
#[UsesClass(InvalidVersionRequirementException::class)]
#[Small]
final class ConstraintRequirementTest extends TestCase
{
    /**
     * @return non-empty-list<array{non-empty-string, string, bool}>
     */
    public static function constraintProvider(): array
    {
        return [
            ['1.0.0', '1.0.0', true],
            ['1.0.0', '1.0.1', false],
            ['1.0.0', '0.9.9', false],
            ['v1.0.0', '1.0.0', true],
            ['V1.0.0', '1.0.0', true],
            ['1.0.0-dev', '1.0.0', true],

            ['8', '8.0.0', true],
            ['8', '8.9.9', true],
            ['8', '7.9.9', false],
            ['8', '9.0.0', false],
            ['8.*', '8.5.0', true],
            ['8.*', '9.0.0', false],
            ['8.*.*', '8.5.0', true],
            ['8.5', '8.5.9', true],
            ['8.5', '8.4.9', false],
            ['8.5', '8.6.0', false],
            ['8.5.*', '8.5.3', true],
            ['8.5.*', '8.6.0', false],
            ['V8.5.*', '9.0.0', false],
            ['v2.*', '2.9.0', true],
            ['v2.*', '3.0.0', false],
            ['20.*', '20.5.0', true],
            ['20.*', '21.0.0', false],
            ['20.*.*', '20.5.0', true],
            ['2.0.*', '2.0.5', true],
            ['2.0.*', '2.1.0', false],
            ['0.*', '0.0.0', true],
            ['0.*', '1.0.0', false],
            ['0.*.*', '0.9.0', true],
            ['*', '0.0.0', true],
            ['*', '99.0.0', true],
            ['*', 'v1.2.3', true],
            ['*.*', '1.0.0', true],

            ['^1.0', '1.5.0', true],
            ['^1.0', '2.0.0', false],
            ['^8.4', '8.4.0', true],
            ['^8.4', '8.3.9', false],
            ['^8.4.1', '8.4.0', false],
            ['^8.4.1', '8.4.1', true],
            ['^8', '8.0.0', true],
            ['^8', '9.0.0', false],
            ['^v8.4', '8.4.0', true],
            ['^v1', '1.9.9', true],
            ['^v1', '2.0.0', false],
            ['^1.2.3', '1.2.2', false],
            ['^1.2.3', '1.8.1', true],
            ['^1.2.3', '2.0.0', false],
            ['^1.2.3-beta.2', '1.2.3', true],
            ['^201903.0', '201903.205830.1', true],
            ['^201903.0', '201904.0.0', false],
            ['^0.3', '0.3.9', true],
            ['^0.3', '0.4.0', false],
            ['^0.2.3', '0.2.2', false],
            ['^0.2.3', '0.2.9', true],
            ['^0.2.3', '0.3.0', false],
            ['^0.0', '0.0.9', true],
            ['^0.0', '0.1.0', false],
            ['^0.0.3', '0.0.2', false],
            ['^0.0.3', '0.0.3', true],
            ['^0.0.3', '0.0.4', false],
            ['^0.0.0', '0.0.0', true],
            ['^0.0.0', '0.0.1', false],
            ['^0', '0.9.0', true],
            ['^0', '1.0.0', false],

            ['~8.5.1', '8.5.0', false],
            ['~8.5.1', '8.5.9', true],
            ['~8.5.1', '8.6.0', false],
            ['~8.5', '8.4.9', false],
            ['~8.5', '8.9.0', true],
            ['~8.5', '9.0.0', false],
            ['~8', '8.9.0', true],
            ['~8', '9.0.0', false],
            ['~v1', '1.9.9', true],
            ['~v1', '2.0.0', false],
            ['~1.0', '1.4.7', true],
            ['~1.0', '2.0.0', false],
            ['~1.0.0', '1.0.9', true],
            ['~1.0.0', '1.1.0', false],
            ['~1.2-beta', '1.2.0', true],
            ['~201903.205830.1-stable', '201903.205830.5', true],
            ['~201903.205830.1-stable', '201903.205831.0', false],
            ['~0.3', '0.3.9', true],
            ['~0.3', '0.4.0', false],
            ['~0.3.1', '0.3.5', true],
            ['~0.3.1', '0.4.0', false],

            ['^7.4 || ^8.0', '7.3.0', false],
            ['^7.4 || ^8.0', '7.4.0', true],
            ['^7.4 || ^8.0', '8.1.0', true],
            ['^7.4 || ^8.0', '9.0.0', false],
            ['^7.4 | ^8.0', '8.1.0', true],
            ['^8.4 || ^9', '9.1.0', true],
            ['1.2.* || 2.*', '1.1.3', false],
            ['1.2.* || 2.*', '1.2.3', true],
            ['1.2.* || 2.*', '2.1.3', true],
            ['1.2.* || 2.*', '3.1.3', false],
            ['0.1.20 || 1.2.4', '1.2.3', false],
            ['0.1.20 || 1.2.4', '1.2.4', true],

            ['^8.4', '8.4.1-dev', true],
            ['^8.4', '8.4.1RC1', true],
            ['^8.4', '8.4.1+build', true],
            ['^8.4', 'v8.4.1', true],
            ['^8.4', '8.4', true],
            ['^8', '8', true],
            ['^8.4', 'foo', false],
            ['^8.4', '', false],
        ];
    }

    /**
     * @return non-empty-list<array{non-empty-string}>
     */
    public static function invalidConstraintProvider(): array
    {
        return [
            ['^8.*'],
            ['~8.5.*'],
            ['^*'],
            ['*8'],
            ['*.5'],
            ['8.*.1'],
            ['8.5.0.1'],
            ['8..5'],
            ['8.'],
            ['^8.4 || ^9.*'],
            ['>= 8.4'],
            ['^'],
            ['~'],
            ['~1 ~'],
            ['~2.*'],
            ['^2.1.2.*'],
            ['^1.*-beta-dev'],
            ['^1. *-dev'],
            ['^8 || ^'],
            ['|| ^8'],
            ['^8 ||'],
            ['^1.2 ^1'],
            ['1.0.0 - 2.0.0'],
        ];
    }

    /**
     * @param non-empty-string $constraint
     */
    #[DataProvider('constraintProvider')]
    #[TestDox('Version constraint "$constraint" is satisfied by version "$version": $satisfied')]
    public function testCanBeCheckedAgainstVersion(string $constraint, string $version, bool $satisfied): void
    {
        $this->assertSame($satisfied, new ConstraintRequirement($constraint)->isSatisfiedBy($version));
    }

    /**
     * @param non-empty-string $constraint
     */
    #[DataProvider('invalidConstraintProvider')]
    #[TestDox('Cannot be created from invalid version constraint "$constraint"')]
    public function testCannotBeCreatedFromInvalidVersionConstraint(string $constraint): void
    {
        $this->expectException(InvalidVersionRequirementException::class);

        new ConstraintRequirement($constraint);
    }

    public function testCanBeRepresentedAsAString(): void
    {
        $this->assertSame('^7.4 || ^8.0', new ConstraintRequirement('^7.4 || ^8.0')->asString());
    }
}
