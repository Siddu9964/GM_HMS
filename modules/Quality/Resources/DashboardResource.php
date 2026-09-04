<?php
namespace GM_HMS\Modules\Quality\Resources;

class DashboardResource
{
    public static function toArray(array $data): array
    {
        $collected  = $data['today_collected']  ?? [];
        $dispatched = $data['today_dispatched'] ?? [];

        return [
            'today' => [
                'collected' => [
                    'entries'      => (int)($collected['total_entries']  ?? 0),
                    'total_weight' => (float)($collected['total_weight'] ?? 0),
                    'green'        => (float)($collected['green']        ?? 0),
                    'red'          => (float)($collected['red']          ?? 0),
                    'yellow'       => (float)($collected['yellow']       ?? 0),
                    'blue'         => (float)($collected['blue']         ?? 0),
                    'white'        => (float)($collected['white']        ?? 0)
                ],
                'dispatched' => [
                    'dispatches'   => (int)($dispatched['total_dispatches'] ?? 0),
                    'total_weight' => (float)($dispatched['total_weight']   ?? 0),
                    'net_variance' => (float)($dispatched['net_variance']   ?? 0),
                    'green'        => (float)($dispatched['green']          ?? 0),
                    'red'          => (float)($dispatched['red']            ?? 0),
                    'yellow'       => (float)($dispatched['yellow']         ?? 0),
                    'blue'         => (float)($dispatched['blue']           ?? 0),
                    'white'        => (float)($dispatched['white']          ?? 0)
                ]
            ],
            'pending_dispatch' => (int)($data['pending_dispatch']    ?? 0),
            'recent_records'   =>       $data['recent_records']      ?? [],
            'monthly_trends'   =>       $data['monthly_trends']      ?? [],
            'location_wise'    =>       $data['location_wise']       ?? []
        ];
    }
}
