<?php
require 'vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;

$api_id = 22575081; // Thay bằng API ID của bạn
$api_hash = '8b8610762ddae668a5a992f31475d4d4'; // Thay bằng API Hash của bạn
$session_name = 'session.madeline';
$group_link = 'https://t.me/congtacvien789'; // Link nhóm Telegram
$target_username = '@traffic90s_bot'; // Username của user cần theo dõi
$token_bot = '7865455629:AAGAT3OC7c7fzeWgN9xy8qkpL9B2o98LXVg'; // Bot Token
$chat_ids = [5566449246, 7039423259]; // Danh sách chat_id để gửi thông báo

try {
    // Tạo client MadelineProto
    $client = new API($session_name, [
        'app_info' => [
            'api_id' => $api_id,
            'api_hash' => $api_hash,
        ],
    ]);

    // Bắt đầu client
    $client->start();
    echo "Đăng nhập thành công!\n";

    // Lấy thông tin nhóm
    $group = $client->getFullInfo($group_link);
    echo "Đã vào nhóm: " . $group['Chat']['title'] . "\n";

    // Lấy thông tin user từ username
    $target_user = $client->getFullInfo($target_username);
    $target_user_id = $target_user['User']['id'];
    echo "Đã lấy ID của $target_username: $target_user_id\n";

    // Lắng nghe tin nhắn mới
    $client->setEventHandler(new class($client, $target_user_id, $chat_ids, $token_bot) extends \danog\MadelineProto\EventHandler {
        private $client;
        private $target_user_id;
        private $chat_ids;
        private $token_bot;

        public function __construct($client, $target_user_id, $chat_ids, $token_bot)
        {
            $this->client = $client;
            $this->target_user_id = $target_user_id;
            $this->chat_ids = $chat_ids;
            $this->token_bot = $token_bot;
        }

        public function onUpdateNewMessage($update): \Generator
        {
            // Kiểm tra nếu tin nhắn đến từ user cần theo dõi
            $message = $update['message'] ?? null;
            if ($message && $message['_'] === 'message' && $message['from_id']['user_id'] === $this->target_user_id) {
                $message_text = $message['message'] ?? '';

                // Lọc thông tin từ tin nhắn bằng regex
                $pattern = '/Keyword:\s*(.*?)\n.*?(Ngày bắt đầu: .*?\d{4})\s*(Ngày kết thúc: .*?\d{4})/s';
                if (preg_match($pattern, $message_text, $matches)) {
                    $keyword = $matches[1];
                    $start_date = str_replace('Ngày bắt đầu: ', '', $matches[2]);
                    $end_date = str_replace('Ngày kết thúc: ', '', $matches[3]);

                    // Soạn nội dung thông báo
                    $notification = "🇻🇳 **NHIỆM VỤ MỚI** 🇻🇳\n"
                        . "---------------------------------\n"
                        . "🔔 **Thông báo nhiệm vụ: REPORT WEBSITE**\n"
                        . "🔑 **Keyword**: `$keyword`\n"
                        . "🕒 **Ngày bắt đầu**: $start_date\n"
                        . "⏳ **Ngày kết thúc**: $end_date\n"
                        . "---------------------------------\n"
                        . "🎯 **Hãy hoàn thành nhiệm vụ ngay để nhận thưởng!** 🎯";

                    // Gửi thông báo qua Telegram Bot
                    foreach ($this->chat_ids as $chat_id) {
                        $this->sendNotification($chat_id, $notification);
                    }
                }
            }
        }

        private function sendNotification($chat_id, $notification)
        {
            $url = "https://api.telegram.org/bot{$this->token_bot}/sendMessage";
            $data = [
                'chat_id' => $chat_id,
                'text' => $notification,
                'parse_mode' => 'Markdown',
            ];

            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                echo "❌ Lỗi gửi thông báo đến CHAT_ID $chat_id\n";
            } else {
                echo "✅ Đã gửi thông báo đến CHAT_ID $chat_id thành công!\n";
            }
        }
    });

    // Chạy client đến khi bị ngắt kết nối
    $client->loop();
} catch (\Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
