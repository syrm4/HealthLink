<?php
require_once __DIR__ . '/../config/db.php';

function classifyAndUpdateRequest(array $data): void {
    $apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
    if (!$apiKey) { ruleBasedClassify($data); return; }

    $typeLabel = ['mailing'=>'Mailing of education materials or safety devices','presentation'=>'In-Person or Virtual Presentation','inperson_support'=>'Community Health In-Person Support at event with Education materials or safety devices'][$data['request_type']] ?? $data['request_type'];
    $inArea = $data['in_service_area'] ? 'Yes' : 'No';

    $prompt = "You are an intelligent request classifier for HealthLink, Intermountain Healthcare's Community Health Request Management System in Salt Lake Valley, Utah.\n\nAnalyze this request and respond ONLY with valid JSON (no markdown, no extra text):\n\nEvent: {$data['event_name']}\nDate: {$data['event_date']}\nLocation: {$data['city']}, {$data['zip_code']}\nAttendees: {$data['estimated_attendees']}\nAudience: {$data['audience_type']}\nRequest type: {$typeLabel}\nMaterials: {$data['material_category']}\nNotes: {$data['notes']}\nIn service area: {$inArea}\n\nRespond with exactly:\n{\n  \"classification\": \"brief 1-sentence description\",\n  \"priority_score\": <1-10 integer>,\n  \"routing_recommendation\": \"Mailing\" or \"In-person presentation\" or \"In-person support\",\n  \"flags\": \"concerns or null\",\n  \"reasoning\": \"1-2 sentence explanation\"\n}";

    $payload = json_encode(['model'=>'claude-sonnet-4-6','max_tokens'=>512,'messages'=>[['role'=>'user','content'=>$prompt]]]);
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload, CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01'], CURLOPT_TIMEOUT=>15]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) { ruleBasedClassify($data); return; }
    $decoded = json_decode($response, true);
    $text = preg_replace('/```json\s*|\s*```/', '', trim($decoded['content'][0]['text'] ?? ''));
    $c = json_decode($text, true);
    if (!$c || !isset($c['priority_score'])) { ruleBasedClassify($data); return; }
    updateClassification($data['id'], $c);
}

function ruleBasedClassify(array $data): void {
    $att = (int)($data['estimated_attendees'] ?? 0);
    $score = 5;
    if ($att > 200) $score += 3; elseif ($att > 100) $score += 2; elseif ($att > 50) $score += 1;
    if (!$data['in_service_area']) $score += 2;
    if ($data['request_type'] === 'inperson_support') $score += 1;
    $score = min(10, $score);
    $flags = [];
    if (!$data['in_service_area']) $flags[] = 'Outside service area — auto-routed to mailing';
    if ($att > 200) $flags[] = 'Very high attendance — prioritize';
    $routing = ['mailing'=>'Mailing','presentation'=>'In-person presentation','inperson_support'=>'In-person support'][$data['request_type']] ?? 'Mailing';
    updateClassification($data['id'], ['classification'=>ucfirst($data['request_type']).' request for '.$data['audience_type'].' in '.$data['city'],'priority_score'=>$score,'routing_recommendation'=>$routing,'flags'=>empty($flags)?null:implode('; ',$flags),'reasoning'=>'Rule-based classification']);
}

function updateClassification(int $id, array $c): void {
    $db = getDB();
    $db->prepare('UPDATE requests SET ai_classification=?,ai_priority_score=?,ai_routing_recommendation=?,ai_flags=?,updated_at=NOW() WHERE id=?')
       ->execute([$c['classification']??null,(int)($c['priority_score']??5),$c['routing_recommendation']??null,$c['flags']??null,$id]);
}
