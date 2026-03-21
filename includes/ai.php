<?php
// HealthLink — OpenAI GPT-4o Integration
// W3Schools best practices: credentials via getenv(), never hardcoded
// Set OPENAI_API_KEY as an environment variable in MAMP or your server config.

define('OPENAI_MODEL', 'gpt-4o');

/**
 * Classify a request using GPT-4o.
 * Falls back to rule-based classification if no API key is set.
 */
function classify_request(array $request, bool $in_service_area): array {
    $apiKey = getenv('OPENAI_API_KEY') ?: '';
    if (!$apiKey) {
        return rule_based_classify($request, $in_service_area);
    }

    $prompt   = build_classification_prompt($request, $in_service_area);
    $response = openai_chat([
        [
            'role'    => 'system',
            'content' => 'You are an AI assistant for HealthLink, Intermountain Healthcare\'s Community Health Request Management System. '
                       . 'Classify incoming requests, assign a priority score, recommend a fulfillment pathway, and flag anything needing attention. '
                       . 'Always respond with valid JSON only. No markdown, no extra text.',
        ],
        [
            'role'    => 'user',
            'content' => $prompt,
        ],
    ]);

    $json = json_decode($response, true);
    if (!$json) {
        return rule_based_classify($request, $in_service_area);
    }
    return $json;
}

function build_classification_prompt(array $r, bool $in_service_area): string {
    $area = $in_service_area
        ? 'YES — within Salt Lake Valley service area'
        : 'NO — outside service area';

    $type_labels = [
        'mailing'          => 'Mailing of education materials or safety devices',
        'presentation'     => 'In-Person or Virtual Presentation',
        'inperson_support' => 'Community Health In-Person Support at event with education materials or safety devices',
    ];
    $type_label = $type_labels[$r['request_type']] ?? $r['request_type'];

    return <<<PROMPT
Classify this community health request and respond ONLY with a JSON object.

Request details:
- Event: {$r['event_name']}
- Date: {$r['event_date']}
- Location: {$r['city']}, {$r['zip_code']}
- In service area: {$area}
- Estimated attendees: {$r['estimated_attendees']}
- Audience: {$r['audience_type']}
- Support requested: {$type_label}
- Material category: {$r['material_category']}
- Notes: {$r['notes']}

Respond with exactly this JSON structure:
{
  "classification": "one sentence describing the request type and context",
  "priority_score": <integer 1-10, where 10 is most urgent>,
  "routing_recommendation": "one of: Mailing | In-person support | Presentation | Virtual presentation",
  "flags": "comma-separated flags if any (e.g. Outside service area, High attendance, Spanish materials needed), or null",
  "in_service_area": <true or false>
}
PROMPT;
}

/** Rule-based fallback when no API key is available. */
function rule_based_classify(array $r, bool $in_service_area): array {
    $att   = (int) ($r['estimated_attendees'] ?? 0);
    $score = 5;
    if ($att > 200)      $score += 3;
    elseif ($att > 100)  $score += 2;
    elseif ($att > 50)   $score += 1;
    if (!$in_service_area)                  $score += 2;
    if ($r['request_type'] === 'inperson_support') $score += 1;
    $score = min(10, $score);

    $flags = [];
    if (!$in_service_area) $flags[] = 'Outside service area — auto-routed to mailing';
    if ($att > 200)        $flags[] = 'Very high attendance — prioritize';

    $routing_map = [
        'mailing'          => 'Mailing',
        'presentation'     => 'Presentation',
        'inperson_support' => 'In-person support',
    ];

    return [
        'classification'         => ucfirst($r['request_type']) . ' request for ' . $r['audience_type'] . ' in ' . $r['city'],
        'priority_score'         => $score,
        'routing_recommendation' => $routing_map[$r['request_type']] ?? 'Mailing',
        'flags'                  => empty($flags) ? null : implode('; ', $flags),
        'in_service_area'        => $in_service_area,
    ];
}

/** Check if a zip code is within the service area. */
function check_service_area(string $zip, PDO $pdo): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service_area_zips WHERE zip_code = ?');
    $stmt->execute([$zip]);
    return (bool) $stmt->fetchColumn();
}

/** Send messages to OpenAI chat completions and return the text response. */
function openai_chat(array $messages): string {
    $apiKey = getenv('OPENAI_API_KEY') ?: '';
    $payload = json_encode([
        'model'       => OPENAI_MODEL,
        'messages'    => $messages,
        'temperature' => 0.2,
        'max_tokens'  => 400,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result, true);
    return $data['choices'][0]['message']['content'] ?? '{}';
}
