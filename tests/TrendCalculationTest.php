<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers.php';

class TrendCalculationTest extends TestCase
{
    public function testImprovedTrend(): void
    {
        $positions = [];
        $date = new DateTime('-29 days');
        for ($i = 0; $i < 30; $i++) {
            $positions[] = [
                'position' => 50 - $i,
                'checked_at' => $date->format('Y-m-d'),
            ];
            $date->modify('+1 day');
        }

        $this->assertEquals('improved', calculateTrend($positions));
    }

    public function testDeclinedTrend(): void
    {
        $positions = [];
        $date = new DateTime('-29 days');
        for ($i = 0; $i < 30; $i++) {
            $positions[] = [
                'position' => 10 + $i,
                'checked_at' => $date->format('Y-m-d'),
            ];
            $date->modify('+1 day');
        }

        $this->assertEquals('declined', calculateTrend($positions));
    }

    public function testStableTrend(): void
    {
        $positions = [];
        $date = new DateTime('-29 days');
        for ($i = 0; $i < 30; $i++) {
            $positions[] = [
                'position' => 25,
                'checked_at' => $date->format('Y-m-d'),
            ];
            $date->modify('+1 day');
        }

        $this->assertEquals('stable', calculateTrend($positions));
    }

    public function testUnknownTrendWithFewerThan7Days(): void
    {
        $positions = [
            ['position' => 10, 'checked_at' => '2026-08-01'],
            ['position' => 12, 'checked_at' => '2026-08-02'],
            ['position' => 11, 'checked_at' => '2026-08-03'],
        ];

        $this->assertEquals('unknown', calculateTrend($positions));
    }

    public function testTrendBoundaryImproved(): void
    {
        $positions = [];
        $date = new DateTime('-29 days');
        for ($i = 0; $i < 30; $i++) {
            if ($i < 23) {
                $pos = 30;
            } elseif ($i === 23) {
                $pos = 30;
            } else {
                $pos = 25;
            }
            $positions[] = [
                'position' => $pos,
                'checked_at' => $date->format('Y-m-d'),
            ];
            $date->modify('+1 day');
        }

        $this->assertEquals('improved', calculateTrend($positions));
    }

    public function testTrendBoundaryStable(): void
    {
        $positions = [];
        $date = new DateTime('-29 days');
        for ($i = 0; $i < 30; $i++) {
            $positions[] = [
                'position' => $i < 23 ? 30 : 31,
                'checked_at' => $date->format('Y-m-d'),
            ];
            $date->modify('+1 day');
        }

        $this->assertEquals('stable', calculateTrend($positions));
    }
}
