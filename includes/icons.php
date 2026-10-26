<?php
function ic(string $name, string $cls = ''): string
{
    static $p = [
        'brush'    => '<path d="M9.06 11.9 17.5 3.5a2.12 2.12 0 1 1 3 3l-8.4 8.44"/><path d="M7.07 14.94C5.4 14.94 4 16.3 4 18c0 1.3-1 2-2 2 1 1.5 3 2 4.5 2C9 22 11 20 11 17.94c0-1.7-1.4-3-3-3Z"/>',
        'ceiling'  => '<path d="M3 4h18"/><path d="M6 4v4h12V4"/><path d="M9 8v3h6V8"/><circle cx="12" cy="15" r="2"/><path d="M12 11v2"/><path d="M5 20h14"/>',
        'curtain'  => '<path d="M3 3h18"/><path d="M5 3c0 7 2 12 5 18H5z"/><path d="M19 3c0 7-2 12-5 18h5z"/>',
        'sofa'     => '<path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><path d="M2 13a2 2 0 0 1 4 0v2h12v-2a2 2 0 0 1 4 0v5H2z"/><path d="M5 18v2M19 18v2"/>',
        'frame'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="10" height="10"/><path d="m7 15 3-3 3 3 2-2 2 2"/>',
        'floor'    => '<path d="M3 21 12 3l9 18z"/><path d="M7.5 12h9"/><path d="M5 16.5h14"/>',
        'bucket'   => '<path d="M5 8h14l-1.5 12a2 2 0 0 1-2 1.8h-7A2 2 0 0 1 6.5 20z"/><path d="M5 8a7 4 0 0 1 14 0"/><path d="M9 13h6"/>',
        'layers'   => '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 12 10 5 10-5"/><path d="m2 17 10 5 10-5"/>',
        'check'    => '<circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/>',
        'tick'     => '<path d="m5 12 5 5L20 7"/>',
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'arrow'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'back'     => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x'        => '<path d="M18 6 6 18M6 6l12 12"/>',
        'flower'   => '<circle cx="12" cy="9" r="3"/><path d="M12 6V3M12 12v9M9 9H6M18 9h-3M9.9 6.9 8 5M14.1 6.9 16 5M9 18c-2 0-3-1.5-3-3 2 0 3 1 3 3ZM15 16c2 0 3-1.5 3-3-2 0-3 1-3 3Z"/>',
        'mirror'   => '<ellipse cx="12" cy="10" rx="6" ry="8"/><path d="M12 18v4M8 22h8"/><path d="m9 7 2-2M9 10l4-4"/>',
        'star'     => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
        'users'    => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/><path d="M16 3.1a4 4 0 0 1 0 7.8M22 21v-2a4 4 0 0 0-3-3.9"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.5L22 8H6.2"/>',
        'bag'      => '<path d="M6 7h12l1 14H5z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'trash'    => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 15h10l1-15"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'truck'    => '<path d="M1 4h14v12H1z"/><path d="M15 9h4l4 4v3h-8"/><circle cx="6" cy="18.5" r="2"/><circle cx="18" cy="18.5" r="2"/>',
        'card'     => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'bank'     => '<path d="M3 10h18L12 4z"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"/><path d="M3 20h18"/>',
        'cash'     => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
        'box'      => '<path d="m21 8-9-5-9 5 9 5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'upload'   => '<path d="M12 16V4"/><path d="m6 10 6-6 6 6"/><path d="M4 20h16"/>',
        'print'    => '<path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
        'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'eye'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z"/>',
        'tag'      => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
        'home'     => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.8 7L3 21l2-6A8 8 0 1 1 21 12z"/>',
        'alert'    => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'lock'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'refresh'  => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
        'external' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
    ];
    $c = $cls !== '' ? ' class="' . $cls . '"' : '';
    return '<svg' . $c . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

function wa_icon(string $cls = ''): string
{
    $c = $cls !== '' ? ' class="' . $cls . '"' : '';
    return '<svg' . $c . ' viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4a9.8 9.8 0 1 1 8.3 4.6zM20.5 3.5A11.8 11.8 0 0 0 1.9 17.6L.2 24l6.5-1.7A11.8 11.8 0 0 0 20.5 3.5z"/></svg>';
}
