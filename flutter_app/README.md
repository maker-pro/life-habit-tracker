# 生活习惯打卡 Flutter App

这是 `life-habit-tracker` 的 Flutter 客户端，调用 Laravel API 完成今日打卡、历史记录、事项列表和统计总结展示。

## 环境要求

- Flutter 3.44+
- Dart 3.12+
- Android SDK：`D:\androidStudio\SDK`
- 已安装 Android SDK Platform 36、Platform Tools、Build Tools

## 获取依赖

```bash
flutter pub get
```

## Web 调试

```bash
flutter run -d chrome
```

## Android 真机调试

先确认设备：

```bash
adb devices
flutter devices
```

运行：

```bash
flutter run -d <device-id>
```

开发机上的 Laravel API 默认地址是：

```text
http://192.168.11.23:18083/api
```

当前默认配置用于局域网真机测试；手机和服务器需要在同一局域网内：

```text
http://192.168.11.23:18083/api
```

## 构建 APK

Debug APK：

```bash
flutter build apk --debug
```

输出位置：

```text
build/app/outputs/flutter-apk/app-debug.apk
```

Release APK：

```bash
flutter build apk --release
```

## 已实现页面

- 首页
- 今日打卡页
- 事项管理页
- 历史记录页
- 统计页
- 设置页

## 常用检查

```bash
flutter analyze
flutter test
```
