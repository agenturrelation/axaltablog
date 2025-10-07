<?php

namespace Drupal\gdpr_video_formatter\OEmbed;

use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcher as MediaResourceFetcher;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Overrides the oEmbed resources.
 *
 * This class overrides the media.oembed.resource_fetcher service from the core
 * media module.
 *
 * @see \Drupal\media\OEmbed\ResourceFetcher
 */
class GdprVideoFormatterResourceFetcher extends MediaResourceFetcher {

  /**
   * @inheritDoc
   */
  protected function createResource(array $data, $url) {
    if ($data['type'] == Resource::TYPE_VIDEO && $data['provider_name'] === 'YouTube') {

      // Replace the default youtube domain with the no-cookie domain.
      $data['html'] = str_replace('youtube.com/', 'youtube-nocookie.com/', $data['html']);

      // Check if there is a larger available thumbnail size.
      if (strpos($data['thumbnail_url'], 'hqdefault.jpg') !== FALSE) {

        // Array of thumbnail sizes above 'hqdefault' to try, starting with the
        // largest size.
        $thumbnailTypes = [
          'maxresdefault' => [
            'width'   => 1280,
            'height'  => 720,
          ],
          'sddefault' => [
            'width'   => 640,
            'height'  => 480,
          ],
        ];

        foreach ($thumbnailTypes as $thumbnailName => $thumbnailDimensions) {
          // Replace 'hqdefault' in the thumbnail URL with the current type we're
          // testing for.
          $testThumbnailURL = str_replace('hqdefault', $thumbnailName, $data['thumbnail_url']);
          // We need to wrap the request in a try {} catch {} because Guzzle will
          // throw an exception on a 404.
          try {
            $response = $this->httpClient->request('GET', $testThumbnailURL);
            // Got an exception? Skip to the next thumbnail size, assuming this
            // returned a 404 or ran into some other error.
          }
          catch (GuzzleException $e) {
            continue;
          }

          // If this was a 200 response, update the thumbnail URL and dimensions
          // with the higher resolution and break out of the loop.
          if ($response->getStatusCode() === 200) {
            $data['thumbnail_url']    = $testThumbnailURL;
            $data['thumbnail_width']  = $thumbnailDimensions['width'];
            $data['thumbnail_height'] = $thumbnailDimensions['height'];
            break;
          }
        }
      }
    }
    // Create the resource.
    return parent::createResource($data, $url);
  }
}
