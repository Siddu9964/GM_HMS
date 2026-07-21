<?php
header('Content-Type: application/json');
session_start();

// Authentication check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'admin', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Comprehensive medication database with generic names and brand names
// This can be replaced with a database query in production
$medications = [
    [
        'generic_name' => 'Paracetamol',
        'brands' => ['Dolo 650', 'Crocin', 'Panadol', 'Calpol', 'Metacin'],
        'category' => 'Analgesic/Antipyretic',
        'common_dosages' => ['500mg', '650mg', '1000mg']
    ],
    [
        'generic_name' => 'Ibuprofen',
        'brands' => ['Brufen', 'Advil', 'Motrin', 'Nurofen'],
        'category' => 'NSAID',
        'common_dosages' => ['200mg', '400mg', '600mg']
    ],
    [
        'generic_name' => 'Amoxicillin',
        'brands' => ['Amoxil', 'Novamox', 'Moxikind', 'Wymox'],
        'category' => 'Antibiotic',
        'common_dosages' => ['250mg', '500mg', '875mg']
    ],
    [
        'generic_name' => 'Azithromycin',
        'brands' => ['Azithral', 'Zithromax', 'Azee', 'Azithro'],
        'category' => 'Antibiotic',
        'common_dosages' => ['250mg', '500mg']
    ],
    [
        'generic_name' => 'Omeprazole',
        'brands' => ['Omez', 'Prilosec', 'Losec', 'Omepral'],
        'category' => 'Proton Pump Inhibitor',
        'common_dosages' => ['20mg', '40mg']
    ],
    [
        'generic_name' => 'Pantoprazole',
        'brands' => ['Pan', 'Pantocid', 'Protonix', 'Pantop'],
        'category' => 'Proton Pump Inhibitor',
        'common_dosages' => ['20mg', '40mg']
    ],
    [
        'generic_name' => 'Metformin',
        'brands' => ['Glycomet', 'Glucophage', 'Obimet', 'Formin'],
        'category' => 'Antidiabetic',
        'common_dosages' => ['500mg', '850mg', '1000mg']
    ],
    [
        'generic_name' => 'Atorvastatin',
        'brands' => ['Atorva', 'Lipitor', 'Storvas', 'Tonact'],
        'category' => 'Statin',
        'common_dosages' => ['10mg', '20mg', '40mg', '80mg']
    ],
    [
        'generic_name' => 'Amlodipine',
        'brands' => ['Amlong', 'Norvasc', 'Amlip', 'Amlovas'],
        'category' => 'Calcium Channel Blocker',
        'common_dosages' => ['2.5mg', '5mg', '10mg']
    ],
    [
        'generic_name' => 'Cetirizine',
        'brands' => ['Cetrizet', 'Zyrtec', 'Alerid', 'Okacet'],
        'category' => 'Antihistamine',
        'common_dosages' => ['5mg', '10mg']
    ],
    [
        'generic_name' => 'Montelukast',
        'brands' => ['Montair', 'Singulair', 'Montek', 'Romilast'],
        'category' => 'Leukotriene Receptor Antagonist',
        'common_dosages' => ['4mg', '5mg', '10mg']
    ],
    [
        'generic_name' => 'Salbutamol',
        'brands' => ['Asthalin', 'Ventolin', 'Albuterol', 'Salbu'],
        'category' => 'Bronchodilator',
        'common_dosages' => ['2mg', '4mg', '100mcg (inhaler)']
    ],
    [
        'generic_name' => 'Diclofenac',
        'brands' => ['Voveran', 'Voltaren', 'Diclo', 'Dynapar'],
        'category' => 'NSAID',
        'common_dosages' => ['50mg', '75mg', '100mg']
    ],
    [
        'generic_name' => 'Ranitidine',
        'brands' => ['Aciloc', 'Zantac', 'Rantac', 'Ranitin'],
        'category' => 'H2 Blocker',
        'common_dosages' => ['150mg', '300mg']
    ],
    [
        'generic_name' => 'Ciprofloxacin',
        'brands' => ['Ciplox', 'Cipro', 'Cifran', 'Ciproxin'],
        'category' => 'Antibiotic',
        'common_dosages' => ['250mg', '500mg', '750mg']
    ],
    [
        'generic_name' => 'Levofloxacin',
        'brands' => ['Levoquin', 'Levoflox', 'Tavanic', 'Levocin'],
        'category' => 'Antibiotic',
        'common_dosages' => ['250mg', '500mg', '750mg']
    ],
    [
        'generic_name' => 'Tramadol',
        'brands' => ['Ultracet', 'Tramazac', 'Tramadol', 'Contramal'],
        'category' => 'Opioid Analgesic',
        'common_dosages' => ['50mg', '100mg']
    ],
    [
        'generic_name' => 'Domperidone',
        'brands' => ['Domstal', 'Motilium', 'Vomistop', 'Domperi'],
        'category' => 'Antiemetic',
        'common_dosages' => ['10mg', '20mg']
    ],
    [
        'generic_name' => 'Ondansetron',
        'brands' => ['Emeset', 'Zofran', 'Ondem', 'Vomiteb'],
        'category' => 'Antiemetic',
        'common_dosages' => ['4mg', '8mg']
    ],
    [
        'generic_name' => 'Prednisolone',
        'brands' => ['Omnacortil', 'Wysolone', 'Predone', 'Deltacortril'],
        'category' => 'Corticosteroid',
        'common_dosages' => ['5mg', '10mg', '20mg', '40mg']
    ],
    [
        'generic_name' => 'Levothyroxine',
        'brands' => ['Eltroxin', 'Thyronorm', 'Synthroid', 'Thyrox'],
        'category' => 'Thyroid Hormone',
        'common_dosages' => ['25mcg', '50mcg', '75mcg', '100mcg']
    ],
    [
        'generic_name' => 'Vitamin D3',
        'brands' => ['Calcirol', 'Uprise D3', 'Shelcal', 'Tayo'],
        'category' => 'Vitamin Supplement',
        'common_dosages' => ['1000IU', '2000IU', '60000IU']
    ],
    [
        'generic_name' => 'Multivitamin',
        'brands' => ['Becosules', 'Supradyn', 'Revital', 'Neurobion'],
        'category' => 'Vitamin Supplement',
        'common_dosages' => ['1 capsule', '1 tablet']
    ],
    [
        'generic_name' => 'Cough Syrup',
        'brands' => ['Benadryl', 'Ascoril', 'Chericof', 'Grilinctus'],
        'category' => 'Antitussive',
        'common_dosages' => ['5ml', '10ml']
    ]
];

// Optional: Filter by search query
$searchQuery = $_GET['search'] ?? '';

if (!empty($searchQuery)) {
    $medications = array_filter($medications, function($med) use ($searchQuery) {
        $search = strtolower($searchQuery);
        $genericMatch = stripos($med['generic_name'], $search) !== false;
        $brandMatch = false;
        
        foreach ($med['brands'] as $brand) {
            if (stripos($brand, $search) !== false) {
                $brandMatch = true;
                break;
            }
        }
        
        return $genericMatch || $brandMatch;
    });
    
    // Re-index array
    $medications = array_values($medications);
}

echo json_encode([
    'success' => true,
    'data' => $medications,
    'count' => count($medications)
]);
