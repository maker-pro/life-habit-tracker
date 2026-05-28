# GitHub 与虚拟机部署流程

## 1. 本地首次推送到 GitHub

在 GitHub 创建一个空仓库，不要勾选 README、.gitignore、license。

然后在本地项目目录执行：

```bash
git remote add origin 你的GitHub仓库地址
git branch -M main
git push -u origin main
```

示例：

```bash
git remote add origin https://github.com/你的用户名/life-habit-tracker.git
git branch -M main
git push -u origin main
```

## 2. 后续每次本地提交

```bash
git status
git add .
git commit -m "描述本次修改"
git push
```

注意：`.env`、`vendor`、Flutter build、部署压缩包不会提交到 GitHub。

## 3. 虚拟机首次拉取

在宝塔网站目录的上级目录执行，例如：

```bash
cd /www/wwwroot
git clone https://github.com/你的用户名/life-habit-tracker.git
cd life-habit-tracker
```

然后配置项目：

```bash
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

`.env` 需要按你的虚拟机配置填写 MySQL 和 Redis。

## 4. 虚拟机后续更新代码

```bash
cd /www/wwwroot/life-habit-tracker
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --seed
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

如果只改了 Flutter App，不需要在服务器上构建 APK；本地构建即可。

## 5. 重要提醒

不要执行：

```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan db:wipe
```

这些命令会清空或重建数据库。
