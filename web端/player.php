<?php
header('Content-Type: text/html; charset=utf-8');

$jsonFile = 'data.json';
$data = json_decode(file_get_contents($jsonFile), true);

$videoId = $_GET['id'] ?? '';
$videoData = null;

foreach ($data as $item) {
    if ($item['id'] === $videoId) {
        $videoData = $item;
        break;
    }
}

if (!$videoData) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($videoData['title']); ?> - 无礁视频播放器</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://vjs.zencdn.net/8.6.1/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/videojs-contrib-hls/5.15.0/videojs-contrib-hls.min.js"></script>
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> 返回列表
            </a>
            <h1><?php echo htmlspecialchars($videoData['title']); ?></h1>
        </header>

        <main class="player-container">
            <div class="video-player-wrapper">
                <video 
                    id="my-video" 
                    class="video-js vjs-default-skin vjs-big-play-centered"
                    controls 
                    preload="auto"
                    poster="<?php echo !empty($videoData['images']) ? htmlspecialchars($videoData['images'][0]['url']) : ''; ?>"
                    data-setup='{}'>
                    <source src="<?php echo htmlspecialchars($videoData['video_url']); ?>" type="application/x-mpegURL">
                    <p class="vjs-no-js">
                        您的浏览器不支持HTML5视频，请使用现代浏览器观看。
                    </p>
                </video>
            </div>

            <div class="video-info-card">
                <h2><i class="fas fa-info-circle"></i> 视频信息</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">标题：</span>
                        <span class="info-value"><?php echo htmlspecialchars($videoData['title']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">上传时间：</span>
                        <span class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($videoData['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">图片数量：</span>
                        <span class="info-value"><?php echo count($videoData['images']); ?> 张</span>
                    </div>
                </div>
            </div>

            <div class="gallery-section">
                <h2><i class="fas fa-images"></i> 相关图片 (<?php echo count($videoData['images']); ?>张)</h2>
                <div class="gallery-grid">
                    <?php foreach ($videoData['images'] as $index => $image): ?>
                        <div class="gallery-item">
                            <img 
                                src="<?php echo htmlspecialchars($image['url']); ?>" 
                                alt="图片 <?php echo $index + 1; ?>" 
                                loading="lazy"
                                onclick="openLightbox('<?php echo htmlspecialchars($image['url']); ?>')">
                            <div class="image-info">
                                <span class="image-name"><?php echo htmlspecialchars($image['original_name']); ?></span>
                                <span class="upload-time"><?php echo $image['upload_time']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>

        <div id="lightbox" class="lightbox" onclick="closeLightbox()">
            <span class="close-btn" onclick="closeLightbox()">&times;</span>
            <img class="lightbox-content" id="lightbox-img">
            <div class="lightbox-caption" id="lightbox-caption"></div>
        </div>

        <footer class="footer">
            <p>无礁视频播放器 &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="js/script.js"></script>
    <script>
    var player = videojs('my-video');
    
    player.on('error', function() {
        console.log('视频播放错误，请检查网络或视频链接');
    });

    function openLightbox(src) {
        var lightbox = document.getElementById('lightbox');
        var lightboxImg = document.getElementById('lightbox-img');
        lightboxImg.src = src;
        lightbox.style.display = "flex";
        document.body.style.overflow = "hidden";
    }

    function closeLightbox() {
        var lightbox = document.getElementById('lightbox');
        lightbox.style.display = "none";
        document.body.style.overflow = "auto";
    }

    document.getElementById('lightbox').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
    </script>
</body>
</html>