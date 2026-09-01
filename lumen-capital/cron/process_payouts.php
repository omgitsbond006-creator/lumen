<?php
/**
 * Payout engine — the heart of the investment simulation.
 *
 * Responsibilities:
 *   1. Credit daily ROI to every active "daily" investment whose next
 *      payout is due.
 *   2. Mature any active investment whose term has ended, returning the
 *      principal (plus any still-owed profit) to the user's balance.
 *
 * This file can be run two ways:
 *   - As a CLI cron job:   php cron/process_payouts.php
 *   - Included and invoked from the admin dashboard's "Run Payouts Now"
 *     button, for environments without a real cron scheduler.
 */

require_once __DIR__ . '/../includes/init.php';

/**
 * @return array{payouts:int, payout_total:float, matured:int, matured_total:float, errors:string[]}
 */
function process_all_due_payouts(): array
{
    $pdo = db();
    $summary = ['payouts' => 0, 'payout_total' => 0.0, 'matured' => 0, 'matured_total' => 0.0, 'errors' => []];

    // -------------------------------------------------------------
    // 1) Daily ROI payouts
    // -------------------------------------------------------------
    $due = $pdo->query("SELECT * FROM investments
        WHERE status = 'active' AND payout_type = 'daily'
        AND next_payout_at IS NOT NULL AND next_payout_at <= NOW()
        AND next_payout_at < maturity_date")->fetchAll();

    foreach ($due as $inv) {
        try {
            $payoutAmount = round((float) $inv['amount'] * ((float) $inv['roi_percent'] / 100), 2);

            record_transaction(
                (int) $inv['user_id'],
                'roi_payout',
                $payoutAmount,
                'investment',
                (int) $inv['id'],
                'Daily ROI — ' . $inv['plan_name']
            );

            $nextPayout = date('Y-m-d H:i:s', strtotime($inv['next_payout_at'] . ' +1 day'));
            $pdo->prepare('UPDATE investments SET paid_out = paid_out + ?, last_payout_at = NOW(), next_payout_at = ? WHERE id = ?')
                ->execute([$payoutAmount, $nextPayout, $inv['id']]);

            notify((int) $inv['user_id'], 'Daily payout received', 'You earned ' . money($payoutAmount) . ' from your ' . $inv['plan_name'] . ' investment.', 'success');

            $summary['payouts']++;
            $summary['payout_total'] += $payoutAmount;
        } catch (Throwable $e) {
            $summary['errors'][] = 'Investment #' . $inv['id'] . ': ' . $e->getMessage();
        }
    }

    // -------------------------------------------------------------
    // 2) Maturities — return principal + any remaining owed profit
    // -------------------------------------------------------------
    $matured = $pdo->query("SELECT * FROM investments
        WHERE status = 'active' AND maturity_date <= NOW()")->fetchAll();

    foreach ($matured as $inv) {
        try {
            $remainingProfit = round((float) $inv['expected_return'] - (float) $inv['paid_out'], 2);
            if ($remainingProfit < 0) $remainingProfit = 0;
            $payout = round((float) $inv['amount'] + $remainingProfit, 2);

            record_transaction(
                (int) $inv['user_id'],
                'maturity_payout',
                $payout,
                'investment',
                (int) $inv['id'],
                $inv['plan_name'] . ' matured — principal + profit'
            );

            $pdo->prepare("UPDATE investments SET status = 'completed', paid_out = expected_return, last_payout_at = NOW(), next_payout_at = NULL WHERE id = ?")
                ->execute([$inv['id']]);

            notify((int) $inv['user_id'], 'Investment matured', 'Your ' . $inv['plan_name'] . ' investment matured. ' . money($payout) . ' has been credited to your balance.', 'success');

            $summary['matured']++;
            $summary['matured_total'] += $payout;
        } catch (Throwable $e) {
            $summary['errors'][] = 'Investment #' . $inv['id'] . ': ' . $e->getMessage();
        }
    }

    return $summary;
}

// Allow this file to be executed directly from the command line.
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $result = process_all_due_payouts();
    echo '[' . date('Y-m-d H:i:s') . "] Payout run complete.\n";
    echo "  Daily payouts credited: {$result['payouts']} (" . money($result['payout_total']) . ")\n";
    echo "  Investments matured:    {$result['matured']} (" . money($result['matured_total']) . ")\n";
    if ($result['errors']) {
        echo "  Errors:\n";
        foreach ($result['errors'] as $err) echo "    - {$err}\n";
    }
}
