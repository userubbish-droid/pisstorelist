# Hostinger Web Hosting（PHP）部署版

这是给 **Hostinger Web Hosting（共享主机 / PHP 环境）** 用的版本：PHP + SQLite + Session 登录 + Telegram 提醒。

## 上传方法

1. 打开 Hostinger 面板 → File Manager
2. 进入 `public_html/`
3. 把本目录 `hostinger-php/` 里的文件 **全部上传到 `public_html/`**（保持目录结构）

上传后目录大致如下：

```
public_html/
  index.php
  install.php
  logout.php
  config.php
  lib/
  templates/
  data/          (需要可写)
```

## 初始化安装

1. 访问 `https://你的临时域名.hostingersite.com/install.php`
2. 按页面提示创建管理员账号、写入配置、初始化数据库
3. **安装完成后务必删除 `install.php`**（安全）

## Telegram 提醒（可选）

在 `config.php` 里填写：

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`
- `TELEGRAM_THROTTLE_MINUTES`

当物品库存 **低于阈值** 时会提醒，并按节流时间避免刷屏。

## 注意

- `data/` 目录必须可写（Hostinger 一般默认可写；若不行，在 File Manager 里把权限设为可写）
- SQLite 数据库文件默认在 `data/app.db`

