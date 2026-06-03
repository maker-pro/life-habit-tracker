import 'package:flutter/material.dart';

import '../services/api_client.dart';

class SettingsPage extends StatefulWidget {
  const SettingsPage({super.key});

  @override
  State<SettingsPage> createState() => _SettingsPageState();
}

class _SettingsPageState extends State<SettingsPage> {
  late final TextEditingController urlController = TextEditingController(text: ApiClient.baseUrl);
  late final TextEditingController tokenController = TextEditingController(text: ApiClient.apiToken);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('设置')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(
            controller: urlController,
            decoration: const InputDecoration(labelText: 'API 地址', border: OutlineInputBorder()),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: tokenController,
            obscureText: true,
            decoration: const InputDecoration(labelText: 'API Token', border: OutlineInputBorder()),
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: () {
              ApiClient.baseUrl = urlController.text.trim();
              ApiClient.apiToken = tokenController.text.trim();
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('API 设置已更新')));
            },
            child: const Text('保存设置'),
          ),
        ],
      ),
    );
  }
}
