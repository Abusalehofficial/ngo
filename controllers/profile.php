<?php
// Require authentication
if (!isLoggedIn()) {
    redirect('/login');
}

$pdo = getDB();
$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? 'update_profile';
        
        if ($action === 'update_profile') {
            // Update profile
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $address = trim($_POST['address'] ?? '');
            
            if (empty($name) || empty($phone)) {
                $error = 'Name and phone are required';
            } else {
                $profile_image = $user['profile_image'];
                
                // Handle profile image upload
                if (!empty($_FILES['profile_image']['name'])) {
                    $upload = uploadFile($_FILES['profile_image'], 'uploads/profiles', ['jpg', 'jpeg', 'png']);
                    if ($upload['success']) {
                        // Delete old image
                        if ($profile_image && file_exists("uploads/profiles/$profile_image")) {
                            unlink("uploads/profiles/$profile_image");
                        }
                        $profile_image = $upload['filename'];
                    }
                }
                
                // Update user
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, phone = ?, whatsapp = ?, address = ?, profile_image = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $phone, $whatsapp ?: $phone, $address, $profile_image, $user['id']]);
                
                $success = 'Profile updated successfully';
            }
            
        } elseif ($action === 'update_nominee') {
            // Update nominee details
            $nominee_name = trim($_POST['nominee_name'] ?? '');
            $relationship = trim($_POST['relationship'] ?? '');
            $nominee_phone = trim($_POST['nominee_phone'] ?? '');
            $nominee_address = trim($_POST['nominee_address'] ?? '');
            
            // Check if nominee exists
            $stmt = $pdo->prepare("SELECT id FROM nominees WHERE user_id = ? LIMIT 1");
            $stmt->execute([$user['id']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $stmt = $pdo->prepare("
                    UPDATE nominees 
                    SET nominee_name = ?, relationship = ?, phone = ?, address = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$nominee_name, $relationship, $nominee_phone, $nominee_address, $user['id']]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("
                    INSERT INTO nominees (user_id, nominee_name, relationship, phone, address) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user['id'], $nominee_name, $relationship, $nominee_phone, $nominee_address]);
            }
            
            $success = 'Nominee details updated successfully';
        }
    }
}

if ($error) {
    setFlash('error', $error);
} elseif ($success) {
    setFlash('success', $success);
}

redirect('/dashboard');