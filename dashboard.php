<?php
require_once 'db.php';

// ดึงจำนวนห้องทั้งหมด, ห้องว่าง, และห้องที่มีผู้เช่า
$room_stats = $pdo->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied,
    SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) AS vacant
    FROM rooms")->fetch();

// ดึงยอดบิลที่ค้างชำระ
$pending_invoices = $pdo->query("SELECT COUNT(*) AS total_pending, SUM(total_amount) AS total_amount FROM invoices WHERE status = 'pending'")->fetch();

// ดึงจำนวนการแจ้งซ่อมที่รอซ่อม
$pending_repairs = $pdo->query("SELECT COUNT(*) AS total FROM repairs WHERE status != 'completed'")->fetch();

// ดึงรายการแจ้งซ่อมล่าสุด 5 รายการ
$recent_repairs_sql = "SELECT rp.*, r.room_number 
                      FROM repairs rp 
                      JOIN rooms r ON rp.room_id = r.room_id 
                      ORDER BY rp.repair_id DESC LIMIT 5";
$recent_repairs = $pdo->query($recent_repairs_sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ระบบจัดการหอพัก</title>
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
                <a href="dashboard.php" class="bg-indigo-700 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                <a href="rooms.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-door-open"></i> ห้องพัก/ผู้เช่า</a>
                <a href="invoices.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-file-invoice-dollar"></i> บิล & ใบเสร็จ</a>
                <a href="repairs.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-wrench"></i> แจ้งซ่อม</a>
                <a href="reports.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> รายงาน</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto mt-10 px-4 pb-12">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">ยินดีต้อนรับสู่ระบบจัดการหอพัก</h1>
                <p class="text-sm text-slate-500">ภาพรวมสถิติและข้อมูลที่ต้องดำเนินการวันนี้</p>
            </div>
            <a href="create_invoice.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-lg shadow-indigo-200 transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> ออกบิลประจำเดือน
            </a>
        </div>

        <!-- การ์ดสรุปสถิติ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- ห้องพักทั้งหมด -->
            <div class="bg-white p-5 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ห้องพักทั้งหมด</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $room_stats['total'] ?> <span class="text-sm font-normal text-slate-500">ห้อง</span></h3>
                    <p class="text-xs text-indigo-600 font-medium mt-1">ว่าง <?= $room_stats['vacant'] ?> / มีผู้เช่า <?= $room_stats['occupied'] ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-door-open"></i>
                </div>
            </div>

            <!-- บิลรอชำระ -->
            <div class="bg-white p-5 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">บิลค้างชำระ</p>
                    <h3 class="text-2xl font-bold text-amber-600 mt-1"><?= $pending_invoices['total_pending'] ?> <span class="text-sm font-normal text-slate-500">รายการ</span></h3>
                    <p class="text-xs text-slate-500 mt-1">ยอดรวม <?= number_format($pending_invoices['total_amount'] ?? 0, 2) ?> ฿</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>

            <!-- รายการแจ้งซ่อมรอดำเนินการ -->
            <div class="bg-white p-5 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider">งานซ่อมรอดำเนินการ</p>
                    <h3 class="text-2xl font-bold text-rose-600 mt-1"><?= $pending_repairs['total'] ?> <span class="text-sm font-normal text-slate-500">รายการ</span></h3>
                    <p class="text-xs text-slate-500 mt-1">ต้องการการแก้ไข</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
            </div>

            <!-- อัตราการเข้าพัก -->
            <div class="bg-white p-5 rounded-2xl shadow-lg border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">อัตราการเข้าพัก</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-1">
                        <?= $room_stats['total'] > 0 ? round(($room_stats['occupied'] / $room_stats['total']) * 100) : 0 ?>%
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">จากห้องพักทั้งหมด</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
        </div>

        <!-- รายการแจ้งซ่อมล่าสุด -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-wrench text-indigo-600"></i> รายการแจ้งซ่อมล่าสุด
                </h2>
                <a href="repairs.php" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                    ดูทั้งหมด <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4">ห้อง</th>
                            <th class="p-4">รายการ / อาการ</th>
                            <th class="p-4 text-center">ความเร่งด่วน</th>
                            <th class="p-4 text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($recent_repairs)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400">ยังไม่มีรายการแจ้งซ่อมล่าสุด</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_repairs as $rp): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-4 font-bold text-indigo-600">ห้อง <?= htmlspecialchars($rp['room_number']) ?></td>
                                    <td class="p-4 font-medium text-slate-800"><?= htmlspecialchars($rp['title']) ?></td>
                                    <td class="p-4 text-center">
                                        <?php if ($rp['priority'] === 'high'): ?>
                                            <span class="bg-red-100 text-red-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">ด่วนมาก</span>
                                        <?php elseif ($rp['priority'] === 'medium'): ?>
                                            <span class="bg-amber-100 text-amber-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">ปานกลาง</span>
                                        <?php else: ?>
                                            <span class="bg-slate-100 text-slate-600 text-xs px-2.5 py-0.5 rounded-full font-semibold">ปกติ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php if ($rp['status'] === 'completed'): ?>
                                            <span class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">ซ่อมเสร็จแล้ว</span>
                                        <?php elseif ($rp['status'] === 'in_progress'): ?>
                                            <span class="bg-blue-100 text-blue-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">กำลังดำเนินการ</span>
                                        <?php else: ?>
                                            <span class="bg-amber-100 text-amber-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">รอรับเรื่อง</span>
                                        <?php endif; ?>
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