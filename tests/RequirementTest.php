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

use PharIo\Version\VersionConstraintParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Requirement::class)]
#[CoversClass(ComparisonRequirement::class)]
#[CoversClass(ConstraintRequirement::class)]
#[CoversClass(InvalidVersionRequirementException::class)]
#[UsesClass(VersionComparisonOperator::class)]
#[Small]
final class RequirementTest extends TestCase
{
    /**
     * @return non-empty-list<array{string, string, bool}>
     */
    public static function constraintProvider(): array
    {
        return [
            ['1.0.0', '1.0.0', true],
            ['1.0.0', '2.0.0', false],
            ['^1.0', '1.5.0', true],
            ['^1.0', '2.0.0', false],
            ['^8.4', '8.4.1-dev', true],
        ];
    }

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

    public function testCannotBeCreatedFromInvalidString(): void
    {
        $this->expectException(InvalidVersionRequirementException::class);
        $this->expectExceptionMessage('"invalid" is not a valid version requirement: expected a version constraint (such as "^8.1", "~8.1.0", or "8.1.*") or a version comparison (such as ">= 8.1.0")');

        Requirement::from('invalid');
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

    #[DataProvider('constraintProvider')]
    #[TestDox('Version constraint "$constraint" is satisfied by version "$version": $satisfied')]
    public function testVersionConstraintCanBeCheckedAgainstVersion(string $constraint, string $version, bool $satisfied): void
    {
        $requirement = new ConstraintRequirement(
            (new VersionConstraintParser)->parse($constraint),
        );

        $this->assertSame($satisfied, $requirement->isSatisfiedBy($version));
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
