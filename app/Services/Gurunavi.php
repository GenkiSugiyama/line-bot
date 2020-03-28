<?php
namespace App\Services;

use GuzzleHttp\Client;

class Gurunavi
{
  private const RESTAURANTS_SEARCH_API_URL = 'https://api.gnavi.co.jp/RestSearchAPI/v3/';

  // php7では引数の前にデータ型を記述することで、その引数のデータ型を宣言できる
  public function searchRestaurants(string $word): array //「: 型」で戻り値のデータ型の宣言
  {
    $client = new Client();
    $response = $client
      ->get(self::RESTAURANTS_SEARCH_API_URL, [
        'query' => [
          'keyid' => env('GURUNAVI_ACCESS_KEY'),
          'freeword' => str_replace(' ',',',$word),
        ],
        'http_errors' => false,
      ]);

    return json_decode($response->getBody()->getContents(), true);
  }
}