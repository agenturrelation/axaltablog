<?php

namespace Drupal\gdpr_video_formatter;

use GuzzleHttp\ClientInterface;

class YouTubeApiRequest {

  const YOUTUBE_API_ENDPOINT = "https://www.googleapis.com/youtube/v3";

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The YouTube API key.
   * @var string
   */
  private string $apiKey = '';

  public function __construct(ClientInterface $http_client) {
    // Set httpClient.
    $this->httpClient = $http_client;

    // Get damage form settings.
    $config = \Drupal::config('gdpr_video_formatter.settings');

    // Set API settings.
    $this->apiKey = $config->get('youtube_api.api_key');
  }

  /**
   * Get video metadata / information.
   *
   * @param string $token
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetchVideoMetadata(string $videoId): array {

    return $this->executeRequest('videos', 'GET', [
      'id' => $videoId,
      'part' => 'id,snippet',
    ]);
  }

  /**
   * Execute Request.
   *
   * @param string $resource
   * @param string $method
   * @param array $query
   * @param array $data
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function executeRequest(string $resource, string $method, array $query = [], array $data = []): array {

    $options = [
      'form_params' => $data,
      'query' => $query + ['key' => $this->apiKey],
      'headers' => [
        'Accept' => 'application/json',
      ]
    ];
    // dsm($options, 'options');

    $request = $this->httpClient->request($method, $this->getApiEndpoint($resource), $options);

    if ($request->getStatusCode() != 200) {
      return [];
    }

    /** @var $result array */
    $result = json_decode($request->getBody()->getContents(), true);
    // dsm($result, "result");

    return $result;
  }

  /**
   * Get API endpoint url.
   *
   * @param $resource
   *
   * @return string
   */
  private function getApiEndpoint($resource):string {
    return self::YOUTUBE_API_ENDPOINT . '/' . $resource;
  }
}
