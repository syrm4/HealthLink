<?php
// HealthLink — Shared UI helper functions

/**
 * Returns a CSS class name based on an AI priority score (1–10).
 * Used to colour-code priority bars in the staff and admin views.
 */
function priorityClass(int $score): string {
    if ($score >= 9) return 'priority-urgent';
    if ($score >= 7) return 'priority-high';
    if ($score >= 5) return 'priority-medium';
    return 'priority-low';
}
