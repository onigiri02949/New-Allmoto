<?php
require_once 'db.php';

// บันทึกรายการแจ้งซ่อมใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_repair'])) {
    $room_id = $_POST['room_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];

    $stmt = $pdo->prepare("INSERT INTO repairs (room_id, title, description, priority, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$room_id, $title, $description, $priority]);

    echo "<script>alert('บันทึกการแจ้งซ่อมเรียบร้อยแล้ว!'); window.location.href='repairs.php';</script>";
    exit;
}

// อัปเดตสถานะการซ่อม
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['status'])) {
    $repair_id = $_GET['id'];
    $status = $_GET['status'];

    $stmt = $pdo->prepare("UPDATE repairs SET status = ? WHERE repair_id = ?");
    $stmt->execute([$status, $repair_id]);

    echo "<script>alert('อัปเดตสถานะเรียบร้อยแล้ว!'); window.location.href='repairs.php';</script>";
    exit;
}

// ดึงรายการห้องพักทั้งหมดไว้เลือกใน Dropdown
$rooms = $pdo->query("SELECT room_id, room_number FROM rooms ORDER BY room_number ASC")->fetchAll();

// ดึงข้อมูลรายการแจ้งซ่อมทั้งหมด
$sql = "SELECT rp.*, r.room_number 
        FROM repairs rp
        JOIN rooms r ON rp.room_id = r.room_id
        ORDER BY rp.repair_id DESC";
$repairs = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแจ้งซ่อม - ระบบจัดการหอพัก</title>
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
                <a href="repairs.php" class="bg-indigo-700 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-wrench"></i> แจ้งซ่อม</a>
                <a href="reports.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> รายงาน</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto mt-10 px-4 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- ฟอร์มเพิ่มรายการแจ้งซ่อม -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden sticky top-24">
                    <div class="bg-indigo-50/50 p-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md">
                            <i class="fa-solid fa-screwdriver-wrench text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">บันทึกแจ้งซ่อมใหม่</h2>
                            <p class="text-xs text-slate-500">กรอกรายละเอียดสิ่งของชำรุด</p>
                        </div>
                    </div>

                    <form method="POST" action="" class="p-5 space-y-4">
                        <input type="hidden" name="add_repair" value="1">

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">ห้องพัก</label>
                            <select name="room_id" required class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-slate-50">
                                <option value="">-- เลือกห้องพัก --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['room_id'] ?>">ห้อง <?= htmlspecialchars($r['room_number']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">หัวข้อ / อุปกรณ์ที่ชำรุด</label>
                            <input type="text" name="title" required placeholder="เช่น ท่อน้ำรั่ว, แอร์ไม่เย็น" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">รายละเอียดเพิ่มเติม</label>
                            <textarea name="description" rows="3" placeholder="ระบุอาการชำรุด หรือเวลาที่สะดวกให้เข้าซ่อม" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">ระดับความเร่งด่วน</label>
                            <select name="priority" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm bg-slate-50">
                                <option value="low">ปกติ</option>
                                <option value="medium">ปานกลาง</option>
                                <option value="high">ด่วนมาก</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-xl shadow-md transition text-sm flex items-center justify-center gap-2 mt-2">
                            <i class="fa-solid fa-paper-plane"></i> ส่งรายการแจ้งซ่อม
                        </button>
                    </form>
                </div>
            </div>

            <!-- ตารางแสดงรายการแจ้งซ่อมทั้งหมด -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-indigo-600"></i> รายการแจ้งซ่อมทั้งหมด
                        </h2>
                        <span class="text-xs bg-slate-200 text-slate-700 font-semibold px-2.5 py-1 rounded-full">
                            <?= count($repairs) ?> รายการ
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                                    <th class="p-4">ห้อง</th>
                                    <th class="p-4">รายการ / รายละเอียด</th>
                                    <th class="p-4 text-center">ความเร่งด่วน</th>
                                    <th class="p-4 text-center">สถานะ</th>
                                    <th class="p-4 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php if (empty($repairs)): ?>
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400">ยังไม่มีรายการแจ้งซ่อมในระบบ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($repairs as $rp): ?>
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="p-4 font-bold text-indigo-600">
                                                ห้อง <?= htmlspecialchars($rp['room_number']) ?>
                                            </td>
                                            <td class="p-4">
                                                <div class="font-semibold text-slate-800"><?= htmlspecialchars($rp['title']) ?></div>
                                                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($rp['description'] ?? '-') ?></div>
                                            </td>
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
                                            <td class="p-4 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <?php if ($rp['status'] === 'pending'): ?>
                                                        <a href="repairs.php?action=update&id=<?= $rp['repair_id'] ?>&status=in_progress" 
                                                           class="bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded text-xs font-medium transition">
                                                            เริ่มซ่อม
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($rp['status'] !== 'completed'): ?>
                                                        <a href="repairs.php?action=update&id=<?= $rp['repair_id'] ?>&status=completed" 
                                                           class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded text-xs font-medium transition">
                                                            เสร็จสิ้น
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-xs text-slate-400"><i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i>เรียบร้อย</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>