<?php

function fetch_indonesia_holidays(int $year): array
{
    static $cache = [];

    if (isset($cache[$year])) {
        return $cache[$year];
    }

    $url = "https://api-hari-libur.vercel.app/api?year=" . $year;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $holidays = [];

    if ($response !== false && $httpCode === 200) {
        $data = json_decode($response, true);

        if (is_array($data['data'] ?? null)) {
            foreach ($data['data'] as $holiday) {
                if (!empty($holiday['date'])) {
                    $description = $holiday['description'] ?? 'Hari Libur';
                    $isCutiBersama = stripos($description, 'Cuti Bersama') !== false;

                    $holidays[$holiday['date']] = [
                        'name' => $description,
                        'is_cuti_bersama' => $isCutiBersama
                    ];
                }
            }
        }
    }

    $cache[$year] = $holidays;

    return $holidays;
}

function get_holiday_info(string $date): ?array
{
    static $cache = [];

    $year = (int)date('Y', strtotime($date));

    if (!isset($cache[$year])) {
        $cache[$year] = fetch_indonesia_holidays($year);
    }

    return $cache[$year][$date] ?? null;
}

function get_indonesian_day(string $date): string
{
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    return $days[date('l', strtotime($date))] ?? date('l', strtotime($date));
}

function format_indonesian_date(string $date): string
{
    return get_indonesian_day($date) . ', ' . date('d-m-Y', strtotime($date));
}

function is_non_working_day(string $date): bool
{
    return (int)date('N', strtotime($date)) >= 6 || get_holiday_info($date) !== null;
}

function get_non_working_dates(string $startDate, string $endDate): array
{
    $dates = [];
    $current = new DateTime($startDate, new DateTimeZone('Asia/Jakarta'));
    $end = new DateTime($endDate, new DateTimeZone('Asia/Jakarta'));

    while ($current <= $end) {
        $date = $current->format('Y-m-d');

        if (is_non_working_day($date)) {
            $dates[] = $date;
        }

        $current->modify('+1 day');
    }

    return $dates;
}