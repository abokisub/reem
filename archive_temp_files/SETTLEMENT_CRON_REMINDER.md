# Settlement Auto-Processing — Action Required

## Status (checked 2026-03-26)
- Auto settlement is ON, timezone is correct (Africa/Lagos)
- 11 settlements pending, all due tonight at 03:00:00
- System is correctly queuing settlements (T+1, next day 3am)
- Laravel scheduler cron is running every 5 mins ✅

## The Problem
The Laravel scheduler alone is not enough guarantee for 3am settlement.
You need dedicated cron entries that fire the settlement commands directly.

## Action: Add These to Live Server Crontab

SSH into server and run: `crontab -e`

Add these two lines below the existing scheduler line:

```
5 3 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process >> /dev/null 2>&1
30 3 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process-overdue >> /dev/null 2>&1
```

Full crontab should look like this:
```
0,5,10,15,20,25,30,35,40,45,50,55 * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
5 3 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process >> /dev/null 2>&1
30 3 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process-overdue >> /dev/null 2>&1
```

## How to Verify Tomorrow Morning (after 3am)

Run on live server:
```bash
php artisan tinker --execute="
echo 'Pending: ' . DB::table('settlement_queue')->where('status','pending')->count() . PHP_EOL;
echo 'Completed today: ' . DB::table('settlement_queue')->where('status','completed')->whereDate('actual_settlement_date', today())->count() . PHP_EOL;
"
```

Expected result: Pending = 0 (or only today's new transactions), Completed today = 11

## Settlement Flow (for reference)
- PalmPay settles YOU at 2am
- Your system settles your users at 3am (1hr buffer after PalmPay)
- Settings: delay=24hrs, time=03:00:00, skip_weekends=OFF
- Friday transactions settle Monday 3am (if skip_weekends is turned ON)
