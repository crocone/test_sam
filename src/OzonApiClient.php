<?php
declare(strict_types=1);

namespace App;

use Exception;

class OzonApiClient
{
    private string $clientId;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(
        string $clientId,
        string $apiKey,
        string $baseUrl = 'https://api-seller.ozon.ru'
    ) {
        $this->clientId  = $clientId;
        $this->apiKey    = $apiKey;
        $this->baseUrl   = rtrim($baseUrl, '/');
    }

    /**
     * Возвращает одну страницу товаров.
     *
     * @param array<string,mixed> $filter Поля фильтрации (e.g. ['visibility'=>'ALL', 'offer_id'=>['123','456']])
     * @param string $lastId Из ответа предыдущего вызова (для пагинации)
     * @param int $limit Число записей на страницу (1–1000)
     * @return array{items: array<int, array>, total: int, last_id: string}
     * @throws Exception
     */
    public function fetchProductPage(array $filter, string $lastId = '', int $limit = 100): array
    {
        $payload = [
            'filter'   => $filter,
            'last_id'  => $lastId,
            'limit'    => $limit,
        ];

        $resp = $this->makeApiRequest('/v3/product/list', $payload);

        return [
            'items'   => $resp['items']   ?? [],
            'total'   => (int)($resp['total']   ?? 0),
            'last_id' => (string)($resp['last_id'] ?? ''),
        ];
    }

    /**
     * Пробегает все страницы и возвращает единый массив товаров.
     *
     * @param array<string,mixed> $filter
     * @param int $limit
     * @return array<int, array{product_id:int|string,offer_id:string,is_fbo_visible:bool,is_fbs_visible:bool,archived:bool,is_discounted:bool}>
     */
    public function fetchAllProducts(array $filter, int $limit = 100): array
    {
        $all   = [];
        $last  = '';
        do {
            $page = $this->fetchProductPage($filter, $last, $limit);
            $all = array_merge($all, $page['items']);
            $last = $page['last_id'];
            // если пришло меньше, чем limit, можем завершить раньше
        } while ($last !== '' && count($page['items']) === $limit);

        return $all;
    }

    /**
     * Общий метод POST-запроса к API Ozon.
     *
     * @param string $endpoint Например '/v3/product/list'
     * @param array $data Тело запроса
     * @return array Разобранный JSON['result']
     * @throws Exception
     */
    private function makeApiRequest(string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch  = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => [
                'Client-Id: '  . $this->clientId,
                'Api-Key: '    . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,

            // полностью отключаем проверку SSL — НЕ безопаcно для продакшена!
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception("Curl error: $err");
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new Exception("API error HTTP $code: $response");
        }

        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $json['result'] ?? [];
    }

}
