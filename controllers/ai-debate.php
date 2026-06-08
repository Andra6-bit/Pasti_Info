<?php
session_start();

/**
 * controllers/ai-debate.php
 *
 * Handler backend PHP untuk melayani request AI debate (Consultation Arena).
 * Berfungsi sebagai middleware/proxy ke Google Gemini API.
 */

header('Content-Type: application/json');

// 1. Validasi Autentikasi User
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Silakan login terlebih dahulu untuk mengakses Chat AI.'
    ]);
    exit();
}

// 2. Load Environment Variables (untuk GEMINI_API_KEY)
require_once __DIR__ . '/../helpers/env.php';
try {
    loadEnv(__DIR__ . '/../.env');
} catch (Exception $e) {
    // Abaikan jika loadEnv gagal, asalkan variabel env sudah di-set dari luar (misal apache/system)
}

$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'GEMINI_API_KEY is not configured on the server. Please add it to your .env file.'
    ]);
    exit();
}

// 3. Membaca JSON Input dari POST Request Body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input.'
    ]);
    exit();
}

$competition = $input['competition'] ?? null;
$messages = $input['messages'] ?? [];
$voteRequested = $input['voteRequested'] ?? false;

// 4. Hitung jumlah jawaban user untuk memantau progress debat
$userMessages = array_filter($messages, function($m) {
    return isset($m['sender']) && $m['sender'] === 'USER';
});
$userMessages = array_values($userMessages);
$userAnswersCount = $competition ? count($userMessages) : max(0, count($userMessages) - 1);

// 5. Menyusun Info Kompetisi untuk Prompt
$compInfo = '';
if ($competition) {
    $fee = isset($competition['registration_fee']) ? (int)$competition['registration_fee'] : 0;
    $feeFormatted = ($fee > 0) ? 'Rp ' . number_format($fee, 0, ',', '.') : 'Free';
    $format = $competition['format'] ?? 'N/A';
    // 1-line reason: Fetch category dynamically via join from competition_categories and categories to replace redundant column.
    $category = 'N/A';
    if (isset($competition['id'])) {
        require_once __DIR__ . '/../config/database.php';
        $cat_stmt = mysqli_prepare($koneksi, "SELECT GROUP_CONCAT(c.name SEPARATOR ', ') as category FROM competition_categories cc JOIN categories c ON cc.category_id = c.id WHERE cc.competition_id = ?");
        if ($cat_stmt) {
            mysqli_stmt_bind_param($cat_stmt, "i", $competition['id']);
            mysqli_stmt_execute($cat_stmt);
            $cat_res = mysqli_stmt_get_result($cat_stmt);
            if ($cat_row = mysqli_fetch_assoc($cat_res)) {
                $category = $cat_row['category'] ?? 'N/A';
            }
            mysqli_stmt_close($cat_stmt);
        }
    }
    $target = $competition['target_audience'] ?? 'N/A';
    $desc = $competition['description'] ?? 'No description provided.';
    
    $compInfo = "
Competition Details:
- Title: {$competition['title']}
- Format: {$format}
- Category: {$category}
- Fee: {$feeFormatted}
- Target Audience: {$target}
- Description: {$desc}
";
} else {
    $introduction = isset($userMessages[0]['text']) ? $userMessages[0]['text'] : '';
    $compInfo = "
Competition Details:
- The user has introduced an external/custom competition casually in their first message: \"{$introduction}\"
Extract the name, format, registration fee, prizes, and description dynamically from this description. If any details are missing, proceed with the debate and let the AI personas deduce them or raise them during their chat.
";
}

// 6. Menyusun Transkrip Obrolan
$chatTranscript = '';
if (count($messages) > 0) {
    $transcriptParts = [];
    foreach ($messages as $m) {
        if (isset($m['sender']) && isset($m['text'])) {
            $senderUpper = strtoupper($m['sender']);
            $transcriptParts[] = "[{$senderUpper}]: {$m['text']}";
        }
    }
    $chatTranscript = implode("\n", $transcriptParts);
}

// 1-line reason: Update system instructions and prompts for the three AI personas (Karin, Tiara, Raka) matching NextJS implementation.
// 7. System Instructions untuk 3 Persona
$systemInstruction = "
Kamu adalah tiga teman yang sedang ngobrol di WhatsApp group chat. Mereka bukan asisten AI — mereka punya kepribadian, opini, dan bias masing-masing. Bahasa Indonesia informal.

Karin (prefix: [REALISTIS])
Cewek, vibe gal, blak-blakan dan jujur tanpa filter. Bukan jahat, tapi tipe yang \"mending lo tau sekarang daripada nyesel nanti\". Skeptis secara default — kalau ada info baru, reaksi pertamanya selalu mempertanyakan. Sering pakai: \"jujur ya...\", \"ya tapi...\", \"eh wait\", \"emang udah dipikirin?\". Emot: 💀🙄😭 — jarang, hanya untuk penekanan. Manggil user dengan \"lo\". Manggil teman lain dengan nama: \"Tiara\", \"Raka\".

Tiara (prefix: [AMBIS])
Cewek, energik dan tulus. Genuinely percaya sama user — semangatnya bukan hype kosong. Tapi bukan auto-setuju: kalau kondisinya memang berat, dia bisa berubah pikiran. Sering pakai: \"ih tapi seru banget loh!\", \"aku yakin kamu bisa kok\", \"coba dulu deh\", \"eh eh eh\". Emot: 🥺✨🎉 — hangat tapi tidak berlebihan. Manggil user dengan \"kamu\". Manggil teman lain dengan nama: \"Karin\", \"Raka\".

Raka (prefix: [STRATEGIS])
Cowok, calm dan calculated. Aktif ngomong tapi setiap kata berasa berbobot karena udah dipikir dulu. Menyerap semua input — dari Karin, dari Tiara, dan dari kondisi real user — sebelum kasih analisis. Fokus ke kemampuan dan motivasi user, bukan cuma ngomongin topiknya secara general. Sering pakai: \"tunggu, kita lihat dari sisi lain dulu\", \"kalau dipikir-pikir...\", \"faktanya adalah...\", \"pertama... kedua...\". Jarang pakai tanda seru. Emot: 🤔 sesekali atau tidak sama sekali. Manggil user dengan \"kamu\" tapi lebih neutral dan dingin. Manggil teman lain dengan nama: \"Karin\", \"Tiara\".

Dinamika mereka:
- Karin dan Tiara sering clash karena beda sudut pandang.
- Karin bicara dari gut feeling dan pengalaman, Raka menganalisis semua input sebelum kesimpulan.
- Raka bisa tidak sepakat dengan Karin kalau data menunjukkan hal berbeda.
- Tiara jadi tiebreaker emosional kalau Karin dan Raka tidak sepakat.

Aturan wajib:
- Ini GROUP CHAT. Kalimat pendek, santai, kayak WhatsApp beneran.
- Maksimal 1-2 kalimat per bubble. Kalau mau panjang, pecah jadi beberapa bubble.
- Mereka boleh potong omongan satu sama lain, setuju sebagian, atau bereaksi spontan.
- Jangan pernah terdengar seperti AI yang sedang menjawab pertanyaan. Mereka sedang NGOBROL.
- Setiap pesan HARUS diawali prefix: [REALISTIS]:, [AMBIS]:, atau [STRATEGIS]: (kecuali jika ada instruksi khusus untuk mengeluarkan laporan format [FINAL_REPORT_JSON]: di akhir sesi).
- Urutan bicara dinamis — siapa yang paling terpancing duluan yang ngomong. Boleh back-to-back, boleh satu karakter ngomong beberapa kali berturut-turut.
- Raka masuk setelah ada cukup input dari diskusi, bukan langsung dari awal.
- INGAT: Karin, Tiara, dan Raka hanyalah TEMAN yang memberi saran/opini atas lomba yang akan diikuti oleh USER. Mereka TIDAK IKUT mendaftar atau mengerjakan proyek tersebut. Gunakan kata ganti \"kamu\" atau \"lo\" saat merujuk pada pengerjaan proyek, JANGAN PERNAH gunakan kata \"kita\" seolah-olah kalian satu tim proyek.
";

// 8. Menyusun Prompt Akhir berdasarkan Request Vote
$prompt = '';
if ($voteRequested) {
    $prompt = "
{$compInfo}

Riwayat Chat:
{$chatTranscript}

User meminta keputusan voting akhir!
Masing-masing dari tiga persona ([REALISTIS], [AMBIS], [STRATEGIS]) harus memberikan vote final mereka (IKUT atau TIDAK IKUT) dan menjelaskan alasan personal mereka dalam 1-2 kalimat pendek yang sesuai karakter masing-masing.

Kamu HARUS menghasilkan tepat tiga giliran bicara secara berurutan: Realistis dulu, lalu Ambis, lalu Strategis.
Setiap giliran harus dimulai persis dengan prefix dan vote mereka:
`[REALISTIS]: [VOTE: IKUT/TIDAK IKUT] <alasan singkat sesuai karakter blak-blakan Realistis>`
`[AMBIS]: [VOTE: IKUT/TIDAK IKUT] <alasan singkat sesuai karakter tulus dan supportif Ambis>`
`[STRATEGIS]: [VOTE: IKUT/TIDAK IKUT] <alasan singkat berdasarkan analisis Strategis dari semua yang sudah dibahas>`

Selain itu, di paling akhir output, kamu HARUS menambahkan satu giliran keempat berisi laporan kelayakan terstruktur dalam format JSON.
Giliran ini harus dimulai persis dengan prefix `[FINAL_REPORT_JSON]: ` diikuti satu baris JSON valid sesuai skema ini:
{
  \"score\": <angka 0 sampai 100 yang merepresentasikan persentase kelayakan berdasarkan vote dan alasan>,
  \"pros\": [<daftar 3 keuntungan atau peluang utama sebagai string>],
  \"cons\": [<daftar 3 risiko atau keterbatasan utama sebagai string>],
  \"nextSteps\": [<daftar 3 langkah aksi konkret sebagai string>]
}

Output hanya empat giliran ini saja. Tidak ada teks lain atau formatting markdown.
";
} else {
    $isStart = ($userAnswersCount === 0);
    $prompt = "
{$compInfo}

Riwayat Chat:
{$chatTranscript}

" . ($isStart 
    ? "React secara natural sebagai tiga teman yang baru dapet info lomba ini. Jangan langsung tanya-tanya terstruktur — mulai dari reaksi spontan dulu. Realistis boleh langsung skeptis, Ambis boleh langsung excited, Strategis boleh langsung nanya kondisi user. Generate 2-3 bubble pembuka yang natural dan tidak kaku."
    : "User baru balas. React secara natural sebagai teman di grup — boleh ada yang setuju, yang counter, yang nanya balik. Jangan semua persona balas sekaligus dengan terstruktur. Biarkan yang paling 'terpancing' duluan yang ngomong. Realistis and Ambis boleh saling nyahut, Strategis masuk kalau sudah ada cukup input untuk dianalisis."
) . "

Output antara 1 sampai 4 pesan/giliran secara dinamis sesuai konteks. Biarkan persona bicara dalam urutan apapun (termasuk back-to-back, atau persona yang sama posting beberapa kali kalau mereka merasa kuat soal topiknya).
Setiap pesan harus sangat pendek, kasual, dan langsung (maks 1-2 kalimat per bubble).
Jangan keluarkan vote dulu.
";
}

// 9. Membuat cURL POST request ke Google Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$requestBody = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => $prompt
                ]
            ]
        ]
    ],
    "systemInstruction" => [
        "parts" => [
            [
                "text" => $systemInstruction
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.95
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 10. Memproses dan Mengirimkan Hasil Respons
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'cURL Error: ' . $curlError
    ]);
    exit();
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    $errorData = json_decode($response, true);
    $errorMessage = $errorData['error']['message'] ?? 'Gemini API returned code ' . $httpCode;
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ]);
    exit();
}

$responseData = json_decode($response, true);
$replyText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

echo json_encode([
    'success' => true,
    'text' => $replyText
]);
exit();
