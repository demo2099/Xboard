<?php

namespace Plugin\CustomNodes;

use App\Services\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function boot(): void
    {
        // 挂载到客户端获取节点列表的末尾
        $this->filter('client.subscribe.servers', function ($servers, $user, $request) {

            // 是否启用了该插件
            if (!$this->getConfig('enabled', false)) {
                return $servers;
            }

            // 获取用户填写的节点字符串文本（假定每行一个分享链接）
            $rawNodesText = $this->getConfig('nodes_text', '');
            if (empty(trim($rawNodesText))) {
                return $servers;
            }

            // 按行拆分，去掉回车符、空行
            $links = array_filter(explode("\n", str_replace("\r", "", $rawNodesText)));

            // 构造假节点容器 (Type设为 custom_raw_node)
            // 使其在 Protocols 遍历时能被特殊处理直接输出
            if (!empty($links)) {
                $servers[] = [
                    'type' => 'custom_raw_node',
                    'name' => '外部自带节点集合',
                    'raw_links' => implode("\r\n", $links) . "\r\n",
                    'sort' => 9999, // 确保排在最后
                ];
            }

            return $servers;
        });
    }

    public function form(): array
    {
        return [
            'enabled' => [
                'label' => '是否启用',
                'type' => 'checkbox',
                'default' => false,
                'description' => '开启后会在下发订阅时追加指定的外部节点'
            ],
            'nodes_text' => [
                'label' => '节点分享链接（每行一个）',
                'type' => 'textarea',
                'description' => '直接填入 vless://、trojan:// 等原版分享链接，它们会被附加到用户的订阅列表最后。'
            ]
        ];
    }
}
