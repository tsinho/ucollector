<?php
$config = [
    'upload_dir' => 'uploads/',
    'data_file' => 'data.json',
    'base_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/',
    'max_file_size' => 5 * 1024 * 1024,
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
];

if (!file_exists($config['upload_dir'])) {
    mkdir($config['upload_dir'], 0755, true);
}

function processUpload($config) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $title = $_POST['title'] ?? '';
        if (empty($title)) {
            throw new Exception('标题不能为空');
        }
        
        $video_url = $_POST['video'] ?? '';
        if (empty($video_url)) {
            throw new Exception('视频URL不能为空');
        }
        
        if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
            throw new Exception('视频URL格式不正确');
        }
        
        $image_urls = [];
        
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $image_count = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $image_count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    $original_name = $_FILES['images']['name'][$i];
                    $file_type = $_FILES['images']['type'][$i];
                    $file_size = $_FILES['images']['size'][$i];
                    
                    if (!in_array($file_type, $config['allowed_image_types'])) {
                        throw new Exception("图片类型不允许");
                    }
                    
                    if ($file_size > $config['max_file_size']) {
                        throw new Exception("图片大小超过限制");
                    }
                    
                    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                    $upload_path = $config['upload_dir'] . $unique_filename;
                    
                    if (!move_uploaded_file($tmp_name, $upload_path)) {
                        throw new Exception("图片上传失败");
                    }
                    
                    $image_urls[] = [
                        'filename' => $unique_filename,
                        'url' => $config['base_url'] . $upload_path,
                        'upload_time' => date('Y-m-d H:i:s')
                    ];
                } elseif ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception("图片上传错误");
                }
            }
        } else {
            throw new Exception('没有收到图片文件');
        }
        
        if (empty($image_urls)) {
            throw new Exception('请至少上传一张图片');
        }
        
        $data_entry = [
            'id' => uniqid('entry_'),
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'video_url' => htmlspecialchars($video_url, ENT_QUOTES, 'UTF-8'),
            'images' => $image_urls,
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $existing_data = [];
        if (file_exists($config['data_file'])) {
            $json_content = file_get_contents($config['data_file']);
            $existing_data = json_decode($json_content, true) ?? [];
        }
        
        $existing_data[] = $data_entry;
        
        $json_result = json_encode($existing_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($config['data_file'], $json_result) === false) {
            throw new Exception('数据保存失败');
        }
        
        $response['success'] = true;
        $response['message'] = '采集成功';
        
    } catch (Exception $e) {
        $response['message'] = '采集失败: ' . $e->getMessage();
    }
    
    return $response;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = processUpload($config);
} else {
    $result = ['success' => false, 'message' => '只支持POST请求'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>采集结果</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .result-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .status-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 36px;
            animation: pulse 1s ease-in-out;
        }
        
        .success .status-icon {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .error .status-icon {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .message {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .sub-message {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 25px;
        }
        
        .countdown {
            text-align: center;
            font-size: 14px;
            color: #777;
            padding: 10px;
            background: #f1f3f5;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .countdown-number {
            font-weight: 700;
            color: #667eea;
            font-size: 18px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-close {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-back {
            background: #f1f3f5;
            color: #555;
        }
        
        .btn-back:hover {
            background: #e9ecef;
        }
        
        @media (max-width: 480px) {
            .result-box {
                padding: 30px 20px;
            }
            
            .status-icon {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }
            
            .message {
                font-size: 18px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="result-box <?php echo $result['success'] ? 'success' : 'error'; ?>">
        <div class="status-icon">
            <?php if ($result['success']): ?>
                <i class="fas fa-check-circle"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php endif; ?>
        </div>
        
        <div class="message">
            <?php echo htmlspecialchars($result['success'] ? '采集成功' : '采集失败'); ?>
        </div>
        
        <div class="sub-message">
            <?php 
                $message = $result['message'];
                if ($result['success']) {
                    echo '数据已保存到系统';
                } else {
                    echo htmlspecialchars($message);
                }
            ?>
        </div>
        
        <div class="countdown">
            页面将在 <span class="countdown-number" id="countdown">1</span> 秒后自动关闭
        </div>
    </div>

    <script>
        let countdown = 1;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                closeWindow();
            }
        }, 1000);
        
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        function closeWindow() {
            if (window.opener) {
                window.close();
            } else {
                window.history.back();
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeWindow();
            }
            if (e.key === 'Enter') {
                closeWindow();
            }
        });
    </script>
</body>
</html>