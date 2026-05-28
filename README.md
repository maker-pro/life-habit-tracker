# 生活习惯打卡统计系统

基于 Laravel 12、PHP 8.2+、MySQL 8、Redis、Layui、Blade、Chart.js 和 Flutter 的生活习惯打卡统计系统。

后台地址：`/admin`
默认账号：`admin@example.com`
默认密码：`admin123456`

## 功能

- 事项管理：新增、编辑、删除、设置颜色、图标、建议打卡时间。
- 打卡记录：按日期筛选、新增补打卡、编辑弹窗、删除记录。
- Dashboard：今日、本周、本月完成率、总打卡次数、连续打卡天数、最近习惯总结。
- 统计报告：今日总结、最近7天总结、最近30天总结、每个事项分析。
- 日历视图和时间线视图。
- Redis 缓存统计数据，打卡变更后自动清理相关缓存。
- 每日总结写入 `daily_summaries` 表。
- Flutter App 支持首页打卡、今日事项、历史、统计、设置页。

## Ubuntu 环境

```bash
sudo apt update
sudo apt install -y nginx mysql-server redis-server unzip git supervisor
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath
```

## 宝塔部署

1. 在宝塔安装 Nginx、MySQL 8、Redis、PHP 8.2+。
2. PHP 扩展启用：fileinfo、redis、mbstring、openssl、pdo_mysql、curl、zip。
3. 网站目录设置为：

```text
/www/wwwroot/life-habit-tracker/public
```

4. 项目代码放在：

```text
/www/wwwroot/life-habit-tracker
```

## Composer 和 Laravel 初始化

```bash
cd /www/wwwroot/life-habit-tracker
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize
```

## .env 配置

```env
APP_NAME="生活习惯打卡统计系统"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://你的域名

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=life_habit_tracker
DB_USERNAME=life_habit_tracker
DB_PASSWORD=你的数据库密码

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## MySQL

```sql
CREATE DATABASE life_habit_tracker DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'life_habit_tracker'@'localhost' IDENTIFIED BY '你的数据库密码';
GRANT ALL PRIVILEGES ON life_habit_tracker.* TO 'life_habit_tracker'@'localhost';
FLUSH PRIVILEGES;
```

## Redis

```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
sudo systemctl status redis-server
```

缓存 key：

- `stats:overview`：5分钟
- `stats:daily:{date}`：5分钟
- `stats:weekly:{week}`：10分钟
- `stats:monthly:{month}`：30分钟
- `stats:habit:{habit_id}`：10分钟
- `stats:timeline:{date}`：5分钟
- `stats:summary`：10分钟

## 每日总结落库

打卡新增、编辑、删除时会自动更新对应日期的 `daily_summaries`。

手动生成：

```bash
php artisan summaries:generate
php artisan summaries:generate 2026-05-27
php artisan summaries:generate 2026-05-27 --days=7
```

## Storage 权限

```bash
sudo chown -R www-data:www-data /www/wwwroot/life-habit-tracker
sudo chmod -R 775 storage bootstrap/cache
```

宝塔常见用户是 `www`：

```bash
chown -R www:www /www/wwwroot/life-habit-tracker
chmod -R 775 storage bootstrap/cache
```

## Nginx 伪静态

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

完整示例：

```nginx
server {
    listen 80;
    server_name example.com;
    root /www/wwwroot/life-habit-tracker/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Supervisor 队列

```ini
[program:life-habit-tracker-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/life-habit-tracker/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/life-habit-tracker/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start life-habit-tracker-worker:*
```

## API

```text
GET    /api/habits
POST   /api/habits
PUT    /api/habits/{id}
DELETE /api/habits/{id}

GET    /api/checkins?date=YYYY-MM-DD
POST   /api/checkins
PUT    /api/checkins/{id}
DELETE /api/checkins/{id}

GET /api/stats/overview
GET /api/stats/daily
GET /api/stats/weekly
GET /api/stats/monthly
GET /api/stats/habit/{id}
GET /api/stats/timeline
GET /api/stats/summary
```

成功返回：

```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

失败返回：

```json
{
  "code": 400,
  "message": "错误信息",
  "data": null
}
```

## Flutter App

```bash
cd flutter_app
flutter pub get
flutter run
```

Android 模拟器访问本机 Laravel 使用：

```text
http://10.0.2.2:8000/api
```

真机联调时，把手机和电脑放在同一局域网，在 App 设置页把 API 地址改为：

```text
http://电脑局域网IP:8000/api
```

部署到服务器后改为：

```text
https://你的域名/api
```

## 本地开发

```bash
php artisan serve
```

访问：

```text
http://127.0.0.1:8000/admin
```
