<?php
// สร้างหน้าพิมพ์รหัสโค้ด - Admin Only
require_once 'protect_admin.php';
include "db.php";

// ดึงข้อมูลโค้ดทั้งหมด
$codes = [];
$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM code_redemptions WHERE code_id = r.id) as redemption_count
        FROM redeem_codes r 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $codes[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รหัสโค้ดสำหรับพิมพ์ | Sushi</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&family=Prompt:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', Arial, sans-serif;
            background: white;
            color: #333;
        }

        /* ซ่อนปุ่มเมื่อพิมพ์ */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            .page-break {
                page-break-after: always;
            }

            .code-card {
                page-break-inside: avoid;
            }
        }

        /* Control Bar */
        .control-bar {
            background: linear-gradient(135deg, #F97316, #EA580C);
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }

        .control-bar .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: white;
            color: #F97316;
        }

        .btn-primary:hover {
            background: #FFF8F0;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Content */
        .content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header Section */
        .header-section {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #F97316;
        }

        .header-section h1 {
            font-family: 'Prompt', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #F97316;
            margin-bottom: 10px;
        }

        .header-section .subtitle {
            color: #666;
            font-size: 16px;
        }

        .header-section .meta {
            margin-top: 15px;
            color: #999;
            font-size: 14px;
        }

        /* Table Style */
        .table-section {
            margin-bottom: 60px;
        }

        .table-section h2 {
            font-family: 'Prompt', sans-serif;
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        thead {
            background: linear-gradient(135deg, #F97316, #EA580C);
            color: white;
        }

        thead th {
            padding: 15px;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
            text-align: center;
            font-size: 14px;
        }

        tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }

        tbody tr:nth-child(even) {
            background: #FFF8F0;
        }

        tbody tr:hover {
            background: #FFEDD5;
        }

        tbody td {
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }

        .code-cell {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 16px;
            color: #F97316;
            background: #FFF8F0;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
        }

        .points-cell {
            color: #059669;
            font-weight: 700;
            font-size: 15px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-active {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-used {
            background: #FEE2E2;
            color: #B91C1C;
        }

        /* Cards Grid */
        .cards-section {
            margin-top: 60px;
        }

        .cards-section h2 {
            font-family: 'Prompt', sans-serif;
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .cards-section .instruction {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-style: italic;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .code-card {
            border: 2px dashed #ddd;
            border-radius: 16px;
            padding: 25px;
            background: linear-gradient(135deg, #FFF8F0 0%, #FFEDD5 100%);
            text-align: center;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .code-card.used {
            background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
            opacity: 0.7;
        }

        .code-card .brand {
            font-family: 'Prompt', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #F97316;
            margin-bottom: 5px;
        }

        .code-card .label {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

        .code-card .code {
            font-family: 'Courier New', monospace;
            font-size: 28px;
            font-weight: bold;
            color: #111;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin: 15px 0;
            letter-spacing: 2px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .code-card .points {
            font-size: 22px;
            font-weight: 700;
            color: #059669;
            margin: 15px 0;
        }

        .code-card .status {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }

        .code-card .status.active {
            background: #059669;
            color: white;
        }

        .code-card .status.used {
            background: #DC2626;
            color: white;
        }

        .code-card .footer {
            margin-top: 15px;
            font-size: 11px;
            color: #999;
        }

        .summary-box {
            background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
            border-left: 5px solid #3B82F6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .summary-box strong {
            color: #1E40AF;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <!-- Control Bar -->
    <div class="control-bar no-print">
        <div class="container">
            <h1 style="margin: 0; font-size: 20px;">
                <i class="fas fa-file-pdf"></i> เอกสารรหัสโค้ด
            </h1>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> พิมพ์เอกสาร
                </button>
                <a href="manage_codes" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Header -->
        <div class="header-section">
            <h1>🍣 ซูชิละกัน - รหัสแลกแต้ม</h1>
            <p class="subtitle">รายการโค้ดทั้งหมดในระบบ</p>
            <p class="meta">
                พิมพ์เมื่อ: <?php echo date('d/m/Y H:i:s'); ?> |
                จำนวนโค้ด: <?php echo count($codes); ?> รายการ
            </p>
        </div>

        <?php if (empty($codes)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 20px;"></i>
                <p style="font-size: 18px;">ยังไม่มีรหัสโค้ดในระบบ</p>
            </div>
        <?php else: ?>
            <!-- Summary -->
            <div class="summary-box">
                <strong>สรุป:</strong> เอกสารนี้แสดงรหัสโค้ดทั้งหมด <?php echo count($codes); ?> รายการ
                สำหรับแจกให้ลูกค้าหรือจัดเก็บเป็นหลักฐาน
            </div>

            <!-- Table Section -->
            <div class="table-section">
                <h2>
                    <i class="fas fa-table"></i> รายการโค้ดทั้งหมด
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">ลำดับ</th>
                            <th>รหัสโค้ด</th>
                            <th>คะแนน</th>
                            <th>การใช้งาน</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($codes as $code):
                            $used = (int) $code['redemption_count'];
                            $max = (int) $code['max_uses'];
                            $is_used_up = $used >= $max;
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <span class="code-cell"><?php echo htmlspecialchars($code['code']); ?></span>
                                </td>
                                <td class="points-cell">+<?php echo number_format($code['points']); ?></td>
                                <td><?php echo $used . '/' . $max; ?> ครั้ง</td>
                                <td>
                                    <span class="status-badge <?php echo $is_used_up ? 'status-used' : 'status-active'; ?>">
                                        <?php echo $is_used_up ? '❌ ใช้หมดแล้ว' : '✅ พร้อมใช้'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Page Break before Cards -->
            <div class="page-break"></div>

            <!-- Cards Section -->
            <div class="cards-section">
                <h2>✂️ การ์ดโค้ดสำหรับตัดแจก</h2>
                <p class="instruction">ตัดตามเส้นประ แล้วแจกให้ลูกค้า</p>

                <div class="cards-grid">
                    <?php foreach ($codes as $code):
                        $used = (int) $code['redemption_count'];
                        $max = (int) $code['max_uses'];
                        $is_used_up = $used >= $max;
                        ?>
                        <div class="code-card <?php echo $is_used_up ? 'used' : ''; ?>">
                            <div class="brand">🍣 ซูชิละกัน</div>
                            <div class="label">รหัสแลกแต้ม</div>
                            <div class="code"><?php echo htmlspecialchars($code['code']); ?></div>
                            <div class="points">+<?php echo number_format($code['points']); ?> คะแนน</div>
                            <div class="status <?php echo $is_used_up ? 'used' : 'active'; ?>">
                                <?php echo $is_used_up ? '❌ ใช้หมดแล้ว' : '✅ พร้อมใช้งาน'; ?>
                            </div>
                            <div class="footer">
                                สร้างเมื่อ: <?php echo date('d/m/Y', strtotime($code['created_at'])); ?><br>
                                ใช้ได้: <?php echo $used . '/' . $max; ?> ครั้ง
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // ทำให้พิมพ์ง่ายด้วย Ctrl+P
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>

</html>