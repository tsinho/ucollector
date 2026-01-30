// 监听插件图标点击事件
chrome.action.onClicked.addListener((tab) => {
  chrome.tabs.sendMessage(tab.id, {
    action: 'toggleFloatingWindow'
  });
});