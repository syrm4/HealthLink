<?php
// HealthLink — OpenAI GPT-4o Integration

define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY_HERE'); // Replace with your key
define('OPENAI_MODEL',   'gpt-4o');

/**
 * Classify a request using GPT-4o.
 * Returns array with classification, priority_score, routing, flags, in_service_area.
 */
function classify_request(array $request, bool $in_service_area): array {
    $prompt = build_classification_prompt($request, $in_service_area);

    $response = openai_chat([
        [
            'role'    => 'system',
            'content' => 'You are an AI assistant for HealthLink, a community health request management system. '
                       . 'Your job is to classify incoming requests, assign a priority score, recommend a fulfillment pathway, '
                       . 'and flag anything that needs special attention. Always respond with valid JSON only. No markdown, no explanation.'
        ],
        [
            'role'    => 'user',
            'content' => $prompt
        ]
    ]);

    $json = json_decode($response, true);
    if (!$json) {
        return [
            'classification'          => 'Unable to classify',
            'priority_score'          => 5,
            'routing_recommendation'  => $request['request_type'],
            'flags'                   => null,
            'in_service_area'         => $in_service_area,
        ];
    }
    return $json;
}

function build_classification_prompt(array $r, bool $in_service_area): string {
    $area  = $in_service_area ? 'YES — within Salt Lake Valley service area' : 'NO — outside service area';
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

Respond with this exact JSON structure:
{
  "classification": "one sentence describing the request type and context",
  "priority_score": <integer 1-10, where 10 is most urgent>,
  "routing_recommendation": "one of: Mailing | In-person support | Presentation | Virtual presentation",
  "flags": "comma-separated list of flags if any (e.g. Outside service area, High attendance, Spanish materials needed, Safety devices, Multi-site event), or null if none",
  "in_service_area": <true or false>
}
PROMPT;
}

function check_service_area(string $zip, PDO $pdo): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM service_area_zips WHERE zip_code = ?');
    $stmt->execute([$zip]);
    return (bool) $stmt->fetchColumn();
}

function openai_chat(array $messages): string {
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
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result, true);
    return $data['choices'][0]['message']['content'] ?? '{}';
}
