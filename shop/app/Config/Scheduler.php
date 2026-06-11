<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Tasks\Scheduler as TasksScheduler;

class Scheduler extends BaseConfig
{
    /**
     * 스케줄 등록
     * cron 설정: * * * * * cd /path/to && php spark schedule:run >> /dev/null 2>&1
     */
    public function init(TasksScheduler $scheduler): void
    {
        // 결제 대기 30분 초과 주문 만료 처리 (매 5분마다)
        $scheduler->command('orders:expire')->everyFiveMinutes();

        // 90일 초과 접속 로그 삭제 (매주 월요일 02:00)
        $scheduler->command('stats:purge-logs')->weekly()->at('02:00');
    }
}
