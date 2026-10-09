<?php
require_once 'db.php';

// ดึงข้อมูลสรุปภาพรวมรายรับทั้งหมด
$total_income_sql = "SELECT 
    SUM(total_amount) AS total_billed,
    SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) AS total_paid,
    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) AS total_pending
    FROM invoices";
$summary = $pdo->query($total_income_sql)->fetch();

// ดึงข้อมูลสรุปรายรับแยกตามเดือน
$monthly_sql = "SELECT 
    billing_month,
    COUNT(invoice_id) AS total_invoices,
    SUM(total_amount) AS total_amount,
    SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) AS pending_amount
    FROM invoices
    GROUP BY billing_month
    ORDER BY billing_month DESC";
$monthly_reports = $pdo->query($monthly_sql)->fetchAll();

// ดึงจำนวนห้องทั้งหมดและสถานะว่าง/มีผู้เช่า
$room_stats = $pdo->query("SELECT 
    COUNT(*) AS total_rooms,
    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
    SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) AS vacant_rooms
    FROM rooms")->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุป - ระบบจัดการหอพัก</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

    <!-- Navbar -->
    <nav class="bg-indigo-600 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-building text-2xl"></i>
                <span class="font-semibold text-lg tracking-wide">Dormitory System</span>
            </div>
            <div class="flex space-x-2">
                <a href="dashboard.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                <a href="rooms.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-door-open"></i> ห้องพัก/ผู้เช่า</a>
                <a href="invoices.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-file-invoice-dollar"></i> บิล & ใบเสร็จ</a>
                <a href="repairs.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-wrench"></i> แจ้งซ่อม</a>
                <a href="reports.php" class="bg-indigo-700 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> รายงาน</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto mt-10 px-4 pb-12">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800"><i class="fa-solid fa-chart-column text-indigo-600 mr-2"></i>รายงานสรุปรายรับและสถิติหอพัก</h1>
            <p class="text-sm text-slate-500">ภาพรวมการเงินและการเข้าพักทั้งหมดในระบบ</p>
        </div>

        <!-- การ์ดสรุปตัวเลขรวม -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ยอดเรียกเก็บรวมทั้งหมด</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= number_format($summary['total_billed'] ?? 0, 2) ?> ฿</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">ชำระแล้วเรียบร้อย</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-1"><?= number_format($summary['total_paid'] ?? 0, 2) ?> ฿</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">ค้างชำระสะสม</p>
                    <h3 class="text-2xl font-bold text-amber-600 mt-1"><?= number_format($summary['total_pending'] ?? 0, 2) ?> ฿</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- ตารางสรุปรายรับแยกตามเดือน -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-indigo-600"></i> สรุปรายรับแยกตามเดือน
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4">ประจำเดือน</th>
                            <th class="p-4 text-center">จำนวนบิล</th>
                            <th class="p-4 text-right">ยอดรวม (บาท)</th>
                            <th class="p-4 text-right text-emerald-600">ชำระแล้ว (บาท)</th>
                            <th class="p-4 text-right text-amber-600">ค้างชำระ (บาท)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($monthly_reports)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">ยังไม่มีข้อมูลรายรับในระบบ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthly_reports as $m): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-4 font-bold text-slate-800">
                                        <?= htmlspecialchars($m['billing_month']) ?>
                                    </td>
                                    <td class="p-4 text-center text-slate-600">
                                        <?= $m['total_invoices'] ?> ฉบับ
                                    </td>
                                    <td class="p-4 text-right font-bold text-slate-800">
                                        <?= number_format($m['total_amount'], 2) ?>
                                    </td>
                                    <td class="p-4 text-right font-semibold text-emerald-600">
                                        <?= number_format($m['paid_amount'], 2) ?>
                                    </td>
                                    <td class="p-4 text-right font-semibold text-amber-600">
                                        <?= number_format($m['pending_amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>