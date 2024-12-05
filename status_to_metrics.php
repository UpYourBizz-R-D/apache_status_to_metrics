#!/usr/bin/php
<?php


//print_r( $_SERVER );

$inputData =  stream_get_contents(fopen("php://stdin", "r"));
//file_get_contents("php://input");

if( strpos($_SERVER['QUERY_STRING'],'metrics') === false )
{
    echo $inputData;
    die();
}   

header("Content-Type: text/plain");

$in = textParse( $inputData);
//print_r( $in);

//echo $inputData;
//die();

/*
Array
(
    [hostname] => uyr.fr
    [ServerVersion] => Apache/2.4.38 (Debian) OpenSSL/1.1.1d
    [ServerMPM] => prefork
    [Server Built] => 2020-08-25T20:08:29
    [CurrentTime] => Monday, 11-Dec-2023 14:43:21 CET
    [RestartTime] => Monday, 11-Dec-2023 14:00:56 CET
    [ParentServerConfigGeneration] => 16
    [ParentServerMPMGeneration] => 15
    [ServerUptimeSeconds] => 2545
    [ServerUptime] => 42 minutes 25 seconds
    [Load1] => 3.08
    [Load5] => 2.94
    [Load15] => 2.84
    [Total Accesses] => 586
    [Total kBytes] => 2777
    [Total Duration] => 84387
    [CPUUser] => 1.18
    [CPUSystem] => .79
    [CPUChildrenUser] => 2.75
    [CPUChildrenSystem] => 1.82
    [CPULoad] => .256974
    [Uptime] => 2545
    [ReqPerSec] => .230255
    [BytesPerSec] => 1117.35
    [BytesPerReq] => 4852.64
    [DurationPerReq] => 144.005
    [BusyWorkers] => 1
    [IdleWorkers] => 9
    [Scoreboard] => ______W._.............__..............................................................................................................................
    [TLSSessionCacheStatus] => 
    [CacheType] => SHMCB
    [CacheSharedMemory] => 512000
    [CacheCurrentEntries] => 157
    [CacheSubcaches] => 32
    [CacheIndexesPerSubcaches] => 88
    [CacheTimeLeftOldestAvg] => 52
    [CacheTimeLeftOldestMin] => 4
    [CacheTimeLeftOldestMax] => 200
    [CacheIndexUsage] => 5%
    [CacheUsage] => 6%
    [CacheStoreCount] => 1141
    [CacheReplaceCount] => 0
    [CacheExpireCount] => 984
    [CacheDiscardCount] => 0
    [CacheRetrieveHitCount] => 111
    [CacheRetrieveMissCount] => 3
    [CacheRemoveHitCount] => 0
    [CacheRemoveMissCount] => 0
)
*/


/*

        "apache_accesses_total",
        "apache_cpu_time_ms_total",
        "apache_cpuload",
        "apache_duration_ms_total",
        "apache_generation",
        "apache_http_build_info",
        "apache_info",
        "apache_load",
        "apache_scoreboard",
        "apache_sent_kilobytes_total",
        "apache_up",
        "apache_uptime_seconds_total",
        "apache_version",
        "apache_workers"
*/


$stats = [
    'apache_accesses_total' => $in['Total Accesses'],
    'apache_cpu_time_ms_total' => ['user'=>$in['CPUUser'],'system'=>$in['CPUSystem'] ],
    'apache_cpuload' => $in['CPULoad'],
    'apache_duration_ms_total' => $in['Total Duration'],
    'apache_generation' => ['config'=>$in['ParentServerConfigGeneration'], 'mpm'=>$in['ParentServerMPMGeneration'] ] ,
   // 'apache_http_build_info' => $in['Server Built'],
    'apache_info' => ['version'=>$in['ServerVersion'],'mpm'=>$in['ServerMPM']],
    'apache_load' => ['1min'=>$in['Load1'],'5min'=>$in['Load5'],'15min'=>$in['Load15']],
    'apache_scoreboard' => countOccurrences($in['Scoreboard']), // state
    'apache_sent_kilobytes_total' => $in['Total kBytes'],
    'apache_up' => 1,
    'apache_uptime_seconds_total' => $in['Uptime'],
    'apache_version' => getVersion($in['ServerVersion']),
    'apache_workers' => ['busy'=>$in['BusyWorkers'] ,'idle' => $in['IdleWorkers'] ],
    'apache_max_workers' => strlen($in['Scoreboard']),

    'apache_total_access' => $in['Total Accesses'],
    'apache_total_kbytes' => $in['Total kBytes'],
    'apache_total_duration' => $in['Total Duration'],

    'apache_request_per_sec' => $in['ReqPerSec'],  
    'apache_bytes_per_sec' => $in['BytesPerSec'],
    'apache_bytes_per_req' => $in['BytesPerReq'],
    'apache_duration_per_req' => $in['DurationPerReq'],



];

//print_r( $stats );



echo "# HELP apache_accesses_total Total number of accesses to this server.\n";
echo "# TYPE apache_accesses_total gauge\n";
echo "apache_cpu_time_ms_total {$stats['apache_accesses_total']}\n";

echo "# HELP apache_cpu_time_ms_total Total CPU time consumed in servicing requests.\n";
echo "# TYPE apache_cpu_time_ms_total gauge\n";
echo "apache_cpu_time_ms_total{type=\"system\"} {$stats['apache_cpu_time_ms_total']['system']}\n";
echo "apache_cpu_time_ms_total{type=\"user\"} {$stats['apache_cpu_time_ms_total']['user']}\n";

echo "# HELP apache_cpuload Current percentage CPU used by each worker and in total by all workers combined.\n";
echo "# TYPE apache_cpuload gauge\n";
echo "apache_cpuload {$stats['apache_cpuload']}\n";

echo "# HELP apache_duration_ms_total The total number of milliseconds spent serving requests.\n";
echo "# TYPE apache_duration_ms_total gauge\n";
echo "apache_duration_ms_total {$stats['apache_duration_ms_total']}\n";

echo "# HELP apache_generation The current generation number of this server process, indicating how many times the server has been restarted.\n";
echo "# TYPE apache_generation gauge\n";
echo "apache_generation{type=\"config\"} {$stats['apache_generation']['config']}\n";
echo "apache_generation{type=\"mpm\"} {$stats['apache_generation']['mpm']}\n";

echo "# HELP apache_info Apache information\n";
echo "# TYPE apache_info gauge\n";
echo "apache_info{version=\"{$stats['apache_info']['version']}\", mpm=\"{$stats['apache_info']['mpm']}\"} 1\n";

echo "# HELP apache_load The current number of idle workers, busy workers, and total workers.\n";
echo "# TYPE apache_load gauge\n";
echo "apache_load{interval=\"1min\"} {$stats['apache_load']['1min']}\n";
echo "apache_load{interval=\"5min\"} {$stats['apache_load']['5min']}\n";
echo "apache_load{interval=\"15min\"} {$stats['apache_load']['15min']}\n";

echo "# HELP apache_scoreboard The current number of idle workers, busy workers, and total workers.\n";
echo "# TYPE apache_scoreboard gauge\n";
echo "apache_scoreboard{state=\"closing\"} {$stats['apache_scoreboard']['closing']}\n";
echo "apache_scoreboard{state=\"dns\"} {$stats['apache_scoreboard']['dns']}\n";
echo "apache_scoreboard{state=\"graceful_stop\"} {$stats['apache_scoreboard']['graceful_stop']}\n";
echo "apache_scoreboard{state=\"idle\"} {$stats['apache_scoreboard']['idle']}\n";
echo "apache_scoreboard{state=\"idle_cleanup\"} {$stats['apache_scoreboard']['idle_cleanup']}\n";
echo "apache_scoreboard{state=\"keepalive\"} {$stats['apache_scoreboard']['keepalive']}\n";
echo "apache_scoreboard{state=\"logging\"} {$stats['apache_scoreboard']['logging']}\n";
echo "apache_scoreboard{state=\"open_slot\"} {$stats['apache_scoreboard']['open_slot']}\n";
echo "apache_scoreboard{state=\"read\"} {$stats['apache_scoreboard']['read']}\n";
echo "apache_scoreboard{state=\"reply\"} {$stats['apache_scoreboard']['reply']}\n";
echo "apache_scoreboard{state=\"startup\"} {$stats['apache_scoreboard']['startup']}\n";

echo "# HELP apache_sent_kilobytes_total The total number of kilobytes sent to clients.\n";
echo "# TYPE apache_sent_kilobytes_total counter\n";
echo "apache_sent_kilobytes_total {$stats['apache_sent_kilobytes_total']}\n";

echo "# HELP apache_up Was the last query of Apache successful.\n";
echo "# TYPE apache_up gauge\n";
echo "apache_up {$stats['apache_up']}\n";

echo "# HELP apache_uptime_seconds_total Number of seconds the Apache service has been running.\n";
echo "# TYPE apache_uptime_seconds_total counter\n";
echo "apache_uptime_seconds_total {$stats['apache_uptime_seconds_total']}\n";

echo "# HELP apache_version Apache version\n";
echo "# TYPE apache_version gauge\n";
echo "apache_version {$stats['apache_version']}\n";

echo "# HELP apache_workers The current number of idle workers, busy workers, and total workers.\n";
echo "# TYPE apache_workers gauge\n";
echo "apache_workers{state=\"busy\"} {$stats['apache_workers']['busy']}\n";
echo "apache_workers{state=\"idle\"} {$stats['apache_workers']['idle']}\n";

echo "# HELP apache_max_workers The current number of idle workers, busy workers, and total workers.\n";
echo "# TYPE apache_max_workers gauge\n";
echo "apache_max_workers {$stats['apache_max_workers']}\n";


echo "# HELP apache_total_access Total number of accesses to this server.\n";
echo "# TYPE apache_total_access gauge\n";
echo "apache_total_access {$stats['apache_total_access']}\n";

echo "# HELP apache_total_kbytes Total number of kilobytes sent to clients.\n";
echo "# TYPE apache_total_kbytes gauge\n";
echo "apache_total_kbytes {$stats['apache_total_kbytes']}\n";

echo "# HELP apache_total_duration Total number of milliseconds spent serving requests.\n";
echo "# TYPE apache_total_duration gauge\n";
echo "apache_total_duration {$stats['apache_total_duration']}\n";

echo "# HELP apache_request_per_sec The number of requests per second this connection has served.\n";
echo "# TYPE apache_request_per_sec gauge\n";
echo "apache_request_per_sec {$stats['apache_request_per_sec']}\n";

echo "# HELP apache_bytes_per_sec The number of bytes per second this connection has served.\n";
echo "# TYPE apache_bytes_per_sec gauge\n";
echo "apache_bytes_per_sec {$stats['apache_bytes_per_sec']}\n";

echo "# HELP apache_bytes_per_req The number of bytes per request this connection has served.\n";
echo "# TYPE apache_bytes_per_req gauge\n";
echo "apache_bytes_per_req {$stats['apache_bytes_per_req']}\n";

echo "# HELP apache_duration_per_req The number of milliseconds per request this connection has served.\n";
echo "# TYPE apache_duration_per_req gauge\n";
echo "apache_duration_per_req {$stats['apache_duration_per_req']}\n";













function textParse($text) {
    $lines = explode("\n", $text);
    $result = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $parts = explode(':', $line, 2);
            // Si il n'y a pas 2 éléments, alors, on sette une key='hostname' et value=$line
            if (count($parts) < 2 && !isset($result['hostname'])) {
                $parts = ['hostname', $line];
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $result[$key] = $value;
        }
    }

    return $result;
}

function getVersion($versionString) {
    $parts = explode('/', $versionString);
    if (count($parts) > 1) {
        $version = explode('.', $parts[1]);
        if (count($version) > 2) {
            return floatval($version[0] . '.' . str_pad($version[1], 2, '0', STR_PAD_LEFT) . $version[2]);
        }
    }
    return 0;
}

function countOccurrences($string) {
    $characters = [
        'closing' => 'C',
        'dns' => 'D',
        'graceful_stop' => 'G',
        'idle' => '_',
        'idle_cleanup' => 'I',
        'keepalive' => 'K',
        'logging' => 'L',
        'open_slot' => '.',
        'read' => 'R',
        'reply' => 'W',
        'startup' => 'S'
    ];

    $occurrences = [];

    foreach ($characters as $key => $character) {
        $occurrences[$key] = substr_count($string, $character);
    }

    return $occurrences;
}



?>
