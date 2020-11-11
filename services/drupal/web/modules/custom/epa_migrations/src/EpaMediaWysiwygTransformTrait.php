<?php

namespace Drupal\epa_migrations;

use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Helpers to transform embedded media tags.
 */
trait EpaMediaWysiwygTransformTrait {

  /**
   * Transform embedded media in wysiwyg content.
   *
   * @param string $wysiwyg_content
   *   The content to search and transform embedded media.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager service.
   * @param bool $remove_alignment
   *   A flag to determine whether the alignment setting should be set to null.
   *
   * @return string
   *   The original wysiwyg_content with embedded media in D8 format.
   */
  public function transformWysiwyg($wysiwyg_content, EntityTypeManager $entityTypeManager, $remove_alignment = FALSE) {
    $view_modes = [
      'media_large' => 'large',
      'medium' => 'medium',
      'media_original' => 'original',
      'full' => 'original',
      'teaser' => 'small',
      'media_small' => 'small',
      'thumbnail' => 'thumbnail',
      'block_header' => 'small',
      'small' => 'small',
    ];

    $pattern = '/\[\[(?<tag_info>.+?"type":"media".+?)\]\]/s';

    $media_embed_replacement_template = <<<'TEMPLATE'
<drupal-media
  alt="%s"
  data-align="%s"
  data-caption="%s"
  data-entity-type="media"
  data-entity-uuid="%s"
  data-view-mode="%s"></drupal-media>
TEMPLATE;

    $inline_embed_replacement_template = <<<'TEMPLATE'
<drupal-inline-media
  data-align="center"
  data-entity-type="media"
  data-entity-uuid="%s"></drupal-inline-media>
TEMPLATE;

    $wysiwyg_content = preg_replace_callback($pattern, function ($matches) use ($inline_embed_replacement_template, $media_embed_replacement_template, $entityTypeManager, $view_modes, $remove_alignment) {
      $decoder = new JsonDecode(TRUE);

      try {
        $tag_info = $decoder->decode($matches['tag_info'], JsonEncoder::FORMAT);

        $media_entity_uuid = $entityTypeManager->getStorage('media')
          ->load($tag_info['fid']);

        $media_entity_uuid = $media_entity_uuid ? $media_entity_uuid->uuid() : 0;

        if ($tag_info['view_mode'] === 'media_link') {
          return sprintf($inline_embed_replacement_template,
            $media_entity_uuid
          );
        }
        else {
          $alignment = $remove_alignment ? '' : $tag_info['fields']['field_image_alignment[und]'] ?? 'center';
          return sprintf($media_embed_replacement_template,
            $tag_info['fields']['field_file_image_alt_text[und][0][value]'] ?? '',
            $alignment,
            htmlentities(stripslashes(urldecode($tag_info['fields']['field_caption[und][0][value]']))) ?? '',
            $media_entity_uuid,
            $view_modes[$tag_info['view_mode']]
          );
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('epa_migrations')->notice('Caught exception: ' . $e->getMessage() . ' while trying to process this json: ' . $matches['tag_info']);
      }
    }, $wysiwyg_content);

    return $wysiwyg_content;
  }

  /**
   * Extract block_header media from wysiwyg content.
   *
   * @param string $wysiwyg_content
   *   The content to search and extract block_header media.
   *
   * @return array
   *   An array that consists of the extracted block_header and the original
   *   wysiwyg_content with the block header removed.
   */
  public function extractBlockHeader($wysiwyg_content) {
    $pattern = '/\[\[(.+?"type":"media".+?)\]\]/s';
    $split = preg_split($pattern, $wysiwyg_content, 2, PREG_SPLIT_DELIM_CAPTURE);
    /**
     * $split is:
     *   [0 => before string, 1 => captured JSON, 2 => after string]
     * OR:
     *   [0 => full string]
     * OR:
     *   false
     */

    if ($split && count($split) === 3) {
      list( $before, $captured, $after) = $split;
      try {
        $decoder = new JsonDecode(TRUE);
        $tag_info = $decoder->decode($captured, JsonEncoder::FORMAT);
        if ($tag_info['view_mode'] == 'block_header') {
          $block_header = [
            'target_id' => $tag_info['fid'],
            'alt' => $tag_info['attributes']['alt'],
          ];

          $wysiwyg_content = $before . $after;
          // TODO remove empty anchor link, set block_header_url to its href

          return [
            'block_header_img' => $block_header,
            // TODO Add 'block_header_url'
            'wysiwyg_content' => $wysiwyg_content,
          ];
        }

      }
      catch (\Exception $e) {
        \Drupal::logger('epa_migrations')->notice('Caught exception: ' . $e->getMessage() . ' while trying to process this json: ' . $captured);
      }
    }

    return [
      'block_header_img' => NULL,
      'wysiwyg_content' => $wysiwyg_content,
    ];
  }

}
