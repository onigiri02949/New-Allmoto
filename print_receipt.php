<?php
require_once 'db.php';

$invoice_id = $_GET['id'] ?? null;

if (!$invoice_id) {
    echo "<script>alert('ไม่พบข้อมูลใบเสร็จ'); window.location.href='invoices.php';</script>";
    exit;
}

// ดึงข้อมูลบิลพร้อมรายละเอียดห้องและผู้เช่า
$stmt = $pdo->prepare("
    SELECT i.*, r.room_number, r.price_per_month, t.full_name, t.phone 
    FROM invoices i
    JOIN leases l ON i.lease_id = l.lease_id
    JOIN rooms r ON l.room_id = r.room_id
    JOIN tenants t ON l.tenant_id = t.tenant_id
    WHERE i.invoice_id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    echo "ไม่พบข้อมูลรายการนี้";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน / ใบแจ้งหนี้ - ห้อง <?= htmlspecialchars($invoice['room_number']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; }
            .print-card { shadow: none; border: none; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen p-4 sm:p-8">

    <!-- แถบปุ่มกด พิมพ์/ย้อนกลับ (จะไม่แสดงเวลาสั่งพิมพ์) -->
    <div class="max-w-2xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="invoices.php" class="text-slate-600 hover:text-slate-900 font-medium flex items-center gap-2 text-sm bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 transition">
            <i class="fa-solid fa-arrow-left"></i> ย้อนกลับไปหน้ารายการบิล
        </a>
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <i class="fa-solid fa-print"></i> พิมพ์ / บันทึก PDF
        </button>
    </div>

    <!-- เอกสารใบเสร็จ -->
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-xl border border-slate-200 print-card">
        <!-- หัวเอกสาร -->
        <div class="flex justify-between items-start border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-2xl font-bold text-indigo-900">ใบเสร็จรับเงิน / ใบแจ้งหนี้</h1>
                <p class="text-xs text-slate-500 mt-1">ระบบจัดการหอพัก Dormitory Management</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-400">เลขที่ใบเสร็จ</div>
                <div class="text-sm font-bold text-slate-700">#INV-<?= str_pad($invoice['invoice_id'], 5, '0', STR_PAD_LEFT) ?></div>
                <div class="text-xs text-slate-500 mt-1">ประจำเดือน: <span class="font-semibold text-slate-700"><?= htmlspecialchars($invoice['billing_month']) ?></span></div>
            </div>
        </div>

        <!-- รายละเอียดห้องพักและผู้เช่า -->
        <div class="grid grid-cols-2 gap-4 my-6 p-4 bg-slate-50 rounded-xl border border-slate-100 text-sm">
            <div>
                <div class="text-xs text-slate-400 mb-1">ผู้เช่า / ผู้ชำระเงิน</div>
                <div class="font-bold text-slate-800">คุณ<?= htmlspecialchars($invoice['full_name']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><i class="fa-solid fa-phone text-slate-400 mr-1"></i><?= htmlspecialchars($invoice['phone'] ?? '-') ?></div>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-400 mb-1">ห้องพัก</div>
                <div class="text-lg font-bold text-indigo-600">ห้อง <?= htmlspecialchars($invoice['room_number']) ?></div>
                <div class="mt-1">
                    <?php if ($invoice['status'] === 'paid'): ?>
                        <span class="inline-block bg-emerald-100 text-emerald-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">ชำระแล้ว</span>
                    <?php else: ?>
                        <span class="inline-block bg-amber-100 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">รอชำระเงิน</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ตารางรายการค่าใช้จ่าย -->
        <table class="w-full text-left border-collapse text-sm mb-6">
            <thead>
                <tr class="border-b-2 border-slate-200 text-slate-600 text-xs uppercase">
                    <th class="py-3">รายการ</th>
                    <th class="py-3 text-center">หน่วยที่ใช้</th>
                    <th class="py-3 text-right">จำนวนเงิน (บาท)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr>
                    <td class="py-3 font-medium">ค่าเช่าห้องพัก</td>
                    <td class="py-3 text-center text-slate-400">-</td>
                    <td class="py-3 text-right font-medium"><?= number_format($invoice['room_price'], 2) ?></td>
                </tr>
                <tr>
                    <td class="py-3 font-medium">ค่าน้ำประปา (18 บ./หน่วย)</td>
                    <td class="py-3 text-center"><?= number_format($invoice['water_unit']) ?></td>
                    <td class="py-3 text-right font-medium"><?= number_format($invoice['water_bill'], 2) ?></td>
                </tr>
                <tr>
                    <td class="py-3 font-medium">ค่าไฟฟ้า (8 บ./หน่วย)</td>
                    <td class="py-3 text-center"><?= number_format($invoice['electric_unit']) ?></td>
                    <td class="py-3 text-right font-medium"><?= number_format($invoice['electric_bill'], 2) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-200">
                    <td colspan="2" class="py-4 font-bold text-slate-800 text-base">ยอดรวมทั้งสิ้น</td>
                    <td class="py-4 text-right font-bold text-indigo-600 text-xl"><?= number_format($invoice['total_amount'], 2) ?> ฿</td>
                </tr>
            </tfoot>
        </table>

        <!-- ลายเซ็น -->
        <div class="mt-12 pt-6 border-t border-slate-100 grid grid-cols-2 text-center text-xs text-slate-500 gap-8">
            <div>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto mb-2"></div>
                <p>ลงชื่อ ผู้จ่ายเงิน</p>
            </div>
            <div>
                <div class="h-12 border-b border-dashed border-slate-300 w-32 mx-auto mb-2"></div>
                <p>ลงชื่อ ผู้รับเงิน (ผู้ดูแลหอพัก)</p>
            </div>
        </div>
    </div>

</body>
</html>