<?php
require_once 'db.php';

// ฟังก์ชันดึงค่าราคาห้องพักอัตโนมัติ (พร้อมกำหนดค่าเริ่มต้นเป็น 3500 หากไม่พบข้อมูล)
function getRoomPrice($room) {
    foreach ($room as $key => $value) {
        if (in_array(strtolower($key), ['price', 'room_price', 'rent_price', 'cost', 'room_cost', 'amount', 'rate']) && is_numeric($value) && $value > 0) {
            return $value;
        }
    }
    return 3500;
}

// บันทึกเพิ่มห้องพักใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    $room_number = trim($_POST['room_number']);
    $price = $_POST['price'];

    if (!empty($room_number) && !empty($price)) {
        $cols = $pdo->query("DESCRIBE rooms")->fetchAll(PDO::FETCH_COLUMN);
        $priceCol = 'price';
        if (in_array('room_price', $cols)) $priceCol = 'room_price';
        elseif (in_array('rent_price', $cols)) $priceCol = 'rent_price';

        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, {$priceCol}, status) VALUES (?, ?, 'vacant')");
        $stmt->execute([$room_number, $price]);
        echo "<script>alert('เพิ่มห้องพักเรียบร้อย!'); window.location.href='rooms.php';</script>";
        exit;
    }
}

// บันทึกผู้เช่าย้ายเข้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_in') {
    $room_id = $_POST['room_id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    if (!empty($room_id) && !empty($full_name)) {
        $stmt = $pdo->prepare("INSERT INTO tenants (full_name, phone) VALUES (?, ?)");
        $stmt->execute([$full_name, $phone]);
        $tenant_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO leases (room_id, tenant_id, start_date, status) VALUES (?, ?, CURDATE(), 'active')");
        $stmt->execute([$room_id, $tenant_id]);

        $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
        $stmt->execute([$room_id]);

        echo "<script>alert('บันทึกผู้เช่าย้ายเข้าเรียบร้อย!'); window.location.href='rooms.php';</script>";
        exit;
    }
}

// บันทึกย้ายออก (แก้ไขแล้ว: ตัด end_date ออก เพื่อแก้ Error 1054)
if (isset($_GET['action']) && $_GET['action'] === 'check_out' && isset($_GET['lease_id']) && isset($_GET['room_id'])) {
    $lease_id = $_GET['lease_id'];
    $room_id = $_GET['room_id'];

    // 1. เปลี่ยนสถานะสัญญาเช่าเป็น ended
    $stmt = $pdo->prepare("UPDATE leases SET status = 'ended' WHERE lease_id = ?");
    $stmt->execute([$lease_id]);

    // 2. ปรับสถานะห้องพักกลับเป็น vacant (ห้องว่าง)
    $stmt = $pdo->prepare("UPDATE rooms SET status = 'vacant' WHERE room_id = ?");
    $stmt->execute([$room_id]);

    echo "<script>alert('บันทึกการย้ายออกเรียบร้อย!'); window.location.href='rooms.php';</script>";
    exit;
}

// ดึงรายการห้องพักทั้งหมด
$sql = "SELECT r.*, l.lease_id, t.full_name, t.phone 
        FROM rooms r 
        LEFT JOIN leases l ON r.room_id = l.room_id AND LOWER(l.status) = 'active'
        LEFT JOIN tenants t ON l.tenant_id = t.tenant_id
        ORDER BY r.room_number ASC";
$rooms = $pdo->query($sql)->fetchAll();

// กรองเฉพาะห้องว่าง
$vacant_rooms = array_filter($rooms, function($r) { 
    return empty($r['lease_id']) && strtolower($r['status'] ?? '') !== 'occupied'; 
});
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องพักและผู้เช่า - ระบบจัดการหอพัก</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; } </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

    <nav class="bg-indigo-600 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-building text-2xl"></i>
                <span class="font-semibold text-lg tracking-wide">Dormitory System</span>
            </div>
            <div class="flex space-x-2">
                <a href="dashboard.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
                <a href="rooms.php" class="bg-indigo-700 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-door-open"></i> ห้องพัก/ผู้เช่า</a>
                <a href="invoices.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-file-invoice-dollar"></i> บิล & ใบเสร็จ</a>
                <a href="repairs.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-wrench"></i> แจ้งซ่อม</a>
                <a href="reports.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> รายงาน</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto mt-10 px-4 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <!-- ฟอร์มเพิ่มผู้เช่าย้ายเข้า -->
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-emerald-600"></i> บันทึกผู้เช่าย้ายเข้า
                    </h2>
                    <form action="rooms.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="check_in">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">เลือกห้องพัก (เฉพาะห้องว่าง)</label>
                            <select name="room_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                                <option value="">-- เลือกห้องพัก --</option>
                                <?php foreach ($vacant_rooms as $v_room): ?>
                                    <option value="<?= $v_room['room_id'] ?>">ห้อง <?= htmlspecialchars($v_room['room_number']) ?> (<?= number_format(getRoomPrice($v_room)) ?> ฿/เดือน)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">ชื่อ-นามสกุล ผู้เช่า</label>
                            <input type="text" name="full_name" required placeholder="เช่น สมชาย ใจดี" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" placeholder="เช่น 0812345678" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-xl transition shadow-lg shadow-emerald-100 text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i> บันทึกการย้ายเข้า
                        </button>
                    </form>
                </div>

                <!-- ฟอร์มเพิ่มห้องพักใหม่ -->
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle text-indigo-600"></i> เพิ่มห้องพักใหม่
                    </h2>
                    <form action="rooms.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_room">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">เลขห้องพัก</label>
                            <input type="text" name="room_number" required placeholder="เช่น 109, A02" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">ค่าเช่าต่อเดือน (บาท)</label>
                            <input type="number" name="price" required placeholder="เช่น 3500" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-xl transition shadow-lg shadow-indigo-100 text-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-save"></i> บันทึกห้องพัก
                        </button>
                    </form>
                </div>
            </div>

            <!-- รายการห้องพักทั้งหมด -->
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-slate-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-border-all text-indigo-600"></i> ผังและสถานะห้องพัก
                        </h2>
                        <span class="bg-slate-100 text-slate-600 text-xs px-3 py-1 rounded-full font-medium">
                            ทั้งหมด <?= count($rooms) ?> ห้อง
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($rooms as $room): ?>
                            <?php 
                                $roomPrice = getRoomPrice($room); 
                                $isOccupied = (!empty($room['lease_id']) || strtolower($room['status'] ?? '') === 'occupied');
                            ?>
                            <div class="border rounded-2xl p-4 transition duration-200 <?= $isOccupied ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 hover:border-slate-300' ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-lg">ห้อง <?= htmlspecialchars($room['room_number']) ?></h3>
                                        <p class="text-xs text-slate-500">ค่าเช่า: <?= number_format($roomPrice, 2) ?> ฿/เดือน</p>
                                    </div>
                                    <?php if ($isOccupied): ?>
                                        <span class="bg-emerald-100 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-semibold">มีผู้เช่า</span>
                                    <?php else: ?>
                                        <span class="bg-slate-100 text-slate-600 text-xs px-2.5 py-1 rounded-full font-semibold">ห้องว่าง</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 text-xs">
                                    <?php if ($isOccupied): ?>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="font-medium text-slate-700"><i class="fa-solid fa-user text-slate-400 mr-1"></i> คุณ<?= htmlspecialchars($room['full_name'] ?? 'ผู้เช่า') ?></div>
                                                <div class="text-slate-400 mt-0.5"><i class="fa-solid fa-phone text-slate-300 mr-1"></i> <?= htmlspecialchars($room['phone'] ?? '-') ?></div>
                                            </div>
                                            <?php if (!empty($room['lease_id'])): ?>
                                                <a href="rooms.php?action=check_out&lease_id=<?= $room['lease_id'] ?>&room_id=<?= $room['room_id'] ?>" 
                                                   onclick="return confirm('ยืนยันแจ้งย้ายออกจากห้อง <?= $room['room_number'] ?>?')"
                                                   class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 rounded-lg font-medium transition flex items-center gap-1 border border-rose-200">
                                                    <i class="fa-solid fa-right-from-bracket"></i> ย้ายออก
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-slate-400 italic">
                                            <i class="fa-solid fa-circle-info mr-1"></i> พร้อมเปิดให้เช่า
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

        </div>
    </main>
</body>
</html>