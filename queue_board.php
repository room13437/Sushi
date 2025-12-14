<?php
date_default_timezone_set('Asia/Bangkok');
include "db_config.php";

// Get Today's Queues
$today = date('Y-m-d');
$sql = "SELECT queue_number, customer_name, status FROM daily_queue 
        WHERE queue_date = '$today' 
        ORDER BY queue_number ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10"> <!-- Auto Refresh every 10s -->
    <title>🍲 กระดานคิว | Delizio Queue 📺</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;900&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-orange: #ff6f00;
            --deep-orange: #e65100;
            --light-orange: #ff9800;
            --accent-orange: #ffb74d;
            --dark-bg: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-light: #ffffff;
            --text-muted: #b0b0b0;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 50px;
            padding: 50px 40px;
            background: var(--card-bg);
            border-radius: 25px;
            border-left: 6px solid var(--primary-orange);
            border-right: 6px solid var(--deep-orange);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-orange), var(--deep-orange), var(--primary-orange));
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-orange), var(--deep-orange), var(--primary-orange));
        }

        .header-icon {
            font-size: 4.5rem;
            margin-bottom: 15px;
            display: inline-block;
        }

        h1 {
            font-family: 'Prompt', sans-serif;
            font-size: 3.8rem;
            font-weight: 900;
            margin: 20px 0;
            color: var(--primary-orange);
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .subtitle {
            font-size: 1.5rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .date-badge {
            background: linear-gradient(135deg, var(--primary-orange), var(--deep-orange));
            padding: 10px 25px;
            border-radius: 50px;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
            color: white;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: var(--card-bg);
            padding: 20px 35px;
            border-radius: 15px;
            border-bottom: 4px solid var(--primary-orange);
            min-width: 150px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--primary-orange);
            font-family: 'Prompt', sans-serif;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-muted);
            margin-top: 5px;
            font-weight: 600;
        }

        /* Queue Grid */
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
            padding-bottom: 100px;
        }

        /* Queue Cards */
        .queue-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            border: 3px solid transparent;
            animation: fadeInUp 0.5s ease-out backwards;
        }

        .queue-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-orange), var(--deep-orange));
            border-radius: 0 0 20px 20px;
        }

        .queue-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary-orange);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .queue-label {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .queue-number {
            font-family: 'Prompt', sans-serif;
            font-size: 7rem;
            font-weight: 900;
            color: var(--primary-orange);
            line-height: 1;
            margin: 25px 0;
        }

        .customer-name {
            font-size: 1.9rem;
            font-weight: 700;
            color: white;
            margin-top: 25px;
            padding: 15px 25px;
            background: rgba(255, 111, 0, 0.1);
            border-radius: 15px;
            border: 2px solid rgba(255, 111, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .customer-name i {
            color: var(--primary-orange);
            font-size: 1.7rem;
        }

        /* Status Badges */
        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-Waiting {
            background: linear-gradient(135deg, var(--light-orange), var(--primary-orange));
        }

        .status-Called {
            background: linear-gradient(135deg, #66bb6a, #43a047);
        }

        .status-Completed {
            background: linear-gradient(135deg, #9e9e9e, #757575);
        }

        .status-Cancelled {
            background: linear-gradient(135deg, #ef5350, #e53935);
        }

        /* Empty State */
        .no-queue {
            grid-column: 1 / -1;
            text-align: center;
            padding: 120px 40px;
            background: var(--card-bg);
            border-radius: 25px;
            border: 3px dashed rgba(255, 111, 0, 0.3);
        }

        .no-queue i {
            font-size: 6rem;
            color: var(--primary-orange);
            margin-bottom: 25px;
            display: block;
        }

        .no-queue-text {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-light);
        }

        /* Home Button */
        .btn-home {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: linear-gradient(135deg, var(--primary-orange), var(--deep-orange));
            color: white;
            padding: 20px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.2rem;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }

        .btn-home:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, var(--deep-orange), var(--primary-orange));
        }

        .btn-home i {
            font-size: 1.4rem;
        }

        /* Live Indicator */
        .live-indicator {
            position: fixed;
            top: 30px;
            right: 30px;
            background: var(--card-bg);
            padding: 14px 28px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid var(--primary-orange);
            z-index: 1000;
        }

        .live-dot {
            width: 14px;
            height: 14px;
            background: var(--primary-orange);
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            h1 {
                font-size: 2.5rem;
                letter-spacing: 1px;
            }

            .header {
                padding: 35px 25px;
            }

            .header-icon {
                font-size: 3rem;
            }

            .subtitle {
                flex-direction: column;
                font-size: 1.2rem;
                gap: 12px;
            }

            .stats-bar {
                gap: 15px;
            }

            .stat-item {
                padding: 15px 25px;
                min-width: 120px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .queue-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .queue-number {
                font-size: 5rem;
            }

            .customer-name {
                font-size: 1.5rem;
                padding: 12px 20px;
            }

            .btn-home {
                bottom: 20px;
                right: 20px;
                padding: 16px 30px;
                font-size: 1rem;
            }

            .live-indicator {
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                font-size: 0.95rem;
            }

            .live-dot {
                width: 12px;
                height: 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Live Indicator -->
    <div class="live-indicator">
        <div class="live-dot"></div>
        <span>LIVE</span>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">🍲</div>
            <h1><i class="fas fa-fire-alt"></i> รายการคิววันนี้</h1>
            <div class="subtitle">
                <span class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></span>
                <span>รอเรียกคิวเพื่อความอร่อย</span>
            </div>
        </div>

        <!-- Stats Bar -->
        <?php
        $result_stats = $conn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Waiting' THEN 1 ELSE 0 END) as waiting,
            SUM(CASE WHEN status = 'Called' THEN 1 ELSE 0 END) as called
            FROM daily_queue WHERE queue_date = '$today'");
        $stats = $result_stats->fetch_assoc();
        ?>
        <?php if ($stats['total'] > 0): ?>
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">คิวทั้งหมด</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['waiting']; ?></div>
                    <div class="stat-label">รอเรียก</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['called']; ?></div>
                    <div class="stat-label">กำลังบริการ</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Queue Grid -->
        <div class="queue-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $delay = 0; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="queue-card" style="animation-delay: <?php echo $delay; ?>s;">
                        <div class="status-badge status-<?php echo $row['status']; ?>">
                            <?php
                            $statusThai = [
                                'Waiting' => '🕐 รอเรียก',
                                'Called' => '📢 เรียกแล้ว',
                                'Completed' => '✅ เสร็จสิ้น',
                                'Cancelled' => '❌ ยกเลิก'
                            ];
                            echo $statusThai[$row['status']] ?? $row['status'];
                            ?>
                        </div>
                        <div class="queue-label">คิวที่</div>
                        <div class="queue-number">
                            <?php echo str_pad($row['queue_number'], 3, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="customer-name">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($row['customer_name']); ?></span>
                        </div>
                    </div>
                    <?php $delay += 0.08; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-queue">
                    <i class="fas fa-utensils"></i>
                    <div class="no-queue-text">🌟 เตรียมตัวให้พร้อม... คิวแรกกำลังจะมา! 🌟</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <a href="index" class="btn-home">
        <i class="fas fa-home"></i>
        <span>กลับหน้าหลัก</span>
    </a>

</body>

</html>