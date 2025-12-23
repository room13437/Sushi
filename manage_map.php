<?php
// จัดการตำแหน่งร้าน - Admin Only
require_once 'protect_admin.php';
// Include database connection (Uses products database as seen in index.php)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// สร้างตารางถ้ายังไม่มี
$conn->query("CREATE TABLE IF NOT EXISTS `store_locations` (
`id` INT(11) NOT NULL AUTO_INCREMENT,
`latitude` DECIMAL(10, 8) NOT NULL,
`longitude` DECIMAL(11, 8) NOT NULL,
`address` TEXT,
`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// บันทึกตำแหน่ง
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_location'])) {
    $lat = (float) $_POST['latitude'];
    $lng = (float) $_POST['longitude'];
    $address = $_POST['address'];

    // ตรวจสอบว่ามีข้อมูลเดิมไหม (เราต้องการแค่แถวเดียว)
    $check = $conn->query("SELECT id FROM store_locations LIMIT 1");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE store_locations SET latitude = ?, longitude = ?, address = ? WHERE id = (SELECT id FROM
(SELECT id FROM store_locations LIMIT 1) as t)");
        $stmt->bind_param("dds", $lat, $lng, $address);
    } else {
        $stmt = $conn->prepare("INSERT INTO store_locations (latitude, longitude, address) VALUES (?, ?, ?)");
        $stmt->bind_param("dds", $lat, $lng, $address);
    }

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ บันทึกพิกัดร้านสำเร็จ!</div>";
    } else {
        $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// ดึงตำแหน่งปัจจุบัน
$store = ['latitude' => 13.73972299, 'longitude' => 100.48529231, 'address' => 'ซูชิละกัน Paradise']; // Default to some cool spot
$result = $conn->query("SELECT * FROM store_locations LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $store = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📍 ตั้งค่าตำแหน่งร้าน | Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'orange': {
                            50: '#FFF8F0', 100: '#FFEDD5', 200: '#FED7AA', 300: '#FDBA74',
                            400: '#FB923C', 500: '#F97316', 600: '#EA580C', 700: '#C2410C',
                        },
                    },
                    fontFamily: {
                        'display': ['Prompt', 'sans-serif'],
                        'body': ['Sarabun', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Leaflet CSS & Control Geocoder -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 30%, #FED7AA 60%, #FDBA74 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(249, 115, 22, 0.15);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.1);
        }

        #map {
            height: 400px;
            border-radius: 1.5rem;
            z-index: 10;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #F97316, #EA580C);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.35);
            transition: all 0.3s ease;
        }

        .alert-success {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }

        /* Search Bar Custom Style */
        .leaflet-control-geocoder {
            border-radius: 1rem !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            border: none !important;
            overflow: hidden;
            margin-top: 20px !important;
            margin-left: 20px !important;
        }

        .leaflet-control-geocoder-form input {
            padding: 12px 15px !important;
            font-family: 'Sarabun', sans-serif !important;
            font-size: 14px !important;
            border: none !important;
        }

        .leaflet-control-geocoder-icon {
            width: 45px !important;
            height: 45px !important;
            background-size: 20px !important;
        }

        .alert-error {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #B91C1C;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-5xl mx-auto">

        <!-- Header -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1
                        class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">
                        📍 ตั้งค่าตำแหน่งร้าน (Google Map Support)
                    </h1>
                    <p class="text-orange-600 mt-2">คลิกบนแผนที่เพื่อระบุตำแหน่งของร้าน</p>
                </div>
                <div class="flex gap-3">
                    <a href="formmenu"
                        class="px-5 py-3 rounded-xl bg-orange-100 text-orange-600 font-display font-bold hover:bg-orange-200 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> กลับ
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-6 font-display font-semibold">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Map Side -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-3xl p-4">
                    <div id="map"></div>
                </div>
            </div>

            <!-- Form Side -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-3xl p-8 h-full">
                    <h2 class="text-xl font-display font-bold text-orange-800 mb-6 flex items-center gap-3">
                        <i class="fas fa-map-pin text-orange-500"></i> ข้อมูลตำแหน่ง
                    </h2>

                    <form method="POST" action="" class="space-y-6">
                        <div>
                            <label class="block text-orange-700 font-display font-semibold mb-2 text-sm">Latitude
                                (ละติจูด)</label>
                            <input type="text" name="latitude" id="lat_input" value="<?php echo $store['latitude']; ?>"
                                required readonly
                                class="w-full px-5 py-3 rounded-2xl border-2 border-orange-200 bg-orange-50 text-orange-800 font-mono focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-orange-700 font-display font-semibold mb-2 text-sm">Longitude
                                (ลองจิจูด)</label>
                            <input type="text" name="longitude" id="lng_input"
                                value="<?php echo $store['longitude']; ?>" required readonly
                                class="w-full px-5 py-3 rounded-2xl border-2 border-orange-200 bg-orange-50 text-orange-800 font-mono focus:outline-none">
                        </div>
                        <div>
                            <label
                                class="block text-orange-700 font-display font-semibold mb-2 text-sm">ชื่อสถานที่/ที่อยู่</label>
                            <textarea name="address" required rows="3"
                                class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none"><?php echo htmlspecialchars($store['address']); ?></textarea>
                        </div>

                        <button type="submit" name="save_location" value="1"
                            class="w-full py-4 rounded-2xl btn-gradient text-white font-display font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all">
                            <i class="fas fa-save mr-2"></i> บันทึกตำแหน่งนี้
                        </button>

                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-2xl text-blue-600 text-xs">
                            <i class="fas fa-info-circle mr-1"></i>
                            ตำแหน่งนี้จะถูกนำไปใช้คำนวณเส้นทางจากตำแหน่งของลูกค้าในหน้าแรก (index.php)
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Leaflet JS & Control Geocoder -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script>
        // พิกัดเริ่มต้น
        var startLat = <?php echo $store['latitude']; ?>;
        var startLng = <?php echo $store['longitude']; ?>;

        var map = L.map('map').setView([startLat, startLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Fix Leaflet Default Icon path
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        });

        var marker = L.marker([startLat, startLng], {
            draggable: true
        }).addTo(map);

        // เพิ่มช่องค้นหา (รองรับภาษาไทย)
        var geocoder = L.Control.geocoder({
            defaultMarkGeocode: false,
            placeholder: "🔍 ค้นหาสถานที่หรือที่อยู่ (ภาษาไทย)...",
            errorMessage: "ไม่พบสถานที่นี้",
            geocoder: L.Control.Geocoder.nominatim({
                geocodingQueryParams: {
                    "accept-language": "th"
                }
            })
        })
            .on('markgeocode', function (e) {
                var bbox = e.geocode.bbox;
                var poly = L.polygon([
                    bbox.getSouthEast(),
                    bbox.getNorthEast(),
                    bbox.getNorthWest(),
                    bbox.getSouthWest()
                ]);
                map.fitBounds(poly.getBounds());

                // ย้ายหมุดและอัปเดตค่า
                var center = e.geocode.center;
                marker.setLatLng(center);
                updateInputs(center.lat, center.lng);

                // อัปเดตที่อยู่ในช่อง Textarea
                document.getElementsByName('address')[0].value = e.geocode.name;
            })
            .addTo(map);

        // เมื่อคลิกบนแผนที่
        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateInputs(e.latlng.lat, e.latlng.lng);
        });

        // เมื่อลาก Marker
        marker.on('dragend', function (e) {
            var position = marker.getLatLng();
            updateInputs(position.lat, position.lng);
        });

        function updateInputs(lat, lng) {
            document.getElementById('lat_input').value = lat.toFixed(8);
            document.getElementById('lng_input').value = lng.toFixed(8);
        }
    </script>
</body>

</html>