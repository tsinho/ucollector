// 标签切换功能
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // 移除所有标签的active类
            tabs.forEach(t => t.classList.remove('active'));
            // 添加当前标签的active类
            tab.classList.add('active');
            
            // 隐藏所有内容
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            // 显示当前标签的内容
            const tabId = tab.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// 加载设置
function loadSettings() {
    chrome.storage.sync.get(['floatingWindowEnabled', 'apiUrl'], (result) => {
        // 加载悬浮窗设置
        document.getElementById('floating-window-toggle').checked = result.floatingWindowEnabled !== false;
        
        // 加载API地址
        document.getElementById('api-url').value = result.apiUrl || 'http://127.0.0.1:9999/api.php';
    });
}

// 保存设置
function saveSettings() {
    const settings = {
        floatingWindowEnabled: document.getElementById('floating-window-toggle').checked,
        apiUrl: document.getElementById('api-url').value
    };
    
    chrome.storage.sync.set(settings, () => {
        // 显示保存成功消息
        const statusMessage = document.getElementById('status-message');
        statusMessage.textContent = '设置保存成功！';
        statusMessage.className = 'status-message status-success';
        statusMessage.style.display = 'block';
        
        // 3秒后隐藏消息
        setTimeout(() => {
            statusMessage.style.display = 'none';
        }, 3000);
        
        // 向所有标签页发送设置更新消息
        chrome.tabs.query({}, (tabs) => {
            tabs.forEach(tab => {
                chrome.tabs.sendMessage(tab.id, {
                    action: 'settingsUpdated',
                    settings: settings
                });
            });
        });
    });
}

// 初始化
function init() {
    initTabs();
    loadSettings();
    
    // 保存设置按钮点击事件
    document.getElementById('save-settings').addEventListener('click', saveSettings);
}

// 页面加载完成后初始化
window.addEventListener('DOMContentLoaded', init);