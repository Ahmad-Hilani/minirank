<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers.php';

class PositionGenerationTest extends TestCase
{
    public function testPositionDriftStaysWithinBounds(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $result = calculatePositionDrift(1);
            $this->assertGreaterThanOrEqual(1, $result);
            $this->assertLessThanOrEqual(100, $result);
        }
    }

    public function testPositionDriftAtUpperBound(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $result = calculatePositionDrift(100);
            $this->assertGreaterThanOrEqual(1, $result);
            $this->assertLessThanOrEqual(100, $result);
        }
    }

    public function testGeneratePositionsReturnsCorrectCount(): void
    {
        $positions = generatePositions(30);
        $this->assertCount(30, $positions);
    }

    public function testGeneratePositionsHasValidDates(): void
    {
        $positions = generatePositions(5);
        $dates = array_column($positions, 'checked_at');

        $expected = [];
        $date = new DateTime('-4 days');
        for ($i = 0; $i < 5; $i++) {
            $expected[] = $date->format('Y-m-d');
            $date->modify('+1 day');
        }

        $this->assertEquals($expected, $dates);
    }

    public function testGeneratePositionsAllWithinRange(): void
    {
        $positions = generatePositions(30, 50);
        foreach ($positions as $pos) {
            $this->assertGreaterThanOrEqual(1, $pos['position']);
            $this->assertLessThanOrEqual(100, $pos['position']);
        }
    }

    public function testGeneratePositionsRespectsStartValue(): void
    {
        $positions = generatePositions(1, 42);
        $this->assertEquals(1, count($positions));
        $this->assertGreaterThanOrEqual(1, $positions[0]['position']);
        $this->assertLessThanOrEqual(100, $positions[0]['position']);
    }
}
