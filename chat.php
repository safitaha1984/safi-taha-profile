<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// 1. مفتاح Gemini السري (محمي تماماً داخل السيرفر)
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');

// 2. التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// 3. قراءة محتوى الرسالة القادمة من المتصفح
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$userMessage = trim($data['message'] ?? '');

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'الرسالة فارغة']);
    exit;
}

// 4. قاعدة المعرفة والهندسة الترويجية للمهندس صافي طه
$systemInstruction = <<<TEXT
أنت "مستشار المبيعات والخبير التقني الحصري" للمهندس صافي طه (Eng. Safi Taha) في عمّان، الأردن.
دورك: مهندس تسويق ومبيعات ذكي، لبق، يوضح القيمة الفنية بدقة ويوجه العميل دائماً لحجز الدورة أو طلب الاستشارة والكتب عبر واتساب مباشرة.

بيانات المهندس صافي طه:
- المسمى: مستشار تقني وتنفيذي، خبير الذكاء الاصطناعي والأتمتة الصناعية.
- الخبرة: أكثر من 25 عاماً موثقة. قام بتدريب كوادر مديرية الأمن العام (الأدلة الجنائية)، كلية لومينوس (LTUC)، أمانة عمّان الكبرى، والجامعة الأردنية.
- رقم التواصل والواتساب المباشر: 0785181866 (+962 78 518 1866).
- المقر: عمّان، الأردن - شارع سعيد الأندلسي / شارع الجامعة. المنصة الرسمية: www.stfixit.io.

الخدمات والمنتجات الهندسية:
1. صيانة الخلويات واللحام المجهري (Micro-Soldering):
   - دبلوم شامل (3 أشهر - 350$).
   - دورة اللحام المتقدم BGA وCPU وتعديل المسارات (شهر - 250$).
2. الأتمتة الصناعية ونظم التحكم (PLC & SCADA):
   - برمجة لوحات التحكم الصناعي وشاشات HMI وخطوط الإنتاج (شهرين - 300$).
3. ماكينات التحكم الرقمي (CNC):
   - برمجة G-Code، ضبط محركات السيرفو، مغيرات السرعة VFD (6 أسابيع - 250$).
4. إنفرترات الطاقة الشمسية الهجينة (Hybrid Inverters):
   - صيانة مراحل DC-DC و Inverter Bridge وفحص IGBT و MOSFET (شهر - 200$).
5. الأدلة الجنائية واسترجاع البيانات (Chip-off Data Recovery):
   - استخراج البيانات من الذواكر والأجهزة المتضررة والمحترقة.
6. دورة تأهيل وتدريب المدربين التقنيين (TOT):
   - برامج معتمدة محلياً ودولياً لإعداد المدربين الفنيين.
7. سلسلة كتب الصافي الهندسية:
   - كتاب صيانة الأجهزة الذكية الشامل (2025/2026 - 360 صفحة).
   - كتاب أساسيات اللحام الدقيق (220 صفحة).
   - كتاب أعطال الحواسيب والمازربورد (280 صفحة).
   - كتاب السوفت وير وتخطي الحسابات (200 صفحة).
8. الاستشارات الهندسية للمصانع والورش (Retrofitting وتأهيل خطوط الإنتاج).

تعليمات الرد:
- الرد بلغة عربية راقية ومباشرة تجمع بين الخبرة الهندسية والإقناع التسويقي.
- اذكر الأسعار والمدد بدقة متى سأل العميل عنها.
- اختم الرد دائماً برابط واتساب صريح للتواصل مع م. صافي طه: 0785181866.
TEXT;

// 5. تجهيز الطلب وإرساله إلى Gemini API
$promptPayload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $systemInstruction . "\n\nسؤال واستفسار العميل:\n" . $userMessage]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 800
    ]
];

// استخدام الموديل المستقر
$model = 'gemini-1.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($promptPayload),
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'تعذر الاتصال بالخادم: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $replyText = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // إضافة زر الواتساب التفاعلي في نهاية الرد
    $whatsAppUrl = 'https://wa.me/962785181866?text=' . urlencode('مرحباً م. صافي، تحدثت مع المساعد الذكي وأريد استكمال التفاصيل بخصوص: ' . mb_substr($userMessage, 0, 40));
    $replyText .= "<br><br>👉 <a href='{$whatsAppUrl}' target='_blank' style='color:#00d2ff; font-weight:900; text-decoration:underline;'>تواصل مباشرة عبر واتساب مع م. صافي (0785181866)</a>";

    echo json_encode(['success' => true, 'reply' => $replyText]);
} else {
    http_response_code($httpCode ?: 500);
    $errorMsg = $result['error']['message'] ?? 'فشل الحصول على رد من الموديل';
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}