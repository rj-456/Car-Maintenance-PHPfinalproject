<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// Initialize SQLite database
$dbPath = __DIR__ . '/appointments.db';
$db = new SQLite3($dbPath);

// Create table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    service_date TEXT NOT NULL,
    vehicle_model TEXT NOT NULL,
    custom_vehicle_model TEXT,
    service_type TEXT NOT NULL,
    custom_service_type TEXT,
    media_url TEXT,
    notes TEXT,
    status TEXT DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Gracefully migrate existing databases if custom_service_type column does not exist
$result = $db->query("PRAGMA table_info(appointments)");
$hasCustomService = false;
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if ($row['name'] === 'custom_service_type') {
        $hasCustomService = true;
        break;
    }
}
if (!$hasCustomService) {
    @$db->exec("ALTER TABLE appointments ADD COLUMN custom_service_type TEXT");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $isValid = true;

    // 1. Validate Name
    if (empty($data->name)) {
        $errors['name'] = "Name is required";
        $isValid = false;
    } else {
        $name = trim($data->name);
        if (!preg_match("/^[a-zA-Z-' ]*$/", $name)) {
            $errors['name'] = "Only letters and spaces allowed";
            $isValid = false;
        }
    }

    // 2. Validate Email
    if (empty($data->email)) {
        $errors['email'] = "Email is required";
        $isValid = false;
    } else {
        $email = trim($data->email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
            $isValid = false;
        }
    }

    // 3. Validate Service Date
    if (empty($data->service_date)) {
        $errors['service_date'] = "Preferred date is required";
        $isValid = false;
    }

    // 4. Validate Vehicle Model
    if (empty($data->vehicle_model)) {
        $errors['vehicle_model'] = "Please select a vehicle model";
        $isValid = false;
    } else {
        if ($data->vehicle_model === "Other") {
            if (empty($data->custom_vehicle_model)) {
                $errors['custom_vehicle_model'] = "Please type your vehicle model";
                $isValid = false;
            }
        }
    }

    // 5. Validate Service Type
    if (empty($data->service_type)) {
        $errors['service_type'] = "Please select a service type";
        $isValid = false;
    } else {
        if ($data->service_type === "Other") {
            if (empty($data->custom_service_type)) {
                $errors['custom_service_type'] = "Please type the service needed";
                $isValid = false;
            }
        }
    }

    // 6. Validate Media URL (Optional)
    if (!empty($data->media_url)) {
        if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $data->media_url)) {
            $errors['media_url'] = "Invalid URL layout";
            $isValid = false;
        }
    }

    if ($isValid) {
        $media_url = $data->media_url ?? "";
        
        // Handle Base64 file upload if present
        if (!empty($data->media_file)) {
            // Find base64 header
            if (preg_match('/^data:(image|video)\/(\w+);base64,/', $data->media_file, $matches)) {
                $fileType = $matches[1]; // image or video
                $fileExt = strtolower($matches[2]); // png, jpeg, mp4, etc.
                
                // Allow safe file formats
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'mp4', 'mov', 'avi', 'mpeg'];
                if (in_array($fileExt, $allowedExtensions)) {
                    $base64Data = substr($data->media_file, strpos($data->media_file, ',') + 1);
                    $decodedFile = base64_decode($base64Data);
                    
                    if ($decodedFile !== false) {
                        $uploadsDir = __DIR__ . '/uploads/';
                        if (!file_exists($uploadsDir)) {
                            mkdir($uploadsDir, 0777, true);
                        }
                        
                        // Generate unique filename
                        $filename = uniqid('upload_', true) . '.' . $fileExt;
                        $filepath = $uploadsDir . $filename;
                        
                        if (file_put_contents($filepath, $decodedFile)) {
                            // Point database record to the local relative link
                            $media_url = 'http://localhost/backend/uploads/' . $filename;
                        }
                    }
                }
            }
        }

        $stmt = $db->prepare("INSERT INTO appointments (
            name, email, service_date, vehicle_model, custom_vehicle_model, service_type, custom_service_type, media_url, notes, status
        ) VALUES (
            :name, :email, :service_date, :vehicle_model, :custom_vehicle_model, :service_type, :custom_service_type, :media_url, :notes, 'Pending'
        )");
        
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':service_date', $data->service_date, SQLITE3_TEXT);
        $stmt->bindValue(':vehicle_model', $data->vehicle_model, SQLITE3_TEXT);
        $stmt->bindValue(':custom_vehicle_model', $data->custom_vehicle_model ?? "", SQLITE3_TEXT);
        $stmt->bindValue(':service_type', $data->service_type, SQLITE3_TEXT);
        $stmt->bindValue(':custom_service_type', $data->custom_service_type ?? "", SQLITE3_TEXT);
        $stmt->bindValue(':media_url', $media_url, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $data->notes ?? "", SQLITE3_TEXT);
        
        $result = $stmt->execute();
        
        if ($result) {
            http_response_code(201);
            echo json_encode([
                "message" => "Appointment successfully booked.",
                "id" => $db->lastInsertRowID(),
                "name" => $name,
                "email" => $email,
                "service_date" => $data->service_date,
                "vehicle_model" => $data->vehicle_model,
                "custom_vehicle_model" => $data->custom_vehicle_model ?? "",
                "service_type" => $data->service_type,
                "custom_service_type" => $data->custom_service_type ?? "",
                "media_url" => $media_url,
                "notes" => $data->notes ?? "",
                "status" => "Pending"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to save appointment to the database."]);
        }
    } else {
        http_response_code(400);
        echo json_encode($errors);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
}
?>
