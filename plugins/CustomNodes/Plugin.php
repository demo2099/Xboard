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

            // 获取允许的全局权限组进行校验
            $globalAllowedGroupsStr = $this->getConfig('allowed_groups', '');
            $globalAllowedGroups = [];
            if (!empty(trim($globalAllowedGroupsStr))) {
                // 将英文逗号或中文逗号隔开的字符串拆为数组
                $globalAllowedGroups = array_filter(array_map('trim', preg_split('/[,，]+/', $globalAllowedGroupsStr)));
            }

            // 获取用户填写的节点字符串文本（假定每行一个分享链接）
            $rawNodesText = $this->getConfig('nodes_text', '');
            if (empty(trim($rawNodesText))) {
                return $servers;
            }

            // 按行拆分，去掉回车符、空行
            $lines = array_filter(explode("\n", str_replace("\r", "", $rawNodesText)));
            $linksToAppend = [];
            $userGroupId = (string) $user->group_id;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line))
                    continue;

                // 检查是否包含局部权限标识符 "|"
                // 格式如: vless://XXXXX... | 1,2,3
                if (str_contains($line, '|')) {
                    $parts = explode('|', $line, 2);
                    $nodeUrl = trim($parts[0]);
                    $localGroupsStr = trim($parts[1]);
                    $localAllowedGroups = array_filter(array_map('trim', preg_split('/[,，]+/', $localGroupsStr)));

                    // 如果局部指定了权限组，且当前用户不在局部允许内，抛弃本条
                    if (!empty($localAllowedGroups) && !in_array($userGroupId, $localAllowedGroups, true)) {
                        continue;
                    }
                    $linksToAppend[] = $nodeUrl;
                } else {
                    // 没有局部标识符，则回退受制于全局权限配置
                    if (!empty($globalAllowedGroups) && !in_array($userGroupId, $globalAllowedGroups, true)) {
                        // 全局组配置了且用户不达标，跳过
                        continue;
                    }
                    $linksToAppend[] = $line;
                }
            }

            // 构造假节点容器 (Type设为 custom_raw_node)
            // 使其在 Protocols 遍历时能被特殊处理直接输出
            if (!empty($linksToAppend)) {
                $servers[] = [
                    'type' => 'custom_raw_node',
                    'name' => '外部自带节点集合',
                    'raw_links' => implode("\r\n", $linksToAppend) . "\r\n",
                    'sort' => 9999, // 确保排在最后
                ];
            }

            return $servers;
        });
    }

    public function form(): array
    {
        $groupHints = '';
        try {
            $groups = \App\Models\ServerGroup::select('id', 'name')->get();
            $groupHints = "\n\n当前可用权限组：";
            foreach ($groups as $group) {
                $groupHints .= "\n  [组ID: {$group->id}] {$group->name}";
            }
        } catch (\Throwable $e) {
        }

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
                'description' => '直接填入 vless://、trojan:// 等分享链接。支持单节点独立权限组: 在链接后加上 | 1,2 表示该节点仅对 1组 和 2组 开放。若无 | 则使用下方全局权限组。' . $groupHints
            ],
            'allowed_groups' => [
                'label' => '全局允许订阅的权限组（选填）',
                'type' => 'text',
                'description' => '可填入权限组ID进行限制（例如: 1,2,3）。如果留空，则表示所有用户权限组都可以获取这些节点！' . $groupHints
            ]
        ];
    }
}
