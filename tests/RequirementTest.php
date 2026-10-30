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

#[CoversClass(Requirement::class)]
#[CoversClass(ComparisonRequirement::class)]
#[CoversClass(InvalidVersionRequirementException::class)]
#[CoversClass(VersionConstraintParser::class)]
#[UsesClass(ConstraintRequirement::class)]
#[UsesClass(VersionRange::class)]
#[UsesClass(Version::class)]
#[UsesClass(VersionComparisonOperator::class)]
#[Small]
final class RequirementTest extends TestCase
{
    /**
     * @return non-empty-list<array{non-empty-string, string, string, bool}>
     */
    public static function comparisonProvider(): array
    {
        return [
            ['1.0.0', '=', '1.0.0', true],
            ['1.0.0', '=', '1.0.1', false],
            ['1.0.0', '>=', '1.0.1', true],
            ['1.0.0', '>=', '0.9.0', false],
        ];
    }

    /**
     * @return non-empty-list<array{non-empty-string, bool}>
     */
    public static function completenessProvider(): array
    {
        return [
            ['>= 8.5.0', true],
            ['>= 8.5', false],
            ['>= 8', false],
            ['<= 8', false],
            ['8.5.0', true],
            ['8.5', true],
            ['^8.5', true],
            ['~8.5.0', true],
            ['8.5.*', true],
            ['8.*', true],
        ];
    }

    /**
     * @return non-empty-list<array{non-empty-string}>
     */
    public static function invalidProvider(): array
    {
        return [
            ['invalid'],
            ['foo >= 1.0'],
            ['^8.4 || >= 9'],
            ['^8.4 '],
            [' ^8.4'],
            ["1.0\nfoo"],
            ['>= 8.5.0Rc1'],
            ['>= 8.5.0BETA1'],
        ];
    }

    /**
     * @return non-empty-list<array{non-empty-string, non-empty-string}>
     */
    public static function preReleaseComparisonProvider(): array
    {
        return [
            ['>= 8.5.0RC1', '8.5.0RC1'],
            ['>= 8.5.0RC12', '8.5.0RC12'],
            ['>= 8.5.0rc1', '8.5.0rc1'],
            ['>= 8.5.0alpha12', '8.5.0alpha12'],
            ['>= 8.5.0beta2', '8.5.0beta2'],
            ['>= 8.5.0dev', '8.5.0dev'],
        ];
    }

    public function testCanBeCreatedFromStringWithVersionConstraint(): void
    {
        $requirement = Requirement::from('^1.0');

        $this->assertInstanceOf(ConstraintRequirement::class, $requirement);
        $this->assertSame('^1.0', $requirement->asString());
    }

    public function testCanBeCreatedFromStringWithSimpleComparison(): void
    {
        $requirement = Requirement::from('>= 1.0');

        $this->assertInstanceOf(ComparisonRequirement::class, $requirement);
        $this->assertSame('>= 1.0', $requirement->asString());
        $this->assertSame('1.0', $requirement->version());
    }

    public function testUsesGreaterThanOrEqualWhenComparisonHasNoOperator(): void
    {
        $requirement = Requirement::from('1.0.0dev');

        $this->assertInstanceOf(ComparisonRequirement::class, $requirement);
        $this->assertSame('>= 1.0.0dev', $requirement->asString());
        $this->assertSame('1.0.0dev', $requirement->version());
    }

    /**
     * @param non-empty-string $versionRequirement
     * @param non-empty-string $version
     */
    #[DataProvider('preReleaseComparisonProvider')]
    #[TestDox('Can be created from string "$versionRequirement" with pre-release version')]
    public function testCanBeCreatedFromStringWithPreReleaseVersion(string $versionRequirement, string $version): void
    {
        $requirement = Requirement::from($versionRequirement);

        $this->assertInstanceOf(ComparisonRequirement::class, $requirement);
        $this->assertSame($version, $requirement->version());
    }

    /**
     * @param non-empty-string $versionRequirement
     */
    #[DataProvider('invalidProvider')]
    #[TestDox('Cannot be created from invalid string "$versionRequirement"')]
    public function testCannotBeCreatedFromInvalidString(string $versionRequirement): void
    {
        $this->expectException(InvalidVersionRequirementException::class);
        $this->expectExceptionMessage('"' . $versionRequirement . '" is not a valid version requirement: expected a version constraint (such as "^8.1", "~8.1.0", or "8.1.*") or a version comparison (such as ">= 8.1.0")');

        Requirement::from($versionRequirement);
    }

    public function testCannotBeCreatedFromStringWithInvalidVersionConstraint(): void
    {
        $this->expectException(InvalidVersionRequirementException::class);
        $this->expectExceptionMessage('"^8.*" is not a valid version requirement: expected a version constraint (such as "^8.1", "~8.1.0", or "8.1.*") or a version comparison (such as ">= 8.1.0")');

        Requirement::from('^8.*');
    }

    /**
     * @param non-empty-string $versionRequirement
     */
    #[DataProvider('completenessProvider')]
    #[TestDox('Version requirement "$versionRequirement" is complete: $complete')]
    public function testCanBeCheckedForCompleteness(string $versionRequirement, bool $complete): void
    {
        $this->assertSame($complete, Requirement::from($versionRequirement)->isComplete());
    }

    /**
     * @param non-empty-string $requiredVersion
     */
    #[DataProvider('comparisonProvider')]
    #[TestDox('Comparison "$operator $requiredVersion" is satisfied by version "$version": $satisfied')]
    public function testSimpleComparisonCanBeCheckedAgainstVersion(string $requiredVersion, string $operator, string $version, bool $satisfied): void
    {
        $requirement = new ComparisonRequirement(
            $requiredVersion,
            new VersionComparisonOperator($operator),
        );

        $this->assertSame($satisfied, $requirement->isSatisfiedBy($version));
    }
}
