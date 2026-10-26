<?php
/**
 * Validates and stores an enquiry from $_POST.
 * @return array{ok:bool, errors:array, data:array, id:int}
 */
function handle_enquiry(string $type): array
{
    csrf_check();
    $d = [
        'name' => mb_substr(pin('name'), 0, 150),
        'phone' => mb_substr(pin('phone'), 0, 40),
        'email' => mb_substr(pin('email'), 0, 190),
        'service' => mb_substr(pin('service'), 0, 150),
        'property' => mb_substr(pin('property'), 0, 120),
        'location' => mb_substr(pin('location'), 0, 190),
        'start_when' => mb_substr(pin('start_when'), 0, 120),
        'details' => mb_substr(pin('details'), 0, 4000),
    ];
    $errors = [];
    if (honeypot_tripped()) return ['ok' => true, 'errors' => [], 'data' => $d, 'id' => 0];
    if ($d['name'] === '') $errors['name'] = 'Please enter your name.';
    if (!valid_phone($d['phone'])) $errors['phone'] = 'Please enter a valid phone number.';
    if ($d['email'] !== '' && !valid_email($d['email'])) $errors['email'] = 'Please enter a valid email or leave it blank.';
    if ($type === 'quote' && $d['location'] === '') $errors['location'] = 'Please tell us where the project is.';
    if (!$errors && throttled('enquiry', 5, 600)) $errors['form'] = 'Too many requests. Please wait a few minutes or call us directly.';
    if ($errors) return ['ok' => false, 'errors' => $errors, 'data' => $d, 'id' => 0];

    $id = insert('enquiries', array_merge(array_map(fn($v) => $v === '' ? null : $v, $d), [
        'name' => $d['name'], 'phone' => $d['phone'], 'type' => $type, 'status' => 'new', 'created_at' => now(),
    ]));
    @file_put_contents(
        ROOT . '/storage/logs/enquiries.txt',
        sprintf("[%s] ID: #%d | TYPE: %s | NAME: %s (%s) | LOCATION: %s\n", now(), $id, strtoupper($type), $d['name'], $d['phone'], $d['location'] ?: 'N/A'),
        FILE_APPEND | LOCK_EX
    );
    notify_enquiry(array_merge($d, ['type' => $type]));
    return ['ok' => true, 'errors' => [], 'data' => $d, 'id' => $id];
}

function enquiry_wa_message(string $type, array $d): string
{
    $lines = ['Hello Mojhometouch — ' . ($type === 'quote' ? 'Quote request' : ($type === 'paint' ? 'Moj Paints bulk enquiry' : 'Enquiry')), ''];
    foreach (['Name' => 'name', 'Phone' => 'phone', 'Service' => 'service', 'Property' => 'property', 'Location' => 'location', 'Start' => 'start_when', 'Details' => 'details'] as $l => $k) {
        if (!empty($d[$k])) $lines[] = $l . ': ' . $d[$k];
    }
    return implode("\n", $lines);
}
