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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Requirement::class)]
#[CoversClass(ComparisonRequirement::class)]
#[CoversClass(ConstraintRequirement::class)]
#[UsesClass(VersionComparisonOperator::class)]
#[UsesClass(InvalidVersionRequirementException::class)]
#[Small]
final class RequirementTest extends TestCase
{
    /**
     * @return non-empty-list<array{bool, string, ConstraintRequirement}>
     */
    public static function constraintProvider(): array
    {
        return [
            [true, '1.0.0', new ConstraintRequirement((new VersionConstraintParser)->parse('1.0.0'))],
            [false, '2.0.0', new ConstraintRequirement((new VersionConstraintParser)->parse('1.0.0'))],
            [true, '1.5.0', new ConstraintRequirement((new VersionConstraintParser)->parse('^1.0'))],
            [false, '2.0.0', new ConstraintRequirement((new VersionConstraintParser)->parse('^1.0'))],
            [true, '8.4.1-dev', new ConstraintRequirement((new VersionConstraintParser)->parse('^8.4'))],
        ];
    }

    /**
     * @return non-empty-list<array{bool, string, ComparisonRequirement}>
     */
    public static function comparisonProvider(): array
    {
        return [
            [true, '1.0.0', new ComparisonRequirement('1.0.0', new VersionComparisonOperator('='))],
            [false, '1.0.1', new ComparisonRequirement('1.0.0', new VersionComparisonOperator('='))],
            [true, '1.0.1', new ComparisonRequirement('1.0.0', new VersionComparisonOperator('>='))],
            [false, '0.9.0', new ComparisonRequirement('1.0.0', new VersionComparisonOperator('>='))],
        ];
    }

    public function testCanBeCreatedFromStringWithVersionConstraint(): void
    {
        $requirement = Requirement::from('^1.0');

        $this->assertInstanceOf(ConstraintRequirement::class, $requirement);
        $this->assertSame('^1.0', $requirement->asString());
    }

    #[DataProvider('constraintProvider')]
    public function testVersionRequirementCanBeCheckedUsingVersionConstraint(bool $expected, string $version, ConstraintRequirement $requirement): void
    {
        $this->assertSame($expected, $requirement->isSatisfiedBy($version));
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

    #[DataProvider('comparisonProvider')]
    public function testVersionRequirementCanBeCheckedUsingSimpleComparison(bool $expected, string $version, ComparisonRequirement $requirement): void
    {
        $this->assertSame($expected, $requirement->isSatisfiedBy($version));
    }

    public function testCannotBeCreatedFromInvalidString(): void
    {
        $this->expectException(InvalidVersionRequirementException::class);

        Requirement::from('invalid');
    }
}
