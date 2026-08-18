<?php
declare(strict_types=1);

function calculateTrend(array $positions): string
{
    if (count($positions) < 7) {
        return 'unknown';
    }

    $latest = end($positions)['position'];
    $weekAgo = $positions[count($positions) - 7]['position'];
    $diff = $latest - $weekAgo;

    if ($diff < -2) {
        return 'improved';
    } elseif ($diff > 2) {
        return 'declined';
    }

    return 'stable';
}

function calculatePositionDrift(int $base, int $range = 8): int
{
    $drift = rand(-$range, $range);
    return max(1, min(100, $base + $drift));
}

function generatePositions(int $days, int $startPosition = 30): array
{
    $positions = [];
    $base = $startPosition;
    $date = new DateTime('-' . ($days - 1) . ' days');

    for ($i = 0; $i < $days; $i++) {
        $base = calculatePositionDrift($base);
        $positions[] = [
            'position' => $base,
            'checked_at' => $date->format('Y-m-d'),
        ];
        $date->modify('+1 day');
    }

    return $positions;
}
