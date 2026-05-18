<?php
// fetch_holidays.php

$apiKey = getenv('HOLIDAY_API_KEY');

if (!$apiKey) {
    echo "Error: HOLIDAY_API_KEY가 설정되지 않았습니다.\n";
    exit(1);
}

// 기준 연도 설정 (작년, 올해, 내년, 내후년 총 4개년)
$currentYear = (int)date('Y');
$yearsToFetch = [$currentYear - 1, $currentYear, $currentYear + 1, $currentYear + 2];

$allHolidays = [];

echo "=== 4개년 공휴일 데이터 수집 시작 ===\n";

foreach ($yearsToFetch as $year) {
    echo "\n> [${year}년] 데이터 수집 중...\n";
    
    for ($month = 1; $month <= 12; $month++) {
        $m = str_pad($month, 2, "0", STR_PAD_LEFT);
        
        $url = "http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/getRestDeInfo";
        $params = [
            'solYear'  => $year,
            'solMonth' => $m,
            '_type'    => 'json',
            'numOfRows' => 100
        ];

        $fullUrl = $url . "?serviceKey=" . $apiKey . "&" . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200) {
            $data = json_decode($response, true);
            $items = $data['response']['body']['items']['item'] ?? [];

            if (!empty($items)) {
                if (!isset($items[0])) {
                    $items = [$items];
                }
                $allHolidays = array_merge($allHolidays, $items);
                echo "${year}년 ${m}월: " . count($items) . "개 수집 완료\n";
            }
        } else {
            echo "${year}년 ${m}월: API 호출 실패 (Status: $status)\n";
        }
    }
}

// 최종 결과물 구조화
$finalResult = [
    'status' => 'success',
    'last_updated' => date('Y-m-d H:i:s'),
    'description' => '작년, 올해, 내년, 내후년(4개년) 공휴일 통합 데이터',
    'total_count' => count($allHolidays),
    'data' => $allHolidays
];

// JSON 파일로 임시 저장
file_put_contents('holidays.json', json_encode($finalResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "\n=== 완료: 4개년 데이터가 holidays.json으로 통합되었습니다. ===\n";
