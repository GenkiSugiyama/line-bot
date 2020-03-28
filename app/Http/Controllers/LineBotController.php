<?php

namespace App\Http\Controllers;

// グルナビクラスを利用するために追加
use App\Services\Gurunavi;

// Flex Messageの返信機能作成のため追加
use App\Services\RestaurantBubbleBuilder;

use Illuminate\Http\Request;
// ログ確認用に追加
use Illuminate\Support\Facades\Log;

// LINEBotクラスを生成するための追加
use LINE\LINEBot;

// LINEBotに返信するために追加
use LINE\LINEBot\Event\MessageEvent\TextMessage;

use LINE\LINEBot\HTTPClient\CurlHTTPClient;

// Flex Messageの返信機能作成のため追加
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;


class LineBotController extends Controller
{
    public function index()
    {
        return view('linebot.index');
    }

    public function restaurants(Request $request)
    {
        // ログ確認用に追加
        // Log::debug(出力したい値)でログを出力できる
        Log::debug($request->header());
        Log::debug($request->input());

        // LINEBotクラスを生成するための追加
        $httpClient = new CurlHTTPClient(env('LINE_ACCESS_TOKEN'));
        $lineBot = new LINEBot($httpClient, ['channelSecret' => env('LINE_CHANNEL_SECRET')]);

        // LINEBotと通信じのセキュリティリスクへの対応
        $signature = $request->header('x-line-signature');
        if (!$lineBot->validateSignature($request->getContent(), $signature)) {
            abort(400, 'Invalid signature');
        }

        // LINEBotからのリクエストからイベントを取り出す
        $events = $lineBot->parseEventRequest($request->getContent(), $signature);
        Log::debug($events);

        // LINEBotへの返信
        foreach($events as $event) {
            if(!($event instanceof TextMessage)) {
                Log::debug('Non text message has come');
                continue;
            }

            // ぐるなび連携用に追加
            $gurunavi = new Gurunavi();
            $gurunaviResponse = $gurunavi->searchRestaurants($event->getText());

            if(array_key_exists('error', $gurunaviResponse)) {
                $replyText = $gurunaviResponse['error'][0]['message'];
                $replyToken = $event->getReplyToken();
                $lineBot->replyText($replyToken, $replyText);
                continue;
            }


            // テキストメッセージではなくFlex Msessageで返信するのでテキストメッセージ用の返信機能はコメントアウト

            // これは返信がくる
            $replyText = '';
            foreach($gurunaviResponse['rest'] as $restaurant) {
                $replyText .=
                    $restaurant['name']."\n".$restaurant['url']."\n"."\n";
            }
            $replyToken = $event->getReplyToken();
            $lineBot->replyText($replyToken, $replyText);

            // FlexMessageで返すためのコード
            // こっちだと返信なし
            // $bubbles = [];
            // foreach ($gurunaviResponse['rest'] as $restaurant) {
            //     $bubble = RestaurantBubbleBuilder::builder();
            //     $bubble->setContents($restaurant);
            //     $bubbles[] = $bubble;
            // }

            // $carousel = CarouselContainerBuilder::builder();
            // $carousel->setContents($bubbles);

            // $flex = FlexMessageBuilder::builder();
            // $flex->setAltText('飲食店検索結果');
            // $flex->setContents($carousel);

            // $replyToken = $event->getReplyToken();
            // $lineBot->replyMessage($replyToken, $flex);
        }
    }
}
