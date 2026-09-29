<?php

    require '../vendor/autoload.php';

    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\RequestException;

// ========== 配置 ==========
    //8056835067:AAFIqZ8feQtKgw_m0_ADnHswh_eYRgqfsw8
    $botToken  = '8056835067:AAFIqZ8feQtKgw_m0_ADnHswh_eYRgqfsw8';
    $chatId    = '5314592797';             // 可以是用户ID、群组ID、频道ID
    $imagePath = 'data/1.png'; // 本地图片完整路径

// ==========================

    $client = new Client([
        'base_uri' => "https://api.telegram.org/bot{$botToken}/",
        'proxy' => 'http://192.168.0.111:1080', // 替换为你自己的代理地址和端口
        'timeout'  => 30.0,
    ]);

    try
    {
        $response = $client->post('sendPhoto', [
            'multipart' => [
                [
                    'name'     => 'chat_id',
                    'contents' => $chatId,
                ],
                [
                    'name'     => 'photo',
                    'contents' => fopen($imagePath, 'r'),
                    // 可选，Telegram会用这个文件名
                    'filename' => basename($imagePath),
                ],
                // 可选参数
                [
                    'name'     => 'caption',
                    'contents' => '这是图片说明文字',
                ],
                // [
                //     'name'     => 'parse_mode',
                //     'contents' => 'HTML',   // 或 MarkdownV2
                // ],
                // [
                //     'name'     => 'disable_notification',
                //     'contents' => 'true',
                // ],
            ],
        ]);

        $body   = $response->getBody()->getContents();
        $result = json_decode($body, true);

        file_put_contents('./data.json', $body);

        // 成功时返回
        if ($result['ok'] === true)
        {
            echo "上传成功！\n";
            echo "消息ID: " . $result['result']['message_id'] . "\n";
            echo "文件ID (file_id): " . $result['result']['photo'][count($result['result']['photo']) - 1]['file_id'] . "\n";

            // 完整返回信息
            print_r($result);
        }
        else
        {
            echo "上传失败: " . $result['description'] . "\n";
        }

    }
    catch (RequestException $e)
    {
        echo "请求异常: " . $e->getMessage() . "\n";

        if ($e->hasResponse())
        {
            $errorBody = $e->getResponse()->getBody()->getContents();
            echo "错误详情: " . $errorBody . "\n";
        }
    }