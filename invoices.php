<?php
require_once 'db.php';

// อัปเดตสถานะการชำระเงิน
if (isset($_GET['action']) && $_GET['action'] === 'pay' && isset($_GET['id'])) {
    $invoice_id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    
    echo "<script>alert('บันทึกการชำระเงินเรียบร้อยแล้ว!'); window.location.href='invoices.php';</script>";
    exit;
}

// ดึงรายการบิลทั้งหมดพร้อมรายละเอียดห้องและผู้เช่า
$sql = "SELECT i.*, r.room_number, t.full_name 
        FROM invoices i
        JOIN leases l ON i.lease_id = l.lease_id
        JOIN rooms r ON l.room_id = r.room_id
        JOIN tenants t ON l.tenant_id = t.tenant_id
        ORDER BY i.invoice_id DESC";
$invoices = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการบิลทั้งหมด - ระบบจัดการหอพัก</title>
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
                <a href="invoices.php" class="bg-indigo-700 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-file-invoice-dollar"></i> บิล & ใบเสร็จ</a>
                <a href="repairs.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-wrench"></i> แจ้งซ่อม</a>
                <a href="reports.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> รายงาน</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto mt-10 px-4 pb-12">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><i class="fa-solid fa-file-invoice text-indigo-600 mr-2"></i>รายการบิลค่าเช่าทั้งหมด</h1>
                <p class="text-sm text-slate-500">จัดการ ตรวจสอบสถานะ และพิมพ์ใบเสร็จรับเงิน</p>
            </div>
            <a href="create_invoice.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-lg shadow-indigo-200 transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> ออกบิลประจำเดือน
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4">ห้อง / ผู้เช่า</th>
                            <th class="p-4 text-center">ประจำเดือน</th>
                            <th class="p-4 text-right">ค่าห้อง</th>
                            <th class="p-4 text-right">ค่าน้ำ</th>
                            <th class="p-4 text-right">ค่าไฟ</th>
                            <th class="p-4 text-right">ยอดรวม (บาท)</th>
                            <th class="p-4 text-center">สถานะ</th>
                            <th class="p-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400">ยังไม่มีรายการบิลในระบบ</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-800">ห้อง <?= htmlspecialchars($inv['room_number']) ?></div>
                                        <div class="text-xs text-slate-500">คุณ<?= htmlspecialchars($inv['full_name']) ?></div>
                                    </td>
                                    <td class="p-4 text-center font-medium text-slate-600">
                                        <?= htmlspecialchars($inv['billing_month']) ?>
                                    </td>
                                    <td class="p-4 text-right font-medium text-slate-700">
                                        <?= number_format($inv['room_price'], 2) ?> ฿
                                    </td>
                                    <td class="p-4 text-right text-slate-600">
                                        <div class="font-medium text-slate-700"><?= number_format($inv['water_bill'], 2) ?> ฿</div>
                                        <div class="text-[11px] text-slate-400">(<?= number_format($inv['water_unit']) ?> หน่วย)</div>
                                    </td>
                                    <td class="p-4 text-right text-slate-600">
                                        <div class="font-medium text-slate-700"><?= number_format($inv['electric_bill'], 2) ?> ฿</div>
                                        <div class="text-[11px] text-slate-400">(<?= number_format($inv['electric_unit']) ?> หน่วย)</div>
                                    </td>
                                    <td class="p-4 text-right font-bold text-indigo-600">
                                        <?= number_format($inv['total_amount'], 2) ?> ฿
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php if (($inv['status'] ?? 'pending') === 'paid'): ?>
                                            <span class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-semibold">ชำระแล้ว</span>
                                        <?php else: ?>
                                            <span class="bg-amber-100 text-amber-700 text-xs px-2.5 py-1 rounded-full font-semibold"><i class="fa-solid fa-clock mr-1"></i>รอชำระ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <?php if (($inv['status'] ?? 'pending') !== 'paid'): ?>
                                                <a href="invoices.php?action=pay&id=<?= $inv['invoice_id'] ?>" 
                                                   onclick="return confirm('ยืนยันการรับชำระเงินสำหรับบิลนี้?')"
                                                   class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 shadow-sm">
                                                    <i class="fa-solid fa-check"></i> ยืนยันชำระ
                                                </a>
                                            <?php endif; ?>
                                            <a href="print_receipt.php?id=<?= $inv['invoice_id'] ?>" 
                                               target="_blank"
                                               class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1">
                                                <i class="fa-solid fa-print"></i> ใบเสร็จ
                                            </a>
                                        </div>
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