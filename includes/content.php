<?php
const GALLERY_CATS = [
    'painting' => 'Painting',
    'ceilings' => 'POP & Ceilings',
    'doors'    => 'Doors & Wood',
    'curtains' => 'Curtains',
    'panels'   => 'Panels & Marble',
    'flooring' => 'Flooring',
    'decor'    => 'Décor',
];

function services(): array
{
    return [
        ['painting', 'brush', 'Professional Painting', 'Interior and exterior painting with clean lines, smooth finishes and lasting colour — emulsion, satin, matt and textured feature walls.', 'assets/img/feature-wall-painting.jpg',
            ['Interior & exterior painting', 'Textured & feature walls', 'Door and wood finishing', 'Colour consultation']],
        ['pop', 'ceiling', 'POP Ceilings & Screeding', 'Decorative POP ceiling designs with concealed LED lighting, plus wall screeding for a perfectly smooth base.', 'assets/img/pop-ceiling-sitting-room.jpg',
            ['Multi-level POP designs', 'Cove & strip LED lighting', 'Wall & ceiling screeding', 'Repairs and re-finishing']],
        ['curtains', 'curtain', 'Curtains & Window Treatments', 'Made-to-measure curtains, pelmets, sheers and blinds that frame your windows and complete the room.', 'assets/img/curtains-sitting-room.jpg',
            ['Custom curtains & sheers', 'Box pelmets & valances', 'Blinds', 'Rods, tracks & installation']],
        ['furniture', 'sofa', 'Furniture & Cabinetry', 'Sofas, wardrobes, TV units, cabinets and doors — built and finished to suit your space and style.', 'assets/img/door-finishing-white.jpg',
            ['Sofas & upholstery', 'Wardrobes & cabinets', 'TV & feature units', 'Door cladding & finishing']],
        ['panels', 'layers', 'Wall Panels & Marble Sheets', 'WPC fluted panels and PVC marble sheets for elegant feature walls that are durable and easy to maintain.', 'assets/img/wpc-fluted-panels.jpg',
            ['WPC fluted panels', 'PVC marble sheets', 'Mirror feature walls', 'Supply & installation']],
        ['epoxy', 'floor', 'Epoxy Flooring & Stamp Concrete', 'Seamless epoxy floors for homes, showrooms and commercial spaces, and decorative stamp concrete for outdoor areas.', null,
            ['3D & metallic epoxy', 'Industrial epoxy coating', 'Stamp concrete driveways', 'Walkways & compounds']],
        ['decor', 'frame', 'Interior Décor & Styling', 'Mirrors, frames, flowers, clocks and art pieces selected and placed to give your space its finishing touch.', 'assets/img/gold-mirror-clock.jpg',
            ['Mirrors & frames', 'Artificial flowers & plants', 'Wall clocks & art', 'Full-room styling']],
    ];
}

function quote_services(): array
{
    $s = array_map(fn($x) => $x[2], services());
    return array_merge($s, ['Moj Paints (bulk / contractor)', 'Interior Design Consultation', 'Other']);
}
