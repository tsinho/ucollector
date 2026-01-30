// 全局设置
let globalSettings = {
    floatingWindowEnabled: true,
    apiUrl: 'http://127.0.0.1:9999/api.php'
};

// 加载设置
function loadSettings() {
    chrome.storage.sync.get(['floatingWindowEnabled', 'apiUrl'], (result) => {
        if (result.floatingWindowEnabled !== undefined) {
            globalSettings.floatingWindowEnabled = result.floatingWindowEnabled;
        }
        if (result.apiUrl) {
            globalSettings.apiUrl = result.apiUrl;
        }
        
        // 根据设置更新悬浮窗
        updateFloatingWindow();
    });
}

// 创建悬浮窗
function createFloatingWindow() {
  const floatWindow = document.createElement('div');
  floatWindow.id = 'wreckless-collector';
  floatWindow.style.position = 'fixed';
  floatWindow.style.top = '20px';
  floatWindow.style.right = '20px';
  floatWindow.style.width = '60px';
  floatWindow.style.height = '60px';
  floatWindow.style.backgroundColor = '#4CAF50';
  floatWindow.style.borderRadius = '50%';
  floatWindow.style.display = 'flex';
  floatWindow.style.alignItems = 'center';
  floatWindow.style.justifyContent = 'center';
  floatWindow.style.cursor = 'pointer';
  floatWindow.style.zIndex = '9999';
  floatWindow.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
  floatWindow.title = '点击采集当前页面资源';
  
  // 显示"无"字
  const text = document.createElement('div');
  text.style.color = 'white';
  text.style.fontSize = '24px';
  text.style.fontWeight = 'bold';
  text.textContent = '无';
  floatWindow.appendChild(text);
  
  // 点击事件 - 直接触发采集
  floatWindow.addEventListener('click', (e) => {
    e.stopPropagation();
    sniffResources();
  });
  
  document.body.appendChild(floatWindow);
}

// 更新悬浮窗状态
function updateFloatingWindow() {
  const existingWindow = document.getElementById('wreckless-collector');
  
  if (globalSettings.floatingWindowEnabled) {
    // 如果需要显示悬浮窗但不存在，则创建
    if (!existingWindow) {
      createFloatingWindow();
    }
  } else {
    // 如果需要隐藏悬浮窗且存在，则移除
    if (existingWindow) {
      existingWindow.remove();
    }
  }
}

// 嗅探资源并发送数据
async function sniffResources() {
  console.log('开始嗅探资源...');
  
  // 获取标题
  const titleElement = document.querySelector('h1.post-title');
  const title = titleElement ? titleElement.textContent.trim() : '无标题';
  console.log('获取到标题:', title);
  
  // 收集图片资源（只收集base64编码的图片）
  const images = [];
  const imgElements = document.querySelectorAll('img[data-action="zoom"]');
  imgElements.forEach((img, index) => {
    const imgUrl = img.getAttribute('src');
    if (imgUrl && imgUrl.startsWith('data:image/')) {
      console.log(`嗅探到图片: ${index + 1}`);
      images.push(imgUrl);
    }
  });
  
  // 收集视频资源（m3u8直链）
  const videos = [];
  const videoElements = document.querySelectorAll('div.dplayer.dplayer-no-danmaku');
  videoElements.forEach((videoDiv, index) => {
    const configStr = videoDiv.getAttribute('data-config');
    if (configStr) {
      try {
        const config = JSON.parse(configStr);
        if (config.video && config.video.url && config.video.url.includes('.m3u8')) {
          const videoUrl = config.video.url;
          console.log(`嗅探到视频: ${videoUrl}`);
          videos.push(videoUrl);
        }
      } catch (e) {
        console.error('解析视频配置失败:', e);
      }
    }
  });
  
  // 检查是否有资源
  if (images.length === 0 || videos.length === 0) {
    console.log('未嗅探到资源，跳过此页面');
    showAutoCloseMessage('当前页面没有嗅探到资源');
    return;
  }
  
  // 构建请求数据
  const requestData = {
    title: title,
    images: images,
    video: videos.length > 0 ? videos[0] : ''
  };
  
  console.log('准备发送的数据:', requestData);
  
  // 在当前页面提交数据
  try {
    console.log('准备提交数据:', requestData);
    
    // 检查是否有图片
    if (requestData.images.length === 0) {
      throw new Error('没有收集到图片');
    }
    
    // 检查是否有视频URL
    if (!requestData.video) {
      throw new Error('没有收集到视频URL');
    }
    
    // 创建一个临时表单，使用multipart/form-data编码
    const form = document.createElement('form');
    form.action = globalSettings.apiUrl;
    form.method = 'POST';
    form.style.display = 'none';
    form.enctype = 'multipart/form-data';
    
    // 添加title参数
    const titleInput = document.createElement('input');
    titleInput.type = 'hidden';
    titleInput.name = 'title';
    titleInput.value = requestData.title;
    form.appendChild(titleInput);
    
    // 添加video参数
    const videoInput = document.createElement('input');
    videoInput.type = 'hidden';
    videoInput.name = 'video';
    videoInput.value = requestData.video;
    form.appendChild(videoInput);
    
    // 处理图片（将base64转换为文件）
    requestData.images.forEach((base64Image, index) => {
      // 从base64字符串中提取文件类型和数据
      const match = base64Image.match(/^data:(.+?);base64,(.+)$/);
      if (!match) {
        console.error('无效的base64图片格式');
        return;
      }
      
      const [, mimeType, base64Data] = match;
      const extension = mimeType.split('/')[1];
      const fileName = `image_${index + 1}.${extension}`;
      
      // 将base64转换为Blob
      const binaryString = atob(base64Data);
      const length = binaryString.length;
      const uint8Array = new Uint8Array(length);
      
      for (let i = 0; i < length; i++) {
        uint8Array[i] = binaryString.charCodeAt(i);
      }
      
      const blob = new Blob([uint8Array], { type: mimeType });
      
      // 创建一个文件输入字段
      const fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.name = 'images[]';
      
      // 将Blob转换为File对象并赋值给输入字段
      const file = new File([blob], fileName, { type: mimeType });
      
      // 使用DataTransfer来模拟文件选择
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      fileInput.files = dataTransfer.files;
      
      form.appendChild(fileInput);
    });
    
    // 添加到页面并提交
    document.body.appendChild(form);
    form.submit();
    
    // 清理表单
    setTimeout(() => {
      form.remove();
    }, 1000);
    
    console.log('数据已提交');
    showAutoCloseMessage('数据已提交');
  } catch (error) {
    console.error('提交数据失败:', error);
    showAutoCloseMessage('提交数据失败：' + error.message);
  }
}

// 显示自动关闭的提示
function showAutoCloseMessage(message, duration = 2000) {
  // 创建提示元素
  const messageElement = document.createElement('div');
  messageElement.style.position = 'fixed';
  messageElement.style.top = '20px';
  messageElement.style.left = '50%';
  messageElement.style.transform = 'translateX(-50%)';
  messageElement.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
  messageElement.style.color = 'white';
  messageElement.style.padding = '12px 24px';
  messageElement.style.borderRadius = '4px';
  messageElement.style.zIndex = '10000';
  messageElement.style.fontSize = '14px';
  messageElement.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
  messageElement.textContent = message;
  
  // 添加到页面
  document.body.appendChild(messageElement);
  
  // 自动移除
  setTimeout(() => {
    // 添加淡出动画
    messageElement.style.transition = 'opacity 0.5s ease';
    messageElement.style.opacity = '0';
    
    // 动画结束后移除元素
    setTimeout(() => {
      if (messageElement.parentNode) {
        messageElement.parentNode.removeChild(messageElement);
      }
    }, 500);
  }, duration);
}

// 监听来自background script或popup的消息
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === 'toggleFloatingWindow') {
    toggleFloatingWindow();
  } else if (message.action === 'settingsUpdated') {
    // 更新全局设置
    if (message.settings) {
      if (message.settings.floatingWindowEnabled !== undefined) {
        globalSettings.floatingWindowEnabled = message.settings.floatingWindowEnabled;
      }
      if (message.settings.apiUrl) {
        globalSettings.apiUrl = message.settings.apiUrl;
      }
      // 根据新设置更新悬浮窗
      updateFloatingWindow();
    }
  }
});

// 显示或隐藏悬浮窗
function toggleFloatingWindow() {
  const existingWindow = document.getElementById('wreckless-collector');
  if (existingWindow) {
    // 如果悬浮窗已存在，则移除它（关闭）
    existingWindow.remove();
  } else {
    // 如果悬浮窗不存在，则创建它（显示）
    createFloatingWindow();
  }
}

// 初始化
function init() {
  // 加载设置
  loadSettings();
}

// 页面加载完成后初始化
window.addEventListener('load', init);