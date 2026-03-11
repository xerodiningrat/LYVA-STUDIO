<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function __invoke()
    {
        $featureGroups = [
            [
                'title' => 'Revenue and commerce',
                'items' => [
                    'Notifikasi game pass dan developer product sales ke Discord.',
                    'Log group revenue harian dengan histori yang bisa diaudit tim.',
                    'Webhook deploy ketika place atau configuration berubah.',
                ],
            ],
            [
                'title' => 'Ops and monitoring',
                'items' => [
                    'Monitor server, game status, badge publish, dan place update.',
                    'Alert shutdown, degraded service, atau publish failure.',
                    'Status board internal untuk tim build, QA, dan community ops.',
                ],
            ],
            [
                'title' => 'Community admin',
                'items' => [
                    'Panel admin untuk report player, bug report, dan moderasi.',
                    'Verifikasi Discord ke Roblox account untuk role gating.',
                    'Threading laporan ke Discord agar follow-up tidak tercecer.',
                ],
            ],
        ];

        $workflow = [
            'Roblox event masuk lewat poller atau webhook.',
            'Laravel menyimpan event, menentukan severity, lalu fan-out ke Discord.',
            'Dashboard tim menampilkan metrics, incident log, dan backlog moderasi.',
        ];

        return view('welcome', compact('featureGroups', 'workflow'));
    }
}
