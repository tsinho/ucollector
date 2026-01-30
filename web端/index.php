<?php
header('Content-Type: text/html; charset=utf-8');

$jsonFile = 'data.json';
$data = [];

if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    if (!empty($jsonContent)) {
        $decodedData = json_decode($jsonContent, true);
        if (is_array($decodedData)) {
            $data = $decodedData;
        }
    }
}

$totalItems = 0;
$totalPages = 1;
$currentPageItems = [];
$page = 1;

if (!empty($data)) {
    usort($data, function($a, $b) {
        $timeA = strtotime($a['created_at'] ?? '1970-01-01');
        $timeB = strtotime($b['created_at'] ?? '1970-01-01');
        return $timeB - $timeA;
    });

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 20;
    $totalItems = count($data);
    $totalPages = max(1, ceil($totalItems / $perPage));
    
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    
    $offset = ($page - 1) * $perPage;
    $currentPageItems = array_slice($data, $offset, $perPage);
} else {
    $totalItems = 0;
    $totalPages = 1;
    $currentPageItems = [];
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    if ($page > 1) {
        $page = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>无礁视频播放系统</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-play-circle"></i> 无礁网络播放系统</h1>
            <p>共 <?php echo $totalItems; ?> 个视频</p>
        </header>

        <main class="main-content">
            <div class="video-grid" id="videoGrid">
                <?php if (empty($currentPageItems)): ?>
                    <div class="no-videos">
                        <i class="fas fa-film fa-3x"></i>
                        <p>暂无视频内容</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($currentPageItems as $item): ?>
                        <?php 
                        $coverImage = 'cover.jpg';
                        if (!empty($item['images']) && is_array($item['images']) && isset($item['images'][0]['url'])) {
                            $coverImage = $item['images'][0]['url'];
                        }
                        
                        $itemId = $item['id'] ?? uniqid();
                        $itemTitle = $item['title'] ?? '无标题';
                        $imageCount = is_array($item['images'] ?? []) ? count($item['images']) : 0;
                        $createdAt = $item['created_at'] ?? date('Y-m-d H:i:s');
                        ?>
                        <div class="video-card" onclick="window.location.href='player.php?id=<?php echo urlencode($itemId); ?>'">
                            <div class="video-thumbnail">
                                <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($itemTitle); ?>" loading="lazy">
                                <div class="play-overlay">
                                    <i class="fas fa-play"></i>
                                </div>
                                <?php if ($imageCount > 0): ?>
                                <div class="image-count">
                                    <i class="fas fa-images"></i> <?php echo $imageCount; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="video-info">
                                <h3 class="video-title"><?php echo htmlspecialchars($itemTitle); ?></h3>
                                <div class="video-meta">
                                    <span class="upload-time">
                                        <i class="far fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($createdAt)); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1 && !empty($data)): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link prev">
                        <i class="fas fa-chevron-left"></i> 上一页
                    </a>
                <?php endif; ?>

                <div class="page-numbers">
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    $startPage = max(1, $endPage - 4);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <span class="page-dots">...</span>
                        <a href="?page=<?php echo $totalPages; ?>" class="page-link">
                            <?php echo $totalPages; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link next">
                        下一页 <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>

        <footer class="footer">
            <p>无礁视频播放系统 &copy; <?php echo date('Y'); ?></p>
            <?php if (!empty($data)): ?>
                <p class="page-info">第 <?php echo $page; ?> 页 / 共 <?php echo $totalPages; ?> 页</p>
            <?php endif; ?>
        </footer>
    </div>

    <script src="js/script.js"></script>
</body>
</html>