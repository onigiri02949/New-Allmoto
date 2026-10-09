<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $lease_id = $_POST['lease_id'];
    $month = $_POST['billing_month'];
    $water_unit = $_POST['water_unit'];
    $electric_unit = $_POST['electric_unit'];
    $room_price = $_POST['room_price'];
    
    $water_bill = $water_unit * 18;
    $electric_bill = $electric_unit * 8;
    $total = $room_price + $water_bill + $electric_bill;

    $stmt = $pdo->prepare("INSERT INTO invoices (lease_id, billing_month, water_unit, electric_unit, water_bill, electric_bill, room_price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$lease_id, $month, $water_unit, $electric_unit, $water_bill, $electric_bill, $room_price, $total]);

    echo "<script>alert('บันทึกและออกบิลเรียบร้อยแล้ว!'); window.location.href='invoices.php';</script>";
    exit;
}

$sql = "SELECT l.lease_id, r.room_number, r.price_per_month, t.full_name 
        FROM leases l
        JOIN rooms r ON l.room_id = r.room_id
        JOIN tenants t ON l.tenant_id = t.tenant_id
        WHERE l.status = 'active'";
$leases = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกบิลประจำเดือน - ระบบจัดการหอพัก</title>
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
                <a href="reports.php" class="hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> รายงาน</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto mt-10 px-4 pb-12">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="bg-indigo-50/50 p-6 border-b border-slate-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md">
                    <i class="fa-solid fa-calculator text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800">คำนวณและออกบิลประจำเดือน</h2>
                    <p class="text-xs text-slate-500">กรอกข้อมูลค่าน้ำและค่าไฟเพื่อสร้างใบแจ้งหนี้</p>
                </div>
            </div>

            <form method="POST" action="" class="p-6 space-y-5">
                <input type="hidden" name="create_invoice" value="1">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-solid fa-bed text-indigo-500 mr-1"></i> ห้องพัก / ผู้เช่า</label>
                    <select name="lease_id" id="lease_select" onchange="updateRoomPrice()" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50/50">
                        <option value="">-- เลือกห้องพัก --</option>
                        <?php foreach ($leases as $lease): ?>
                            <option value="<?= $lease['lease_id'] ?>" data-price="<?= $lease['price_per_month'] ?>">
                                ห้อง <?= $lease['room_number'] ?> — คุณ<?= $lease['full_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-regular fa-calendar-days text-indigo-500 mr-1"></i> ประจำเดือน</label>
                        <input type="month" name="billing_month" value="<?= date('Y-m') ?>" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1"><i class="fa-solid fa-tag text-indigo-500 mr-1"></i> ค่าห้อง (บาท)</label>
                        <input type="number" id="room_price" name="room_price" readonly placeholder="0.00" class="w-full px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-100 text-slate-600 font-semibold cursor-not-allowed">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
                        <label class="block text-sm font-semibold text-blue-900 mb-1"><i class="fa-solid fa-droplet text-blue-500 mr-1"></i> หน่วยน้ำที่ใช้ (18 บ./หน่วย)</label>
                        <input type="number" name="water_unit" required min="0" value="0" class="w-full px-4 py-2 rounded-lg border border-blue-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    </div>

                    <div class="p-4 bg-amber-50/50 border border-amber-100 rounded-xl">
                        <label class="block text-sm font-semibold text-amber-900 mb-1"><i class="fa-solid fa-bolt text-amber-500 mr-1"></i> หน่วยไฟที่ใช้ (8 บ./หน่วย)</label>
                        <input type="number" name="electric_unit" required min="0" value="0" class="w-full px-4 py-2 rounded-lg border border-amber-200 focus:ring-2 focus:ring-amber-500 outline-none bg-white">
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-xl shadow-lg shadow-indigo-200 transition duration-200 flex items-center justify-center gap-2 text-base mt-4">
                    <i class="fa-solid fa-paper-plane"></i> บันทึกและออกบิลค่าเช่า
                </button>
            </form>
        </div>
    </main>

    <script>
        function updateRoomPrice() {
            const select = document.getElementById('lease_select');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price') || 0;
            document.getElementById('room_price').value = price;
        }
    </script>
</body>
</html>