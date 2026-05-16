<?php
// fetch_holidays.php

// GitHub Actions에서 주입해주는 환경 변수
$apiKey = getenv('HOLIDAY_API_KEY');

if (!$apiKey) {
    echo "Error: HOLIDAY_API_KEY가 설정되지 않았습니다.\n";
    exit(1);
}

$year = date('Y');
$allHolidays = [];

echo "[$year] 연도 공휴일 데이터 수집 시작...\n";

for ($month = 1; $month <= 12; $month++) {
    $m = str_pad($month, 2, "0", STR_PAD_LEFT);
    
    // 한국천문연구원 특일 정보 엔드포인트
    $url = "http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/getRestDeInfo";
    
    // 파라미터 설정 (인코딩된 키는 http_build_query 시 주의가 필요하여 직접 붙임)
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
            // 데이터가 1개일 때를 대비한 배열화 작업
            if (!isset($items[0])) {
                $items = [$items];
            }
            $allHolidays = array_merge($allHolidays, $items);
            echo "$m월: " . count($items) . "개의 휴일을 찾았습니다.\n";
        } else {
            echo "$m월: 공휴일 없음\n";
        }
    } else {
        echo "$m월: API 호출 실패 (Status: $status)\n";
    }
}

// 최종 결과물 구조화
$finalResult = [
    'status' => 'success',
    'last_updated' => date('Y-m-d H:i:s'),
    'count' => count($allHolidays),
    'data' => $allHolidays
];

// JSON 파일로 저장
file_put_contents('holidays.json', json_encode($finalResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "완료: holidays.json 파일이 생성되었습니다.\n";
