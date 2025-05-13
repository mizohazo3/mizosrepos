<?php
// includes/level_functions.php

/**
 * Determines the level achieved based on total hours, level definitions, and difficulty.
 *
 * @param float $totalHours The total accumulated hours for the timer.
 * @param array $levels_config_map Associative array map of level definitions (level => data).
 * @param float $difficulty_multiplier The current difficulty multiplier.
 * @return int The highest level achieved.
 */
function getLevelForHours($totalHours, $levels_config_map, $difficulty_multiplier) {
    $achieved_level = 1; // Default to level 1

    if (!is_array($levels_config_map) || empty($levels_config_map)) {
        return 1; // Return default level on error
    }

    // Ensure levels are sorted by level number (map keys should be numeric)
    ksort($levels_config_map, SORT_NUMERIC);

    foreach ($levels_config_map as $level_num => $level_def) {
        if (!isset($level_def['hours_required'])) {
            continue;
        }

        $base_hours_req = (float)$level_def['hours_required'];
        // Level 1 always requires 0 effective hours
        $effective_hours_required = ($level_num == 1) ? 0.0 : round($base_hours_req * (float)$difficulty_multiplier, 6); // Use precision

        // Use a small tolerance if needed, but >= should generally work
        // $tolerance = 0.000001;
        // if ($totalHours >= ($effective_hours_required - $tolerance)) {
        if ($totalHours >= $effective_hours_required) {
            $achieved_level = max($achieved_level, (int)$level_num);
        } else {
            // Since levels are sorted, we can stop checking once a requirement isn't met
            break;
        }
    }
    return $achieved_level;
}
?>